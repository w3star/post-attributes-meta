<?php

namespace OutdoorWww\Editor;

use OutdoorWww\Config\Meta as MetaConfig;
use OutdoorWww\Editor;


/**
 * Fügt dem Block-Editor eine Sidebar hinzu
 *
 * @package OutdoorWww\Editor
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
        add_action('enqueue_block_editor_assets', function () {
            // alte Handles stilllegen, falls sie existieren
            foreach (['pam-editor-panel', 'editor-panel', 'pam-sidebar-panels'] as $h) {
                if (wp_script_is($h, 'enqueued') || wp_script_is($h, 'registered')) {
                    wp_dequeue_script($h);
                    wp_deregister_script($h);
                }
            }
        }, 1);
    }


    /**
     * Assets für den Block-Editor laden
     */
    public function enqueue(): void
    {
        // Nur im Beitrags-Editor laden
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen || $screen->base !== 'post' || $screen->post_type !== 'post') {
            return;
        }



        // 1) Script registrieren
        wp_register_script(
            'owww-editor',
            plugins_url('includes/editor/editor.js', OUTDOOR_WWW_FILE), // dein neuer Pfad
            ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-editor', 'wp-i18n'],
            OUTDOOR_WWW_VERSION,
            true
        );

        // 2) Panels aus meta.php liefern (Single Source of Truth)
        wp_localize_script(
            'owww-editor',
            'OWWW_PANELS',
            $this->sections // liest src/config/meta.php
        );

        // 3) Enqueue
        wp_enqueue_script('owww-editor');

        // 4) optional: kompaktes Editor-CSS
        wp_enqueue_style(
            'owww-editor-css',
            plugins_url('includes/editor/editor.css', OUTDOOR_WWW_FILE),
            ['wp-edit-blocks'],
            OUTDOOR_WWW_VERSION
        );
        // (optional) Editor-CSS nur laden, wenn vorhanden
        $editor_css = plugins_url('assets/editor.css', dirname(__DIR__, 2) . '/outdoor-www.php');
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
