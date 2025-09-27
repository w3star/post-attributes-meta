<?php
namespace OutdoorWww\Config;

final class Meta
{
    /**
     * Definition aller Post-Meta-Felder des Plugins
     */
    public static function defaults(): array
    {
        return [
            'star_distance'      => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_ascent'        => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_descent'       => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_rating'        => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_exclusivity'   => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_difficulty'    => ['type' => 'string',  'single' => true, 'default' => ''],
            'star_requirements'  => ['type' => 'string',  'single' => true, 'default' => ''],

            // Touren-Dauer/Zeiten je nach Geschwindigkeit der Gruppe
            'star_time_relaxed'  => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_time_steady'   => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_time_moderate' => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_time_fast'     => ['type' => 'integer', 'single' => true, 'default' => 0],
            'star_time_veryfast' => ['type' => 'integer', 'single' => true, 'default' => 0],
        ];
    }
}
