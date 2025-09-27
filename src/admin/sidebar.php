<?php

namespace OutdoorWww\Admin;

use OutdoorWww\Config\Meta as MetaConfig;


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
    public function __construct()
    {
        $this->sections = $sections ?? self::sectionsFromMeta(
            MetaConfig::defaults(),
            MetaConfig::groups()
        );

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



    /** Baut Gutenberg-Sections aus Meta-Defs + Gruppen */
    public static function sectionsFromMeta(array $defs, array $groups): array
    {
        $byGroup = [];
        foreach ($defs as $key => $def) {
            $g = $def['group'] ?? 'general';
            $ui = $def['ui']    ?? ['label' => $key, 'widget' => 'input'];
            $field = [
                'key'    => $key,
                'type'   => ($def['type'] ?? 'string') === 'integer' ? 'int' : 'text',
                'label'  => $ui['label'] ?? $key,
                'widget' => $ui['widget'] ?? 'input',
            ];
            // range/input Details:
            foreach (['min', 'max', 'step', 'options'] as $k) {
                if (isset($ui[$k])) $field[$k] = $ui[$k];
            }
            $byGroup[$g]['fields'][] = $field;
        }
        // Titel einsetzen
        $sections = [];
        foreach ($byGroup as $gid => $data) {
            $sections[] = [
                'id'     => 'owww_' . $gid,
                'title'  => $groups[$gid] ?? ucfirst($gid),
                'fields' => array_values($data['fields'] ?? []),
            ];
        }
        return $sections;
    }
}
