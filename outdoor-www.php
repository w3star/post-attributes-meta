<?php

/**
 * Outdoor www - A WordPress plugin for outdoor activity planning and execution
 *
 * @link        https://w3star.ch/outdoor-www
 * @since       1.1.0
 * @package     Outdoor_www
 *
 * Plugin Name: Outdoor www
 * Plugin URI:  https://w3star.ch/outdoor-www
 * Description: Categorization and presentation of outdoor adventures to support the planning and execution of hikes, bike tours, and other outdoor activities.
 *
 * Version:     1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * Author:      w3star
 * Author URI:  https://w3star.ch
 *
 * Text Domain: outdoor-www
 * Domain Path: /languages
 *
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

use OutdoorWww\Config\Meta as MetaConfig;



if (!defined('ABSPATH')) exit;

define('OUTDOOR_WWW_FILE', __FILE__); // zuverlässiger Verweis auf die Hauptdatei
define('OUTDOOR_WWW_VERSION', '1.1.0');
define('OUTDOOR_WWW_PATH', plugin_dir_path(__FILE__));
define('OUTDOOR_WWW_URL',  plugin_dir_url(__FILE__));


// --- einfacher PSR-4 Autoloader für unser Namespace "OutdoorWww\" ---
spl_autoload_register(function ($class) {
    if (strpos($class, 'OutdoorWww\\') !== 0) return;
    $rel = str_replace(['OutdoorWww\\', '\\'], ['', '/'], $class);
    $file = __DIR__ . '/src/' . $rel . '.php';
    if (is_file($file)) require $file;
});



class Outdoor_www
{
    /** -------------------------- Konstruktor & Hooks -------------------------- */
    public function __construct()
    {
        // 1) Meta-Registrierung (Defs kommen jetzt aus Config\Meta)
        //new \OutdoorWww\Meta\Registrar(MetaConfig::defaults(), ['post', 'page']);
        new \OutdoorWww\Meta\Registrar(MetaConfig::defaults(), ['post']);

        // 2) Gutenberg-Sidebar (weiterhin deine Sections)
        new \OutdoorWww\Admin\Sidebar(\OutdoorWww\Admin\Sidebar::defaultSections());

        // 3) Frontend/Editor assets
        new \OutdoorWww\Assets\Registrar();

        // 4) Classic-Editor-Fallback als eigene Klasse
        new \OutdoorWww\Admin\ClassicMetaBox(MetaConfig::defaults());

        // 5) Blocks
        new \OutdoorWww\Blocks\Registrar();
    }
}



new Outdoor_www();

// --- Lifecycle-Hooks: Klasse beim Aufruf explizit laden ---
register_activation_hook(
    __FILE__,
    function () {
        require_once __DIR__ . '/src/Core/Lifecycle.php';
        OutdoorWww\Core\Lifecycle::activate();
    }
);

register_deactivation_hook(
    __FILE__,
    function () {
        require_once __DIR__ . '/src/Core/Lifecycle.php';
        OutdoorWww\Core\Lifecycle::deactivate();
    }
);
