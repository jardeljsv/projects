<?php

namespace GlpiPlugin\G4freports;

class Security
{
    public static function currentUserId(): int
    {
        try {
            if (class_exists('Session') && method_exists('Session', 'getLoginUserID')) {
                $uid = (int)\Session::getLoginUserID();
                if ($uid > 0) {
                    return $uid;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return (int)($_SESSION['glpiID'] ?? 0);
    }

    public static function currentProfileId(): int
    {
        return (int)($_SESSION['glpiactiveprofile']['id'] ?? 0);
    }

    public static function currentGroupIds(): array
    {
        $ids = [];
        if (isset($_SESSION['glpigroups']) && is_array($_SESSION['glpigroups'])) {
            foreach ($_SESSION['glpigroups'] as $k => $v) {
                if (is_numeric($k) && (int)$k > 0) {
                    $ids[(int)$k] = true;
                }
                if (is_numeric($v) && (int)$v > 0) {
                    $ids[(int)$v] = true;
                }
            }
        }

        $uid = self::currentUserId();
        if ($uid > 0) {
            foreach (self::groupsFromDb($uid) as $gid) {
                $ids[(int)$gid] = true;
            }
        }

        return array_keys($ids);
    }

    private static function groupsFromDb(int $uid): array
    {
        $ids = [];
        global $DB;
        if (!isset($DB) || !is_object($DB) || $uid <= 0) {
            return [];
        }

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
                        if ($gid > 0) $ids[$gid] = $gid;
                    }
                }
                return array_values($ids);
            }
        } catch (\Throwable $e) {
            // fall through
        }

        try {
            if (method_exists($DB, 'query')) {
                $uidSql = (int)$uid;
                $res = @$DB->query("SELECT `groups_id` FROM `glpi_groups_users` WHERE `users_id` = " . $uidSql);
                if ($res) {
                    if (method_exists($DB, 'fetchAssoc')) {
                        while ($row = $DB->fetchAssoc($res)) {
                            $gid = (int)($row['groups_id'] ?? 0);
                            if ($gid > 0) $ids[$gid] = $gid;
                        }
                    } elseif (method_exists($DB, 'fetch_assoc')) {
                        while ($row = $DB->fetch_assoc($res)) {
                            $gid = (int)($row['groups_id'] ?? 0);
                            if ($gid > 0) $ids[$gid] = $gid;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return array_values($ids);
    }

    private static function expandGroupAncestors(array $groupIds): array
    {
        $dist = [];
        foreach ($groupIds as $gid) {
            $gid = (int)$gid;
            if ($gid > 0) {
                $dist[$gid] = 0;
            }
        }
        if (empty($dist)) {
            return [];
        }

        global $DB;
        if (!isset($DB) || !is_object($DB)) {
            return array_keys($dist);
        }

        $frontier = $dist;
        for ($depth = 0; $depth < 16 && !empty($frontier); $depth++) {
            $next = [];
            $ids = array_map('intval', array_keys($frontier));
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
                        if ($id > 0 && $parent > 0 && !isset($dist[$parent])) {
                            $dist[$parent] = ((int)($dist[$id] ?? 0)) + 1;
                            $next[$parent] = true;
                        }
                    }
                } elseif (method_exists($DB, 'query')) {
                    $in = implode(',', $ids);
                    if ($in === '') break;
                    $res = @$DB->query('SELECT `id`, `groups_id` FROM `glpi_groups` WHERE `id` IN (' . $in . ')');
                    if ($res && method_exists($DB, 'fetchAssoc')) {
                        while ($row = $DB->fetchAssoc($res)) {
                            $id = (int)($row['id'] ?? 0);
                            $parent = (int)($row['groups_id'] ?? 0);
                            if ($id > 0 && $parent > 0 && !isset($dist[$parent])) {
                                $dist[$parent] = ((int)($dist[$id] ?? 0)) + 1;
                                $next[$parent] = true;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                break;
            }
            $frontier = $next;
        }

        return array_keys($dist);
    }

    public static function parseIds($raw): array
    {
        if (is_array($raw)) {
            $out = [];
            foreach ($raw as $v) {
                $id = (int)$v;
                if ($id > 0) $out[$id] = true;
            }
            return array_keys($out);
        }
        $raw = trim((string)$raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return self::parseIds($decoded);
        }
        $out = [];
        foreach (preg_split('/[^0-9]+/', $raw) as $part) {
            $id = (int)$part;
            if ($id > 0) {
                $out[$id] = true;
            }
        }
        return array_keys($out);
    }

    public static function canConfigure(): bool
    {
        try {
            if (class_exists('Session') && method_exists('Session', 'haveRight')) {
                return \Session::haveRight('config', UPDATE);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }

    public static function canUse(array $cfg = null): bool
    {
        $cfg = $cfg ?? Config::merged();
        if ((int)($cfg['enabled'] ?? 1) !== 1) {
            return false;
        }

        if (self::canConfigure()) {
            return true;
        }

        $uid = self::currentUserId();
        if ($uid <= 0) {
            return false;
        }

        // Route mode behaves like GLPIBot: when at least one enabled route
        // exists, legacy allowed-groups/profile lists are not used.
        if (Config::hasRouteMode($cfg)) {
            return Config::matchRoute($cfg, self::currentGroupIds()) !== null;
        }

        $allowedProfiles = self::parseIds($cfg['allowed_profiles'] ?? '');
        $allowedGroups   = self::parseIds($cfg['allowed_groups'] ?? '');

        // No restriction configured: any logged-in user may use the module.
        if (empty($allowedProfiles) && empty($allowedGroups)) {
            return true;
        }

        if (!empty($allowedProfiles)) {
            $pid = self::currentProfileId();
            if ($pid > 0 && in_array($pid, $allowedProfiles, true)) {
                return true;
            }
        }

        if (!empty($allowedGroups)) {
            $expandedGroups = self::expandGroupAncestors(self::currentGroupIds());
            foreach ($expandedGroups as $gid) {
                if (in_array((int)$gid, $allowedGroups, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function denyJson(int $code = 403, string $message = 'Acesso negado'): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
