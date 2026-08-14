<?php
// plugins/g4freports/setup.php

require_once __DIR__ . '/inc/autoload.php';
require_once __DIR__ . '/inc/common.php';
require_once __DIR__ . '/inc/menu.class.php';
require_once __DIR__ . '/inc/config.class.php';

if (!defined('PLUGIN_G4FREPORTS_VERSION')) { define('PLUGIN_G4FREPORTS_VERSION', '1.6.0'); }
if (!defined('PLUGIN_G4FREPORTS_MIN_GLPI')) { define('PLUGIN_G4FREPORTS_MIN_GLPI', '10.0.0'); }

function plugin_init_g4freports(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['g4freports'] = true;

    if (class_exists('Plugin')) {
        Plugin::registerClass(\GlpiPlugin\G4freports\Config::class, [
            'addtabon' => \Config::class
        ]);
        Plugin::registerClass('PluginG4freportsMenu');
    }

    // Left-menu entry under Tools. The menu points only to the report generator;
    // administrative configuration is exposed through Setup > General.
    $PLUGIN_HOOKS['menu_toadd']['g4freports'] = [
        'tools' => 'PluginG4freportsMenu'
    ];
}

function plugin_version_g4freports(): array
{
    return [
        'name'           => 'Relatórios G4F',
        'version'        => PLUGIN_G4FREPORTS_VERSION,
        'author'         => 'G4F',
        'license'        => 'MIT',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_G4FREPORTS_MIN_GLPI,
                'max' => '11.99.99'
            ]
        ]
    ];
}

function plugin_g4freports_check_prerequisites(): bool
{
    if (defined('GLPI_VERSION') && version_compare(GLPI_VERSION, PLUGIN_G4FREPORTS_MIN_GLPI, '<')) {
        if (method_exists('Plugin', 'messageIncompatible')) {
            Plugin::messageIncompatible('core', PLUGIN_G4FREPORTS_MIN_GLPI);
        }
        return false;
    }

    // No PHPWord, ZipArchive, cURL or composer-installed package is required.
    return true;
}

function plugin_g4freports_check_config($verbose = false): bool
{
    return true;
}
