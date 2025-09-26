<?php

namespace OutdoorWww\Admin;


/**
 * Fügt dem Block-Editor eine Sidebar hinzu
 *
 * @package OutdoorWww\Admin
 */
class Sidebar
{
    /** @var array[] Sektionen/Konfiguration */
    private array $sections;


    /**
     * Konstruktor
     *
     * @param array[] $sections Sektionen/Konfiguration
     */
    public function __construct(array $sections)
    {
        $this->sections = $sections;

        // Nur im Block-Editor laden
        add_action('enqueue_block_editor_assets', [$this, 'enqueue']);
    }


    /**
     * Assets für den Block-Editor laden
     */
    public function enqueue(): void
    {
        // Sidebar-Script (unser Panel)
        wp_register_script(
            'owww-sidebar',
            plugins_url('blocks/pam-sidebar.js', dirname(__DIR__, 2) . '/outdoor-www.php'),
            ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-editor', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );

        // Konfiguration ins Script schieben
        wp_localize_script('owww-sidebar', 'PAM_SECTIONS', $this->sections);

        wp_enqueue_script('owww-sidebar');

        // (optional) Editor-CSS nur laden, wenn vorhanden
        $editor_css = plugins_url('assets/editor.css', dirname(__DIR__, 2) . '/outdoor-www.php');
        // wenn du die Datei sicher hast, kannst du die Existenzprüfung weglassen:
        wp_enqueue_style('owww-editor-css', $editor_css, ['wp-edit-blocks'], defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false);
    }



    /**
     * Hilfs-Factory: Default-Sektionen – kann extern überschrieben werden
     */
    public static function defaultSections(): array
    {
        return [
            [
                'id'    => 'owww_general',
                'title' => 'Allgemein',
                'fields' => [
                    ['key' => 'star_distance',     'type' => 'int',    'label' => 'Distanz',      'min' => 0, 'max' => 1000, 'step' => 10, 'widget' => 'input'],
                    ['key' => 'star_ascent',       'type' => 'int',    'label' => 'Aufstieg',     'min' => 0, 'max' => 10000, 'step' => 10, 'widget' => 'input'],
                    ['key' => 'star_descent',      'type' => 'int',    'label' => 'Abstieg',      'min' => 0, 'max' => 10000, 'step' => 10, 'widget' => 'input'],
                    ['key' => 'star_rating',       'type' => 'int',    'label' => 'Rating',       'min' => 0, 'max' => 5, 'step' => 1, 'widget' => 'input'],
                    ['key' => 'star_exclusivity',  'type' => 'int',    'label' => 'Exklusivität', 'min' => 0, 'max' => 5, 'step' => 1, 'widget' => 'input'],
                    [
                        'key' => 'star_difficulty',
                        'type' => 'select',
                        'label' => 'tech. Schwierigkeit',
                        'options' => [
                            ['value' => '',   'label' => '—'],
                            ['value' => 'T1', 'label' => 'T1'],
                            ['value' => 'T2', 'label' => 'T2'],
                            ['value' => 'T3', 'label' => 'T3'],
                            ['value' => 'T4', 'label' => 'T4'],
                            ['value' => 'T5', 'label' => 'T5'],
                            ['value' => 'T6', 'label' => 'T6'],
                        ]
                    ],
                    [
                        'key' => 'star_requirements',
                        'type' => 'select',
                        'label' => 'phys. Anforderungen',
                        'options' => [
                            ['value' => '',          'label' => '—'],
                            ['value' => 'very easy', 'label' => 'sehr leicht'],
                            ['value' => 'easy',      'label' => 'leicht'],
                            ['value' => 'medium',    'label' => 'mittel'],
                            ['value' => 'hard',      'label' => 'schwer'],
                            ['value' => 'very hard', 'label' => 'sehr schwer'],
                        ]
                    ],
                ],
            ],
            [
                'id'    => 'owww_time',
                'title' => 'Outdoor www | Zeiten',
                'fields' => [
                    ['key' => 'star_days',          'type' => 'int', 'label' => 'Anzahl Tage',          'min' => 0, 'max' => 7,    'step' => 1, 'widget' => 'input'],
                    ['key' => 'star_time_relaxed',  'type' => 'int', 'label' => 'Dauer (entspannt)',    'min' => 0, 'max' => 4000, 'step' => 1, 'widget' => 'input'],
                    ['key' => 'star_time_steady',   'type' => 'int', 'label' => 'Dauer (gemächlich)',   'min' => 0, 'max' => 4000, 'step' => 1, 'widget' => 'input'],
                    ['key' => 'star_time_moderate', 'type' => 'int', 'label' => 'Dauer (mässig)',       'min' => 0, 'max' => 4000, 'step' => 1, 'widget' => 'input'],
                    ['key' => 'star_time_fast',     'type' => 'int', 'label' => 'Dauer (schnell)',      'min' => 0, 'max' => 4000, 'step' => 1, 'widget' => 'input'],
                    ['key' => 'star_time_veryfast', 'type' => 'int', 'label' => 'Dauer (sehr schnell)', 'min' => 0, 'max' => 4000, 'step' => 1, 'widget' => 'input'],
                ],
            ],
        ];
    }
}
