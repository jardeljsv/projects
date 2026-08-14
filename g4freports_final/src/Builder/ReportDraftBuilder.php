<?php

namespace GlpiPlugin\G4freports\Builder;

use GlpiPlugin\G4freports\Api\GlpiApiClient;
use GlpiPlugin\G4freports\Api\SearchOptionResolver;
use GlpiPlugin\G4freports\Util\DateHelper;
use GlpiPlugin\G4freports\Util\TextNormalizer;

class ReportDraftBuilder
{
    private array $cfg;
    private array $profile;
    private GlpiApiClient $client;
    private SearchOptionResolver $resolver;
    private array $warnings = [];
    private bool $maskSensitive = true;
    private int $maxItems = 200;
    private int $candidateLimit = 60;
    private int $subitemLimit = 20;
    private int $maxAutoGroups = 5;
    private int $timeLimitSeconds = 22;
    private float $startedAt = 0.0;
    private bool $budgetExceeded = false;
    private string $agentName = '';
    private array $labelCache = [];
    private array $userCache = [];
    private array $subitemCache = [];
    private int $rangeStart = 0;
    private int $rangeLimit = 0;
    private string $batchContext = '';
    private string $batchTargetType = '';
    private int $batchTargetId = 0;
    private string $batchDateScope = '';
    private string $batchDateBasis = '';
    private bool $managerMode = false;
    private array $sourceTotals = [];
    private array $sourceReturned = [];
    private bool $batchHasMore = false;
    private bool $needLocationAnalytics = false;
    private bool $needTimelineAnalytics = false;
    private array $coverage = ['sources' => [], 'filters' => [], 'limitations' => [], 'warnings' => []];
    private array $dateContext = [];
    private string $responseTimeMode = 'fast';
    private bool $highVolumeFast = false;

    public function __construct(array $cfg, array $profile)
    {
        $this->cfg = $cfg;
        $this->profile = $profile;

        // Report extraction must fail fast. In the target GLPI deployment the
        // web server can return 504 before PHP finishes if one GLPI API search
        // waits too long. Keep per-request timeouts shorter than the page
        // timeout and return a partial draft with warnings instead of hanging.
        $extractTimeout = max(3000, min(20000, (int)($cfg['extract_request_timeout_ms'] ?? 12000)));
        $profile['timeout_ms'] = min(max(1000, (int)($profile['timeout_ms'] ?? $extractTimeout)), $extractTimeout);
        $profile['connect_timeout_ms'] = min(max(1000, (int)($profile['connect_timeout_ms'] ?? 5000)), 5000);

        $this->client = new GlpiApiClient($profile);
        $overrides = json_decode((string)($cfg['field_overrides_json'] ?? '{}'), true);
        $this->resolver = new SearchOptionResolver($this->client, is_array($overrides) ? $overrides : []);
        $this->maskSensitive = ((int)($cfg['mask_sensitive_data'] ?? 1)) === 1;
        $this->maxItems = max(10, min(1000, (int)($cfg['max_items'] ?? 200)));
        $this->candidateLimit = max(10, min($this->maxItems, (int)($cfg['max_candidates'] ?? 60)));
        $this->subitemLimit = max(10, min(100, (int)($cfg['subitem_limit'] ?? 50)));
        $this->maxAutoGroups = max(0, min(20, (int)($cfg['max_auto_groups'] ?? 5)));
        $this->timeLimitSeconds = max(5, min(45, (int)($cfg['max_runtime_seconds'] ?? 22)));
    }


    public function close(): void
    {
        try {
            $this->client->killSession();
        } catch (\Throwable $e) {
            // Session cleanup must never break the GLPI page.
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function build(array $request): array
    {
        $this->startedAt = microtime(true);
        $this->budgetExceeded = false;
        $this->subitemCache = [];
        $this->userCache = [];
        $this->sourceTotals = [];
        $this->sourceReturned = [];
        $this->coverage = ['sources' => [], 'filters' => [], 'limitations' => [], 'warnings' => []];
        $this->dateContext = [];
        $this->batchHasMore = false;
        $this->batchContext = trim((string)($request['batch_context'] ?? ''));
        $this->batchTargetType = trim((string)($request['batch_target_type'] ?? ''));
        $this->batchTargetId = max(0, (int)($request['batch_target_id'] ?? 0));
        $this->batchDateScope = trim((string)($request['batch_date_scope'] ?? ''));
        $this->batchDateBasis = trim((string)($request['batch_date_basis'] ?? ''));
        $this->rangeStart = max(0, (int)($request['batch_offset'] ?? 0));
        $this->rangeLimit = max(0, min(500, (int)($request['batch_limit'] ?? 0)));

        $requestedLimit = (int)($request['max_items'] ?? 0);
        if ($requestedLimit > 0 && $this->batchContext === '') {
            $this->maxItems = max(10, min($this->maxItems, $requestedLimit));
            $this->candidateLimit = max(10, min($this->candidateLimit, $this->maxItems));
        }
        if ($this->batchContext !== '') {
            if ($this->rangeLimit <= 0) {
                $this->rangeLimit = 50;
            }
            // One browser batch must stay small enough to survive nginx/PHP
            // timeouts. The browser will request the next page if has_more=true.
            $this->candidateLimit = $this->rangeLimit;
            $this->maxItems = max($this->rangeLimit * 5, $this->rangeLimit + 20);
        }

        $start = DateHelper::normalizeDate((string)($request['period_start'] ?? ''), date('Y-m-01'));
        $end   = DateHelper::normalizeDate((string)($request['period_end'] ?? ''), date('Y-m-t'));
        if (strtotime($end) < strtotime($start)) {
            [$start, $end] = [$end, $start];
        }

        $agentId = (int)($request['agent_id'] ?? 0);
        $contexts = $request['contexts'] ?? [];
        if (!is_array($contexts)) {
            $contexts = preg_split('/[,\s]+/', (string)$contexts) ?: [];
        }
        $contexts = array_values(array_unique(array_filter(array_map('strval', $contexts))));
        if ($this->batchContext !== '') {
            $contexts = [$this->batchContext];
        }
        if (empty($contexts)) {
            $contexts = ['tickets_assigned_user', 'ticket_tasks_by_user', 'ticket_followups_by_user', 'ticket_solutions_by_user', 'manual_entries'];
        }

        $analyticsOptions = $request['analytics'] ?? [];
        if (!is_array($analyticsOptions)) {
            $analyticsOptions = preg_split('/[,\s]+/', (string)$analyticsOptions) ?: [];
        }
        $analyticsOptions = array_values(array_unique(array_filter(array_map('strval', $analyticsOptions))));
        $dateContext = is_array($request['date_context'] ?? null) ? $request['date_context'] : [];
        $dateContext = $this->normalizeDateContext($dateContext);
        $this->dateContext = $dateContext;
        $dataContext = is_array($request['data_context'] ?? null) ? $request['data_context'] : [];
        $managerMode = !empty($dataContext['manager_mode_enabled']);
        $mode = strtolower(trim((string)($dataContext['response_time_mode'] ?? ($this->cfg['response_time_mode'] ?? 'fast'))));
        if (!in_array($mode, ['fast','precise','deep'], true)) { $mode = 'fast'; }
        // high_volume_fast is a safety mode for API extraction, not only a visual manager option.
        // When enabled, exact event/timeline reads are skipped and the report becomes summary-first.
        $this->highVolumeFast = !empty($dataContext['high_volume_fast']) || ($managerMode && $mode === 'fast');
        if ($this->highVolumeFast) {
            $dataContext['high_volume_fast'] = true;
            $dataContext['response_time_mode'] = 'fast';
            $mode = 'fast';
        }
        $this->managerMode = $managerMode;
        $this->responseTimeMode = $mode;
        $titleFilters = $this->titleFiltersFromContext($dataContext);
        $titleFilterEnabled = !empty($dataContext['ticket_title_filter_enabled']) && !empty($titleFilters);
        $titleFilterScope = (string)($dataContext['ticket_title_filter_scope'] ?? 'detail_only');
        if ($managerMode && $titleFilterEnabled) {
            $titleFilterScope = 'global';
            $dataContext['ticket_title_filter_scope'] = 'global';
        }
        $this->needLocationAnalytics = in_array('location_distribution', $analyticsOptions, true);
        $wantsTimeline = count(array_intersect($analyticsOptions, ['avg_response_time','first_response_time','unanswered_events'])) > 0 || in_array($this->batchDateScope, ['created_with_agent_action','solved_or_closed_by_agent'], true);
        $this->needTimelineAnalytics = $wantsTimeline && !$this->highVolumeFast;
        if ($wantsTimeline && $this->highVolumeFast) {
            $this->addCoverageLimitation('Modo rápido de alto volume ativo: a linha do tempo completa de cada chamado não foi lida. Indicadores de tempo de resposta preciso ficam indisponíveis; use modo Preciso/Profundo em recortes menores.');
        }


        $manualGroupIds = $this->parseIdList($request['group_ids'] ?? '');
        if ($this->highVolumeFast && !empty($manualGroupIds)) {
            $managerMode = true;
            $this->managerMode = true;
            $dataContext['manager_mode_enabled'] = true;
        }
        if ($managerMode && !empty($manualGroupIds) && $this->batchContext === '' && !in_array('tickets_assigned_groups', $contexts, true)) {
            // Manager mode is explicitly scoped by the groups selected in the UI.
            // It composes with other contexts but must always include group tickets
            // to build per-agent/team indicators. No automatic user-group fallback is used.
            $contexts[] = 'tickets_assigned_groups';
        }
        $template = trim((string)($request['template'] ?? 'operational')) ?: 'operational';

        $agent = ['id' => $agentId, 'name' => trim((string)($request['agent_name'] ?? ''))];
        if ($agentId > 0) {
            try {
                $item = $this->client->getItem('User', $agentId, ['expand_dropdowns' => true, 'get_hateoas' => false]);
                $agent['raw'] = $item;
                $agent['name'] = TextNormalizer::firstNonEmpty($item['name'] ?? '', $item['realname'] ?? '', $item['firstname'] ?? '', $agent['name']);
                if (isset($item['firstname']) || isset($item['realname'])) {
                    $full = trim((string)($item['firstname'] ?? '') . ' ' . (string)($item['realname'] ?? ''));
                    if ($full !== '') {
                        $agent['name'] = $full;
                    }
                }
            } catch (\Throwable $e) {
                $this->warnings[] = 'Não foi possível obter dados completos do agente via API: ' . $e->getMessage();
            }
        }
        if ($agent['name'] === '') {
            $agent['name'] = $agentId > 0 ? ('Usuário ID ' . $agentId) : 'Profissional não informado';
        }
        $agent['name'] = trim((string)$agent['name'], " .\t\n\r\0\x0B");
        $this->agentName = (string)$agent['name'];

        // Important operational rule: group contexts are explicit. The plugin no
        // longer falls back to every group linked to the selected agent, because
        // some contracts have groups with very large queues and that automatic
        // expansion can make even a one-day report time out. If the user wants
        // group data, they must select the group in the searchable group field.
        $groupIds = array_values(array_unique(array_map('intval', $manualGroupIds)));
        $groups = $this->resolveGroups($groupIds);
        if ($managerMode && empty($groupIds)) {
            $this->warnings[] = 'Modo gerencial requer grupos selecionados. Nenhum grupo foi informado para o escopo gerencial.';
        }
        if (empty($groupIds) && $this->hasAny($contexts, ['tickets_assigned_groups','projects_managed_by_groups','project_tasks_groups'])) {
            $this->warnings[] = 'Contexto por grupo selecionado, mas nenhum grupo foi escolhido no campo "Grupos selecionados". A extração por grupo foi ignorada para evitar varredura automática de filas grandes.';
        }

        $entries = [];
        $seen = [];
        $detailEntries = [];
        $seenDetail = [];
        $facts = ['tickets' => [], 'projects' => []];

        if ($agentId <= 0 && $this->requiresAgent($contexts)) {
            $this->warnings[] = 'Nenhum agente foi informado. Os contextos vinculados a usuário podem retornar poucos dados.';
        }

        if ($this->hasAny($contexts, ['tickets_assigned_user','tickets_requested_by_user','tickets_assigned_groups','ticket_tasks_by_user','ticket_followups_by_user','ticket_solutions_by_user'])) {
            $tickets = $this->collectTickets($agentId, $groupIds, $contexts, $start, $end);
            if ($titleFilterEnabled && $titleFilterScope === 'global') {
                $beforeTitle = count($tickets);
                $tickets = array_filter($tickets, function ($ticket) use ($titleFilters): bool {
                    return $this->ticketMatchesTitleFilter($ticket, $titleFilters);
                });
                $this->recordCoverageFilter('title_filtered_out', max(0, $beforeTitle - count($tickets)));
            }
            foreach ($tickets as $ticketId => $ticket) {
                $ticketMatchesTitle = !$titleFilterEnabled || $this->ticketMatchesTitleFilter($ticket, $titleFilters);
                if ($titleFilterEnabled && $titleFilterScope === 'global' && !$ticketMatchesTitle) {
                    continue;
                }

                if (!$managerMode && !$this->highVolumeFast) {
                    $ticketEntries = $this->ticketToEntries((int)$ticketId, $ticket, $agentId, $contexts, $start, $end);
                    $includeTicketInDetail = !$titleFilterEnabled || $titleFilterScope !== 'detail_only' || $ticketMatchesTitle;
                    if ($titleFilterEnabled && $titleFilterScope === 'detail_only' && !$ticketMatchesTitle) {
                        $this->recordCoverageFilter('title_filtered_out');
                    }
                    foreach ($ticketEntries as $entry) {
                        $this->addEntry($entries, $seen, $entry);
                        if ($includeTicketInDetail) {
                            $this->addEntry($detailEntries, $seenDetail, $entry);
                        }
                    }
                }
                $facts['tickets'][(int)$ticketId] = $this->ticketFact((int)$ticketId, $ticket, $agentId, $groupIds, $start, $end);
                if (!$managerMode && !$this->highVolumeFast && count($entries) >= $this->maxItems) {
                    $this->warnings[] = 'Limite máximo de entradas atingido durante a leitura de chamados. Ajuste o período, os contextos ou o limite nas configurações.';
                    break;
                }
            }
        }

        if ($this->budgetExceeded) {
            // Keep whatever was already collected and skip expensive stages.
        } elseif ($this->hasAny($contexts, ['projects_managed_by_user','projects_managed_by_groups','projects_member_user','project_tasks_user','project_tasks_groups'])) {
            $projects = $this->collectProjects($agentId, $groupIds, $contexts, $start, $end);
            foreach ($projects as $projectId => $project) {
                if (!$managerMode && !$this->highVolumeFast) {
                    $projectEntries = $this->projectToEntries((int)$projectId, $project, $agentId, $groupIds, $contexts, $start, $end);
                    foreach ($projectEntries as $entry) {
                        $this->addEntry($entries, $seen, $entry);
                        $this->addEntry($detailEntries, $seenDetail, $entry);
                    }
                }
                $facts['projects'][(int)$projectId] = $this->projectFact((int)$projectId, $project, $start, $end);
                if (!$managerMode && !$this->highVolumeFast && count($entries) >= $this->maxItems) {
                    $this->warnings[] = 'Limite máximo de entradas atingido durante a leitura de projetos. Ajuste o período, os contextos ou o limite nas configurações.';
                    break;
                }
            }
        }

        if ($this->budgetExceeded) {
            $this->warnings[] = 'A extração foi interrompida pelo limite de segurança antes do timeout do servidor. O rascunho pode estar parcial; reduza o período/contextos ou aumente o limite somente se o ambiente suportar.';
        }

        usort($entries, static function ($a, $b) {
            $d = strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
            if ($d !== 0) return $d;
            return strcmp((string)($a['title'] ?? ''), (string)($b['title'] ?? ''));
        });
        usort($detailEntries, static function ($a, $b) {
            $d = strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
            if ($d !== 0) return $d;
            return strcmp((string)($a['title'] ?? ''), (string)($b['title'] ?? ''));
        });

        if ($titleFilterEnabled && $titleFilterScope === 'detail_only' && !$managerMode) {
            $this->warnings[] = 'Filtro por título aplicado ao detalhamento: ' . count($detailEntries) . ' de ' . count($entries) . ' entradas permaneceram visíveis no corpo editável; indicadores e gráficos mantêm o escopo completo.';
        }
        if ($titleFilterEnabled && $titleFilterScope === 'global') {
            $this->warnings[] = 'Filtro global por título aplicado após os contextos de agente/grupo/período. Indicadores, gráficos e detalhamento consideram apenas chamados com os títulos selecionados.';
        }

        $metrics = ($managerMode || $this->highVolumeFast) ? $this->calculateManagerMetrics($facts['tickets']) : $this->calculateMetrics($entries);
        $daily = $this->groupByDay(($managerMode || $this->highVolumeFast) ? [] : $detailEntries);
        $validations = ($managerMode || $this->highVolumeFast) ? $this->validateManagerDraft($facts['tickets'], $contexts) : $this->validateDraft($detailEntries, $contexts, $start, $end);

        return [
            'schema_version' => 2,
            'created_at' => date('c'),
            'connection' => [
                'id' => (string)($this->profile['id'] ?? 'default'),
                'name' => (string)($this->profile['name'] ?? 'GLPI')
            ],
            'contract_label' => (string)($this->cfg['contract_label'] ?? ''),
            'report_title' => trim((string)($request['report_title'] ?? '')) ?: (string)($this->cfg['default_report_title'] ?? 'Relatório Gerencial de Atividades'),
            'template' => $template,
            'period_start' => $start,
            'period_end' => $end,
            'subject' => [
                'type' => 'user',
                'id' => $agentId,
                'name' => $agent['name']
            ],
            'groups' => array_values($groups),
            'contexts' => $contexts,
            'analytics_options' => $analyticsOptions,
            'date_context' => $dateContext,
            'data_context' => $dataContext,
            'manager_context' => ['enabled' => ($managerMode || $this->highVolumeFast), 'selected_groups' => array_values($groups), 'hide_detail_entries' => ($managerMode || $this->highVolumeFast)],
            'facts' => ['tickets' => array_values($facts['tickets']), 'projects' => array_values($facts['projects'])],
            'scope_text' => (string)($request['scope_text'] ?? ($this->cfg['default_scope_text'] ?? '')),
            'objective_text' => (string)($request['objective_text'] ?? ($this->cfg['default_objective_text'] ?? '')),
            'executive_summary' => ($managerMode || $this->highVolumeFast) ? $this->defaultManagerExecutiveSummary($facts['tickets'], $groups, $start, $end) : $this->defaultExecutiveSummary($entries, $agent['name'], $start, $end),
            'metrics' => $metrics,
            'entries' => $entries,
            'detail_entries' => ($managerMode || $this->highVolumeFast) ? [] : $detailEntries,
            'daily_groups' => $daily,
            'validations' => $validations,
            'warnings' => array_values(array_unique($this->warnings)),
            'source_totals' => $this->sourceTotals,
            'source_returned' => $this->sourceReturned,
            'coverage' => $this->coverage,
            'batch' => [
                'context' => $this->batchContext,
                'target_type' => $this->batchTargetType,
                'target_id' => $this->batchTargetId,
                'date_scope' => $this->batchDateScope,
                'date_basis' => $this->batchDateBasis,
                'offset' => $this->rangeStart,
                'limit' => $this->rangeLimit,
                'returned_candidates' => array_sum($this->sourceReturned),
                'total_candidates' => $this->batchTotalCandidates(),
                'has_more' => $this->batchHasMore,
            ],
            'footer' => (string)($this->cfg['default_footer'] ?? '')
        ];
    }



    private function normalizeDateContext(array $input): array
    {
        $allowed = ['created_with_agent_action', 'updated_in_period', 'solved_or_closed_in_period', 'solved_or_closed_by_agent'];
        $out = [];
        foreach ($allowed as $key) {
            $out[$key] = !empty($input[$key]);
        }
        if (!array_filter($out)) {
            $out['updated_in_period'] = true;
        }
        return $out;
    }

    private function titleFiltersFromContext(array $dataContext): array
    {
        $rows = [];
        $raw = $dataContext['ticket_titles'] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }
        foreach ($raw as $item) {
            if (!is_array($item)) continue;
            $title = trim((string)($item['title'] ?? ''));
            if ($title === '') continue;
            $key = $this->normalizeMatch($title) . '|' . trim((string)($item['ticket_id'] ?? ''));
            $rows[$key] = ['title' => $title, 'ticket_id' => (int)($item['ticket_id'] ?? 0)];
        }
        return array_values($rows);
    }

    private function ticketMatchesTitleFilter(array $ticket, array $filters): bool
    {
        if (empty($filters)) return true;
        $item = $ticket['item'] ?? [];
        $row = $ticket['row'] ?? [];
        $title = TextNormalizer::firstNonEmpty($item['name'] ?? '', $this->rowVal($row, 1));
        $hay = $this->normalizeMatch($title);
        if ($hay === '') return false;
        foreach ($filters as $filter) {
            $needle = $this->normalizeMatch((string)($filter['title'] ?? ''));
            if ($needle !== '' && (strpos($hay, $needle) !== false || strpos($needle, $hay) !== false)) {
                return true;
            }
        }
        return false;
    }

    private function normalizeMatch(string $value): string
    {
        $value = trim(self::lower($value));
        if ($value === '') return '';
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && $ascii !== '') {
            $value = $ascii;
        }
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return trim($value);
    }

    private function collectTickets(int $agentId, array $groupIds, array $contexts, string $start, string $end): array
    {
        $fields = $this->resolver->fields('Ticket', [
            'id' => [2], 'name' => [1], 'status' => [12], 'date' => [15], 'date_mod' => [19],
            'closedate' => [16], 'solvedate' => [18], 'users_id_assign' => [5],
            'groups_id_assign' => [8], 'users_id_recipient' => [4], 'itilcategories_id' => [7], 'locations_id' => [], 'priority' => [3], 'content' => [21, 1]
        ]);
        $display = array_values(array_filter($fields));
        $tickets = [];
        $ticketContextSelected = $this->hasAny($contexts, ['tickets_assigned_user','tickets_requested_by_user','tickets_assigned_groups']);
        $dateInfo = $this->ticketDateInfo($fields, $ticketContextSelected);
        $dateField = (int)($dateInfo['field'] ?? 0);
        $dateScope = (string)($dateInfo['scope'] ?? '');
        [$from, $to] = $this->searchBounds($start, $end);

        if ($ticketContextSelected && $dateField <= 0) {
            $this->addCoverageLimitation('Ticket: critério temporal indisponível para o alvo atual. Campo solicitado: ' . ($this->batchDateBasis ?: $this->batchDateScope ?: 'padrão'));
        }

        if ($dateField > 0 && $agentId > 0 && in_array('tickets_assigned_user', $contexts, true) && $fields['users_id_assign'] > 0) {
            $source = $this->sourceKey('tickets_assigned_user');
            $this->mergeTicketSearchRows($tickets, $fields, [
                $this->crit($fields['users_id_assign'], 'equals', $agentId),
                $this->crit($dateField, 'morethan', $from, 'AND'),
                $this->crit($dateField, 'lessthan', $to, 'AND')
            ], $display, $source);
        }

        if ($dateField > 0 && $agentId > 0 && in_array('tickets_requested_by_user', $contexts, true) && $fields['users_id_recipient'] > 0) {
            $source = $this->sourceKey('tickets_requested_by_user');
            $this->mergeTicketSearchRows($tickets, $fields, [
                $this->crit($fields['users_id_recipient'], 'equals', $agentId),
                $this->crit($dateField, 'morethan', $from, 'AND'),
                $this->crit($dateField, 'lessthan', $to, 'AND')
            ], $display, $source);
        }

        if ($dateField > 0 && in_array('tickets_assigned_groups', $contexts, true) && $fields['groups_id_assign'] > 0) {
            $targetGroups = $groupIds;
            if ($this->batchTargetType === 'group' && $this->batchTargetId > 0) {
                $targetGroups = [$this->batchTargetId];
            }
            if (empty($targetGroups)) {
                $this->warnings[] = 'Chamados por grupo ignorados: selecione pelo menos um grupo no campo de grupos.';
            }
            foreach ($targetGroups as $gid) {
                if (!$this->checkBudget('pesquisa de chamados por grupo')) { break; }
                $gid = (int)$gid;
                if ($gid <= 0) continue;
                $source = $this->sourceKey('tickets_assigned_groups', $gid);
                $this->mergeTicketSearchRows($tickets, $fields, [
                    $this->crit($fields['groups_id_assign'], 'equals', $gid),
                    $this->crit($dateField, 'morethan', $from, 'AND'),
                    $this->crit($dateField, 'lessthan', $to, 'AND')
                ], $display, $source);
            }
        }

        // Direct subitem searches can discover work performed by the agent even when the ticket is not assigned to them.
        // They remain strict: if the API does not expose safe search options, already-discovered tickets are still enriched by getSubItems().
        if ($this->highVolumeFast && $agentId > 0 && $this->hasAny($contexts, ['ticket_tasks_by_user','ticket_followups_by_user','ticket_solutions_by_user'])) {
            $this->addCoverageLimitation('Contextos diretos de tarefas/acompanhamentos/soluções por agente foram omitidos no modo rápido de alto volume. Use modo Preciso/Profundo em recortes menores para descoberta por subitens.');
        }
        if (!$this->highVolumeFast && $agentId > 0 && in_array('ticket_tasks_by_user', $contexts, true) && count($tickets) < $this->candidateLimit && $this->checkBudget('pesquisa de tarefas de chamado')) {
            foreach ($this->searchSubitemParents('TicketTask', 'tickets_id', $agentId, $start, $end) as $ticketId) {
                if (count($tickets) >= $this->candidateLimit) { break; }
                $tickets[$ticketId]['_sources']['ticket_tasks_by_user'] = true;
            }
        }
        if (!$this->highVolumeFast && $agentId > 0 && in_array('ticket_followups_by_user', $contexts, true) && count($tickets) < $this->candidateLimit && $this->checkBudget('pesquisa de acompanhamentos')) {
            foreach ($this->searchSubitemParents('ITILFollowup', 'items_id', $agentId, $start, $end, 'Ticket') as $ticketId) {
                if (count($tickets) >= $this->candidateLimit) { break; }
                $tickets[$ticketId]['_sources']['ticket_followups_by_user'] = true;
            }
        }
        if (!$this->highVolumeFast && $agentId > 0 && in_array('ticket_solutions_by_user', $contexts, true) && count($tickets) < $this->candidateLimit && $this->checkBudget('pesquisa de soluções')) {
            foreach ($this->searchSubitemParents('ITILSolution', 'items_id', $agentId, $start, $end, 'Ticket') as $ticketId) {
                if (count($tickets) >= $this->candidateLimit) { break; }
                $tickets[$ticketId]['_sources']['ticket_solutions_by_user'] = true;
            }
        }

        if (count($tickets) > $this->candidateLimit) {
            $tickets = array_slice($tickets, 0, $this->candidateLimit, true);
        }
        if ($this->highVolumeFast) {
            // High-volume manager reports may include tens of thousands of tickets.
            // Do not call getItem() once per ticket in this mode; the search row
            // already contains the fields required for macro analytics. Deep
            // detail/location/event enrichment is available through Precise/Deep mode.
            $this->addCoverageLimitation('Detalhamento individual de Ticket via getItem foi omitido no modo rápido de alto volume para evitar milhares de chamadas REST.');
        } else {
            foreach (array_keys($tickets) as $id) {
                if (!$this->checkBudget('detalhamento de chamados')) { break; }
                try {
                    $tickets[$id]['item'] = $this->client->getItem('Ticket', (int)$id, ['expand_dropdowns' => true, 'get_hateoas' => false]);
                } catch (\Throwable $e) {
                    $this->warnings[] = 'Não foi possível detalhar o chamado ' . $id . ': ' . $e->getMessage();
                }
            }
        }

        if ($ticketContextSelected && in_array($dateScope, ['created_with_agent_action','solved_or_closed_by_agent'], true)) {
            if ($this->highVolumeFast) {
                $this->addCoverageLimitation('Critério temporal  . $dateScope .  foi tratado em modo rápido sem confirmação individual por eventos para evitar timeout.');
            } else {
                $tickets = $this->filterTicketsByDateScope($tickets, $agentId, $dateScope, $start, $end);
            }
        }

        return $tickets;
    }

    private function ticketDateInfo(array $fields, bool $ticketContextSelected): array
    {
        if (!$ticketContextSelected) {
            return ['scope' => '', 'basis' => '', 'field' => 0];
        }
        $allowedBasis = ['date_mod', 'date', 'solvedate', 'closedate'];
        $scope = $this->batchDateScope !== '' ? $this->batchDateScope : 'updated_in_period';
        $basis = $this->batchDateBasis;
        if ($basis === '') {
            $basis = [
                'created_with_agent_action' => 'date',
                'updated_in_period' => 'date_mod',
                'solved_or_closed_in_period' => 'solvedate',
                'solved_or_closed_by_agent' => 'solvedate',
            ][$scope] ?? 'date_mod';
        }
        if (!in_array($basis, $allowedBasis, true)) {
            $this->addCoverageLimitation('Critério temporal de chamados desconhecido: ' . $basis);
            return ['scope' => $scope, 'basis' => $basis, 'field' => 0];
        }
        $field = (int)($fields[$basis] ?? 0);
        if ($field <= 0) {
            $this->addCoverageLimitation('Campo temporal de Ticket indisponível na API para ' . $basis . '. O alvo foi ignorado sem fallback silencioso.');
        }
        return ['scope' => $scope, 'basis' => $basis, 'field' => $field];
    }

    private function sourceKey(string $base, int $targetId = 0): string
    {
        $parts = [$base];
        if ($targetId > 0) $parts[] = (string)$targetId;
        if ($this->batchDateScope !== '') $parts[] = $this->batchDateScope;
        if ($this->batchDateBasis !== '') $parts[] = $this->batchDateBasis;
        return implode(':', $parts);
    }

    private function searchBounds(string $start, string $end): array
    {
        $a = strtotime($start . ' 00:00:00');
        $b = strtotime($end . ' 23:59:59');
        if ($a === false) $a = strtotime($start) ?: time();
        if ($b === false) $b = strtotime($end) ?: time();
        // GLPI v1 search uses morethan/lessthan. Widen by one second to avoid boundary drops.
        return [date('Y-m-d H:i:s', $a - 1), date('Y-m-d H:i:s', $b + 1)];
    }

    private function filterTicketsByDateScope(array $tickets, int $agentId, string $scope, string $start, string $end): array
    {
        if ($this->highVolumeFast) {
            $this->addCoverageLimitation('Critério temporal "' . $scope . '" exige confirmação por eventos e foi ignorado no modo rápido de alto volume. Use modo Preciso/Profundo para validar autoria por agente.');
            return [];
        }
        if ($agentId <= 0) {
            $this->addCoverageLimitation('Critério "pelo agente" solicitado, mas nenhum agente principal foi selecionado.');
            return [];
        }
        $out = [];
        foreach ($tickets as $ticketId => $ticket) {
            $ok = true;
            if ($scope === 'created_with_agent_action') {
                $ok = $this->ticketHasAgentActionInPeriod((int)$ticketId, $agentId, $start, $end);
                if (!$ok) $this->recordCoverageFilter('agent_action_not_detected');
            } elseif ($scope === 'solved_or_closed_by_agent') {
                $ok = $this->ticketClosedByAgentInPeriod((int)$ticketId, $agentId, $start, $end);
                if (!$ok) $this->recordCoverageFilter('closed_by_agent_not_confirmed');
            }
            if ($ok) {
                $out[$ticketId] = $ticket;
            }
        }
        return $out;
    }

    private function ticketHasAgentActionInPeriod(int $ticketId, int $agentId, string $start, string $end): bool
    {
        foreach ([
            'ITILFollowup' => ['date', 'date_creation', 'date_mod'],
            'TicketTask' => ['date', 'begin', 'date_creation', 'date_mod'],
            'ITILSolution' => ['date_approval', 'date_creation', 'date_mod'],
        ] as $subtype => $dateFields) {
            foreach ($this->safeSubitems('Ticket', $ticketId, $subtype, true) as $row) {
                if (!is_array($row)) continue;
                $date = $this->firstField($row, $dateFields);
                if ($date === '' || !DateHelper::within($date, $start, $end)) continue;
                if ($this->subitemBelongsToUser($row, $agentId)) return true;
            }
        }
        return false;
    }

    private function ticketClosedByAgentInPeriod(int $ticketId, int $agentId, string $start, string $end): bool
    {
        foreach ($this->safeSubitems('Ticket', $ticketId, 'ITILSolution', true) as $solution) {
            if (!is_array($solution)) continue;
            $date = $this->firstField($solution, ['date_approval', 'date_creation', 'date_mod']);
            if ($date === '' || !DateHelper::within($date, $start, $end)) continue;
            if ($this->subitemBelongsToUser($solution, $agentId)) return true;
        }
        return false;
    }

    private function mergeTicketSearchRows(array &$bucket, array $fields, array $criteria, array $display, string $source): void
    {
        $before = array_keys($bucket);
        $this->mergeSearchRows($bucket, 'Ticket', $criteria, $display, $source);

        $parts = explode(':', $source);
        $base = (string)($parts[0] ?? $source);
        $knownBase = in_array($base, ['tickets_assigned_user', 'tickets_requested_by_user', 'tickets_assigned_groups'], true);
        if (!$knownBase) {
            return;
        }

        $groupSpecific = null;
        if ($base === 'tickets_assigned_groups' && isset($parts[1]) && is_numeric($parts[1])) {
            $groupSpecific = $base . ':' . (int)$parts[1];
        }

        $added = 0;
        $dupes = 0;
        $beforeMap = array_flip($before);
        foreach ($bucket as $id => &$row) {
            if (empty($row['_sources'][$source])) {
                continue;
            }
            if (!isset($row['_sources']) || !is_array($row['_sources'])) {
                $row['_sources'] = [];
            }
            // Keep both the detailed source (context:target:date-scope:field) and
            // normalized base sources. Later analytics, detail rendering and title
            // filters rely on base keys such as tickets_assigned_groups; without this
            // normalization a ticket can be found but then treated as unrelated to
            // the selected agent/group context.
            $row['_sources'][$base] = true;
            if ($groupSpecific !== null) {
                $row['_sources'][$groupSpecific] = true;
            }
            if (isset($beforeMap[$id])) {
                $dupes++;
            } else {
                $added++;
            }
        }
        unset($row);
        $this->recordCoverageUnique($source, $added, $dupes);
    }

    private function collectProjects(int $agentId, array $groupIds, array $contexts, string $start, string $end): array
    {
        $fields = $this->resolver->fields('Project', [
            'id' => [2], 'name' => [1], 'status' => [12], 'date_mod' => [19], 'date' => [15],
            'manager_user' => [4], 'manager_group' => [8], 'percent_done' => [86], 'code' => [3], 'content' => [21, 1]
        ]);
        $display = array_values(array_filter($fields));
        $projects = [];

        if ($agentId > 0 && in_array('projects_managed_by_user', $contexts, true) && $fields['manager_user'] > 0) {
            $this->mergeSearchRows($projects, 'Project', [
                $this->crit($fields['manager_user'], 'equals', $agentId),
                $this->crit($fields['date_mod'] ?: $fields['date'], 'morethan', $start . ' 00:00:00', 'AND'),
                $this->crit($fields['date_mod'] ?: $fields['date'], 'lessthan', $end . ' 23:59:59', 'AND')
            ], $display, 'projects_managed_by_user');
        }

        if (in_array('projects_managed_by_groups', $contexts, true) && $fields['manager_group'] > 0) {
            $targetGroups = $groupIds;
            if ($this->batchTargetType === 'group' && $this->batchTargetId > 0) {
                $targetGroups = [$this->batchTargetId];
            }
            if (empty($targetGroups)) {
                $this->warnings[] = 'Projetos por grupo ignorados: selecione pelo menos um grupo no campo de grupos.';
            }
            foreach ($targetGroups as $gid) {
                if (!$this->checkBudget('pesquisa de projetos por grupo')) { break; }
                $gid = (int)$gid;
                if ($gid <= 0) continue;
                $this->mergeSearchRows($projects, 'Project', [
                    $this->crit($fields['manager_group'], 'equals', $gid),
                    $this->crit($fields['date_mod'] ?: $fields['date'], 'morethan', $start . ' 00:00:00', 'AND'),
                    $this->crit($fields['date_mod'] ?: $fields['date'], 'lessthan', $end . ' 23:59:59', 'AND')
                ], $display, 'projects_managed_by_groups:' . $gid);
                foreach ($projects as &$projectRow) {
                    if (isset($projectRow['_sources']['projects_managed_by_groups:' . $gid])) {
                        $projectRow['_sources']['projects_managed_by_groups'] = true;
                    }
                }
                unset($projectRow);
            }
        }

        if ($agentId > 0 && in_array('project_tasks_user', $contexts, true) && count($projects) < $this->candidateLimit && $this->checkBudget('pesquisa de tarefas de projeto')) {
            foreach ($this->searchSubitemParents('ProjectTask', 'projects_id', $agentId, $start, $end) as $projectId) {
                if (count($projects) >= $this->candidateLimit) { break; }
                $projects[$projectId]['_sources']['project_tasks_user'] = true;
            }
        }

        if (in_array('project_tasks_groups', $contexts, true) && empty($groupIds)) {
            $this->warnings[] = 'Tarefas de projeto por grupo ignoradas: selecione pelo menos um grupo no campo de grupos.';
        }

        // Do not probe all projects in the period. That was the main cause of 504
        // errors in larger GLPI instances. Try direct membership/searchable team
        // tables only; if the API does not expose them, return a warning and let
        // the user use managed projects, project tasks, or manual entries.
        if ($agentId > 0 && in_array('projects_member_user', $contexts, true) && count($projects) < $this->candidateLimit && $this->checkBudget('pesquisa de participação em projetos')) {
            $foundMemberProject = false;
            foreach (['ProjectTeam', 'Project_User'] as $teamType) {
                foreach ($this->searchSubitemParents($teamType, 'projects_id', $agentId, $start, $end) as $projectId) {
                    if (count($projects) >= $this->candidateLimit) { break 2; }
                    $projects[$projectId]['_sources']['projects_member_user'] = true;
                    $foundMemberProject = true;
                }
            }
            if (!$foundMemberProject && empty($projects)) {
                $this->warnings[] = 'O contexto "projetos em que o agente participa" depende de campos de equipe pesquisáveis pela API. Nesta instância, use projetos gerenciados, tarefas de projeto ou adicione a narrativa manualmente se a API não expuser essa relação.';
            }
        }

        if (count($projects) > $this->candidateLimit) {
            $this->warnings[] = 'Foram encontrados mais projetos do que o limite seguro deste rascunho. Apenas os primeiros ' . $this->candidateLimit . ' candidatos foram detalhados.';
            $projects = array_slice($projects, 0, $this->candidateLimit, true);
        }
        foreach (array_keys($projects) as $id) {
            if (!$this->checkBudget('detalhamento de projetos')) { break; }
            try {
                $projects[$id]['item'] = $this->client->getItem('Project', (int)$id, ['expand_dropdowns' => true, 'get_hateoas' => false]);
            } catch (\Throwable $e) {
                $this->warnings[] = 'Não foi possível detalhar o projeto ' . $id . ': ' . $e->getMessage();
            }
        }

        return $projects;
    }

    private function ticketToEntries(int $ticketId, array $ticket, int $agentId, array $contexts, string $start, string $end): array
    {
        $item = $ticket['item'] ?? [];
        $row = $ticket['row'] ?? [];
        $title = TextNormalizer::firstNonEmpty($item['name'] ?? '', $this->rowVal($row, 1), 'Chamado ' . $ticketId);
        $statusRaw = TextNormalizer::firstNonEmpty($item['status'] ?? '', $this->rowVal($row, 12));
        $status = $this->ticketStatusLabel($statusRaw);
        $sourceContexts = array_values(array_unique(array_keys($ticket['_sources'] ?? [])));

        // A report detail is ticket-centric: one editable block per unique ticket.
        // Older builds rendered a base Ticket entry and then additional task/followup/solution
        // entries for the same ticket. When the same ticket was discovered through user + group
        // contexts, that made 21 API tickets become ~40 detail cards. Keep the raw discovery
        // sources for coverage/analytics, but render a single canonical ticket entry here.
        $content = TextNormalizer::clean($item['content'] ?? '', $this->maskSensitive);
        $latest = [];
        $ticketCameFromAgentActivity = !empty($ticket['_sources']['ticket_tasks_by_user'])
            || !empty($ticket['_sources']['ticket_followups_by_user'])
            || !empty($ticket['_sources']['ticket_solutions_by_user'])
            || $this->sourceHasDateScope($sourceContexts, 'created_with_agent_action')
            || $this->sourceHasDateScope($sourceContexts, 'solved_or_closed_by_agent');

        if ($agentId > 0 && ($ticketCameFromAgentActivity || !empty($ticket['_sources']['tickets_assigned_user']))) {
            $latest = $this->latestTicketActivity($ticketId, $agentId, $start, $end);
        }

        $solutionSummary = '';
        if (empty($latest['text']) && $agentId > 0 && ($ticketCameFromAgentActivity || !empty($ticket['_sources']['tickets_assigned_user']))) {
            foreach ($this->safeSubitems('Ticket', $ticketId, 'ITILSolution') as $sol) {
                if (!is_array($sol)) continue;
                $sDate = TextNormalizer::firstNonEmpty($sol['date_approval'] ?? '', $sol['date_creation'] ?? '', $sol['date_mod'] ?? '', $item['solvedate'] ?? '');
                if ($sDate !== '' && !DateHelper::within($sDate, $start, $end)) continue;
                if ($this->hasExplicitUserField($sol) && !$this->subitemBelongsToUser($sol, $agentId)) continue;
                $solutionSummary .= "\n" . TextNormalizer::clean($sol['content'] ?? ($sol['solution'] ?? ''), $this->maskSensitive);
            }
        }

        $baseText = trim((string)($latest['text'] ?? '')) ?: (trim($solutionSummary) ?: $content);
        $date = $this->ticketDisplayDate($ticket, (string)($latest['date'] ?? ''), $start, $end);
        if ($date === '' || !DateHelper::within($date, $start, $end)) {
            $this->recordCoverageFilter('out_of_period_detail_suppressed');
            return [];
        }

        $baseReference = TextNormalizer::firstNonEmpty($latest['reference'] ?? '', 'Chamado ' . $ticketId);
        return [$this->entry([
            'date' => $date,
            'source_type' => 'ticket',
            'source_itemtype' => 'Ticket',
            'source_id' => $ticketId,
            'source_reference' => $baseReference,
            'title' => 'Chamado ' . $ticketId . ' - ' . $title,
            'status' => $status,
            'category' => $this->dropdownLabel('ITILCategory', TextNormalizer::firstNonEmpty($item['itilcategories_id'] ?? '', $this->rowVal($row, 7))),
            'activity_performed' => TextNormalizer::summarize($baseText ?: 'Registro localizado no GLPI dentro do contexto selecionado. Complementar a narrativa técnica, se necessário.', 1800),
            'result' => $this->resultFromStatus($status),
            'next_steps' => '',
            'observations' => trim((string)($latest['note'] ?? '')) ?: 'Origem: chamado vinculado ao contexto selecionado.',
            'ticket_id' => $ticketId,
            'ticket_title' => $title,
            'source_contexts' => $sourceContexts
        ])];
    }

    private function sourceHasDateScope(array $sources, string $scope): bool
    {
        foreach ($sources as $source) {
            if (strpos((string)$source, ':' . $scope . ':') !== false || preg_match('/:' . preg_quote($scope, '/') . '$/', (string)$source)) {
                return true;
            }
        }
        return false;
    }

    private function sourceHasDateBasis(array $sources, string $basis): bool
    {
        foreach ($sources as $source) {
            if (strpos((string)$source, ':' . $basis) !== false || preg_match('/:' . preg_quote($basis, '/') . '$/', (string)$source)) {
                return true;
            }
        }
        return false;
    }

    private function ticketDisplayDate(array $ticket, string $latestDate, string $start, string $end): string
    {
        $item = $ticket['item'] ?? [];
        $row = $ticket['row'] ?? [];
        $sources = array_values(array_unique(array_keys($ticket['_sources'] ?? [])));
        $opened = TextNormalizer::firstNonEmpty($item['date'] ?? '', $this->rowVal($row, 15));
        $updated = TextNormalizer::firstNonEmpty($item['date_mod'] ?? '', $this->rowVal($row, 19));
        $solved = TextNormalizer::firstNonEmpty($item['solvedate'] ?? '', $this->rowVal($row, 18));
        $closed = TextNormalizer::firstNonEmpty($item['closedate'] ?? '', $this->rowVal($row, 16));

        $preferred = [];
        if ($this->sourceHasDateScope($sources, 'created_with_agent_action') || (!empty($this->dateContext['created_with_agent_action']) && $this->batchDateScope === 'created_with_agent_action')) {
            $preferred[] = $opened;
        }
        if ($this->sourceHasDateScope($sources, 'solved_or_closed_in_period') || $this->sourceHasDateScope($sources, 'solved_or_closed_by_agent')) {
            if ($this->sourceHasDateBasis($sources, 'closedate') || $this->batchDateBasis === 'closedate') {
                $preferred[] = $closed;
            }
            if ($this->sourceHasDateBasis($sources, 'solvedate') || $this->batchDateBasis === 'solvedate') {
                $preferred[] = $solved;
            }
            $preferred[] = $solved;
            $preferred[] = $closed;
        }
        if ($this->sourceHasDateScope($sources, 'updated_in_period') || (!empty($this->dateContext['updated_in_period']) && $this->batchDateScope === 'updated_in_period')) {
            $preferred[] = $updated;
        }

        // Only use latest activity as a fallback. For "created in period" reports the
        // detail date must remain the creation/reference date, otherwise tickets created
        // on 01/06 can be rendered under 12/06, 17/06 or even 01/07.
        $preferred[] = $latestDate;
        $preferred[] = $opened;
        $preferred[] = $updated;
        $preferred[] = $solved;
        $preferred[] = $closed;

        foreach ($preferred as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate === '') continue;
            if (DateHelper::within($candidate, $start, $end)) {
                return DateHelper::normalizeDate($candidate, $start);
            }
        }
        return '';
    }

    private function projectToEntries(int $projectId, array $project, int $agentId, array $groupIds, array $contexts, string $start, string $end): array
    {
        $item = $project['item'] ?? [];
        $row = $project['row'] ?? [];
        $title = TextNormalizer::firstNonEmpty($item['name'] ?? '', $this->rowVal($row, 1), 'Projeto ' . $projectId);
        $statusRaw = TextNormalizer::firstNonEmpty($item['projectstates_id'] ?? '', $item['status'] ?? '', $this->rowVal($row, 12));
        $status = $this->projectStatusLabel($statusRaw);
        $fallbackDate = TextNormalizer::firstNonEmpty($item['date_mod'] ?? '', $item['date_creation'] ?? '', $this->rowVal($row, 19), $start);
        $entries = [];
        $sourceContexts = array_keys($project['_sources'] ?? []);

        $isMember = !in_array('projects_member_user', $contexts, true)
            || !empty($project['_sources']['projects_member_user'])
            || !empty($project['_sources']['projects_managed_by_user'])
            || !empty($project['_sources']['projects_managed_by_groups'])
            || !empty($project['_sources']['project_tasks_user']);
        if ($agentId > 0 && in_array('projects_member_user', $contexts, true) && !$isMember) {
            return [];
        }

        if (!empty($project['_sources']['projects_managed_by_user']) || !empty($project['_sources']['projects_managed_by_groups']) || ($isMember && in_array('projects_member_user', $contexts, true))) {
            $content = TextNormalizer::clean($item['content'] ?? ($item['comment'] ?? ''), $this->maskSensitive);
            $latest = $this->latestProjectActivity($projectId, $agentId, $start, $end);
            $activityText = trim((string)($latest['text'] ?? '')) ?: $content;
            $entries[] = $this->entry([
                'date' => TextNormalizer::firstNonEmpty($latest['date'] ?? '', $fallbackDate),
                'source_type' => 'project',
                'source_itemtype' => 'Project',
                'source_id' => $projectId,
                'source_reference' => TextNormalizer::firstNonEmpty($latest['reference'] ?? '', 'Projeto ' . $projectId),
                'title' => 'Projeto ' . $projectId . ' - ' . $title,
                'status' => $status,
                'category' => $this->dropdownLabel('ProjectType', TextNormalizer::firstNonEmpty($item['projecttypes_id'] ?? '', $item['type'] ?? '')),
                'activity_performed' => TextNormalizer::summarize($activityText ?: 'Projeto localizado no GLPI dentro do contexto selecionado. Complementar a narrativa técnica, se necessário.', 1800),
                'result' => $this->projectResult($item),
                'next_steps' => '',
                'observations' => trim((string)($latest['note'] ?? '')) ?: 'Origem: projeto vinculado ao contexto selecionado.',
                'source_contexts' => $sourceContexts
            ]);
        }

        $shouldScanProjectTasks = !empty($project['_sources']['project_tasks_user'])
            || !empty($project['_sources']['projects_managed_by_user'])
            || (in_array('project_tasks_groups', $contexts, true) && !empty($project['_sources']['projects_managed_by_groups']));
        if ($this->hasAny($contexts, ['project_tasks_user','project_tasks_groups']) && $shouldScanProjectTasks) {
            foreach ($this->safeSubitems('Project', $projectId, 'ProjectTask') as $task) {
                $taskDate = TextNormalizer::firstNonEmpty($task['real_start_date'] ?? '', $task['plan_start_date'] ?? '', $task['date_creation'] ?? '', $task['date_mod'] ?? '', $fallbackDate);
                if (!DateHelper::within($taskDate, $start, $end)) continue;
                $belongs = false;
                if (in_array('project_tasks_user', $contexts, true) && ($agentId <= 0 || $this->subitemBelongsToUser($task, $agentId))) {
                    $belongs = true;
                }
                if (!$belongs && in_array('project_tasks_groups', $contexts, true) && $this->subitemBelongsToAnyGroup($task, $groupIds)) {
                    $belongs = true;
                }
                if (!$belongs && !$this->hasExplicitOwnerField($task)) {
                    // Some APIs omit task team; include tasks from already selected project as fallback.
                    $belongs = true;
                }
                if (!$belongs) continue;

                $txt = TextNormalizer::clean(($task['content'] ?? '') ?: ($task['name'] ?? ''), $this->maskSensitive);
                $taskStatus = $this->projectTaskStatusLabel(TextNormalizer::firstNonEmpty($task['projecttaskstates_id'] ?? '', $task['state'] ?? '', $status));
                $entries[] = $this->entry([
                    'date' => $taskDate,
                    'source_type' => 'project_task',
                    'source_itemtype' => 'ProjectTask',
                    'source_id' => (int)($task['id'] ?? 0),
                    'source_reference' => 'Projeto ' . $projectId . ' / Tarefa ' . (int)($task['id'] ?? 0),
                    'title' => 'Tarefa de projeto - ' . TextNormalizer::firstNonEmpty($task['name'] ?? '', $title),
                    'status' => $taskStatus,
                    'activity_performed' => $txt ?: 'Tarefa de projeto registrada no GLPI.',
                    'result' => $this->projectTaskResult($task),
                    'next_steps' => '',
                    'observations' => 'Atividade extraída de tarefa de projeto.',
                    'source_contexts' => array_values(array_unique(array_merge($sourceContexts, ['project_tasks_user'])))
                ]);
            }
        }

        return $entries;
    }


    private function ticketFact(int $ticketId, array $ticket, int $agentId, array $groupIds, string $start, string $end): array
    {
        $item = $ticket['item'] ?? [];
        $row = $ticket['row'] ?? [];
        $title = TextNormalizer::firstNonEmpty($item['name'] ?? '', $this->rowVal($row, 1), 'Chamado ' . $ticketId);
        $status = $this->ticketStatusLabel(TextNormalizer::firstNonEmpty($item['status'] ?? '', $this->rowVal($row, 12)));
        $category = $this->dropdownLabel('ITILCategory', TextNormalizer::firstNonEmpty($item['itilcategories_id'] ?? '', $this->rowVal($row, 7)));
        $requesterRaw = $this->firstRawNonEmpty($item['users_id_recipient'] ?? null, $this->rowVal($row, 4));
        $requesterId = $this->extractId($requesterRaw);
        $requesterName = $this->labelFromValue($requesterRaw);
        $assignedUsers = $this->entitiesFromValue($this->firstRawNonEmpty($item['users_id_assign'] ?? null, $item['_users_id_assign'] ?? null, $this->rowVal($row, 5)), 'user');
        $assignedGroups = $this->entitiesFromValue($this->firstRawNonEmpty($item['groups_id_assign'] ?? null, $item['_groups_id_assign'] ?? null, $this->rowVal($row, 8)), 'group');
        return [
            'ticket_id' => $ticketId,
            'title' => TextNormalizer::clean($title, $this->maskSensitive),
            'status' => $status,
            'category' => $category,
            'opened_at' => (string)TextNormalizer::firstNonEmpty($item['date'] ?? '', $this->rowVal($row, 15)),
            'updated_at' => (string)TextNormalizer::firstNonEmpty($item['date_mod'] ?? '', $this->rowVal($row, 19)),
            'solved_at' => (string)TextNormalizer::firstNonEmpty($item['solvedate'] ?? '', $this->rowVal($row, 18)),
            'closed_at' => (string)TextNormalizer::firstNonEmpty($item['closedate'] ?? '', $this->rowVal($row, 16)),
            'requester_id' => $requesterId,
            'requester_name' => TextNormalizer::clean($requesterName, $this->maskSensitive),
            'assigned_users' => $assignedUsers,
            'assigned_groups' => $assignedGroups,
            'location_label' => $this->resolveTicketLocation($item + ['_row_location' => $this->rowVal($row, (int)($this->resolver->fieldStrict('Ticket', 'locations_id') ?: 0))], $requesterId),
            'source_contexts' => array_values(array_unique(array_keys($ticket['_sources'] ?? []))),
            'primary_source' => $this->primaryTicketSource(array_keys($ticket['_sources'] ?? [])),
            'events' => $this->needTimelineAnalytics ? $this->ticketEvents($ticketId, $item, $requesterId, $agentId, $groupIds, $start, $end) : [],
        ];
    }


    private function primaryTicketSource(array $sources): string
    {
        $priority = [
            'tickets_assigned_groups',
            'tickets_assigned_user',
            'ticket_solutions_by_user',
            'ticket_tasks_by_user',
            'ticket_followups_by_user',
            'tickets_requested_by_user',
        ];
        foreach ($priority as $needle) {
            foreach ($sources as $source) {
                if (strpos((string)$source, $needle) === 0) return $needle;
            }
        }
        return (string)($sources[0] ?? 'ticket');
    }

    private function projectFact(int $projectId, array $project, string $start, string $end): array
    {
        $item = $project['item'] ?? [];
        $row = $project['row'] ?? [];
        $title = TextNormalizer::firstNonEmpty($item['name'] ?? '', $this->rowVal($row, 1), 'Projeto ' . $projectId);
        $status = $this->projectStatusLabel(TextNormalizer::firstNonEmpty($item['projectstates_id'] ?? '', $item['status'] ?? '', $this->rowVal($row, 12)));
        return [
            'project_id' => $projectId,
            'title' => TextNormalizer::clean($title, $this->maskSensitive),
            'status' => $status,
            'percent_done' => (string)TextNormalizer::firstNonEmpty($item['percent_done'] ?? '', $item['percent'] ?? ''),
            'updated_at' => (string)TextNormalizer::firstNonEmpty($item['date_mod'] ?? '', $item['date_creation'] ?? '', $this->rowVal($row, 19)),
            'source_contexts' => array_values(array_unique(array_keys($project['_sources'] ?? []))),
        ];
    }

    private function ticketEvents(int $ticketId, array $item, int $requesterId, int $agentId, array $groupIds, string $start, string $end): array
    {
        $events = [];
        $opened = (string)TextNormalizer::firstNonEmpty($item['date'] ?? '', $item['date_creation'] ?? '');
        if ($opened !== '' && DateHelper::within($opened, $start, $end)) {
            $events[] = $this->event('ticket_opened', $opened, $requesterId, $this->labelFromValue($item['users_id_recipient'] ?? ''), 'requester', 'Ticket', $ticketId, 'Abertura do chamado pelo solicitante.');
        }

        foreach ($this->safeSubitems('Ticket', $ticketId, 'ITILFollowup') as $follow) {
            if (!is_array($follow)) continue;
            $date = (string)TextNormalizer::firstNonEmpty($follow['date'] ?? '', $follow['date_creation'] ?? '', $follow['date_mod'] ?? '');
            if ($date === '' || !DateHelper::within($date, $start, $end)) continue;
            $role = $this->actorRole($follow, $requesterId, $agentId, $groupIds);
            $type = $role === 'requester' ? 'requester_followup' : ($role === 'selected_agent' ? 'agent_followup' : ($role === 'selected_group' ? 'team_followup' : ($role === 'other_technical' ? 'other_technical_event' : 'unknown_followup')));
            $events[] = $this->event($type, $date, $this->actorId($follow), $this->actorName($follow), $role, 'ITILFollowup', (int)($follow['id'] ?? 0), TextNormalizer::summarize((string)($follow['content'] ?? ''), 220));
        }

        foreach ($this->safeSubitems('Ticket', $ticketId, 'TicketTask') as $task) {
            if (!is_array($task)) continue;
            $date = (string)TextNormalizer::firstNonEmpty($task['date'] ?? '', $task['begin'] ?? '', $task['date_creation'] ?? '', $task['date_mod'] ?? '');
            if ($date === '' || !DateHelper::within($date, $start, $end)) continue;
            $role = $this->actorRole($task, $requesterId, $agentId, $groupIds);
            $type = $role === 'selected_agent' ? 'agent_task' : ($role === 'selected_group' ? 'team_task' : 'other_technical_event');
            $events[] = $this->event($type, $date, $this->actorId($task), $this->actorName($task), $role, 'TicketTask', (int)($task['id'] ?? 0), TextNormalizer::summarize((string)TextNormalizer::firstNonEmpty($task['content'] ?? '', $task['name'] ?? ''), 220));
        }

        foreach ($this->safeSubitems('Ticket', $ticketId, 'ITILSolution') as $solution) {
            if (!is_array($solution)) continue;
            $date = (string)TextNormalizer::firstNonEmpty($solution['date_approval'] ?? '', $solution['date_creation'] ?? '', $solution['date_mod'] ?? '');
            if ($date === '' || !DateHelper::within($date, $start, $end)) continue;
            $role = $this->actorRole($solution, $requesterId, $agentId, $groupIds);
            $type = $role === 'selected_agent' ? 'agent_solution' : ($role === 'selected_group' ? 'team_solution' : 'other_technical_event');
            $events[] = $this->event($type, $date, $this->actorId($solution), $this->actorName($solution), $role, 'ITILSolution', (int)($solution['id'] ?? 0), TextNormalizer::summarize((string)TextNormalizer::firstNonEmpty($solution['content'] ?? '', $solution['solution'] ?? ''), 220));
        }

        usort($events, static function ($a, $b) {
            return strcmp((string)($a['datetime'] ?? ''), (string)($b['datetime'] ?? ''));
        });
        return $events;
    }

    private function event(string $type, string $datetime, int $actorId, string $actorName, string $actorRole, string $itemtype, int $id, string $text): array
    {
        $actor = $this->resolveUserEntity($actorId, $actorName);
        $displayName = $actor['name'] ?? '';
        if ($displayName === '' || preg_match('/^#?\d+$/', $displayName)) {
            $displayName = TextNormalizer::clean($actorName, $this->maskSensitive);
        }
        if ($displayName === '' && $actorId > 0) {
            $displayName = 'Usuário #' . $actorId;
        }

        return [
            'event_type' => $type,
            'datetime' => $datetime,
            'date' => DateHelper::normalizeDate($datetime, date('Y-m-d')),
            'actor_user_id' => $actorId,
            'actor_name' => $displayName,
            'actor_login' => (string)($actor['login'] ?? ''),
            'actor_reference' => (string)($actor['reference'] ?? ($actorId > 0 ? ('#' . $actorId) : '')),
            'actor_role' => $actorRole,
            'is_technical' => in_array($actorRole, ['selected_agent', 'selected_group', 'other_technical'], true) || strpos($type, 'agent_') === 0 || strpos($type, 'team_') === 0,
            'source_itemtype' => $itemtype,
            'source_id' => $id,
            'text' => TextNormalizer::clean($text, $this->maskSensitive),
        ];
    }

    private function actorRole(array $row, int $requesterId, int $agentId, array $groupIds): string
    {
        if ($agentId > 0 && $this->subitemBelongsToUser($row, $agentId)) {
            return 'selected_agent';
        }
        if (!empty($groupIds) && $this->subitemBelongsToAnyGroup($row, $groupIds)) {
            return 'selected_group';
        }
        if ($requesterId > 0 && $this->rowBelongsToUserId($row, $requesterId)) {
            return 'requester';
        }
        if ($this->managerMode && $this->actorId($row) > 0) {
            return 'other_technical';
        }
        return 'unknown';
    }

    private function rowBelongsToUserId(array $row, int $userId): bool
    {
        if ($userId <= 0) return false;
        foreach (['users_id', 'users_id_tech', 'users_id_editor', 'users_id_recipient', 'users_id_validate', 'users_id_approval'] as $k) {
            if (array_key_exists($k, $row) && $this->extractId($row[$k]) === $userId) {
                return true;
            }
        }
        if (isset($row['items_id']) && isset($row['itemtype']) && strtolower((string)$row['itemtype']) === 'user') {
            return $this->extractId($row['items_id']) === $userId;
        }
        return false;
    }

    private function actorId(array $row): int
    {
        foreach (['users_id', 'users_id_tech', 'users_id_editor', 'users_id_recipient', 'users_id_validate', 'users_id_approval'] as $k) {
            if (array_key_exists($k, $row)) {
                $id = $this->extractId($row[$k]);
                if ($id > 0) return $id;
            }
        }
        return 0;
    }

    private function actorName(array $row): string
    {
        foreach (['users_id', 'users_id_tech', 'users_id_editor', 'users_id_recipient'] as $k) {
            if (array_key_exists($k, $row)) {
                $label = $this->labelFromValue($row[$k]);
                if ($label !== '') return $label;
            }
        }
        return '';
    }

    private function resolveTicketLocation(array $item, int $requesterId): string
    {
        $locationRaw = $this->firstRawNonEmpty($item['locations_id'] ?? null, $item['location'] ?? null, $item['locations_id_recipient'] ?? null, $item['_row_location'] ?? null);
        $label = $this->dropdownLabel('Location', $locationRaw);
        if ($label !== '') {
            return $label;
        }
        if ($this->highVolumeFast) {
            return 'Local não informado';
        }
        if ($this->needLocationAnalytics && $requesterId > 0 && $this->checkBudget('resolução de localização do solicitante')) {
            try {
                $user = $this->client->getItem('User', $requesterId, ['expand_dropdowns' => true, 'get_hateoas' => false]);
                if (is_array($user)) {
                    $label = $this->dropdownLabel('Location', $this->firstRawNonEmpty($user['locations_id'] ?? null, $user['location'] ?? null));
                    if ($label !== '') {
                        return $label;
                    }
                }
            } catch (\Throwable $e) {
                // Keep honest fallback.
            }
        }
        return 'Local não informado';
    }


    private function firstRawNonEmpty(...$values)
    {
        foreach ($values as $value) {
            if ($value === null) continue;
            if (is_array($value) || is_object($value)) {
                if (!empty((array)$value)) return $value;
                continue;
            }
            if (trim((string)$value) !== '') return $value;
        }
        return '';
    }

    private function extractId($value): int
    {
        if (is_array($value)) {
            foreach (['id', 'items_id', 'users_id', 'groups_id'] as $key) {
                if (isset($value[$key]) && is_numeric($value[$key])) return (int)$value[$key];
            }
            return 0;
        }
        if (is_object($value)) {
            return $this->extractId((array)$value);
        }
        if (is_numeric($value)) return (int)$value;
        $s = trim((string)$value);
        if ($s === '') return 0;
        if (preg_match('/\((\d+)\)\s*$/', $s, $m)) return (int)$m[1];
        if (preg_match('/#(\d+)\b/', $s, $m)) return (int)$m[1];
        if (preg_match('/\b(?:Chamado|Ticket|Projeto|Project|ID)\D*(\d+)\b/i', $s, $m)) return (int)$m[1];
        if (preg_match_all('/\b(\d{2,})\b/', $s, $m) && !empty($m[1])) {
            $nums = $m[1];
            return (int)$nums[count($nums) - 1];
        }
        return 0;
    }

    private function labelFromValue($value): string
    {
        if (is_array($value)) {
            return (string)TextNormalizer::firstNonEmpty($value['completename'] ?? '', $value['name'] ?? '', $value['realname'] ?? '', $value['firstname'] ?? '');
        }
        if (is_object($value)) {
            return $this->labelFromValue((array)$value);
        }
        $s = trim((string)$value);
        if (preg_match('/^(.*?)\s*\(\d+\)\s*$/', $s, $m)) {
            return trim($m[1]);
        }
        return is_numeric($s) ? '' : $s;
    }

    private function entitiesFromValue($value, string $kind = 'user'): array
    {
        $out = [];
        $add = function ($raw) use (&$out, $kind): void {
            $id = $this->extractId($raw);
            if ($kind === 'user' && $id > 0) {
                $entity = $this->resolveUserEntity($id, $raw);
                $key = 'id:' . $id;
                $out[$key] = $entity;
                return;
            }

            $name = $this->labelFromValue($raw);
            if ($name === '' && is_array($raw)) {
                $name = TextNormalizer::firstNonEmpty($raw['completename'] ?? '', $raw['name'] ?? '', $raw['realname'] ?? '', $raw['firstname'] ?? '');
            }
            $name = TextNormalizer::clean(trim((string)$name, " .\t\n\r\0\x0B"), $this->maskSensitive);
            if ($id <= 0 && $name === '') return;
            $key = ($id > 0 ? ('id:' . $id) : ('name:' . self::lower($name)));
            $out[$key] = ['id' => $id, 'name' => $name !== '' ? $name : ('#' . $id), 'login' => '', 'reference' => $id > 0 ? ('#' . $id) : ''];
        };
        if (is_object($value)) {
            $value = (array)$value;
        }
        if (is_array($value)) {
            $isList = array_keys($value) === range(0, count($value) - 1);
            if ($isList) {
                foreach ($value as $v) {
                    foreach ($this->entitiesFromValue($v, $kind) as $row) {
                        $key = ((int)($row['id'] ?? 0) > 0 ? ('id:' . (int)$row['id']) : ('name:' . self::lower((string)($row['name'] ?? ''))));
                        $out[$key] = $row;
                    }
                }
            } else {
                $add($value);
            }
            return array_values($out);
        }
        $text = trim((string)$value);
        if ($text === '') return [];
        $parts = preg_split('/(?:\r?\n|;|\|)+/u', $text) ?: [$text];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $add($part);
        }
        return array_values($out);
    }

    private function resolveUserEntity(int $userId, $raw = null): array
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            $label = TextNormalizer::clean(trim($this->labelFromValue($raw), " .\t\n\r\0\x0B"), $this->maskSensitive);
            return ['id' => 0, 'name' => $label, 'login' => '', 'reference' => ''];
        }

        if (isset($this->userCache[$userId])) {
            return $this->userCache[$userId];
        }

        $rawLabel = TextNormalizer::clean(trim($this->labelFromValue($raw), " .\t\n\r\0\x0B"), $this->maskSensitive);
        $login = '';
        $display = '';

        if ($this->checkBudget('resolução de nomes de usuários')) {
            try {
                $item = $this->client->getItem('User', $userId, ['expand_dropdowns' => true, 'get_hateoas' => false]);
                if (is_array($item)) {
                    $login = TextNormalizer::clean((string)($item['name'] ?? ''), $this->maskSensitive);
                    $first = TextNormalizer::clean((string)($item['firstname'] ?? ''), $this->maskSensitive);
                    $real = TextNormalizer::clean((string)($item['realname'] ?? ''), $this->maskSensitive);
                    $complete = TextNormalizer::clean((string)($item['completename'] ?? ''), $this->maskSensitive);
                    $full = trim($first . ' ' . $real);
                    if ($full !== '') {
                        $display = $full;
                    } elseif ($complete !== '' && !$this->sameText($complete, $login)) {
                        $display = $complete;
                    } elseif ($real !== '') {
                        $display = $real;
                    } elseif ($first !== '') {
                        $display = $first;
                    }
                }
            } catch (\Throwable $e) {
                // Keep fallback below. Name resolution should never break a report.
            }
        }

        if ($display === '' && $rawLabel !== '' && !preg_match('/^#?\d+$/', $rawLabel)) {
            $display = $rawLabel;
        }
        if ($display === '') {
            $display = 'Usuário #' . $userId;
        }
        if ($login === '' && $rawLabel !== '' && !$this->sameText($rawLabel, $display)) {
            $login = $rawLabel;
        }

        $display = TextNormalizer::clean(trim($display, " .\t\n\r\0\x0B"), $this->maskSensitive);
        $login = TextNormalizer::clean(trim($login, " .\t\n\r\0\x0B"), $this->maskSensitive);
        $reference = trim('#' . $userId . ($login !== '' ? ' · ' . $login : ''));

        return $this->userCache[$userId] = [
            'id' => $userId,
            'name' => $display !== '' ? $display : ('Usuário #' . $userId),
            'login' => $login,
            'reference' => $reference,
        ];
    }

    private function latestTicketActivity(int $ticketId, int $agentId, string $start, string $end): array
    {
        return $this->latestActivityFromSubitems('Ticket', $ticketId, [
            'ITILFollowup' => ['label' => 'Acompanhamento', 'text' => ['content'], 'date' => ['date', 'date_creation', 'date_mod']],
            'TicketTask'   => ['label' => 'Tarefa', 'text' => ['content', 'name'], 'date' => ['date', 'begin', 'date_creation', 'date_mod']],
            'ITILSolution' => ['label' => 'Solução', 'text' => ['content', 'solution'], 'date' => ['date_approval', 'date_creation', 'date_mod']],
        ], $agentId, $start, $end, 'Chamado ' . $ticketId);
    }

    private function latestProjectActivity(int $projectId, int $agentId, string $start, string $end): array
    {
        return $this->latestActivityFromSubitems('Project', $projectId, [
            'ProjectTask' => ['label' => 'Tarefa de projeto', 'text' => ['content', 'name'], 'date' => ['real_start_date', 'plan_start_date', 'date_creation', 'date_mod']],
        ], $agentId, $start, $end, 'Projeto ' . $projectId);
    }

    private function latestActivityFromSubitems(string $parentType, int $parentId, array $subtypes, int $agentId, string $start, string $end, string $baseRef): array
    {
        $candidates = [];
        foreach ($subtypes as $subtype => $meta) {
            if (!$this->checkBudget('leitura de atividades relacionadas')) { break; }
            $rows = $this->safeSubitems($parentType, $parentId, (string)$subtype, true);
            foreach ($rows as $row) {
                if (!is_array($row)) continue;
                $date = $this->firstField($row, $meta['date'] ?? ['date_mod', 'date_creation']);
                if ($date === '') {
                    $date = date('Y-m-d');
                }
                if (!DateHelper::within($date, $start, $end)) continue;
                $text = TextNormalizer::clean($this->firstField($row, $meta['text'] ?? ['content', 'name']), $this->maskSensitive);
                if ($text === '') continue;
                $belongs = $agentId <= 0 || $this->subitemBelongsToUser($row, $agentId);
                $hasOwner = $this->hasExplicitUserField($row);
                $score = $belongs ? 2 : ($hasOwner ? 0 : 1);
                if ($score <= 0) continue;
                $id = (int)($row['id'] ?? 0);
                $label = (string)($meta['label'] ?? $subtype);
                $candidates[] = [
                    'date' => DateHelper::normalizeDate($date, date('Y-m-d')),
                    'datetime' => (string)$date,
                    'text' => $text,
                    'score' => $score,
                    'reference' => $baseRef . ' / último ' . $label . ($id > 0 ? ' ' . $id : ''),
                    'note' => $score === 2 ? 'Narrativa baseada na última atividade registrada pelo agente no período.' : 'Narrativa baseada na última atividade encontrada no período; a API não expôs autoria numérica suficiente para validação pelo agente.'
                ];
            }
        }
        if (empty($candidates)) {
            return [];
        }
        usort($candidates, static function ($a, $b) {
            $score = ((int)$b['score']) <=> ((int)$a['score']);
            if ($score !== 0) return $score;
            return strcmp((string)$b['datetime'], (string)$a['datetime']);
        });
        return $candidates[0];
    }

    private function firstField(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && trim((string)$row[$key]) !== '') {
                return (string)$row[$key];
            }
        }
        return '';
    }

    private function ticketStatusLabel($status): string
    {
        if (is_array($status)) {
            $label = TextNormalizer::firstNonEmpty($status['name'] ?? '', $status['completename'] ?? '');
            if ($label !== '') return $label;
            $status = $status['id'] ?? '';
        }
        $s = trim((string)$status);
        if ($s === '') return '';
        if (!is_numeric($s)) return TextNormalizer::clean($s, $this->maskSensitive);
        $map = [
            1 => 'Novo',
            2 => 'Em atendimento (atribuído)',
            3 => 'Em atendimento (planejado)',
            4 => 'Pendente',
            5 => 'Solucionado',
            6 => 'Fechado'
        ];
        return $map[(int)$s] ?? ('Status ' . (int)$s);
    }

    private function projectStatusLabel($status): string
    {
        $label = $this->dropdownLabel('ProjectState', $status);
        $s = trim((string)(is_array($status) ? ($status['id'] ?? '') : $status));
        $map = [
            1 => 'Novo',
            2 => 'Em andamento',
            17 => 'Em andamento',
            20 => 'Concluído'
        ];
        if (preg_match('/^ProjectState #([0-9]+)$/', $label, $m)) {
            return $map[(int)$m[1]] ?? $label;
        }
        return $label !== '' ? $label : ($map[(int)$s] ?? '');
    }

    private function projectTaskStatusLabel($status): string
    {
        $label = $this->dropdownLabel('ProjectTaskState', $status);
        if (preg_match('/^ProjectTaskState #([0-9]+)$/', $label, $m)) {
            $map = [1 => 'Novo', 2 => 'Em andamento', 17 => 'Em andamento', 20 => 'Concluído'];
            return $map[(int)$m[1]] ?? $label;
        }
        return $label;
    }

    private function dropdownLabel(string $itemtype, $value): string
    {
        if (is_array($value)) {
            $label = TextNormalizer::firstNonEmpty($value['completename'] ?? '', $value['name'] ?? '', $value['comment'] ?? '');
            if ($label !== '') {
                return TextNormalizer::clean($label, $this->maskSensitive);
            }
            $value = $value['id'] ?? $value['items_id'] ?? '';
        } elseif (is_object($value)) {
            $value = (array)$value;
            return $this->dropdownLabel($itemtype, $value);
        }
        $s = trim((string)$value);
        if ($s === '') return '';
        if (!is_numeric($s)) return TextNormalizer::clean($s, $this->maskSensitive);
        $id = (int)$s;
        if ($id <= 0) return '';
        $cacheKey = $itemtype . ':' . $id;
        if (isset($this->labelCache[$cacheKey])) {
            return $this->labelCache[$cacheKey];
        }
        try {
            $item = $this->client->getItem($itemtype, $id, ['expand_dropdowns' => true, 'get_hateoas' => false]);
            if (is_array($item)) {
                $label = TextNormalizer::firstNonEmpty($item['completename'] ?? '', $item['name'] ?? '', $item['comment'] ?? '');
                if ($label !== '') {
                    return $this->labelCache[$cacheKey] = TextNormalizer::clean($label, $this->maskSensitive);
                }
            }
        } catch (\Throwable $e) {
            // Keep numeric fallback below.
        }
        return $this->labelCache[$cacheKey] = ($itemtype . ' #' . $id);
    }

    private function hasExplicitUserField(array $row): bool
    {
        foreach (['users_id', 'users_id_tech', 'users_id_editor', 'users_id_recipient', 'users_id_validate', 'users_id_approval'] as $k) {
            if (array_key_exists($k, $row) && trim((string)$row[$k]) !== '') {
                return true;
            }
        }
        if (isset($row['itemtype']) && strtolower((string)$row['itemtype']) === 'user' && isset($row['items_id'])) {
            return true;
        }
        return false;
    }

    private function hasExplicitOwnerField(array $row): bool
    {
        return $this->hasExplicitUserField($row)
            || array_key_exists('groups_id', $row)
            || array_key_exists('groups_id_tech', $row)
            || array_key_exists('groups_id_assign', $row)
            || array_key_exists('groups_id_recipient', $row)
            || (isset($row['itemtype']) && in_array(strtolower((string)$row['itemtype']), ['user', 'group'], true) && isset($row['items_id']));
    }

    private function mergeSearchRows(array &$bucket, string $itemtype, array $criteria, array $display, string $source): void
    {
        $display = array_values(array_unique(array_filter($display)));
        if (!$this->checkBudget('pesquisa ' . $itemtype)) {
            return;
        }
        try {
            $start = $this->rangeLimit > 0 ? $this->rangeStart : 0;
            $limit = $this->rangeLimit > 0 ? $this->rangeLimit : $this->candidateLimit;
            $range = $start . '-' . max($start, $start + $limit - 1);
            $res = $this->client->search($itemtype, $criteria, $display, $range, ['sort' => $display[0] ?? 2, 'order' => 'DESC']);
            $rows = is_array($res['data'] ?? null) ? $res['data'] : [];
            $this->recordSearchMeta($source, $res, count($rows), $start, $limit);
            foreach ($rows as $row) {
                if (!is_array($row)) continue;
                $id = $this->rowId($row);
                if ($id <= 0) continue;
                if (!isset($bucket[$id])) {
                    if (count($bucket) >= max($limit, $this->candidateLimit)) { break; }
                    $bucket[$id] = ['row' => $row, '_sources' => []];
                }
                $bucket[$id]['_sources'][$source] = true;
            }
        } catch (\Throwable $e) {
            if ($this->isRangeExceededException($e)) {
                $this->recordRangeExceededMeta($source, $e, $start, $limit);
                return;
            }
            $this->warnings[] = 'Falha ao pesquisar ' . $itemtype . ' para o contexto ' . $source . ': ' . $e->getMessage();
        }
    }

    private function searchSubitemParents(string $itemtype, string $parentFieldLogical, int $agentId, string $start, string $end, string $requiredItemtype = ''): array
    {
        // Subitem search option IDs vary a lot between GLPI REST API versions,
        // especially for TicketTask, ITILFollowup, ITILSolution, ProjectTask and
        // ProjectTeam. Do not guess criteria field IDs here: guessed IDs caused
        // HTTP 400 warnings in the report UI and, in some installations, slow
        // retries. Use only fields explicitly exposed by listSearchOptions or
        // configured by admin overrides. Parent tickets/projects that are already
        // discovered are still enriched through getSubItems(), which does not rely
        // on search criteria IDs.
        $idField = $this->resolver->fieldStrict($itemtype, 'id') ?: 2;
        $parentField = $this->resolver->fieldStrict($itemtype, $parentFieldLogical);
        $userField = $this->resolver->fieldStrict($itemtype, 'users_id');
        $dateField = $this->resolver->fieldStrict($itemtype, 'date_mod') ?: $this->resolver->fieldStrict($itemtype, 'date');

        if ($parentField <= 0 || $userField <= 0 || $dateField <= 0) {
            $this->addCoverageLimitation('Busca direta de ' . $itemtype . ' indisponível: campos de pai/usuário/data não foram expostos pela API. O módulo usará enriquecimento apenas dos chamados já descobertos por agente/grupo.');
            return [];
        }

        $parents = [];
        if (!$this->checkBudget('pesquisa ' . $itemtype)) {
            return [];
        }
        try {
            [$from, $to] = $this->searchBounds($start, $end);
            $criteria = [
                $this->crit($userField, 'equals', $agentId),
                $this->crit($dateField, 'morethan', $from, 'AND'),
                $this->crit($dateField, 'lessthan', $to, 'AND')
            ];
            if ($requiredItemtype !== '') {
                $itField = $this->resolver->fieldStrict($itemtype, 'itemtype');
                if ($itField > 0) {
                    $criteria[] = $this->crit($itField, 'equals', $requiredItemtype, 'AND');
                }
            }
            $startAt = $this->rangeLimit > 0 ? $this->rangeStart : 0;
            $limit = $this->rangeLimit > 0 ? $this->rangeLimit : $this->candidateLimit;
            $range = $startAt . '-' . max($startAt, $startAt + $limit - 1);
            $res = $this->client->search($itemtype, $criteria, array_values(array_filter([$idField, $parentField])), $range);
            $rows = is_array($res['data'] ?? null) ? $res['data'] : [];
            $this->recordSearchMeta($itemtype . ':' . $parentFieldLogical, $res, count($rows), $startAt, $limit);
            foreach ($rows as $row) {
                if (!is_array($row)) continue;
                $pid = $this->extractId($this->rowVal($row, $parentField));
                if ($pid > 0) {
                    if (count($parents) >= $limit) { break; }
                    $parents[$pid] = $pid;
                }
            }
        } catch (\Throwable $e) {
            // Do not put low-level API search-option failures into the normal report.
            // Still keep a compact coverage limitation so missing ticket discovery can
            // be explained without leaking raw GLPI errors into the DOCX.
            $this->addCoverageLimitation('Busca direta de ' . $itemtype . ' falhou ou não foi aceita pela API; use Diagnóstico para mapear campos pesquisáveis. O módulo usará enriquecimento apenas dos chamados já descobertos por agente/grupo.');
        }
        return array_values($parents);
    }

    private function resolveUserGroups(int $userId): array
    {
        $ids = [];
        foreach (['Group_User', 'Group'] as $sub) {
            if (!$this->checkBudget('resolução de grupos do usuário')) { break; }
            try {
                $rows = $this->client->getSubItems('User', $userId, $sub, ['expand_dropdowns' => false, 'get_hateoas' => false]);
                if (!is_array($rows)) continue;
                foreach ($rows as $row) {
                    if (!is_array($row)) continue;
                    foreach (['groups_id', 'id'] as $k) {
                        if (isset($row[$k]) && is_numeric($row[$k])) {
                            $ids[(int)$row[$k]] = true;
                        }
                    }
                }
                if (!empty($ids)) break;
            } catch (\Throwable $e) {
                // Try next route.
            }
        }
        return array_keys($ids);
    }

    private function resolveGroups(array $groupIds): array
    {
        $out = [];
        foreach ($groupIds as $gid) {
            if (!$this->checkBudget('resolução de nomes de grupos')) { break; }
            $gid = (int)$gid;
            if ($gid <= 0) continue;
            $name = 'Grupo ID ' . $gid;
            try {
                $item = $this->client->getItem('Group', $gid, ['expand_dropdowns' => true, 'get_hateoas' => false]);
                $name = TextNormalizer::firstNonEmpty($item['name'] ?? '', $name);
            } catch (\Throwable $e) {
                // keep fallback
            }
            $out[$gid] = ['id' => $gid, 'name' => $name];
        }
        return $out;
    }

    private function safeSubitems(string $itemtype, int $id, string $subitemtype, bool $full = false): array
    {
        $cacheKey = $itemtype . ':' . $id . ':' . $subitemtype . ':' . ($full ? 'full' : 'page');
        if (array_key_exists($cacheKey, $this->subitemCache)) {
            return $this->subitemCache[$cacheKey];
        }
        if (!$this->checkBudget('leitura de ' . $subitemtype)) {
            return $this->subitemCache[$cacheKey] = [];
        }
        $all = [];
        $offset = 0;
        $limit = $this->subitemLimit;
        $maxRows = $full ? max($this->subitemLimit, min(500, $this->subitemLimit * 10)) : $this->subitemLimit;
        try {
            do {
                if (!$this->checkBudget('leitura de ' . $subitemtype)) {
                    $this->addCoverageLimitation('Leitura parcial de ' . $subitemtype . ' para ' . $itemtype . ' #' . $id . ': limite de segurança atingido.');
                    break;
                }
                $rows = $this->client->getSubItems($itemtype, $id, $subitemtype, [
                    'expand_dropdowns' => false,
                    'get_hateoas' => false,
                    'range' => $offset . '-' . ($offset + $limit - 1)
                ]);
                if (!is_array($rows) || empty($rows)) {
                    break;
                }
                foreach ($rows as $row) {
                    $all[] = $row;
                    if (count($all) >= $maxRows) {
                        break 2;
                    }
                }
                if (!$full || count($rows) < $limit) {
                    break;
                }
                $offset += $limit;
            } while (true);
            return $this->subitemCache[$cacheKey] = $all;
        } catch (\Throwable $e) {
            $this->addCoverageLimitation('Não foi possível ler ' . $subitemtype . ' de ' . $itemtype . ' #' . $id . ': API não retornou subitens utilizáveis.');
            return $this->subitemCache[$cacheKey] = $all;
        }
    }

    private function projectHasUser(int $projectId, int $agentId): bool
    {
        if ($agentId <= 0) return true;
        foreach (['ProjectTeam', 'Project_User', 'ProjectTaskTeam'] as $sub) {
            if (!$this->checkBudget('verificação de equipe do projeto')) { break; }
            foreach ($this->safeSubitems('Project', $projectId, $sub) as $row) {
                if ($this->subitemBelongsToUser($row, $agentId)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function subitemBelongsToUser(array $row, int $userId): bool
    {
        if ($userId <= 0) return true;
        foreach (['users_id', 'users_id_tech', 'users_id_editor', 'users_id_recipient', 'users_id_validate', 'users_id_approval'] as $k) {
            if (!array_key_exists($k, $row)) {
                continue;
            }
            if ($this->valueMatchesUser($row[$k], $userId)) {
                return true;
            }
        }
        if (isset($row['items_id']) && isset($row['itemtype']) && strtolower((string)$row['itemtype']) === 'user') {
            return $this->valueMatchesUser($row['items_id'], $userId);
        }
        return false;
    }

    private function valueMatchesUser($value, int $userId): bool
    {
        if (is_array($value)) {
            foreach (['id', 'items_id', 'users_id'] as $key) {
                if (isset($value[$key]) && is_numeric($value[$key]) && (int)$value[$key] === $userId) {
                    return true;
                }
            }
            foreach (['name', 'completename', 'realname'] as $key) {
                if (isset($value[$key]) && $this->agentName !== '' && $this->sameText((string)$value[$key], $this->agentName)) {
                    return true;
                }
            }
            return false;
        }
        if (is_object($value)) {
            return $this->valueMatchesUser((array)$value, $userId);
        }
        if (is_numeric($value) && (int)$value === $userId) {
            return true;
        }
        return !is_numeric($value) && $this->agentName !== '' && $this->sameText((string)$value, $this->agentName);
    }

    private function subitemBelongsToAnyGroup(array $row, array $groupIds): bool
    {
        if (empty($groupIds)) return false;
        $map = array_flip(array_map('intval', $groupIds));
        foreach (['groups_id', 'groups_id_tech', 'groups_id_assign', 'groups_id_recipient'] as $k) {
            if (isset($row[$k]) && $this->valueMatchesAnyGroup($row[$k], $map)) {
                return true;
            }
        }
        if (isset($row['items_id']) && isset($row['itemtype']) && strtolower((string)$row['itemtype']) === 'group' && $this->valueMatchesAnyGroup($row['items_id'], $map)) {
            return true;
        }
        return false;
    }

    private function valueMatchesAnyGroup($value, array $groupMap): bool
    {
        if (is_array($value)) {
            foreach (['id', 'items_id', 'groups_id'] as $key) {
                if (isset($value[$key]) && is_numeric($value[$key]) && isset($groupMap[(int)$value[$key]])) {
                    return true;
                }
            }
            return false;
        }
        if (is_object($value)) {
            return $this->valueMatchesAnyGroup((array)$value, $groupMap);
        }
        return is_numeric($value) && isset($groupMap[(int)$value]);
    }

    private function addEntry(array &$entries, array &$seen, array $entry): void
    {
        if (count($entries) >= $this->maxItems) return;
        $key = implode('|', [
            $entry['source_type'] ?? '',
            $entry['source_itemtype'] ?? '',
            $entry['source_id'] ?? '',
            $entry['date'] ?? '',
            md5((string)($entry['title'] ?? '') . '|' . (string)($entry['activity_performed'] ?? ''))
        ]);
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $entries[] = $entry;
    }

    private function entry(array $data): array
    {
        $date = TextNormalizer::firstNonEmpty($data['date'] ?? '', date('Y-m-d'));
        $date = DateHelper::normalizeDate($date, date('Y-m-d'));
        return [
            'date' => $date,
            'source_type' => (string)($data['source_type'] ?? 'manual'),
            'source_itemtype' => (string)($data['source_itemtype'] ?? ''),
            'source_id' => (int)($data['source_id'] ?? 0),
            'source_reference' => TextNormalizer::clean($data['source_reference'] ?? '', $this->maskSensitive),
            'ticket_id' => (int)($data['ticket_id'] ?? 0),
            'ticket_title' => TextNormalizer::clean($data['ticket_title'] ?? '', $this->maskSensitive),
            'professional' => '',
            'title' => TextNormalizer::clean($data['title'] ?? 'Atividade', $this->maskSensitive),
            'status' => TextNormalizer::clean($data['status'] ?? '', $this->maskSensitive),
            'category' => TextNormalizer::clean($data['category'] ?? '', $this->maskSensitive),
            'activity_performed' => TextNormalizer::clean($data['activity_performed'] ?? '', $this->maskSensitive),
            'result' => TextNormalizer::clean($data['result'] ?? '', $this->maskSensitive),
            'next_steps' => TextNormalizer::clean($data['next_steps'] ?? '', $this->maskSensitive),
            'observations' => TextNormalizer::clean($data['observations'] ?? '', $this->maskSensitive),
            'include_in_report' => true,
            'source_contexts' => is_array($data['source_contexts'] ?? null) ? array_values(array_unique(array_map('strval', $data['source_contexts']))) : []
        ];
    }

    private function calculateManagerMetrics(array $tickets): array
    {
        $total = count($tickets);
        $closed = 0;
        foreach ($tickets as $ticket) {
            $status = self::lower((string)($ticket['status'] ?? ''));
            if (preg_match('/fech|soluc|conclu|closed|solved|done/', $status)) {
                $closed++;
            }
        }
        $rate = $total > 0 ? round(($closed / $total) * 100, 1) : 0;
        return [
            ['key' => 'tickets_scope', 'label' => 'Chamados no escopo', 'value' => $total],
            ['key' => 'closed_tickets', 'label' => 'Chamados concluídos/solucionados', 'value' => $closed],
            ['key' => 'closed_rate', 'label' => 'Taxa de encerramento/conclusão', 'value' => $rate . '%']
        ];
    }

    private function calculateMetrics(array $entries): array
    {
        $total = count($entries);
        $tickets = 0; $projects = 0; $tasks = 0; $closed = 0;
        foreach ($entries as $e) {
            $stype = (string)($e['source_type'] ?? '');
            if (strpos($stype, 'ticket') === 0) $tickets++;
            if (strpos($stype, 'project') === 0) $projects++;
            if (strpos($stype, 'task') !== false) $tasks++;
            $status = self::lower((string)($e['status'] ?? ''));
            if (preg_match('/fech|soluc|conclu|closed|solved|done/', $status)) $closed++;
        }
        $rate = $total > 0 ? round(($closed / $total) * 100, 1) : 0;
        return [
            ['key' => 'entries', 'label' => 'Entradas do relatório', 'value' => $total],
            ['key' => 'tickets', 'label' => 'Entradas de chamados', 'value' => $tickets],
            ['key' => 'projects', 'label' => 'Entradas de projetos', 'value' => $projects],
            ['key' => 'closed_rate', 'label' => 'Taxa de encerramento/conclusão', 'value' => $rate . '%']
        ];
    }

    private function groupByDay(array $entries): array
    {
        $groups = [];
        foreach ($entries as $entry) {
            if (empty($entry['include_in_report'])) continue;
            $date = (string)($entry['date'] ?? '');
            if ($date === '') $date = date('Y-m-d');
            if (!isset($groups[$date])) {
                $groups[$date] = ['date' => $date, 'display_date' => DateHelper::displayDate($date), 'entries' => []];
            }
            $groups[$date]['entries'][] = $entry;
        }
        ksort($groups);
        return array_values($groups);
    }

    private function validateManagerDraft(array $tickets, array $contexts): array
    {
        $out = [];
        if (empty($tickets) && $this->batchContext === '') {
            $out[] = ['level' => 'warning', 'message' => 'Nenhum chamado foi retornado para o escopo gerencial. Verifique grupos selecionados, período e contextos.'];
        } else {
            $out[] = ['level' => 'info', 'message' => 'Modo gerencial ativo: o detalhamento individual de chamados foi omitido para priorizar indicadores por agente/grupo e evitar documentos excessivamente extensos.'];
        }
        if ($this->needTimelineAnalytics) {
            $withEvents = 0;
            foreach ($tickets as $ticket) {
                if (!empty($ticket['events'])) $withEvents++;
            }
            if (!empty($tickets) && $withEvents < count($tickets)) {
                $out[] = ['level' => 'info', 'message' => 'Extração parcial de eventos técnicos: indicadores de tempo de resposta consideram somente chamados com linha do tempo disponível via API.'];
            }
        }
        return $out;
    }

    private function validateDraft(array $entries, array $contexts, string $start, string $end): array
    {
        $out = [];
        if (empty($entries) && $this->batchContext === '') {
            $out[] = ['level' => 'warning', 'message' => 'Nenhuma atividade foi retornada pela API para o período e contextos selecionados. Verifique credenciais, campos de pesquisa e escopo do agente.'];
        }
        if (count($entries) > 80) {
            $out[] = ['level' => 'warning', 'message' => 'O rascunho possui mais de 80 entradas. Considere usar o relatório gerencial resumido e anexar evidência completa separadamente.'];
        }
        foreach ($entries as $idx => $entry) {
            if (!DateHelper::within((string)($entry['date'] ?? ''), $start, $end)) {
                $out[] = ['level' => 'warning', 'message' => 'Entrada fora do período selecionado: ' . (($entry['title'] ?? 'Atividade') ?: 'Atividade')];
                break;
            }
            if (trim((string)($entry['activity_performed'] ?? '')) === '') {
                $out[] = ['level' => 'warning', 'message' => 'Há entradas sem descrição de atividade realizada. Complete a narrativa antes do DOCX final.'];
                break;
            }
        }
        if (in_array('manual_entries', $contexts, true)) {
            $out[] = ['level' => 'info', 'message' => 'Contexto manual habilitado. Use o editor para adicionar atividades que não estejam bem representadas em tickets ou projetos.'];
        }
        return $out;
    }

    private function defaultManagerExecutiveSummary(array $tickets, array $groups, string $start, string $end): string
    {
        $count = count($tickets);
        $groupNames = [];
        foreach ($groups as $group) {
            $name = trim((string)($group['name'] ?? ''));
            if ($name !== '') $groupNames[] = $name;
        }
        $scope = !empty($groupNames) ? ' nos grupos selecionados (' . implode('; ', $groupNames) . ')' : ' nos grupos selecionados';
        return 'No período de ' . DateHelper::displayDate($start) . ' a ' . DateHelper::displayDate($end) . ', foram analisados ' . $count . ' chamados' . $scope . '. O modo gerencial omite o detalhamento individual e prioriza indicadores por agente/equipe, status, categoria, localização, evolução diária e tempo de resposta quando disponível.';
    }

    private function defaultExecutiveSummary(array $entries, string $agentName, string $start, string $end): string
    {
        $total = count($entries);
        return 'No período de ' . DateHelper::displayDate($start) . ' a ' . DateHelper::displayDate($end) . ', foram consolidadas ' . $total . ' entradas de atividade relacionadas a ' . $agentName . '. O rascunho combina registros extraídos da API do GLPI com espaço para complementações narrativas, permitindo revisão técnica antes da exportação em DOCX.';
    }

    private function resultFromStatus(string $status): string
    {
        $s = self::lower($status);
        if (preg_match('/fech|soluc|conclu|closed|solved|done/', $s)) {
            return 'Atividade registrada como concluída/solucionada no GLPI.';
        }
        if (preg_match('/pend|andamento|novo|process/', $s)) {
            return 'Atividade em acompanhamento, com continuidade conforme tratamento registrado.';
        }
        return '';
    }

    private function projectResult(array $item): string
    {
        $pct = TextNormalizer::firstNonEmpty($item['percent_done'] ?? '', $item['percent'] ?? '');
        if ($pct !== '') return 'Percentual de evolução registrado: ' . $pct . '.';
        return '';
    }

    private function projectTaskResult(array $task): string
    {
        $pct = TextNormalizer::firstNonEmpty($task['percent_done'] ?? '', $task['percent'] ?? '');
        if ($pct !== '') return 'Percentual de execução da tarefa: ' . $pct . '.';
        return '';
    }



    private function recordCoverageFilter(string $key, int $count = 1): void
    {
        if (!isset($this->coverage['filters'][$key])) {
            $this->coverage['filters'][$key] = 0;
        }
        $this->coverage['filters'][$key] += max(0, $count);
    }

    private function addCoverageLimitation(string $message): void
    {
        $message = trim($message);
        if ($message === '') return;
        if (!in_array($message, $this->coverage['limitations'], true)) {
            $this->coverage['limitations'][] = $message;
        }
    }

    private function ensureCoverageSource(string $source): void
    {
        if (!isset($this->coverage['sources'][$source])) {
            $this->coverage['sources'][$source] = [
                'api_total' => 0,
                'rows_loaded' => 0,
                'unique_added' => 0,
                'duplicates' => 0,
                'completed' => false,
            ];
        }
    }

    private function recordCoverageUnique(string $source, int $uniqueAdded, int $duplicates): void
    {
        $this->ensureCoverageSource($source);
        $this->coverage['sources'][$source]['unique_added'] += max(0, $uniqueAdded);
        $this->coverage['sources'][$source]['duplicates'] += max(0, $duplicates);
        if ($duplicates > 0) {
            $this->recordCoverageFilter('duplicate_merged', $duplicates);
        }
    }

    private function isRangeExceededException(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        if (stripos($msg, 'ERROR_RANGE_EXCEED_TOTAL') !== false || stripos($msg, 'range exceed total') !== false) {
            return true;
        }
        if ($e instanceof \GlpiPlugin\G4freports\Api\GlpiApiException) {
            $body = $e->getResponseBody();
            $json = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($json) && (stripos($json, 'ERROR_RANGE_EXCEED_TOTAL') !== false || stripos($json, 'range exceed total') !== false);
        }
        return false;
    }

    private function recordRangeExceededMeta(string $source, \Throwable $e, int $offset, int $limit): void
    {
        $msg = $e->getMessage();
        $total = 0;
        if (preg_match('/total count of data:\s*(\d+)/i', $msg, $m)) {
            $total = (int)$m[1];
        }
        if ($total > 0) {
            $this->sourceTotals[$source] = max((int)($this->sourceTotals[$source] ?? 0), $total);
            $this->ensureCoverageSource($source);
            $this->coverage['sources'][$source]['api_total'] = max((int)$this->coverage['sources'][$source]['api_total'], $total);
            $this->coverage['sources'][$source]['completed'] = true;
        }
        $this->sourceReturned[$source] = (int)($this->sourceReturned[$source] ?? 0);
    }

    private function recordSearchMeta(string $source, array $res, int $returned, int $offset, int $limit): void
    {
        $total = 0;
        foreach (['totalcount', 'total_count', 'total', 'count'] as $k) {
            if (isset($res[$k]) && is_numeric($res[$k])) {
                $total = max($total, (int)$res[$k]);
            }
        }
        // GLPI normally provides totalcount. If it does not, infer enough to
        // let the browser continue while pages are full.
        if ($total <= 0) {
            $total = $offset + $returned;
            if ($limit > 0 && $returned >= $limit) {
                $total = $offset + $returned + 1;
            }
        }
        $this->sourceTotals[$source] = max((int)($this->sourceTotals[$source] ?? 0), $total);
        $this->sourceReturned[$source] = (int)($this->sourceReturned[$source] ?? 0) + $returned;
        $this->ensureCoverageSource($source);
        $this->coverage['sources'][$source]['api_total'] = max((int)$this->coverage['sources'][$source]['api_total'], $total);
        $this->coverage['sources'][$source]['rows_loaded'] += $returned;
        $this->coverage['sources'][$source]['completed'] = !($limit > 0 && $returned > 0 && ($offset + $returned) < $total);
        if ($limit > 0 && $returned > 0 && ($offset + $returned) < $total) {
            $this->batchHasMore = true;
        }
    }

    private function batchTotalCandidates(): int
    {
        $total = 0;
        foreach ($this->sourceTotals as $value) {
            $total += (int)$value;
        }
        return $total;
    }

    private function checkBudget(string $stage = ''): bool
    {
        if ($this->startedAt <= 0) {
            return true;
        }
        if ((microtime(true) - $this->startedAt) <= $this->timeLimitSeconds) {
            return true;
        }
        if (!$this->budgetExceeded) {
            $this->budgetExceeded = true;
            $msg = 'Limite interno de tempo atingido';
            if ($stage !== '') {
                $msg .= ' durante: ' . $stage;
            }
            $this->warnings[] = $msg . '. A extração foi encerrada de forma controlada para evitar erro 504.';
        }
        return false;
    }

    private function requiresAgent(array $contexts): bool
    {
        foreach ($contexts as $ctx) {
            if (strpos($ctx, '_user') !== false || strpos($ctx, '_by_user') !== false || strpos($ctx, '_member_user') !== false) {
                return true;
            }
        }
        return false;
    }

    private function hasAny(array $contexts, array $needles): bool
    {
        foreach ($needles as $n) {
            if (in_array($n, $contexts, true)) return true;
        }
        return false;
    }

    private function crit(int $field, string $searchtype, $value, string $link = ''): array
    {
        $c = ['field' => $field, 'searchtype' => $searchtype, 'value' => (string)$value];
        if ($link !== '') $c['link'] = $link;
        return $c;
    }

    private function rowId(array $row): int
    {
        foreach (['2', 2, 'id', 'ID'] as $k) {
            if (isset($row[$k])) {
                $id = $this->extractId($row[$k]);
                if ($id > 0) return $id;
            }
        }
        foreach ($row as $k => $v) {
            if ((string)$k === 'id' || (string)$k === 'ID') {
                $id = $this->extractId($v);
                if ($id > 0) return $id;
            }
        }
        return 0;
    }

    private function rowVal(array $row, int $field)
    {
        if ($field <= 0) return '';
        foreach ([(string)$field, $field] as $k) {
            if (array_key_exists($k, $row)) return $row[$k];
        }
        return '';
    }

    private function sameText(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === '' || $b === '') return false;
        $la = self::lower($a);
        $lb = self::lower($b);
        return $la === $lb || strpos($la, $lb) !== false || strpos($lb, $la) !== false;
    }

    private function parseIdList($raw): array
    {
        if (is_array($raw)) {
            $ids = [];
            foreach ($raw as $v) {
                $id = (int)$v;
                if ($id > 0) $ids[$id] = $id;
            }
            return array_values($ids);
        }
        $ids = [];
        foreach (preg_split('/[^0-9]+/', (string)$raw) as $p) {
            $id = (int)$p;
            if ($id > 0) $ids[$id] = $id;
        }
        return array_values($ids);
    }
    private static function lower(string $text): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    }

}
