<?php

namespace OutdoorWww\Config;

/**
 * Eine Quelle für die "rollenbasierten" Meta-Keys.
 * Wenn du Keys umbenennst, NUR HIER anpassen.
 */
final class Fields
{
    public static function rating(): string        { return 'owww_rating'; }
    public static function exclusivity(): string   { return 'owww_exclusivity'; }
    public static function duration(): string      { return 'owww_time_relaxed'; }
    public static function difficulty(): string    { return 'owww_difficulty_hiking'; }

    public static function season(): string        { return 'owww_season'; }
    public static function landscape(): string     { return 'owww_landscape'; }
    public static function region(): string        { return 'owww_region'; }
    public static function routeType(): string     { return 'owww_route_type'; }
    public static function start(): string         { return 'owww_start'; }
    public static function target(): string        { return 'owww_target'; }
    public static function ascent(): string        { return 'owww_ascent'; }
    public static function descent(): string       { return 'owww_descent'; }
    public static function length(): string        { return 'owww_length'; }
    public static function trailType(): string     { return 'owww_trail_type'; }
    public static function map(): string           { return 'owww_map'; }
    public static function webLink(): string       { return 'owww_web_link'; }
    public static function gpx(): string           { return 'owww_gpx'; }
    public static function imageSource(): string   { return 'owww_image_source'; }
    public static function imageAuthor(): string   { return 'owww_image_author'; }
    public static function imageLicense(): string  { return 'owww_image_license'; }
    public static function video(): string         { return 'owww_video'; }
    public static function author(): string        { return 'owww_author'; }    
    public static function date(): string          { return 'owww_date'; }
    public static function lastUpdate(): string    { return 'owww_last_update'; }
    public static function status(): string        { return 'owww_status'; }


    // (optional) weitere Felder:
    // public static function requirements(): string { return 'owww_requirements'; }
    // public static function distance(): string     { return 'owww_distance'; }
}
