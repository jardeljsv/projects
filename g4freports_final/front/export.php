<?php

require_once dirname(__DIR__) . '/inc/bootstrap.php';

use GlpiPlugin\G4freports\Config;
use GlpiPlugin\G4freports\Security;
use GlpiPlugin\G4freports\Export\DocxExporter;

@ini_set('display_errors', '0');
@set_time_limit(120);

function g4fr_export_tmp_dir_export(): string {
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

function g4fr_export_token_path_export(string $token, string $suffix = '.json'): string {
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Token de exportação inválido.';
        exit;
    }
    return g4fr_export_tmp_dir_export() . '/export_' . $token . $suffix;
}

function g4fr_read_token_draft(string $token): string {
    $metaPath = g4fr_export_token_path_export($token, '.meta.json');
    $jsonPath = g4fr_export_token_path_export($token, '.json');
    $meta = is_file($metaPath) ? json_decode((string)@file_get_contents($metaPath), true) : null;
    if (!is_array($meta) || !is_file($jsonPath)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Rascunho temporário de exportação não encontrado ou expirado.';
        exit;
    }
    $uid = g4fr_get_current_uid_glpi();
    if ((int)($meta['uid'] ?? 0) !== $uid) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Token de exportação pertence a outro usuário/sessão.';
        exit;
    }
    if (time() - (int)($meta['created_at'] ?? 0) > 1800) {
        @unlink($jsonPath);
        @unlink($metaPath);
        http_response_code(410);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Rascunho temporário de exportação expirado.';
        exit;
    }
    return (string)@file_get_contents($jsonPath);
}

$cfg = Config::merged();
if (!Security::canUse($cfg)) {
    http_response_code(403);
    echo 'Acesso negado';
    exit;
}

$token = trim((string)($_POST['export_token'] ?? $_GET['export_token'] ?? ''));
if ($token !== '') {
    $raw = g4fr_read_token_draft($token);
} else {
    // Backward compatibility for imported/local drafts. The normal UI uses the
    // tokenized prepare_export flow to avoid request-body limits.
    $raw = $_POST['draft_json'] ?? file_get_contents('php://input');
    if (is_array($raw)) {
        $raw = reset($raw);
    }
}

$draft = json_decode((string)$raw, true);
if (!is_array($draft)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Rascunho JSON inválido.';
    exit;
}

$exporter = new DocxExporter();
$binary = $exporter->toBinary($draft);
$filename = $exporter->filename($draft);

if ($token !== '') {
    @unlink(g4fr_export_token_path_export($token, '.json'));
    @unlink(g4fr_export_token_path_export($token, '.meta.json'));
}

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('Content-Length: ' . strlen($binary));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo $binary;
exit;
