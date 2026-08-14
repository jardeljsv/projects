<?php
// Administrative configuration is intentionally exposed through GLPI's native
// Setup > General > Relatórios G4F tab. This front endpoint is kept only as a
// compatibility redirect for old bookmarks from early test builds.

require_once dirname(__DIR__) . '/inc/bootstrap.php';

if (class_exists('Session')) {
    Session::checkLoginUser();
}

if (!class_exists('Html')) {
    header('Location: ../../front/config.form.php');
    exit;
}

$root = (string)($GLOBALS['CFG_GLPI']['root_doc'] ?? '');
Html::redirect(rtrim($root, '/') . '/front/config.form.php');
