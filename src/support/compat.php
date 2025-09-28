<?php

namespace OutdoorWww\Support;

/**
 * Leitet alte Helfer-Aufrufe auf die neuen Html/Icons-Methoden um.
 * Entfernen, sobald alles umgestellt ist.
 */
final class Compat
{
    public static function icons_group($iconHtml, $count)
    {
        return Html::iconGroup((string)$iconHtml, (int)$count);
    }



    public static function stars_html($rating)
    {
        return Html::stars((int)$rating);
    }
    
    
    
    public static function duration_text($minutes)
    {
        return Html::durationText((int)$minutes);
    }

    
    
    public static function svg_sun()
    {
        return Icons::sun();
    }
    
    
    
    
    public static function svg_mountain()
    {
        return Icons::mountain();
    }
    
    
    
    
    public static function svg_stopwatch()
    {
        return Icons::stopwatch();
    }
}

/** Backwards-Alias: alte Klassenreferenz weiter nutzbar */
class RenderUtils extends Compat {}
