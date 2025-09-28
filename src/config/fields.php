<?php

namespace OutdoorWww\Config;

/**
 * Eine Quelle für die "rollenbasierten" Meta-Keys.
 * Wenn du Keys umbenennst, NUR HIER anpassen.
 */
final class Fields
{
    public static function rating(): string
    {
        return 'star_rating';
    }



    public static function difficulty(): string
    {
        return 'star_difficulty';
    }
    
    
    
    public static function exclusivity(): string
    {
        return 'star_exclusivity';
    }
    
    
    
    public static function duration(): string
    {
        return 'star_time_relaxed';
    }
}
