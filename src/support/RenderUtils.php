<?php

namespace OutdoorWww\Support;

class RenderUtils
{
    public static function svg_star_filled(): string
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    }



    public static function svg_star_empty(): string
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.5" d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    }



    public static function svg_mountain(): string
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 19h18L14 7l-2 3-2-3L3 19z"/></svg>';
    }



    public static function svg_sun(): string
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4" fill="currentColor"/><g stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/><line x1="4.5" y1="4.5" x2="6.7" y2="6.7"/><line x1="17.3" y1="17.3" x2="19.5" y2="19.5"/><line x1="17.3" y1="6.7" x2="19.5" y2="4.5"/><line x1="4.5" y1="19.5" x2="6.7" y2="17.3"/></g></svg>';
    }



    public static function svg_stopwatch(): string
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="14" r="7" fill="none" stroke="currentColor" stroke-width="1.5"/><rect x="10" y="2" width="4" height="2" fill="currentColor"/><line x1="12" y1="14" x2="15" y2="10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
    }



    public static function icons_group(string $icon_html, int $count): string
    {
        $out = '<span class="pam-icongroup">';
        for ($i = 0; $i < $count; $i++) $out .= $icon_html;
        return $out . '</span>';
    }



    public static function stars_html(int $rating): string
    {
        $rating = max(0, min(5, (int)$rating));
        $out = '<span class="pam-icongroup" aria-label="Rating ' . $rating . ' von 5">';
        for ($i = 1; $i <= 5; $i++) $out .= ($i <= $rating) ? self::svg_star_filled() : self::svg_star_empty();
        return $out . '</span>';
    }


    
    public static function duration_text(int $minutes): string
    {
        $minutes = (int)$minutes;
        if ($minutes <= 0) return '—';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return $h . ' h ' . $m . ' min';
        if ($h > 0) return $h . ' h';
        return $m . ' min';
    }
}
