<?php

namespace GlpiPlugin\G4freports\Util;

class Http
{
    public static function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function readJsonOrPost(): array
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'GET') {
            return is_array($_GET) ? $_GET : [];
        }
        $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        if (strpos($ct, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode((string)$raw, true);
            return is_array($data) ? $data : [];
        }
        return is_array($_POST) ? $_POST : [];
    }

    public static function bool($value, bool $default = false): bool
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

    public static function int($value, int $default = 0, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        if (!is_numeric($value)) return $default;
        $n = (int)$value;
        if ($n < $min) $n = $min;
        if ($n > $max) $n = $max;
        return $n;
    }
}
