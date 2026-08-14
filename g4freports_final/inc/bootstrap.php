<?php
// Robust GLPI bootstrap used by interactive plugin pages/endpoints.

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/autoload.php';

$g4fr_glpi_root = null;
if (!g4fr_bootstrap_glpi_runtime($g4fr_glpi_root)) {
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=UTF-8');
    }
    http_response_code(500);
    echo 'Relatórios G4F: não foi possível inicializar o GLPI. Verifique GLPI_ROOT ou a localização do plugin.';
    exit;
}

require_once __DIR__ . '/autoload.php';

$menuClass = dirname(__DIR__) . '/inc/menu.class.php';
if (is_file($menuClass)) {
    require_once $menuClass;
}
$configClass = dirname(__DIR__) . '/inc/config.class.php';
if (is_file($configClass)) {
    require_once $configClass;
}
