<?php
// Common helpers for Relatórios G4F.
// Mirrors the GLPIBot pattern: no separate database credentials and no public
// static-asset dependency. Front endpoints bootstrap GLPI through inc/includes.php.

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(404);
    exit;
}


if (!defined('PLUGIN_G4FREPORTS_VERSION')) {
    define('PLUGIN_G4FREPORTS_VERSION', '1.6.0');
}
if (!defined('PLUGIN_G4FREPORTS_MIN_GLPI')) {
    define('PLUGIN_G4FREPORTS_MIN_GLPI', '10.0.0');
}

if (!function_exists('g4fr_json_response')) {
    function g4fr_json_response(int $status, array $payload): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Vary: Cookie');
        }
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('g4fr_files_log_path')) {
    function g4fr_files_log_path(?string $glpiRoot): string {
        if ($glpiRoot) {
            $candidate = rtrim($glpiRoot, '/\\') . '/files/_log/g4freports-plugin.log';
            $dir = dirname($candidate);
            if (is_dir($dir) && is_writable($dir)) {
                return $candidate;
            }
        }
        return '/tmp/g4freports-plugin.log';
    }
}

if (!function_exists('g4fr_log')) {
    function g4fr_log(?string $glpiRoot, string $message): void {
        @file_put_contents(g4fr_files_log_path($glpiRoot), '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", FILE_APPEND);
    }
}

if (!function_exists('g4fr_find_glpi_root')) {
    function g4fr_find_glpi_root(): ?string {
        $candidates = [];

        $envRoot = getenv('GLPI_ROOT');
        if (is_string($envRoot) && trim($envRoot) !== '') {
            $candidates[] = trim($envRoot);
        }

        // Normal plugin layout:
        //   <glpi>/plugins/g4freports/inc/bootstrap.php
        // Public-root layout:
        //   <glpi>/public/plugins/g4freports/inc/bootstrap.php
        $candidates[] = dirname(__DIR__, 4);
        $candidates[] = dirname(__DIR__, 3);
        $candidates[] = dirname(__DIR__, 2);
        $candidates[] = dirname(__DIR__, 1);

        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (is_string($docRoot) && trim($docRoot) !== '') {
            $docRoot = rtrim(trim($docRoot), '/\\');
            $candidates[] = $docRoot;
            $candidates[] = dirname($docRoot);
            $candidates[] = $docRoot . '/glpi';
        }

        $seen = [];
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }
            $real = realpath($candidate);
            if ($real === false) {
                $real = rtrim($candidate, '/\\');
            }
            if (isset($seen[$real])) {
                continue;
            }
            $seen[$real] = true;
            if (is_dir($real) && is_file($real . '/inc/includes.php')) {
                return $real;
            }
        }
        return null;
    }
}

if (!function_exists('g4fr_bootstrap_glpi_runtime')) {
    function g4fr_bootstrap_glpi_runtime(?string &$glpiRootOut = null): bool {
        if (class_exists('Config') && isset($GLOBALS['DB']) && is_object($GLOBALS['DB'])) {
            $glpiRootOut = defined('GLPI_ROOT') ? GLPI_ROOT : null;
            return true;
        }

        $glpiRoot = g4fr_find_glpi_root();
        $glpiRootOut = $glpiRoot;
        if ($glpiRoot === null) {
            g4fr_log(null, 'GLPI_ROOT_NOT_FOUND');
            return false;
        }

        $includes = rtrim($glpiRoot, '/\\') . '/inc/includes.php';
        if (!is_file($includes)) {
            g4fr_log($glpiRoot, 'GLPI_INCLUDES_NOT_FOUND ' . $includes);
            return false;
        }

        try {
            require_once $includes;
        } catch (Throwable $e) {
            g4fr_log($glpiRoot, 'GLPI_BOOTSTRAP_ERROR ' . $e->getMessage());
            return false;
        }

        if (!isset($GLOBALS['DB']) || !is_object($GLOBALS['DB'])) {
            g4fr_log($glpiRoot, 'GLPI_DB_OBJECT_UNAVAILABLE_AFTER_BOOTSTRAP');
            return false;
        }

        return true;
    }
}

if (!function_exists('g4fr_parse_request_payload')) {
    function g4fr_parse_request_payload(): array {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'GET') {
            $payload = (string)($_GET['payload'] ?? ($_GET['payload_json'] ?? ''));
            if ($payload !== '') {
                $decoded = json_decode($payload, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                g4fr_json_response(400, ['ok' => false, 'error' => 'payload_json_invalido: ' . json_last_error_msg()]);
            }
            return is_array($_GET) ? $_GET : [];
        }

        if ($method === 'POST') {
            $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
            if (strpos($ct, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                $decoded = json_decode((string)$raw, true);
                if (!is_array($decoded)) {
                    g4fr_json_response(400, ['ok' => false, 'error' => 'body_json_invalido: ' . json_last_error_msg()]);
                }
                return $decoded;
            }
            return is_array($_POST) ? $_POST : [];
        }

        g4fr_json_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
    }
}

if (!function_exists('g4fr_str_limit')) {
    function g4fr_str_limit(string $s, int $max): string {
        if ($max <= 0) {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($s, 'UTF-8') > $max ? mb_substr($s, 0, $max, 'UTF-8') : $s;
        }
        return strlen($s) > ($max * 4) ? substr($s, 0, $max * 4) : $s;
    }
}

if (!function_exists('g4fr_parse_id_list')) {
    function g4fr_parse_id_list($raw): array {
        if (is_array($raw)) {
            $out = [];
            foreach ($raw as $v) {
                $id = (int)$v;
                if ($id > 0) $out[$id] = $id;
            }
            return array_values($out);
        }
        $raw = is_string($raw) ? trim($raw) : '';
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return g4fr_parse_id_list($decoded);
        $out = [];
        foreach ((preg_split('/[^0-9]+/', $raw) ?: []) as $part) {
            $id = (int)$part;
            if ($id > 0) $out[$id] = $id;
        }
        return array_values($out);
    }
}

if (!function_exists('g4fr_get_current_uid_glpi')) {
    function g4fr_get_current_uid_glpi(): int {
        try {
            if (class_exists('Session') && method_exists('Session', 'getLoginUserID')) {
                $uid = (int)\Session::getLoginUserID();
                if ($uid > 0) return $uid;
            }
        } catch (Throwable $e) {}
        if (isset($_SESSION['glpiID'])) {
            $uid = (int)$_SESSION['glpiID'];
            if ($uid > 0) return $uid;
        }
        return 0;
    }
}

if (!function_exists('g4fr_get_groups_from_session_glpi')) {
    function g4fr_get_groups_from_session_glpi(): array {
        $ids = [];
        if (!isset($_SESSION) || !is_array($_SESSION)) return [];
        if (isset($_SESSION['glpigroups']) && is_array($_SESSION['glpigroups'])) {
            foreach ($_SESSION['glpigroups'] as $k => $v) {
                if (is_numeric($k) && (int)$k > 0) $ids[(int)$k] = true;
                if (is_numeric($v) && (int)$v > 0) $ids[(int)$v] = true;
                if (is_array($v)) {
                    foreach ($v as $vv) {
                        if (is_numeric($vv) && (int)$vv > 0) $ids[(int)$vv] = true;
                    }
                }
            }
        }
        return array_keys($ids);
    }
}

if (!function_exists('g4fr_get_groups_from_db_glpi')) {
    function g4fr_get_groups_from_db_glpi(int $uid): array {
        $ids = [];
        global $DB;
        if (!isset($DB) || !is_object($DB) || $uid <= 0) return [];
        try {
            if (method_exists($DB, 'request')) {
                $it = $DB->request([
                    'SELECT' => ['groups_id'],
                    'FROM'   => 'glpi_groups_users',
                    'WHERE'  => ['users_id' => $uid],
                ]);
                foreach ($it as $row) {
                    if (is_array($row)) {
                        $gid = (int)($row['groups_id'] ?? 0);
                        if ($gid > 0) $ids[$gid] = true;
                    }
                }
                return array_keys($ids);
            }
        } catch (Throwable $e) {}
        try {
            if (method_exists($DB, 'query')) {
                $res = @$DB->query('SELECT `groups_id` FROM `glpi_groups_users` WHERE `users_id` = ' . (int)$uid);
                if ($res) {
                    if (method_exists($DB, 'fetchAssoc')) {
                        while ($row = $DB->fetchAssoc($res)) {
                            $gid = (int)($row['groups_id'] ?? 0);
                            if ($gid > 0) $ids[$gid] = true;
                        }
                    } elseif (method_exists($DB, 'fetch_assoc')) {
                        while ($row = $DB->fetch_assoc($res)) {
                            $gid = (int)($row['groups_id'] ?? 0);
                            if ($gid > 0) $ids[$gid] = true;
                        }
                    }
                }
            }
        } catch (Throwable $e) {}
        return array_keys($ids);
    }
}

if (!function_exists('g4fr_resolve_current_user_groups_glpi')) {
    function g4fr_resolve_current_user_groups_glpi(int $uid = 0): array {
        $ids = g4fr_get_groups_from_session_glpi();
        if (empty($ids)) {
            if ($uid <= 0) $uid = g4fr_get_current_uid_glpi();
            if ($uid > 0) $ids = g4fr_get_groups_from_db_glpi($uid);
        }
        $out = [];
        foreach ($ids as $gid) {
            $gid = (int)$gid;
            if ($gid > 0) $out[$gid] = true;
        }
        return array_keys($out);
    }
}

if (!function_exists('g4fr_expand_group_ancestors_glpi')) {
    function g4fr_expand_group_ancestors_glpi(array $groupIds): array {
        $dist = [];
        $frontier = [];
        foreach ($groupIds as $gid) {
            $gid = (int)$gid;
            if ($gid > 0 && !isset($dist[$gid])) {
                $dist[$gid] = 0;
                $frontier[$gid] = 0;
            }
        }
        if (empty($frontier)) return $dist;
        global $DB;
        if (!isset($DB) || !is_object($DB)) return $dist;
        $depth = 0;
        while (!empty($frontier) && $depth < 20) {
            $depth++;
            $ids = array_map('intval', array_keys($frontier));
            $next = [];
            try {
                if (method_exists($DB, 'request')) {
                    $it = $DB->request([
                        'SELECT' => ['id', 'groups_id'],
                        'FROM'   => 'glpi_groups',
                        'WHERE'  => ['id' => $ids],
                    ]);
                    foreach ($it as $row) {
                        if (!is_array($row)) continue;
                        $id = (int)($row['id'] ?? 0);
                        $parent = (int)($row['groups_id'] ?? 0);
                        if ($id <= 0 || $parent <= 0) continue;
                        $candidateDistance = ((int)($dist[$id] ?? 0)) + 1;
                        if (!isset($dist[$parent]) || $candidateDistance < (int)$dist[$parent]) {
                            $dist[$parent] = $candidateDistance;
                            $next[$parent] = $candidateDistance;
                        }
                    }
                    $frontier = $next;
                    continue;
                }
            } catch (Throwable $e) {}
            try {
                if (!method_exists($DB, 'query')) break;
                $in = implode(',', $ids);
                if ($in === '') break;
                $res = @$DB->query('SELECT `id`, `groups_id` FROM `glpi_groups` WHERE `id` IN (' . $in . ')');
                if (!$res) break;
                if (method_exists($DB, 'fetchAssoc')) {
                    while ($row = $DB->fetchAssoc($res)) {
                        $id = (int)($row['id'] ?? 0);
                        $parent = (int)($row['groups_id'] ?? 0);
                        if ($id <= 0 || $parent <= 0) continue;
                        $candidateDistance = ((int)($dist[$id] ?? 0)) + 1;
                        if (!isset($dist[$parent]) || $candidateDistance < (int)$dist[$parent]) {
                            $dist[$parent] = $candidateDistance;
                            $next[$parent] = $candidateDistance;
                        }
                    }
                } elseif (method_exists($DB, 'fetch_assoc')) {
                    while ($row = $DB->fetch_assoc($res)) {
                        $id = (int)($row['id'] ?? 0);
                        $parent = (int)($row['groups_id'] ?? 0);
                        if ($id <= 0 || $parent <= 0) continue;
                        $candidateDistance = ((int)($dist[$id] ?? 0)) + 1;
                        if (!isset($dist[$parent]) || $candidateDistance < (int)$dist[$parent]) {
                            $dist[$parent] = $candidateDistance;
                            $next[$parent] = $candidateDistance;
                        }
                    }
                }
                $frontier = $next;
            } catch (Throwable $e) {
                break;
            }
        }
        return $dist;
    }
}
