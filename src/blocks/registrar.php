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
            'owww-summary-editor',
            plugins_url('blocks/owww-summary/index.js', OUTDOOR_WWW_FILE),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );

        // ⭐ Stars (NEU)
        wp_register_script(
            'owww-stars-editor',
            plugins_url('blocks/owww-stars/index.js', OUTDOOR_WWW_FILE),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );

        // Explorer (NEU)
        wp_register_script(
            'owww-explorer-editor',
            plugins_url('blocks/owww-explorer/index.js', OUTDOOR_WWW_FILE),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );
    }

    public function register_blocks(): void
    {
        // Summary (bestehend)
        register_block_type(plugin_dir_path(OUTDOOR_WWW_FILE) . 'blocks/owww-summary', [
            'editor_script'   => 'owww-summary-editor',
            'style'           => 'owww-summary-style',
            'render_callback' => [Summary::class, 'render'],
        ]);

        // ⭐ Stars (NEU)
        register_block_type(plugin_dir_path(OUTDOOR_WWW_FILE) . 'blocks/owww-stars', [
            'editor_script'   => 'owww-stars-editor',
            'style'           => 'owww-stars-style',
            'render_callback' => [Stars::class, 'render'],
        ]);

        // Explorer (NEU)
        register_block_type(plugin_dir_path(OUTDOOR_WWW_FILE) . 'blocks/owww-explorer', [
            'editor_script'   => 'owww-explorer-editor',
            'style'           => 'owww-explorer-style',
            // KEIN 'script' → view.js kommt aus block.json::viewScript
            'render_callback' => [Explorer::class, 'render'],
        ]);
    }
}
