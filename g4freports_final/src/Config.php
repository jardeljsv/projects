<?php

namespace GlpiPlugin\G4freports;

use CommonGLPI;
use Session;
use Glpi\Application\View\TemplateRenderer;

class Config extends \Config
{
    public static function getTypeName($nb = 0)
    {
        return __('Relatórios G4F', 'g4freports');
    }

    public static function getConfig(): array
    {
        $cfg = \Config::getConfigurationValues('plugin:g4freports');
        return is_array($cfg) ? $cfg : [];
    }

    public static function getDefaultConfig(): array
    {
        return [
            'enabled'                => 1,
            'default_profile_name'   => 'GLPI padrão',
            'api_base_url'           => '',
            'api_app_token'          => '',
            'api_user_token'         => '',
            'api_session_token'      => '',
            'api_app_token_env'      => '',
            'api_user_token_env'     => '',
            'api_session_token_env'  => '',
            'api_auth_mode'          => 'user_token',
            'api_verify_tls'         => 1,
            'default_entity_id'      => 0,
            'recursive_entities'     => 1,
            'allowed_groups'         => '',
            'allowed_profiles'       => '',
            'api_profiles_json'      => '',
            'group_routes_json'      => '',
            'max_items'              => 200,
            'max_candidates'         => 60,
            'subitem_limit'          => 50,
            'max_auto_groups'        => 5,
            'max_runtime_seconds'    => 22,
            'extract_request_timeout_ms' => 12000,
            'parallel_discovery'      => 3,
            'parallel_enrichment'     => 2,
            'response_time_mode'      => 'fast',
            'request_timeout_ms'     => 60000,
            'mask_sensitive_data'    => 1,
            'field_overrides_json'   => '{}',
            'default_footer'         => 'Brasília - DF, 70712-900 | SCN Q 2 BL A - Asa Norte, Corporate Financial Center | contato@g4f.com.br | www.g4f.com.br',
            'contract_label'         => 'SES-DF',
            'default_report_title'   => 'Relatório Gerencial de Atividades',
            'default_scope_text'     => 'Este relatório apresenta as atividades executadas no período selecionado, consolidando registros obtidos via GLPI e complementações narrativas inseridas pelo profissional responsável.',
            'default_objective_text' => 'Formalizar, padronizar e evidenciar as ações técnicas executadas, mantendo rastreabilidade por chamados, projetos, tarefas, acompanhamentos e registros manuais.'
        ];
    }

    public static function merged(): array
    {
        $raw = self::getConfig();
        $cfg = array_replace(self::getDefaultConfig(), $raw);

        // Backward compatibility with early MVP builds that used app_token,
        // user_token, session_token and verify_tls names directly.
        if (trim((string)($cfg['api_app_token'] ?? '')) === '' && isset($raw['app_token'])) {
            $cfg['api_app_token'] = (string)$raw['app_token'];
        }
        if (trim((string)($cfg['api_user_token'] ?? '')) === '' && isset($raw['user_token'])) {
            $cfg['api_user_token'] = (string)$raw['user_token'];
        }
        if (trim((string)($cfg['api_session_token'] ?? '')) === '' && isset($raw['session_token'])) {
            $cfg['api_session_token'] = (string)$raw['session_token'];
        }
        if (!isset($raw['api_verify_tls']) && isset($raw['verify_tls'])) {
            $cfg['api_verify_tls'] = !empty($raw['verify_tls']) ? 1 : 0;
        }

        // Backward compatibility with v0.1.x where the default profile lived only
        // inside api_profiles_json.
        if (trim((string)($cfg['api_base_url'] ?? '')) === '') {
            $legacyProfiles = self::parseAdditionalProfiles($cfg['api_profiles_json'] ?? '');
            if (!empty($legacyProfiles)) {
                $first = reset($legacyProfiles);
                if (is_array($first)) {
                    $cfg['default_profile_name'] = (string)($first['name'] ?? $cfg['default_profile_name']);
                    $cfg['api_base_url'] = (string)($first['base_url'] ?? ($first['api_base_url'] ?? ''));
                    $cfg['api_app_token'] = (string)($first['app_token'] ?? '');
                    $cfg['api_user_token'] = (string)($first['user_token'] ?? '');
                    $cfg['api_session_token'] = (string)($first['session_token'] ?? '');
                    $cfg['api_verify_tls'] = !empty($first['verify_tls']) ? 1 : 0;
                    $cfg['default_entity_id'] = (int)($first['default_entity_id'] ?? 0);
                    $cfg['recursive_entities'] = !empty($first['recursive_entities']) ? 1 : 0;
                }
            }
        }

        return $cfg;
    }

    public static function save(array $values): void
    {
        \Config::setConfigurationValues('plugin:g4freports', $values);
    }

    public static function defaultProfile(array $cfg = null): array
    {
        $cfg = $cfg ?? self::merged();
        return self::normalizeProfile([
            'id'                => '__default__',
            'name'              => $cfg['default_profile_name'] ?? 'GLPI padrão',
            'base_url'          => $cfg['api_base_url'] ?? '',
            'app_token'         => $cfg['api_app_token'] ?? '',
            'user_token'        => $cfg['api_user_token'] ?? '',
            'session_token'     => $cfg['api_session_token'] ?? '',
            'app_token_env'     => $cfg['api_app_token_env'] ?? '',
            'user_token_env'    => $cfg['api_user_token_env'] ?? '',
            'session_token_env' => $cfg['api_session_token_env'] ?? '',
            'auth_mode'         => $cfg['api_auth_mode'] ?? 'user_token',
            'verify_tls'        => (int)($cfg['api_verify_tls'] ?? 1) === 1,
            'timeout_ms'        => (int)($cfg['request_timeout_ms'] ?? 60000),
            'default_entity_id' => (int)($cfg['default_entity_id'] ?? 0),
            'recursive_entities'=> (int)($cfg['recursive_entities'] ?? 1) === 1,
            'enabled'           => true,
            'is_default'        => true,
        ], '__default__');
    }

    public static function parseAdditionalProfiles($raw): array
    {
        $raw = is_string($raw) ? trim($raw) : '';
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $idx => $profile) {
            if (!is_array($profile)) {
                continue;
            }
            $id = trim((string)($profile['id'] ?? ''));
            if ($id === 'default') {
                $id = '__default__';
            }
            if ($id === '') {
                $id = 'profile_' . ($idx + 1);
            }
            $out[$id] = self::normalizeProfile($profile, $id);
        }
        return $out;
    }

    public static function normalizeProfile(array $profile, string $fallbackId): array
    {
        $id = trim((string)($profile['id'] ?? $fallbackId));
        if ($id === '') {
            $id = $fallbackId;
        }
        $base = trim((string)($profile['base_url'] ?? ($profile['api_base_url'] ?? '')));
        $authMode = trim((string)($profile['auth_mode'] ?? 'user_token'));
        if (!in_array($authMode, ['user_token', 'session_token', 'env'], true)) {
            $authMode = 'user_token';
        }

        return [
            'id'                => $id,
            'name'              => trim((string)($profile['name'] ?? $id)),
            'base_url'          => $base,
            'app_token'         => trim((string)($profile['app_token'] ?? '')),
            'user_token'        => trim((string)($profile['user_token'] ?? '')),
            'session_token'     => trim((string)($profile['session_token'] ?? '')),
            'app_token_env'     => trim((string)($profile['app_token_env'] ?? '')),
            'user_token_env'    => trim((string)($profile['user_token_env'] ?? '')),
            'session_token_env' => trim((string)($profile['session_token_env'] ?? '')),
            'auth_mode'         => $authMode,
            'verify_tls'        => self::bool($profile['verify_tls'] ?? true, true),
            'timeout_ms'        => max(1000, min(600000, (int)($profile['timeout_ms'] ?? ($profile['request_timeout_ms'] ?? 60000)))),
            'default_entity_id' => max(0, (int)($profile['default_entity_id'] ?? 0)),
            'recursive_entities'=> self::bool($profile['recursive_entities'] ?? true, true),
            'enabled'           => self::bool($profile['enabled'] ?? true, true),
            'is_default'        => !empty($profile['is_default']) || $id === '__default__',
        ];
    }

    public static function profiles(array $cfg = null): array
    {
        $cfg = $cfg ?? self::merged();
        $profiles = ['__default__' => self::defaultProfile($cfg)];
        foreach (self::parseAdditionalProfiles($cfg['api_profiles_json'] ?? '') as $id => $profile) {
            if ($id === '__default__') {
                continue;
            }
            $profiles[$id] = $profile;
        }
        return $profiles;
    }

    public static function getProfile(string $id, array $cfg = null): ?array
    {
        $profiles = self::profiles($cfg);
        if (isset($profiles[$id])) {
            return $profiles[$id];
        }
        return $profiles['__default__'] ?? null;
    }

    public static function parseGroupRoutes($raw): array
    {
        $raw = is_string($raw) ? trim($raw) : '';
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $gid = (int)($row['group_id'] ?? ($row['groups_id'] ?? 0));
            if ($gid <= 0) {
                continue;
            }
            $out[] = [
                'id' => trim((string)($row['id'] ?? ('route_' . $gid . '_' . $idx))),
                'group_id' => $gid,
                'profile_id' => trim((string)($row['profile_id'] ?? '__default__')) ?: '__default__',
                'priority' => max(0, min(999999, (int)($row['priority'] ?? 100))),
                'include_children' => self::bool($row['include_children'] ?? true, true),
                'enabled' => self::bool($row['enabled'] ?? true, true),
            ];
        }
        return $out;
    }

    public static function enabledRoutes(array $cfg = null): array
    {
        $cfg = $cfg ?? self::merged();
        return array_values(array_filter(self::parseGroupRoutes($cfg['group_routes_json'] ?? ''), static fn($r) => !empty($r['enabled'])));
    }

    public static function hasRouteMode(array $cfg = null): bool
    {
        return !empty(self::enabledRoutes($cfg));
    }

    public static function matchRoute(array $cfg, array $groupIds): ?array
    {
        $routes = self::enabledRoutes($cfg);
        if (empty($routes)) {
            return null;
        }
        $distances = function_exists('g4fr_expand_group_ancestors_glpi') ? g4fr_expand_group_ancestors_glpi($groupIds) : [];
        $best = null;
        foreach ($routes as $route) {
            $gid = (int)$route['group_id'];
            if (!isset($distances[$gid])) {
                continue;
            }
            $distance = (int)$distances[$gid];
            if (empty($route['include_children']) && $distance > 0) {
                continue;
            }
            $candidate = ['route' => $route, 'distance' => $distance];
            if ($best === null) {
                $best = $candidate;
                continue;
            }
            $cmp = ((int)$candidate['route']['priority']) <=> ((int)$best['route']['priority']);
            if ($cmp === 0) {
                $cmp = ((int)$candidate['distance']) <=> ((int)$best['distance']);
            }
            if ($cmp < 0) {
                $best = $candidate;
            }
        }
        return $best;
    }

    public static function profilesForCurrentUser(array $cfg = null): array
    {
        $cfg = $cfg ?? self::merged();
        $profiles = self::profiles($cfg);
        if (Security::canConfigure()) {
            return $profiles;
        }
        if (self::hasRouteMode($cfg)) {
            $route = self::matchRoute($cfg, Security::currentGroupIds());
            if (is_array($route)) {
                $pid = (string)($route['route']['profile_id'] ?? '__default__');
                if (isset($profiles[$pid]) && !empty($profiles[$pid]['enabled'])) {
                    return [$pid => $profiles[$pid]];
                }
            }
            return [];
        }
        return $profiles;
    }

    public static function getAllowedProfileForCurrentUser(string $id, array $cfg = null): ?array
    {
        $profiles = self::profilesForCurrentUser($cfg);
        if (isset($profiles[$id])) {
            return $profiles[$id];
        }
        if (isset($profiles['__default__'])) {
            return $profiles['__default__'];
        }
        return !empty($profiles) ? reset($profiles) : null;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case \Config::class:
                return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case \Config::class:
                return self::showForConfig($item, $withtemplate);
        }
        return true;
    }

    public static function showForConfig(\Config $config, $withtemplate = 0)
    {
        if (!self::canView()) {
            return false;
        }
        self::renderConfigForm();
        return true;
    }

    public static function renderConfigForm(bool $standalone = false, string $notice = '', string $error = ''): void
    {
        $cfg = self::merged();
        $canedit = Session::haveRight(self::$rightname, UPDATE);
        $groups = self::fetchGroupsList();
        $profiles = self::profiles($cfg);
        $rootDoc = (string)($GLOBALS['CFG_GLPI']['root_doc'] ?? '');
        $rawRoutes = self::parseGroupRoutes($cfg['group_routes_json'] ?? '');

        TemplateRenderer::getInstance()->display('@g4freports/config.html.twig', [
            'current_config' => $cfg,
            'profiles'       => $profiles,
            'local_profiles' => self::fetchProfilesList(),
            'additional_profiles_json' => json_encode(array_values(array_filter(self::parseAdditionalProfiles($cfg['api_profiles_json'] ?? ''), static fn($p) => empty($p['is_default']) && ($p['id'] ?? '') !== '__default__')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'group_routes_json' => json_encode($rawRoutes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'groups'         => $groups,
            'can_edit'       => $canedit,
            'standalone'     => $standalone,
            'notice'         => $notice,
            'error'          => $error,
            'diagnostics_url' => rtrim($rootDoc, '/') . '/plugins/g4freports/front/diagnostics.php',
            'plugin_key'     => 'g4freports'
        ]);
    }

    private static function fetchProfilesList(): array
    {
        $profiles = [];
        global $DB;
        if (!isset($DB) || !is_object($DB)) {
            return $profiles;
        }

        try {
            if (method_exists($DB, 'request')) {
                $it = $DB->request([
                    'SELECT' => ['id', 'name'],
                    'FROM'   => 'glpi_profiles',
                    'ORDER'  => ['name'],
                ]);
                foreach ($it as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $id = (int)($row['id'] ?? 0);
                    $name = (string)($row['name'] ?? '');
                    if ($id > 0) {
                        $profiles[] = ['id' => $id, 'name' => $name];
                    }
                }
                return $profiles;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        try {
            if (method_exists($DB, 'query')) {
                $res = @$DB->query('SELECT `id`, `name` FROM `glpi_profiles` ORDER BY `name` ASC');
                if ($res && method_exists($DB, 'fetchAssoc')) {
                    while ($row = $DB->fetchAssoc($res)) {
                        $id = (int)($row['id'] ?? 0);
                        if ($id > 0) {
                            $profiles[] = ['id' => $id, 'name' => (string)($row['name'] ?? '')];
                        }
                    }
                } elseif ($res && method_exists($DB, 'fetch_assoc')) {
                    while ($row = $DB->fetch_assoc($res)) {
                        $id = (int)($row['id'] ?? 0);
                        if ($id > 0) {
                            $profiles[] = ['id' => $id, 'name' => (string)($row['name'] ?? '')];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $profiles;
    }

    private static function fetchGroupsList(): array
    {
        $groups = [];
        global $DB;
        if (!isset($DB) || !is_object($DB)) {
            return $groups;
        }
        try {
            if (method_exists($DB, 'request')) {
                $it = $DB->request([
                    'SELECT' => ['id', 'name'],
                    'FROM'   => 'glpi_groups',
                    'ORDER'  => ['name'],
                ]);
                foreach ($it as $row) {
                    if (!is_array($row)) continue;
                    $id = (int)($row['id'] ?? 0);
                    $name = (string)($row['name'] ?? '');
                    if ($id > 0) $groups[] = ['id' => $id, 'name' => $name];
                }
                return $groups;
            }
        } catch (\Throwable $e) {}
        try {
            if (method_exists($DB, 'query')) {
                $res = @$DB->query('SELECT `id`, `name` FROM `glpi_groups` ORDER BY `name` ASC');
                if ($res && method_exists($DB, 'fetchAssoc')) {
                    while ($row = $DB->fetchAssoc($res)) {
                        $id = (int)($row['id'] ?? 0);
                        if ($id > 0) $groups[] = ['id' => $id, 'name' => (string)($row['name'] ?? '')];
                    }
                }
            }
        } catch (\Throwable $e) {}
        return $groups;
    }

    private static function bool($value, bool $default = false): bool
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return ((int)$value) !== 0;
        if (is_string($value)) {
            $v = strtolower(trim($value));
            if (in_array($v, ['1','true','yes','y','on','sim'], true)) return true;
            if (in_array($v, ['0','false','no','n','off','nao','não'], true)) return false;
        }
        return $default;
    }
}
