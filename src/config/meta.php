<?php

/**
 * Post-Meta-Felder des Plugins
 * Posts können mittels Outdoor-Meta-Feldern angereichert werden
 *
 * @package OutdoorWww\Config
 */

namespace OutdoorWww\Config;



final class Meta
{
    // Gruppentitel (id => Label)
    public static function groups(): array
    {
        return [
            'general' => 'Allgemein',
            'times'   => 'Zeiten',
        ];
    }


    
    /**
     * Definition aller Post-Meta-Felder des Plugins
     */
    public static function defaults(): array
    {
        return [
            // Input Meta-Felder für Outdoor-Posts
            'owww_distance'      => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'general', 'ui' => ['label' => 'Distanz',              'min' => 0, 'max' => 1000,  'step' => 10, 'widget' => 'input']],
            'owww_ascent'        => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'general', 'ui' => ['label' => 'Aufstieg',             'min' => 0, 'max' => 10000, 'step' => 10, 'widget' => 'input']],
            'owww_descent'       => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'general', 'ui' => ['label' => 'Abstieg',              'min' => 0, 'max' => 10000, 'step' => 10, 'widget' => 'input']],
            'owww_rating'        => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'general', 'ui' => ['label' => 'Rating',               'min' => 0, 'max' => 5,     'step' => 1,  'widget' => 'input']],
            'owww_exclusivity'   => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'general', 'ui' => ['label' => 'Exklusivität',         'min' => 0, 'max' => 5,     'step' => 1,  'widget' => 'input']],

            // Touren-Dauer/Zeiten je nach Geschwindigkeit der Gruppe
            'owww_days'          => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'times',   'ui' => ['label' => 'Anzahl Tage',          'min' => 0, 'max' => 7,     'step' => 1,  'widget' => 'input']],

            'owww_time_relaxed'  => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'times',   'ui' => ['label' => 'Dauer (entspannt)',    'min' => 0, 'max' => 4000,  'step' => 1,  'widget' => 'input']],
            'owww_time_steady'   => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'times',   'ui' => ['label' => 'Dauer (gemächlich)',   'min' => 0, 'max' => 4000,  'step' => 1,  'widget' => 'input']],
            'owww_time_moderate' => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'times',   'ui' => ['label' => 'Dauer (mässig)',       'min' => 0, 'max' => 4000,  'step' => 1,  'widget' => 'input']],
            'owww_time_fast'     => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'times',   'ui' => ['label' => 'Dauer (schnell)',      'min' => 0, 'max' => 4000,  'step' => 1,  'widget' => 'input']],
            'owww_time_veryfast' => ['type' => 'integer', 'single' => true, 'default' => 0, 'group' => 'times',   'ui' => ['label' => 'Dauer (sehr schnell)', 'min' => 0, 'max' => 4000,  'step' => 1,  'widget' => 'input']],

            // Dopdown Meta-Felder für Outdoor-Posts
            'owww_difficulty_hiking'    => ['type' => 'string',  'single' => true, 'default' => '', 'group' => 'general', 'ui' => ['label' => 'Schwierigkeit', 'widget' => 'select', 'options' => [
                        ['value' => '',   'label' => '—'],
                        ['value' => 'T1', 'label' => 'T1'],
                        ['value' => 'T2', 'label' => 'T2'],
                        ['value' => 'T3', 'label' => 'T3'],
                        ['value' => 'T4', 'label' => 'T4'],
                        ['value' => 'T5', 'label' => 'T5'],
                        ['value' => 'T6', 'label' => 'T6'],
                    ]
                ]
            ],
            'owww_requirements'  => ['type' => 'string',  'single' => true, 'default' => '', 'group' => 'general', 'ui' => ['label' => 'phys. Anforderungen', 'widget' => 'select', 'options' => [
                        ['value' => '',   'label' => '—'],
                        ['value' => 'R1', 'label' => 'sehr leicht'],
                        ['value' => 'R2', 'label' => 'leicht'],
                        ['value' => 'R3', 'label' => 'mittel'],
                        ['value' => 'R4', 'label' => 'schwer'],
                        ['value' => 'R5', 'label' => 'sehr schwer'],
                    ]
                ]
            ],

        ];
    }
}
