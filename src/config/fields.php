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
        return 'owww_rating';
    }
    public static function exclusivity(): string
    {
        return 'owww_exclusivity';
    }
    public static function duration(): string
    {
        return 'owww_time_relaxed';
    }
    public static function difficulty(): string
    {
        return 'owww_difficulty_hiking';
    }

    // (optional) weitere Felder:
    // public static function requirements(): string { return 'owww_requirements'; }
    // public static function distance(): string     { return 'owww_distance'; }
}
