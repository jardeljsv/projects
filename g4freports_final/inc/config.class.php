<?php

require_once dirname(__DIR__) . '/inc/autoload.php';

if (!class_exists('GlpiPlugin\\G4freports\\Config')) {
    $src = dirname(__DIR__) . '/src/Config.php';
    if (is_file($src)) {
        require_once $src;
    }
}

class PluginG4freportsConfig extends \GlpiPlugin\G4freports\Config
{
}
