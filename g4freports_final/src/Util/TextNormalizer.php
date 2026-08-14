<?php

namespace GlpiPlugin\G4freports\Util;

class TextNormalizer
{
    public static function clean($value, bool $maskSensitive = true): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $text = (string)$value;
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*\/\s*p\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*li[^>]*>/i', "\n• ", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\t ]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);
        if ($maskSensitive) {
            $text = self::maskSensitive($text);
        }
        return $text;
    }

    public static function maskSensitive(string $text): string
    {
        // CPF: keep only first three digits for traceability.
        $text = preg_replace('/\b(\d{3})\.?\d{3}\.?\d{3}-?\d{2}\b/', '$1.***.***-**', $text) ?? $text;

        // Email: keep the first two chars and domain.
        $text = preg_replace_callback('/\b([A-Z0-9._%+\-]{2})([A-Z0-9._%+\-]*)(@[A-Z0-9.\-]+\.[A-Z]{2,})\b/i', static function ($m) {
            return $m[1] . '***' . $m[3];
        }, $text) ?? $text;

        // Brazilian phone-ish numbers: mask middle digits.
        $text = preg_replace('/\b(\(?\d{2}\)?\s?)(\d{4,5})[-\s]?(\d{4})\b/', '$1****-$3', $text) ?? $text;
        return $text;
    }

    public static function firstNonEmpty(...$values): string
    {
        foreach ($values as $v) {
            $s = self::clean($v, false);
            if ($s !== '') {
                return $s;
            }
        }
        return '';
    }

    public static function summarize(string $text, int $max = 1200): string
    {
        $text = trim($text);
        if ($max <= 0) {
            return $text;
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $max) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, $max, 'UTF-8')) . '...';
        }
        if (strlen($text) <= $max) {
            return $text;
        }
        return rtrim(substr($text, 0, $max)) . '...';
    }
}
