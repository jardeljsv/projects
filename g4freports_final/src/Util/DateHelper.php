<?php

namespace GlpiPlugin\G4freports\Util;

class DateHelper
{
    public static function normalizeDate(string $date, string $fallback = ''): string
    {
        $date = trim($date);
        if ($date === '') {
            return $fallback;
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return $fallback;
        }
        return date('Y-m-d', $ts);
    }

    public static function displayDate(string $date): string
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }
        return date('d/m/Y', $ts);
    }

    public static function displayDateTime(string $date): string
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }
        return date('d/m/Y H:i', $ts);
    }

    public static function within(string $date, string $start, string $end): bool
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return false;
        }
        $a = strtotime($start . ' 00:00:00');
        $b = strtotime($end . ' 23:59:59');
        if ($a === false || $b === false) {
            return true;
        }
        return $ts >= $a && $ts <= $b;
    }
}
