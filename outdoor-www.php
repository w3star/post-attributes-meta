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
     * Hinweis: Keys sind bereits mit "star_" benannt – NICHT nochmals prefixen.
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
        //add_action('init',                        [$this, 'register_blocks'], 10);
        add_action('wp_enqueue_scripts',          [$this, 'register_view_assets']);

        // OOP: Meta-Registrierung kapseln
        new \OutdoorWww\Meta\Registrar(self::META, ['post', 'page']);

        // OOP: Gutenberg-Sidebar + Sections
        new \OutdoorWww\Admin\Sidebar(\OutdoorWww\Admin\Sidebar::defaultSections());

        // OOP: Blocks – Schritt 1: nur Summary in OOP
        new \OutdoorWww\Blocks\Registrar();
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



    public function register_view_assets()
    {
        wp_register_style('pam-summary-style',  plugins_url('blocks/pam-summary/style.css', __FILE__), [], OUTDOOR_WWW_VERSION);
        wp_register_style('pam-stars-style',    plugins_url('blocks/pam-stars/style.css', __FILE__), [], OUTDOOR_WWW_VERSION);
        wp_register_style('pam-explorer-style', plugins_url('blocks/pam-explorer/style.css', __FILE__), [], OUTDOOR_WWW_VERSION);
        // ❌ Kein wp_register_script('pam-explorer-view' ...) mehr – doppelte Ladung vermeiden
    }

    /** ------------------------------- SVG ICONS ---------------------------------- */
    private function svg_star_filled()
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    }

    private function svg_star_empty()
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.5" d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    }
    
    private function svg_mountain()
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 19h18L14 7l-2 3-2-3L3 19z"/></svg>';
    }
    
    private function svg_sun()
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4" fill="currentColor"/><g stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/><line x1="4.5" y1="4.5" x2="6.7" y2="6.7"/><line x1="17.3" y1="17.3" x2="19.5" y2="19.5"/><line x1="17.3" y1="6.7" x2="19.5" y2="4.5"/><line x1="4.5" y1="19.5" x2="6.7" y2="17.3"/></g></svg>';
    }
    
    private function svg_stopwatch()
    {
        return '<svg class="pam-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="14" r="7" fill="none" stroke="currentColor" stroke-width="1.5"/><rect x="10" y="2" width="4" height="2" fill="currentColor"/><line x1="12" y1="14" x2="15" y2="10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
    }

    private function icons_group($icon_html, $count)
    {
        $out = '<span class="pam-icongroup">';
        for ($i = 0; $i < $count; $i++) $out .= $icon_html;
        return $out . '</span>';
    }
    
    private function stars_html($star_rating)
    {
        $star_rating = max(0, min(5, (int)$star_rating));
        $out = '<span class="pam-icongroup" aria-label="Rating ' . $star_rating . ' von 5">';
        for ($i = 1; $i <= 5; $i++) $out .= ($i <= $star_rating) ? $this->svg_star_filled() : $this->svg_star_empty();
        return $out . '</span>';
    }
    
    private function duration_text($minutes)
    {
        $minutes = (int)$minutes;
        if ($minutes <= 0) return '—';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return $h . ' h ' . $m . ' min';
        if ($h > 0) return $h . ' h';
        return $m . ' min';
    }



    /** ------------------------------- Explorer (SSR) ------------------------------ */
    private function build_url($args = [])
    {
        $url  = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $base = remove_query_arg(array_keys($_GET), $url);
        $new  = add_query_arg(array_merge($_GET, $args), $base);
        return esc_url($new);
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
