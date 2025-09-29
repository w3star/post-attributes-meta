<?php

namespace OutdoorWww\Support;



use OutdoorWww\Config\Fields;
use OutdoorWww\Config\MetaHelper;
use OutdoorWww\Support\Icons;



/**
 * HTML-Hilfsfunktionen
 */
final class Html
{
    public static function iconGroup(string $iconHtml, int $count): string
    {
        if ($count <= 0) return '—';
        $out = '<span class="owww-icongroup">';
        for ($i = 0; $i < $count; $i++) $out .= $iconHtml;
        return $out . '</span>';
    }



    public static function stars(int $rating): string
    {
        $rating = max(0, min(5, (int)$rating));
        $out = '<span class="owww-icongroup" aria-label="Rating ' . $rating . ' von 5">';
        for ($i = 1; $i <= 5; $i++) {
            $out .= ($i <= $rating) ? Icons::starFilled() : Icons::starEmpty();
        }
        return $out . '</span>';
    }



    public static function durationText(int $minutes): string
    {
        $minutes = (int)$minutes;
        if ($minutes <= 0) return '—';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return $h . ' h ' . $m . ' min';
        if ($h > 0) return $h . ' h';
        return $m . ' min';
    }



    public static function difficultyHiking_count(string $diff): int
    {
        return $diff === 'T6' ? 6 : ($diff === 'T5' ? 5 : ($diff === 'T4' ? 4 : ($diff === 'T3' ? 3 : ($diff === 'T2' ? 2 : ($diff === 'T1' ? 1 : 0)))));
    }
    
    
    
    // public static function difficultyLabel(string $diff): string
    // {
    //     return $diff === 'T6' ? 'T6' : ($diff === 'T5' ? 'T5' : ($diff === 'T4' ? 'T4' : ($diff === 'T3' ? 'T3' : ($diff === 'T2' ? 'T2' : ($diff === 'T1' ? 'T1' : '—')))));
    // }
    /** Label für die aktuelle Schwierigkeit, direkt aus Meta::defaults() */
    public static function difficultyLabel(string $value): string {
        return MetaHelper::labelFor(Fields::difficulty(), (string)$value);
    }



    /** Icons für die aktuelle Schwierigkeit (Anzahl = Index der Option, ohne evtl. leere Placeholder-Option) */
    public static function difficultyIcons(string $value): string {
        $vals = MetaHelper::optionValues(Fields::difficulty());
        if (!$vals) return '—';

        // leere Placeholder am Anfang ignorieren
        $hasEmptyFirst = ($vals[0] === '');
        $list = $hasEmptyFirst ? array_slice($vals, 1) : $vals;

        $idx = array_search((string)$value, $list, true);
        if ($idx === false) return '—';

        $count = $idx + 1; // 1..N
        return $count > 0 ? self::iconGroup(Icons::mountain(), $count) : '—';
    }
}
