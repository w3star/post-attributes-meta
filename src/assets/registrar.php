<?php
namespace OutdoorWww\Assets;

class Registrar
{
    public function __construct()
    {
        add_action('init', [$this, 'registerFrontendAssets']); // Styles u. ggf. view-scripts
        add_action('init', [$this, 'registerBlockEditorAssets'], 9); // nur Registrieren (falls gebraucht)
    }



    public function registerFrontendAssets(): void
    {
        // Styles (wie zuvor in register_view_assets)
        wp_register_style('owww-summary-style',  plugins_url('blocks/owww-summary/style.css', OUTDOOR_WWW_FILE), [], OUTDOOR_WWW_VERSION);
        wp_register_style('owww-stars-style',    plugins_url('blocks/owww-stars/style.css',   OUTDOOR_WWW_FILE), [], OUTDOOR_WWW_VERSION);
        wp_register_style('owww-explorer-style', plugins_url('blocks/owww-explorer/style.css',OUTDOOR_WWW_FILE), [], OUTDOOR_WWW_VERSION);
        // WICHTIG: view.js kommt über block.json::viewScript – kein doppeltes Enqueue
    }


    
    public function registerBlockEditorAssets(): void
    {
        // Falls du Editor-Scripts zentral registrieren willst (optional)
        // Wir registrieren sie schon in Blocks\Registrar – diesen Teil könntest du sogar weglassen.
    }
}
