<?php
// Unified API endpoint. Accepts GET with ?action=...&payload={json} by default
// and also supports JSON/form POST. It follows the GLPIBot-style pattern:
// short-lived server-side API calls, no browser-side token exposure, and no
// /ajax dependency.

require_once dirname(__DIR__) . '/inc/bootstrap.php';

use GlpiPlugin\G4freports\Config;
use GlpiPlugin\G4freports\Security;
use GlpiPlugin\G4freports\Api\GlpiApiClient;
use GlpiPlugin\G4freports\Api\SearchOptionResolver;
use GlpiPlugin\G4freports\Builder\ReportDraftBuilder;

@ini_set('display_errors', '0');
@set_time_limit(45);

$cfg = Config::merged();
if (!Security::canUse($cfg) && !Security::canConfigure()) {
    g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
}

$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? ''));
$input = g4fr_parse_request_payload();
if ($action === '') {
    $action = trim((string)($input['action'] ?? ''));
}
if ($action === '') {
    g4fr_json_response(400, ['ok' => false, 'error' => 'action_nao_informada']);
}

function g4fr_api_profile_from_input(array $input, array $cfg): ?array {
    if (isset($input['adhoc_profile']) && is_array($input['adhoc_profile']) && \GlpiPlugin\G4freports\Security::canConfigure()) {
        return \GlpiPlugin\G4freports\Config::normalizeProfile($input['adhoc_profile'], '__adhoc__');
    }
    $profileId = trim((string)($input['profile_id'] ?? '__default__'));
    return \GlpiPlugin\G4freports\Config::getAllowedProfileForCurrentUser($profileId, $cfg);
}

function g4fr_row_value(array $row, int $field): string {
    if ($field > 0 && isset($row[(string)$field])) {
        return is_scalar($row[(string)$field]) ? (string)$row[(string)$field] : json_encode($row[(string)$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if ($field > 0 && isset($row[$field])) {
        return is_scalar($row[$field]) ? (string)$row[$field] : json_encode($row[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return '';
}

function g4fr_row_id(array $row, int $field = 2): int {
    foreach ([$field, 2, 'id'] as $key) {
        if (is_int($key) && isset($row[(string)$key]) && is_numeric($row[(string)$key])) return (int)$row[(string)$key];
        if (isset($row[$key]) && is_numeric($row[$key])) return (int)$row[$key];
    }
    return 0;
}

function g4fr_add_group_result(array &$groups, int $id, string $name, array $raw = []): void {
    if ($id <= 0) return;
    $name = trim($name) ?: ('Grupo #' . $id);
    foreach ($groups as $g) {
        if ((int)($g['id'] ?? 0) === $id) return;
    }
    $groups[] = ['id' => $id, 'name' => $name, 'raw' => $raw];
}


function g4fr_export_tmp_dir(): string {
    $base = null;
    if (defined('GLPI_TMP_DIR') && is_dir((string)GLPI_TMP_DIR)) {
        $base = rtrim((string)GLPI_TMP_DIR, '/\\');
    } elseif (defined('GLPI_ROOT') && is_dir((string)GLPI_ROOT . '/files/_tmp')) {
        $base = rtrim((string)GLPI_ROOT, '/\\') . '/files/_tmp';
    } else {
        $base = rtrim(sys_get_temp_dir(), '/\\');
    }
    $dir = $base . '/g4freports_exports';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    return $dir;
}

function g4fr_export_cleanup(): void {
    $dir = g4fr_export_tmp_dir();
    $ttl = 1800;
    foreach (glob($dir . '/export_*') ?: [] as $file) {
        if (is_file($file) && (time() - (int)@filemtime($file)) > $ttl) {
            @unlink($file);
        }
    }
}

function g4fr_export_token_path(string $token, string $suffix = '.json'): string {
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        g4fr_json_response(400, ['ok' => false, 'error' => 'Token de exportação inválido.']);
    }
    return g4fr_export_tmp_dir() . '/export_' . $token . $suffix;
}

function g4fr_export_meta(string $token): array {
    $metaPath = g4fr_export_token_path($token, '.meta.json');
    $meta = is_file($metaPath) ? json_decode((string)@file_get_contents($metaPath), true) : null;
    if (!is_array($meta)) {
        g4fr_json_response(404, ['ok' => false, 'error' => 'Rascunho temporário de exportação não encontrado ou expirado.']);
    }
    $uid = g4fr_get_current_uid_glpi();
    if ((int)($meta['uid'] ?? 0) !== $uid) {
        g4fr_json_response(403, ['ok' => false, 'error' => 'Token de exportação pertence a outro usuário/sessão.']);
    }
    return $meta;
}



function g4fr_build_tmp_dir(): string {
    $base = null;
    if (defined('GLPI_TMP_DIR') && is_dir((string)GLPI_TMP_DIR)) {
        $base = rtrim((string)GLPI_TMP_DIR, '/\\');
    } elseif (defined('GLPI_ROOT') && is_dir((string)GLPI_ROOT . '/files/_tmp')) {
        $base = rtrim((string)GLPI_ROOT, '/\\') . '/files/_tmp';
    } else {
        $base = rtrim(sys_get_temp_dir(), '/\\');
    }
    $dir = $base . '/g4freports_builds';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    return $dir;
}

function g4fr_build_cleanup(): void {
    $dir = g4fr_build_tmp_dir();
    $ttl = 1800;
    foreach (glob($dir . '/build_*.json') ?: [] as $file) {
        if (is_file($file) && (time() - (int)@filemtime($file)) > $ttl) {
            @unlink($file);
        }
    }
}

function g4fr_build_token_path(string $token): string {
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        g4fr_json_response(400, ['ok' => false, 'error' => 'Token de construção inválido.']);
    }
    return g4fr_build_tmp_dir() . '/build_' . $token . '.json';
}

function g4fr_build_load(string $token): array {
    $path = g4fr_build_token_path($token);
    $meta = is_file($path) ? json_decode((string)@file_get_contents($path), true) : null;
    if (!is_array($meta)) {
        g4fr_json_response(404, ['ok' => false, 'error' => 'Construção temporária não encontrada ou expirada.']);
    }
    $uid = g4fr_get_current_uid_glpi();
    if ((int)($meta['uid'] ?? 0) !== $uid) {
        g4fr_json_response(403, ['ok' => false, 'error' => 'Token de construção pertence a outro usuário/sessão.']);
    }
    if (time() - (int)($meta['created_at'] ?? 0) > 1800) {
        @unlink($path);
        g4fr_json_response(410, ['ok' => false, 'error' => 'Construção temporária expirada. Gere o rascunho novamente.']);
    }
    return $meta;
}

function g4fr_context_label_api(string $ctx): string {
    $labels = [
        'tickets_assigned_user' => 'Chamados atribuídos ao agente',
        'tickets_requested_by_user' => 'Chamados solicitados pelo agente',
        'tickets_assigned_groups' => 'Chamados atualmente atribuídos aos grupos selecionados',
        'ticket_tasks_by_user' => 'Tarefas feitas pelo agente',
        'ticket_followups_by_user' => 'Acompanhamentos feitos pelo agente',
        'ticket_solutions_by_user' => 'Soluções registradas pelo agente',
        'projects_managed_by_user' => 'Projetos gerenciados pelo agente',
        'projects_managed_by_groups' => 'Projetos dos grupos selecionados',
        'projects_member_user' => 'Projetos com participação do agente',
        'project_tasks_user' => 'Tarefas de projeto do agente',
        'project_tasks_groups' => 'Tarefas de projeto dos grupos selecionados',
    ];
    return $labels[$ctx] ?? $ctx;
}

function g4fr_group_name_from_request(array $req, int $id): string {
    foreach (($req['groups'] ?? []) as $g) {
        if (is_array($g) && (int)($g['id'] ?? 0) === $id) {
            $name = trim((string)($g['name'] ?? ''));
            if ($name !== '') return $name;
        }
    }
    return 'Grupo #' . $id;
}

function g4fr_ticket_date_targets(array $dateContext, bool $highVolumeFast = false): array {
    if ($highVolumeFast) {
        // Exact event-author criteria require per-ticket subitem reads and are
        // not safe for high-volume API runs. Keep searchable Ticket date fields only.
        $dateContext['created_with_agent_action'] = false;
        $dateContext['solved_or_closed_by_agent'] = false;
        if (empty($dateContext['updated_in_period']) && empty($dateContext['solved_or_closed_in_period'])) {
            $dateContext['updated_in_period'] = true;
            $dateContext['solved_or_closed_in_period'] = true;
        }
    }
    $targets = [];
    if (!empty($dateContext['created_with_agent_action'])) {
        $targets[] = ['scope' => 'created_with_agent_action', 'basis' => 'date', 'label' => 'criados no período + ação do agente'];
    }
    if (!empty($dateContext['updated_in_period'])) {
        $targets[] = ['scope' => 'updated_in_period', 'basis' => 'date_mod', 'label' => 'atualizados no período'];
    }
    if (!empty($dateContext['solved_or_closed_in_period'])) {
        $targets[] = ['scope' => 'solved_or_closed_in_period', 'basis' => 'solvedate', 'label' => 'solucionados no período'];
        $targets[] = ['scope' => 'solved_or_closed_in_period', 'basis' => 'closedate', 'label' => 'fechados no período'];
    }
    if (!empty($dateContext['solved_or_closed_by_agent'])) {
        $targets[] = ['scope' => 'solved_or_closed_by_agent', 'basis' => 'solvedate', 'label' => 'solucionados pelo agente'];
        $targets[] = ['scope' => 'solved_or_closed_by_agent', 'basis' => 'closedate', 'label' => 'fechados pelo agente'];
    }
    if (!$targets) {
        $targets[] = ['scope' => 'updated_in_period', 'basis' => 'date_mod', 'label' => 'atualizados no período'];
    }
    $seen = [];
    $out = [];
    foreach ($targets as $t) {
        $key = $t['scope'] . '|' . $t['basis'];
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $out[] = $t;
    }
    return $out;
}


function g4fr_apply_high_volume_plan_api(array $req): array {
    $contexts = $req['contexts'] ?? [];
    if (!is_array($contexts)) { $contexts = preg_split('/[,\s]+/', (string)$contexts) ?: []; }
    $contexts = array_values(array_unique(array_filter(array_map('strval', $contexts))));
    $groups = g4fr_parse_id_list($req['group_ids'] ?? '');
    $dc = is_array($req['data_context'] ?? null) ? $req['data_context'] : [];
    $dateCtx = is_array($req['date_context'] ?? null) ? $req['date_context'] : [];
    $responseMode = (string)($dc['response_time_mode'] ?? 'fast');
    $analytics = $req['analytics'] ?? [];
    if (!is_array($analytics)) { $analytics = preg_split('/[,\s]+/', (string)$analytics) ?: []; }
    $responseAnalytics = (bool)array_intersect($analytics, ['avg_response_time','first_response_time','unanswered_events']);
    $dateTargets = g4fr_ticket_date_targets($dateCtx, false);
    $roughTargets = 0;
    foreach ($contexts as $ctx) {
        if ($ctx === 'tickets_assigned_groups') { $roughTargets += max(1, count($groups)) * max(1, count($dateTargets)); }
        elseif ($ctx === 'tickets_assigned_user' || $ctx === 'tickets_requested_by_user') { $roughTargets += max(1, count($dateTargets)); }
        elseif ($ctx === 'projects_managed_by_groups' || $ctx === 'project_tasks_groups') { $roughTargets += max(1, count($groups)); }
        elseif ($ctx !== 'manual_entries' && $ctx !== '') { $roughTargets += 1; }
    }
    $reasons = [];
    if (count($groups) >= 4) { $reasons[] = count($groups) . ' grupos selecionados'; }
    if ($roughTargets > 36) { $reasons[] = $roughTargets . ' alvos estimados'; }
    if ($responseMode !== 'fast' && $responseAnalytics && count($groups) >= 2) { $reasons[] = 'tempo de resposta preciso em múltiplos grupos'; }
    if (!empty($dc['full_detail_export_enabled']) && (count($groups) >= 2 || $roughTargets > 20)) { $reasons[] = 'detalhamento completo em escopo amplo'; }
    if (count($dateTargets) >= 4 && count($groups) >= 2) { $reasons[] = 'múltiplos critérios temporais em múltiplos grupos'; }
    if (count(array_intersect($contexts, ['ticket_tasks_by_user','ticket_followups_by_user','ticket_solutions_by_user','project_tasks_user','project_tasks_groups','projects_member_user'])) > 0 && count($groups) >= 2) { $reasons[] = 'contextos de eventos/tarefas em escopo amplo'; }
    $warnings = [];
    if ($reasons || !empty($dc['high_volume_fast'])) {
        $warnings[] = 'Plano de segurança para alto volume aplicado no servidor: ' . ($reasons ? implode('; ', $reasons) : 'solicitado pela interface') . '.';
        $warnings[] = 'Modo rápido via API: o rascunho usa apenas campos pesquisáveis de Ticket/Project; critérios que exigem leitura de eventos por chamado e detalhamento completo foram omitidos para evitar 504.';
        $dc['high_volume_fast'] = true;
        $dc['response_time_mode'] = 'fast';
        $dc['full_detail_export_enabled'] = false;
        if (count($groups) > 0) { $dc['manager_mode_enabled'] = true; }
        $keep = ['tickets_assigned_groups' => true, 'projects_managed_by_groups' => true, 'manual_entries' => true];
        if (count($groups) === 0) {
            $keep['tickets_assigned_user'] = true;
            $keep['tickets_requested_by_user'] = true;
            $keep['projects_managed_by_user'] = true;
        }
        $contexts = array_values(array_filter($contexts, static function ($ctx) use ($keep) { return !empty($keep[$ctx]); }));
        if (count($groups) > 0 && !in_array('tickets_assigned_groups', $contexts, true)) { $contexts[] = 'tickets_assigned_groups'; }
        $dateCtx = ['updated_in_period' => true, 'solved_or_closed_in_period' => true];
    }
    $req['contexts'] = $contexts;
    $req['data_context'] = $dc;
    $req['date_context'] = $dateCtx;
    if ($warnings) { $req['_planner_warnings'] = $warnings; }
    return [$req, $warnings];
}

function g4fr_build_targets_from_request(array $req): array {
    $contexts = $req['contexts'] ?? [];
    if (!is_array($contexts)) $contexts = [];
    $dc = is_array($req['data_context'] ?? null) ? $req['data_context'] : [];
    if (!empty($dc['manager_mode_enabled']) && !in_array('tickets_assigned_groups', $contexts, true)) {
        $contexts[] = 'tickets_assigned_groups';
    }
    $groups = g4fr_parse_id_list($req['group_ids'] ?? '');
    $analytics = $req['analytics'] ?? [];
    if (!is_array($analytics)) $analytics = [];
    $responseAnalytics = (bool)array_intersect($analytics, ['avg_response_time','first_response_time','unanswered_events']);
    $responseMode = (string)($dc['response_time_mode'] ?? 'fast');
    $highVolumeFast = !empty($dc['high_volume_fast']);
    $managerLimit = $highVolumeFast ? 80 : ($responseMode === 'fast' ? 120 : ($responseAnalytics ? 20 : 60));
    $dateTargets = g4fr_ticket_date_targets(is_array($req['date_context'] ?? null) ? $req['date_context'] : [], $highVolumeFast);
    $targets = [];
    foreach ($contexts as $ctx) {
        $ctx = (string)$ctx;
        if ($ctx === 'manual_entries' || $ctx === '') continue;
        if ($highVolumeFast && in_array($ctx, ['ticket_tasks_by_user','ticket_followups_by_user','ticket_solutions_by_user','project_tasks_user','project_tasks_groups','projects_member_user'], true)) {
            continue;
        }
        if ($ctx === 'tickets_assigned_groups') {
            foreach ($groups as $gid) {
                foreach ($dateTargets as $dt) {
                    $targets[] = [
                        'context' => $ctx,
                        'target_type' => 'group',
                        'target_id' => $gid,
                        'date_scope' => $dt['scope'],
                        'date_basis' => $dt['basis'],
                        'limit' => $managerLimit,
                        'label' => g4fr_context_label_api($ctx) . ' • ' . g4fr_group_name_from_request($req, $gid) . ' • ' . $dt['label'],
                    ];
                }
            }
            continue;
        }
        if ($ctx === 'projects_managed_by_groups' || $ctx === 'project_tasks_groups') {
            foreach ($groups as $gid) {
                $targets[] = ['context' => $ctx, 'target_type' => 'group', 'target_id' => $gid, 'label' => g4fr_context_label_api($ctx) . ' • ' . g4fr_group_name_from_request($req, $gid)];
            }
            continue;
        }
        if ($ctx === 'tickets_assigned_user' || $ctx === 'tickets_requested_by_user') {
            foreach ($dateTargets as $dt) {
                $targets[] = [
                    'context' => $ctx,
                    'target_type' => 'user',
                    'target_id' => (int)($req['agent_id'] ?? 0),
                    'date_scope' => $dt['scope'],
                    'date_basis' => $dt['basis'],
                    'label' => g4fr_context_label_api($ctx) . ' • ' . $dt['label'],
                ];
            }
            continue;
        }
        $targets[] = ['context' => $ctx, 'target_type' => '', 'target_id' => 0, 'label' => g4fr_context_label_api($ctx)];
    }
    return $targets;
}

try {
    switch ($action) {
        case 'test_connection': {
            $profile = g4fr_api_profile_from_input($input, $cfg);
            if (!$profile) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Perfil de API não encontrado.']);
            }
            $client = new GlpiApiClient($profile);
            try {
                $client->initSession();
                try {
                    $session = $client->request('GET', '/getFullSession');
                } catch (Throwable $e) {
                    $session = ['warning' => $e->getMessage()];
                }
                g4fr_json_response(200, ['ok' => true, 'base_url' => $client->getBaseUrl(), 'session' => $session]);
            } finally {
                $client->killSession();
            }
        }

        case 'test_profiles': {
            if (!Security::canConfigure()) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $profiles = Config::profiles($cfg);
            $out = [];
            foreach ($profiles as $id => $profile) {
                $client = null;
                try {
                    $client = new GlpiApiClient($profile);
                    $client->initSession();
                    $out[] = ['id' => $id, 'name' => $profile['name'] ?? $id, 'ok' => true, 'base_url' => $client->getBaseUrl()];
                } catch (Throwable $e) {
                    $out[] = ['id' => $id, 'name' => $profile['name'] ?? $id, 'ok' => false, 'error' => $e->getMessage()];
                } finally {
                    if ($client instanceof GlpiApiClient) {
                        $client->killSession();
                    }
                }
            }
            g4fr_json_response(200, ['ok' => true, 'results' => $out]);
        }

        case 'search_users': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $q = trim((string)($input['q'] ?? ''));
            if ($q === '') {
                g4fr_json_response(200, ['ok' => true, 'users' => []]);
            }
            $profile = g4fr_api_profile_from_input($input, $cfg);
            if (!$profile) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Perfil de API não encontrado.']);
            }
            $client = new GlpiApiClient($profile);
            try {
                $resolver = new SearchOptionResolver($client, json_decode((string)($cfg['field_overrides_json'] ?? '{}'), true) ?: []);
                $fields = $resolver->fields('User', ['id' => [2], 'name' => [1], 'realname' => [9], 'firstname' => [34], 'login' => [1]]);
                $nameField = $fields['name'] ?: 1;
                $display = array_values(array_filter(array_unique([$fields['id'] ?: 2, $fields['name'] ?: 1, $fields['realname'], $fields['firstname']])));
                $res = $client->search('User', [['field' => $nameField, 'searchtype' => 'contains', 'value' => $q]], $display, '0-20');
                $users = [];
                foreach (($res['data'] ?? []) as $row) {
                    if (!is_array($row)) continue;
                    $id = g4fr_row_id($row, $fields['id'] ?: 2);
                    $name = g4fr_row_value($row, $fields['name'] ?: 1);
                    $real = g4fr_row_value($row, (int)$fields['realname']);
                    $first = g4fr_row_value($row, (int)$fields['firstname']);
                    $full = trim($first . ' ' . $real);
                    if ($id > 0) {
                        $users[] = ['id' => $id, 'name' => $full !== '' ? $full : $name, 'login' => $name, 'raw' => $row];
                    }
                }
                g4fr_json_response(200, ['ok' => true, 'users' => $users]);
            } finally {
                $client->killSession();
            }
        }

        case 'search_groups': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $q = trim((string)($input['q'] ?? ''));
            if ($q === '') {
                g4fr_json_response(200, ['ok' => true, 'groups' => []]);
            }
            $profile = g4fr_api_profile_from_input($input, $cfg);
            if (!$profile) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Perfil de API não encontrado.']);
            }
            $client = new GlpiApiClient($profile);
            try {
                $groups = [];
                if (preg_match('/^\d+$/', $q)) {
                    try {
                        $item = $client->getItem('Group', (int)$q, ['expand_dropdowns' => true, 'get_hateoas' => false]);
                        if (is_array($item) && (int)($item['id'] ?? 0) > 0) {
                            g4fr_add_group_result($groups, (int)$item['id'], (string)(($item['completename'] ?? '') ?: ($item['name'] ?? '')), $item);
                        }
                    } catch (Throwable $e) {
                        // Continue with searchable fields.
                    }
                }

                $resolver = new SearchOptionResolver($client, json_decode((string)($cfg['field_overrides_json'] ?? '{}'), true) ?: []);
                $idField = $resolver->fieldStrict('Group', 'id') ?: 2;
                $nameFields = $resolver->fieldsMatching('Group', [
                    '/\b(completename|complete name|nome completo|full name)\b/',
                    '/\b(group name|nome|name)\b/',
                    '/\bglpi_groups\s+(name|completename)\b/',
                    '/\b(group\.name|group\.completename)\b/'
                ], 8);
                foreach ([1, 80] as $fallback) {
                    if (!in_array($fallback, $nameFields, true)) {
                        $nameFields[] = $fallback;
                    }
                }
                $nameFields = array_values(array_unique(array_filter(array_map('intval', $nameFields))));
                $display = array_values(array_unique(array_filter(array_merge([$idField], $nameFields))));
                $searchErrors = [];
                foreach ($nameFields as $field) {
                    try {
                        $res = $client->search('Group', [['field' => $field, 'searchtype' => 'contains', 'value' => $q]], $display, '0-30');
                        foreach (($res['data'] ?? []) as $row) {
                            if (!is_array($row)) continue;
                            $id = g4fr_row_id($row, $idField);
                            if ($id <= 0) continue;
                            $label = '';
                            foreach ($nameFields as $nf) {
                                $candidate = g4fr_row_value($row, $nf);
                                if (trim($candidate) !== '') {
                                    $label = $candidate;
                                    break;
                                }
                            }
                            g4fr_add_group_result($groups, $id, $label, $row);
                        }
                    } catch (Throwable $e) {
                        $searchErrors[] = 'campo ' . $field . ': ' . $e->getMessage();
                    }
                    if (count($groups) >= 30) {
                        break;
                    }
                }

                if (empty($groups) && !empty($searchErrors)) {
                    g4fr_json_response(200, [
                        'ok' => true,
                        'groups' => [],
                        'warning' => 'Nenhum grupo encontrado por nome. A API não aceitou os campos pesquisáveis testados; use Diagnóstico > Group para mapear o campo correto.'
                    ]);
                }
                g4fr_json_response(200, ['ok' => true, 'groups' => array_values($groups)]);
            } finally {
                $client->killSession();
            }
        }


        case 'search_ticket': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $ticketId = (int)($input['ticket_id'] ?? $input['id'] ?? 0);
            if ($ticketId <= 0) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Informe um número de chamado válido.']);
            }
            $profile = g4fr_api_profile_from_input($input, $cfg);
            if (!$profile) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Perfil de API não encontrado.']);
            }
            $client = new GlpiApiClient($profile);
            try {
                $item = $client->getItem('Ticket', $ticketId, ['expand_dropdowns' => true, 'get_hateoas' => false]);
                if (!is_array($item) || (int)($item['id'] ?? $ticketId) <= 0) {
                    g4fr_json_response(404, ['ok' => false, 'error' => 'Chamado não encontrado.']);
                }
                $status = (string)($item['status'] ?? '');
                $statusMap = [1 => 'Novo', 2 => 'Em atendimento (atribuído)', 3 => 'Em atendimento (planejado)', 4 => 'Pendente', 5 => 'Solucionado', 6 => 'Fechado'];
                if (is_numeric($status) && isset($statusMap[(int)$status])) {
                    $status = $statusMap[(int)$status];
                }
                $category = '';
                if (isset($item['itilcategories_id'])) {
                    if (is_array($item['itilcategories_id'])) {
                        $category = (string)(($item['itilcategories_id']['completename'] ?? '') ?: ($item['itilcategories_id']['name'] ?? ''));
                    } elseif (!is_numeric((string)$item['itilcategories_id'])) {
                        $category = (string)$item['itilcategories_id'];
                    }
                }
                g4fr_json_response(200, ['ok' => true, 'ticket' => [
                    'id' => (int)($item['id'] ?? $ticketId),
                    'title' => (string)($item['name'] ?? ('Chamado ' . $ticketId)),
                    'status' => $status,
                    'category' => $category,
                    'date' => (string)($item['date'] ?? ''),
                    'raw' => $item,
                ]]);
            } finally {
                $client->killSession();
            }
        }

        case 'search_options': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $itemtype = trim((string)($input['itemtype'] ?? 'Ticket'));
            $profile = g4fr_api_profile_from_input($input, $cfg);
            if (!$profile) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Perfil de API não encontrado.']);
            }
            $client = new GlpiApiClient($profile);
            try {
                $options = $client->listSearchOptions($itemtype);
                g4fr_json_response(200, ['ok' => true, 'itemtype' => $itemtype, 'options' => $options]);
            } finally {
                $client->killSession();
            }
        }

        case 'prepare_export': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            g4fr_export_cleanup();
            $op = trim((string)($input['op'] ?? ''));
            $uid = g4fr_get_current_uid_glpi();
            if ($uid <= 0) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Sessão GLPI inválida para preparar exportação.']);
            }
            if ($op === 'start') {
                $token = bin2hex(random_bytes(16));
                $meta = [
                    'uid' => $uid,
                    'created_at' => time(),
                    'total_chunks' => max(1, (int)($input['total_chunks'] ?? 1)),
                    'received_chunks' => 0,
                    'bytes' => 0,
                ];
                @file_put_contents(g4fr_export_token_path($token, '.meta.json'), json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                @file_put_contents(g4fr_export_token_path($token, '.part'), '');
                g4fr_json_response(200, ['ok' => true, 'token' => $token]);
            }
            $token = trim((string)($input['token'] ?? ''));
            $meta = g4fr_export_meta($token);
            if ($op === 'chunk') {
                $chunk = (string)($input['chunk'] ?? '');
                if ($chunk === '' && (int)($input['total_chunks'] ?? 1) > 1) {
                    g4fr_json_response(400, ['ok' => false, 'error' => 'Lote de exportação vazio.']);
                }
                $partPath = g4fr_export_token_path($token, '.part');
                @file_put_contents($partPath, $chunk, FILE_APPEND | LOCK_EX);
                $meta['received_chunks'] = (int)($meta['received_chunks'] ?? 0) + 1;
                $meta['bytes'] = (int)($meta['bytes'] ?? 0) + strlen($chunk);
                @file_put_contents(g4fr_export_token_path($token, '.meta.json'), json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
                g4fr_json_response(200, ['ok' => true, 'received_chunks' => $meta['received_chunks'], 'bytes' => $meta['bytes']]);
            }
            if ($op === 'finish') {
                $partPath = g4fr_export_token_path($token, '.part');
                if (!is_file($partPath)) {
                    g4fr_json_response(404, ['ok' => false, 'error' => 'Arquivo temporário de exportação não encontrado.']);
                }
                $jsonPath = g4fr_export_token_path($token, '.json');
                @rename($partPath, $jsonPath);
                if (!is_file($jsonPath)) {
                    g4fr_json_response(500, ['ok' => false, 'error' => 'Falha ao finalizar rascunho temporário de exportação.']);
                }
                $meta['finished_at'] = time();
                @file_put_contents(g4fr_export_token_path($token, '.meta.json'), json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
                g4fr_json_response(200, ['ok' => true, 'token' => $token, 'bytes' => filesize($jsonPath)]);
            }
            g4fr_json_response(400, ['ok' => false, 'error' => 'Operação de exportação inválida.']);
        }

        case 'build_start': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            g4fr_build_cleanup();
            $uid = g4fr_get_current_uid_glpi();
            if ($uid <= 0) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Sessão GLPI inválida para iniciar construção.']);
            }
            $request = $input['request'] ?? $input;
            if (!is_array($request)) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Requisição de construção inválida.']);
            }
            [$request, $planWarnings] = g4fr_apply_high_volume_plan_api($request);
            $targets = g4fr_build_targets_from_request($request);
            $token = bin2hex(random_bytes(16));
            $meta = [
                'uid' => $uid,
                'created_at' => time(),
                'request' => $request,
                'targets' => $targets,
                'plan_warnings' => $planWarnings,
            ];
            @file_put_contents(g4fr_build_token_path($token), json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
            g4fr_json_response(200, ['ok' => true, 'token' => $token, 'total_targets' => count($targets), 'plan_warnings' => $planWarnings, 'plan_applied' => !empty($planWarnings)]);
        }

        case 'build_step': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $token = trim((string)($input['token'] ?? ''));
            $meta = g4fr_build_load($token);
            $targets = is_array($meta['targets'] ?? null) ? $meta['targets'] : [];
            $index = (int)($input['target_index'] ?? 0);
            if (!isset($targets[$index]) || !is_array($targets[$index])) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Alvo de construção inválido.']);
            }
            $target = $targets[$index];
            $request = is_array($meta['request'] ?? null) ? $meta['request'] : [];
            $payload = $request;
            $payload['contexts'] = [(string)($target['context'] ?? '')];
            $payload['batch_context'] = (string)($target['context'] ?? '');
            $payload['batch_target_type'] = (string)($target['target_type'] ?? '');
            $payload['batch_target_id'] = (int)($target['target_id'] ?? 0);
            $payload['batch_date_scope'] = (string)($target['date_scope'] ?? '');
            $payload['batch_date_basis'] = (string)($target['date_basis'] ?? '');
            $payload['batch_offset'] = max(0, (int)($input['offset'] ?? 0));
            $payload['batch_limit'] = max(1, (int)($target['limit'] ?? 80));
            $profile = g4fr_api_profile_from_input($payload, $cfg);
            if (!$profile) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Perfil de API não encontrado.']);
            }
            if (!empty($payload['data_context']['high_volume_fast'])) {
                // Fail faster than nginx/PHP gateway timeout so the UI can retry/adapt
                // instead of receiving raw 504 HTML. The browser will continue with
                // small GET build steps.
                $profile['timeout_ms'] = min((int)($profile['timeout_ms'] ?? 60000), 12000);
                $profile['connect_timeout_ms'] = min((int)($profile['connect_timeout_ms'] ?? 10000), 5000);
            }
            $builder = new ReportDraftBuilder($cfg, $profile);
            try {
                $draft = $builder->build($payload);
                $planWarnings = is_array($meta['plan_warnings'] ?? null) ? $meta['plan_warnings'] : [];
                if ($planWarnings) {
                    $draft['warnings'] = array_values(array_unique(array_merge($draft['warnings'] ?? [], $planWarnings)));
                    if (!isset($draft['coverage']) || !is_array($draft['coverage'])) { $draft['coverage'] = []; }
                    if (!isset($draft['coverage']['limitations']) || !is_array($draft['coverage']['limitations'])) { $draft['coverage']['limitations'] = []; }
                    $draft['coverage']['limitations'] = array_values(array_unique(array_merge($draft['coverage']['limitations'], $planWarnings)));
                }
                g4fr_json_response(200, ['ok' => true, 'draft' => $draft, 'target_label' => (string)($target['label'] ?? $target['context'] ?? ''), 'target_limit' => $payload['batch_limit']]);
            } finally {
                if (method_exists($builder, 'close')) {
                    $builder->close();
                }
            }
        }

        case 'build_finish': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $token = trim((string)($input['token'] ?? ''));
            $meta = g4fr_build_load($token);
            @unlink(g4fr_build_token_path($token));
            g4fr_json_response(200, ['ok' => true]);
        }

        case 'build_draft': {
            if (!Security::canUse($cfg)) {
                g4fr_json_response(403, ['ok' => false, 'error' => 'Acesso negado']);
            }
            $profile = g4fr_api_profile_from_input($input, $cfg);
            if (!$profile) {
                g4fr_json_response(400, ['ok' => false, 'error' => 'Perfil de API não encontrado.']);
            }
            $builder = new ReportDraftBuilder($cfg, $profile);
            try {
                $draft = $builder->build($input);
                g4fr_json_response(200, ['ok' => true, 'draft' => $draft]);
            } finally {
                if (method_exists($builder, 'close')) {
                    $builder->close();
                }
            }
        }

        default:
            g4fr_json_response(404, ['ok' => false, 'error' => 'acao_desconhecida: ' . $action]);
    }
} catch (Throwable $e) {
    $trace = g4fr_str_limit($e->getTraceAsString(), 3000);
    g4fr_json_response(500, ['ok' => false, 'error' => $e->getMessage(), 'trace' => $trace]);
}
