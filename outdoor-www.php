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
    /**
     * Alle verwendeten Meta-Keys (inkl. Typ/Default).
     */
    const META = [
        'star_rating'        => ['type' => 'integer', 'single' => true, 'default' => 0],
        'star_difficulty'    => ['type' => 'string',  'single' => true, 'default' => ''],
        'star_exclusivity'   => ['type' => 'integer', 'single' => true, 'default' => 0],

        'star_time_relaxed'  => ['type' => 'integer', 'single' => true, 'default' => 0],
        'star_time_steady'   => ['type' => 'integer', 'single' => true, 'default' => 0],
        'star_time_moderate' => ['type' => 'integer', 'single' => true, 'default' => 0],
        'star_time_fast'     => ['type' => 'integer', 'single' => true, 'default' => 0],
        'star_time_veryfast' => ['type' => 'integer', 'single' => true, 'default' => 0],
    ];


    /** -------------------------- Konstruktor & Hooks -------------------------- */
    public function __construct()
    {

        add_action('add_meta_boxes',              [$this, 'add_classic_metabox']); // Fallback nur Classic
        add_action('save_post',                   [$this, 'save_classic_metabox']); // Fallback nur Classic
        //add_action('wp_enqueue_scripts',          [$this, 'register_view_assets']);

        // OOP: Meta-Registrierung kapseln
        new \OutdoorWww\Meta\Registrar(self::META, ['post', 'page']);

        // OOP: Gutenberg-Sidebar + Sections
        new \OutdoorWww\Admin\Sidebar(\OutdoorWww\Admin\Sidebar::defaultSections());

        // OOP: Blocks – Schritt 1: nur Summary in OOP
        new \OutdoorWww\Blocks\Registrar();

        new \OutdoorWww\Assets\Registrar();

    }



    /** ----------------------- CLASSIC EDITOR: Fallback-Metabox --------------------- */
    public function add_classic_metabox()
    {
        // Nur anzeigen, wenn der Classic-Editor aktiv ist
        if (function_exists('use_block_editor_for_post_type') && use_block_editor_for_post_type('post')) {
            return;
        }
        foreach (['post', 'page'] as $ptype) {
            add_meta_box(
                'star_meta_box',
                __('Zusatzinfos (Fallback)', 'outdoor-www'),
                [$this, 'render_classic_metabox'],
                $ptype,
                'side',
                'default'
            );
        }
    }

    public function render_classic_metabox($post)
    {
        wp_nonce_field('star_meta_box_save', 'star_meta_box_nonce');

        $vals = [];
        foreach (array_keys(self::META) as $k) {
            $vals[$k] = get_post_meta($post->ID, $k, true);
        }
?>
        <p><label>Rating (0–5)<br>
                <input type="number" name="star_rating" value="<?php echo esc_attr((int)($vals['star_rating'] ?? 0)); ?>" min="0" max="5" step="1">
            </label></p>

        <p><label>Schwierigkeit<br>
                <select name="star_difficulty">
                    <?php $cur = (string)($vals['star_difficulty'] ?? ''); ?>
                    <option value="" <?php selected($cur, '');      ?>>—</option>
                    <option value="easy" <?php selected($cur, 'easy');  ?>>Leicht</option>
                    <option value="medium" <?php selected($cur, 'medium'); ?>>Mittel</option>
                    <option value="hard" <?php selected($cur, 'hard');  ?>>Schwer</option>
                </select>
            </label></p>

        <p><label>Exklusivität (0–5)<br>
                <input type="number" name="star_exclusivity" value="<?php echo esc_attr((int)($vals['star_exclusivity'] ?? 0)); ?>" min="0" max="5" step="1">
            </label></p>

        <p><label>Dauer (Minuten)<br>
                <input type="number" name="star_time_relaxed" value="<?php echo esc_attr((int)($vals['star_time_relaxed'] ?? 0)); ?>" min="0" step="1">
            </label></p>
    <?php
    }


    /**
     * Speichern der Metadaten aus der klassischen Metabox
     */
    public function save_classic_metabox($post_id)
    {
        if (!isset($_POST['star_meta_box_nonce']) || !wp_verify_nonce($_POST['star_meta_box_nonce'], 'star_meta_box_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        foreach (array_keys(self::META) as $key) {
            if (isset($_POST[$key])) {
                $type = self::META[$key]['type'] ?? 'string';
                $clean = \OutdoorWww\Meta\Registrar::sanitizeForKey($key, $type, $_POST[$key]);
                update_post_meta($post_id, $key, $clean);
            }
        }
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
