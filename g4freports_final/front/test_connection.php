<?php
// /plugins/g4freports/front/test_connection.php
// Connection test endpoint used only by the configuration UI.
// It accepts GET parameters so it follows the same public-root/proxy-safe pattern
// used by the hardened GLPIBot plugin.

require_once dirname(__DIR__) . '/inc/bootstrap.php';

use GlpiPlugin\G4freports\Config;
use GlpiPlugin\G4freports\Security;
use GlpiPlugin\G4freports\Api\GlpiApiClient;

@ini_set('display_errors', '0');


if (!Security::canConfigure()) {
    g4fr_json_response(403, ['ok' => false, 'reason' => 'acesso_negado']);
}

function g4fr_tc_input(): array
{
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === 'GET') {
        return is_array($_GET) ? $_GET : [];
    }
    if ($method === 'POST') {
        $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        if (strpos($ct, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode((string)$raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($_POST) ? $_POST : [];
    }
    g4fr_json_response(405, ['ok' => false, 'reason' => 'method_not_allowed']);
}

function g4fr_tc_profile_from_input(array $input): ?array
{
    $profileId = trim((string)($input['profile_id'] ?? ''));
    $hasAdhoc = false;
    foreach (['base_url','api_base_url','app_token','user_token','session_token','app_token_env','user_token_env','session_token_env'] as $key) {
        if (trim((string)($input[$key] ?? '')) !== '') {
            $hasAdhoc = true;
            break;
        }
    }

    if (!$hasAdhoc && $profileId !== '') {
        $cfg = Config::merged();
        return Config::getProfile($profileId, $cfg);
    }

    if (!$hasAdhoc) {
        return null;
    }

    return Config::normalizeProfile([
        'id' => '__adhoc__',
        'name' => trim((string)($input['name'] ?? 'Teste de conexão')) ?: 'Teste de conexão',
        'base_url' => trim((string)($input['base_url'] ?? ($input['api_base_url'] ?? ''))),
        'app_token' => trim((string)($input['app_token'] ?? '')),
        'user_token' => trim((string)($input['user_token'] ?? '')),
        'session_token' => trim((string)($input['session_token'] ?? '')),
        'app_token_env' => trim((string)($input['app_token_env'] ?? '')),
        'user_token_env' => trim((string)($input['user_token_env'] ?? '')),
        'session_token_env' => trim((string)($input['session_token_env'] ?? '')),
        'auth_mode' => trim((string)($input['auth_mode'] ?? 'user_token')) ?: 'user_token',
        'verify_tls' => !in_array((string)($input['verify_tls'] ?? '1'), ['0','false','no','nao','não'], true),
        'timeout_ms' => max(1000, min(600000, (int)($input['timeout_ms'] ?? ($input['request_timeout_ms'] ?? 60000)))),
        'enabled' => true,
    ], '__adhoc__');
}

$input = g4fr_tc_input();
$profile = g4fr_tc_profile_from_input($input);
if (!is_array($profile)) {
    g4fr_json_response(200, ['ok' => false, 'reason' => 'not_configured', 'message' => 'Informe a URL da API e os tokens antes de testar.']);
}

try {
    $client = new GlpiApiClient($profile);
    $baseUrl = $client->getBaseUrl();
    if ($baseUrl === '') {
        g4fr_json_response(200, ['ok' => false, 'reason' => 'missing_base_url', 'message' => 'URL da API GLPI não informada.']);
    }

    $client->initSession();
    $sessionInfo = null;
    try {
        $sessionInfo = $client->request('GET', '/getFullSession');
    } catch (Throwable $sessionError) {
        // initSession is enough for user_token mode, but a fixed session token must
        // also prove it can perform a normal authenticated request.
        if (trim((string)($profile['session_token'] ?? '')) !== '') {
            throw $sessionError;
        }
        $sessionInfo = ['warning' => $sessionError->getMessage()];
    }
    $client->killSession();

    $message = 'Conexão realizada com sucesso em ' . $baseUrl;
    g4fr_json_response(200, [
        'ok' => true,
        'reason' => 'connected',
        'message' => $message,
        'base_url' => $baseUrl,
        'profile_name' => (string)($profile['name'] ?? ''),
        'session' => $sessionInfo,
    ]);
} catch (Throwable $e) {
    g4fr_json_response(200, [
        'ok' => false,
        'reason' => 'connection_failed',
        'message' => $e->getMessage(),
        'detail' => g4fr_str_limit($e->getMessage(), 700),
        'profile_name' => (string)($profile['name'] ?? ''),
    ]);
}
