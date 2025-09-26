<?php

namespace OutdoorWww\Blocks;

use OutdoorWww\Blocks\Render\Summary;

class Registrar
{
    public function __construct()
    {
        add_action('init', [$this, 'register_editor_scripts'], 9);
        add_action('init', [$this, 'register_blocks'], 10);
    }

    public function register_editor_scripts(): void
    {
        // exakt wie bisher
        wp_register_script(
            'pam-summary-editor',
            plugins_url('blocks/pam-summary/index.js', dirname(__DIR__, 2) . '/outdoor-www.php'),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'],
            defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : false,
            true
        );
    }

    public function register_blocks(): void
    {
        register_block_type(__DIR__ . '/../../blocks/pam-summary', [
            'editor_script'   => 'pam-summary-editor',
            'style'           => 'pam-summary-style',
            'render_callback' => [Summary::class, 'render'],
        ]);
    }
}
