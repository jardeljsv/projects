<?php

require_once dirname(__DIR__) . '/inc/autoload.php';

if (!class_exists('GlpiPlugin\\G4freports\\Menu')) {
    $src = dirname(__DIR__) . '/src/Menu.php';
    if (is_file($src)) {
        require_once $src;
    }
}

class PluginG4freportsMenu extends \GlpiPlugin\G4freports\Menu
{
}
