<?php

namespace OutdoorWww\Blocks;

use OutdoorWww\Blocks\Render\Summary;
use OutdoorWww\Blocks\Render\Stars;
use OutdoorWww\Blocks\Render\Explorer;



class Registrar
{
    public function __construct()
    {
        add_action('init', [$this, 'register_editor_scripts'], 9);
        add_action('init', [$this, 'register_blocks'], 10);
    }

    public function register_editor_scripts(): void
    {
        // Summary (bereits vorhanden)
        wp_register_script(
            'pam-summary-editor',
            plugins_url('blocks/pam-summary/index.js', OUTDOOR_WWW_FILE),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );

        // ⭐ Stars (NEU)
        wp_register_script(
            'pam-stars-editor',
            plugins_url('blocks/pam-stars/index.js', OUTDOOR_WWW_FILE),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );

        // Explorer (NEU)
        wp_register_script(
            'pam-explorer-editor',
            plugins_url('blocks/pam-explorer/index.js', OUTDOOR_WWW_FILE),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );
    }

    public function register_blocks(): void
    {
        // Summary (bestehend)
        register_block_type(plugin_dir_path(OUTDOOR_WWW_FILE) . 'blocks/pam-summary', [
            'editor_script'   => 'pam-summary-editor',
            'style'           => 'pam-summary-style',
            'render_callback' => [Summary::class, 'render'],
        ]);

        // ⭐ Stars (NEU)
        register_block_type(plugin_dir_path(OUTDOOR_WWW_FILE) . 'blocks/pam-stars', [
            'editor_script'   => 'pam-stars-editor',
            'style'           => 'pam-stars-style',
            'render_callback' => [Stars::class, 'render'],
        ]);

        // Explorer (NEU)
        register_block_type(plugin_dir_path(OUTDOOR_WWW_FILE) . 'blocks/pam-explorer', [
            'editor_script'   => 'pam-explorer-editor',
            'style'           => 'pam-explorer-style',
            // KEIN 'script' → view.js kommt aus block.json::viewScript
            'render_callback' => [Explorer::class, 'render'],
        ]);
    }
}
