<?php

namespace OutdoorWww\Config;

final class MetaHelper
{
    /** Gesamtes Feld-Def aus Meta::defaults() holen */
    public static function def(string $key): ?array
    {
        $all = Meta::defaults();
        return $all[$key] ?? null;
    }

    /** UI-Optionen eines Feldes (['value','label']...) */
    public static function options(string $key): array
    {
        $def = self::def($key);
        $opts = $def['ui']['options'] ?? [];
        // Normalisieren, reindizieren
        return array_values(array_map(function ($o) {
            return [
                'value' => (string)($o['value'] ?? ''),
                'label' => (string)($o['label'] ?? ''),
            ];
        }, $opts));
    }

    /** Nur die VALUES-Liste (in Options-Reihenfolge) */
    public static function optionValues(string $key): array
    {
        return array_values(array_map(fn($o) => $o['value'], self::options($key)));
    }

    /** Label zu einem konkreten Value */
    public static function labelFor(string $key, string $value): string
    {
        foreach (self::options($key) as $o) {
            if ($o['value'] === $value) return $o['label'] ?: $value;
        }
        return '—';
    }
}
