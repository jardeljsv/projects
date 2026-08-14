<?php

namespace GlpiPlugin\G4freports\Api;

class SearchOptionResolver
{
    private GlpiApiClient $client;
    private array $cache = [];
    private array $overrides = [];

    public function __construct(GlpiApiClient $client, array $overrides = [])
    {
        $this->client = $client;
        $this->overrides = $overrides;
    }

    public function getOptions(string $itemtype): array
    {
        if (!array_key_exists($itemtype, $this->cache)) {
            try {
                $opts = $this->client->listSearchOptions($itemtype);
                $this->cache[$itemtype] = is_array($opts) ? $opts : [];
            } catch (\Throwable $e) {
                $this->cache[$itemtype] = [];
            }
        }
        return $this->cache[$itemtype];
    }

    public function field(string $itemtype, string $logical, array $fallbackIds = []): int
    {
        $strict = $this->fieldStrict($itemtype, $logical);
        if ($strict > 0) {
            return $strict;
        }

        foreach ($fallbackIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                return $id;
            }
        }
        return 0;
    }

    /**
     * Resolve a search option only when the instance explicitly exposes a
     * matching option or the admin configured an override. This is used for
     * risky item types such as ProjectTeam, where guessing fallback IDs may
     * accidentally trigger very broad API searches.
     */
    public function fieldStrict(string $itemtype, string $logical): int
    {
        if (isset($this->overrides[$itemtype][$logical]) && (int)$this->overrides[$itemtype][$logical] > 0) {
            return (int)$this->overrides[$itemtype][$logical];
        }

        $patterns = $this->patterns($itemtype, $logical);
        if (empty($patterns)) {
            return 0;
        }
        $opts = $this->getOptions($itemtype);
        foreach ($opts as $id => $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $hay = strtolower(trim(implode(' ', array_filter([
                (string)($opt['name'] ?? ''),
                (string)($opt['table'] ?? ''),
                (string)($opt['field'] ?? ''),
                (string)($opt['uid'] ?? '')
            ]))));
            foreach ($patterns as $pattern) {
                if ($pattern !== '' && preg_match($pattern, $hay)) {
                    return (int)$id;
                }
            }
        }
        return 0;
    }


    public function fieldsMatching(string $itemtype, array $patterns, int $max = 8): array
    {
        $out = [];
        $opts = $this->getOptions($itemtype);
        foreach ($opts as $id => $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $hay = strtolower(trim(implode(' ', array_filter([
                (string)($opt['name'] ?? ''),
                (string)($opt['table'] ?? ''),
                (string)($opt['field'] ?? ''),
                (string)($opt['uid'] ?? '')
            ]))));
            foreach ($patterns as $pattern) {
                if ($pattern !== '' && @preg_match($pattern, $hay)) {
                    $fieldId = (int)$id;
                    if ($fieldId > 0 && !in_array($fieldId, $out, true)) {
                        $out[] = $fieldId;
                    }
                    break;
                }
            }
            if ($max > 0 && count($out) >= $max) {
                break;
            }
        }
        return $out;
    }

    public function fields(string $itemtype, array $logicalToFallbacks): array
    {
        $out = [];
        foreach ($logicalToFallbacks as $logical => $fallbacks) {
            $out[$logical] = $this->field($itemtype, $logical, is_array($fallbacks) ? $fallbacks : [(int)$fallbacks]);
        }
        return $out;
    }

    private function patterns(string $itemtype, string $logical): array
    {
        $common = [
            'id'           => ['/(^|\s)(id|identificador)(\s|$)/', '/\bid\b/'],
            'name'         => ['/\b(nome|name|titulo|title)\b/'],
            'status'       => ['/\b(status|estado)\b/'],
            'date'         => ['/\b(data de abertura|opening date|date|abertura)\b/'],
            'date_mod'     => ['/\b(ultima atualizacao|última atualização|last update|date_mod|modified|modification)\b/'],
            'closedate'    => ['/\b(data de fechamento|closing date|closedate|closed)\b/'],
            'solvedate'    => ['/\b(data de solução|solution date|solvedate|solved)\b/'],
            'content'      => ['/\b(descricao|descrição|content|description)\b/'],
            'users_id'     => ['/\b(usuario|usuário|user|author|technician|tecnico|técnico)\b/'],
            'groups_id'    => ['/\b(grupo|group)\b/'],
            'priority'     => ['/\b(prioridade|priority)\b/'],
            'locations_id' => ['/\b(localizacao|localização|location|locations_id|local)\b/'],
            'percent_done' => ['/\b(percentual|percent|done|finalizado|completion)\b/'],
            'code'         => ['/\b(codigo|código|code)\b/'],
            'tickets_id'   => ['/\b(tickets?_id|chamado|ticket)\b/'],
            'projects_id'  => ['/\b(projects?_id|projeto|project)\b/'],
            'items_id'     => ['/\b(items?_id|id do item|item id|item)\b/'],
            'itemtype'     => ['/\b(itemtype|tipo de item|item type|tipo)\b/']
        ];

        $ticket = [
            'users_id_assign'  => ['/\b(tecnico|técnico|assigned.*user|user.*assigned|atribuido.*usuario|usuário atribuído|responsavel|responsável)\b/'],
            'groups_id_assign' => ['/\b(grupo.*atribuido|grupo.*t[eé]cnico|assigned.*group|group.*assigned|grupo gerente|grupo respons[aá]vel)\b/'],
            'users_id_recipient' => ['/\b(requerente|requester|recipient|solicitante)\b/'],
            'itilcategories_id' => ['/\b(categoria|category|itilcategories)\b/']
        ];

        $project = [
            'manager_user'  => ['/\b(gerente.*usuario|manager.*user|usu[aá]rio.*gerente|respons[aá]vel)\b/'],
            'manager_group' => ['/\b(gerente.*grupo|manager.*group|grupo gerente|grupo respons[aá]vel)\b/'],
            'planned_start' => ['/\b(data planejada.*come|planned.*start|planned.*begin)\b/'],
            'planned_end'   => ['/\b(data planejada.*fim|planned.*end)\b/']
        ];

        $map = array_merge($common, $itemtype === 'Ticket' ? $ticket : [], in_array($itemtype, ['Project', 'ProjectTask'], true) ? $project : []);
        return $map[$logical] ?? [];
    }
}
