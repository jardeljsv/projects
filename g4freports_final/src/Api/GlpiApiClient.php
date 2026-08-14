<?php

namespace GlpiPlugin\G4freports\Api;

class GlpiApiClient
{
    private array $profile;
    private string $baseUrl;
    private string $sessionToken = '';
    private bool $ownsSession = false;

    public function __construct(array $profile)
    {
        foreach ([
            'app_token' => 'app_token_env',
            'user_token' => 'user_token_env',
            'session_token' => 'session_token_env'
        ] as $tokenKey => $envKey) {
            $current = trim((string)($profile[$tokenKey] ?? ''));
            $envName = trim((string)($profile[$envKey] ?? ''));
            if ($current === '' && $envName !== '') {
                $envValue = getenv($envName);
                if (is_string($envValue) && trim($envValue) !== '') {
                    $profile[$tokenKey] = trim($envValue);
                }
            }
        }

        $this->profile = $profile;
        $this->baseUrl = self::normalizeBaseUrl((string)($profile['base_url'] ?? ''));
        if ($this->baseUrl === '') {
            $this->baseUrl = self::guessLocalApiUrl();
        }
        $this->sessionToken = trim((string)($profile['session_token'] ?? ''));
    }

    public function __destruct()
    {
        $this->killSession();
    }

    public static function normalizeBaseUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $url = rtrim($url, '/');
        if (!preg_match('#/apirest\.php$#i', $url)) {
            $url .= '/apirest.php';
        }
        return $url;
    }

    public static function guessLocalApiUrl(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $root = '';
            if (isset($GLOBALS['CFG_GLPI']['root_doc'])) {
                $root = (string)$GLOBALS['CFG_GLPI']['root_doc'];
            }
            return rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $root, '/') . '/apirest.php';
        }
        return '/apirest.php';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function initSession(): void
    {
        if ($this->sessionToken !== '') {
            return;
        }
        $data = $this->rawRequest('GET', '/initSession', [], null, true);
        $token = trim((string)($data['session_token'] ?? ''));
        if ($token === '') {
            throw new GlpiApiException('GLPI API did not return a session_token');
        }
        $this->sessionToken = $token;
        $this->ownsSession = true;
    }

    public function killSession(): void
    {
        if ($this->ownsSession && $this->sessionToken !== '') {
            try {
                $this->request('GET', '/killSession');
            } catch (\Throwable $e) {
                // Ignore cleanup failures.
            }
        }
        $this->sessionToken = '';
        $this->ownsSession = false;
    }

    public function request(string $method, string $path, array $query = [], $body = null)
    {
        $this->initSession();
        return $this->rawRequest($method, $path, $query, $body, false);
    }

    public function getItem(string $itemtype, int $id, array $query = [])
    {
        return $this->request('GET', '/' . rawurlencode($itemtype) . '/' . (int)$id, $query);
    }

    public function getSubItems(string $itemtype, int $id, string $subitemtype, array $query = [])
    {
        return $this->request('GET', '/' . rawurlencode($itemtype) . '/' . (int)$id . '/' . rawurlencode($subitemtype), $query);
    }

    public function listSearchOptions(string $itemtype)
    {
        return $this->request('GET', '/listSearchOptions/' . rawurlencode($itemtype));
    }

    public function search(string $itemtype, array $criteria = [], array $forcedisplay = [], string $range = '0-49', array $extra = [])
    {
        $query = $extra;
        $idx = 0;
        foreach ($criteria as $crit) {
            if (!is_array($crit)) {
                continue;
            }
            foreach ($crit as $key => $value) {
                $query['criteria'][$idx][$key] = $value;
            }
            $idx++;
        }
        foreach (array_values(array_unique(array_filter(array_map('intval', $forcedisplay)))) as $i => $field) {
            $query['forcedisplay'][$i] = $field;
        }
        if ($range !== '') {
            $query['range'] = $range;
        }
        return $this->request('GET', '/search/' . rawurlencode($itemtype), $query);
    }

    public function rawRequest(string $method, string $path, array $query = [], $body = null, bool $forInit = false)
    {
        [$url, $headers, $payload, $method] = $this->prepareRequest($method, $path, $query, $body, $forInit);
        if (function_exists('curl_init')) {
            [$status, $responseBody] = $this->rawRequestCurl($url, $headers, $payload, $method);
        } else {
            [$status, $responseBody] = $this->rawRequestStream($url, $headers, $payload, $method);
        }
        return $this->decodeResponse($status, $responseBody);
    }

    private function prepareRequest(string $method, string $path, array $query, $body, bool $forInit): array
    {
        $method = strtoupper($method);
        $path = '/' . ltrim($path, '/');
        $url = $this->baseUrl . $path;
        if (!empty($query)) {
            $qs = http_build_query($query);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
        }

        $headers = ['Accept: application/json'];
        $appToken = trim((string)($this->profile['app_token'] ?? ''));
        if ($appToken !== '') {
            $headers[] = 'App-Token: ' . $appToken;
        }

        if ($forInit) {
            $userToken = trim((string)($this->profile['user_token'] ?? ''));
            if ($userToken !== '') {
                $headers[] = 'Authorization: user_token ' . $userToken;
            }
        } elseif ($this->sessionToken !== '') {
            $headers[] = 'Session-Token: ' . $this->sessionToken;
        }

        $payload = null;
        if ($body !== null) {
            $payload = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
        }

        return [$url, $headers, $payload, $method];
    }

    private function rawRequestCurl(string $url, array $headers, ?string $payload, string $method): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, (int)($this->profile['connect_timeout_ms'] ?? 10000));
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int)($this->profile['timeout_ms'] ?? 60000));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !empty($this->profile['verify_tls']));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, !empty($this->profile['verify_tls']) ? 2 : 0);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new GlpiApiException('GLPI API request failed: ' . $err);
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseBody = substr($raw, $headerSize);
        curl_close($ch);
        return [$status, $responseBody];
    }

    private function rawRequestStream(string $url, array $headers, ?string $payload, string $method): array
    {
        if (!ini_get('allow_url_fopen')) {
            throw new GlpiApiException('Neither cURL nor allow_url_fopen is available for HTTP requests');
        }
        $timeout = max(1, (int)ceil(((int)($this->profile['timeout_ms'] ?? 60000)) / 1000));
        $verify = !empty($this->profile['verify_tls']);
        $opts = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => $timeout,
            ],
            'ssl' => [
                'verify_peer' => $verify,
                'verify_peer_name' => $verify,
            ]
        ];
        if ($payload !== null) {
            $opts['http']['content'] = $payload;
        }
        $ctx = stream_context_create($opts);
        $responseBody = @file_get_contents($url, false, $ctx);
        if ($responseBody === false) {
            $err = error_get_last();
            throw new GlpiApiException('GLPI API stream request failed: ' . (is_array($err) ? ($err['message'] ?? 'unknown error') : 'unknown error'));
        }
        $status = 0;
        $headersOut = $http_response_header ?? [];
        foreach ($headersOut as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string)$line, $m)) {
                $status = (int)$m[1];
                break;
            }
        }
        return [$status ?: 200, (string)$responseBody];
    }

    private function decodeResponse(int $status, string $responseBody)
    {
        $decoded = null;
        if ($responseBody !== '') {
            $decoded = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded = $responseBody;
            }
        }

        if ($status < 200 || $status >= 300) {
            $detail = is_string($decoded) ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $detail = $detail ? $this->limit($detail, 500) : '';
            throw new GlpiApiException('GLPI API returned HTTP ' . $status . ($detail ? ': ' . $detail : ''), $status, $decoded);
        }

        return $decoded;
    }

    private function limit(string $text, int $max): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text, 'UTF-8') > $max ? mb_substr($text, 0, $max, 'UTF-8') : $text;
        }
        return strlen($text) > $max ? substr($text, 0, $max) : $text;
    }
}
