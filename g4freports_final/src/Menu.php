<?php

namespace GlpiPlugin\G4freports;

class Menu extends \CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return __('Relatórios G4F', 'g4freports');
    }

    public static function getMenuName()
    {
        return self::getTypeName();
    }

    public static function getMenuContent(): array
    {
        global $CFG_GLPI;

        $base = ($CFG_GLPI['root_doc'] ?? '') . '/plugins/g4freports/front';

        return [
            'title' => self::getMenuName(),
            'page'  => $base . '/report.php',
            'icon'  => 'ti ti-file-export'
        ];
    }
}
