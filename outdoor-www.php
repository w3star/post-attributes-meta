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

define('OUTDOOR_WWW_VERSION', '1.1.0');
define('OUTDOOR_WWW_PATH', plugin_dir_path(__FILE__));
define('OUTDOOR_WWW_URL',  plugin_dir_url(__FILE__));


// --- einfacher PSR-4 Autoloader für unser Namespace "OutdoorWww\" ---
spl_autoload_register(function($class){
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

    public function __construct()
    {
        add_action('init',                        [$this, 'register_meta']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_action('add_meta_boxes',              [$this, 'add_classic_metabox']); // Fallback nur Classic
        add_action('save_post',                   [$this, 'save_classic_metabox']); // Fallback nur Classic
        add_action('init',                        [$this, 'register_block_scripts'], 9);
        add_action('init',                        [$this, 'register_blocks'], 10);
        add_action('wp_enqueue_scripts',          [$this, 'register_view_assets']);
    }

    /** ---------- Gutenberg-Bereiche konfigurieren (frei benennbar) ---------- */
    private function meta_sections_config(): array
    {
        // 👉 Hier bestimmst du Titel & Felder deiner Bereiche. Du kannst das jederzeit erweitern.
        return [
            [
                'id'    => 'owww_general',
                'title' => 'Allgemein',
                'fields' => [
                    ['key' => 'star_rating',       'type' => 'int',    'label' => 'Rating',        'min' => 0, 'max' => 5, 'step' => 1, 'widget'=>'input'],
                    ['key' => 'star_exclusivity',  'type' => 'int',    'label' => 'Exklusivität',  'min' => 0, 'max' => 5, 'step' => 1, 'widget'=>'input'],
                    ['key' => 'star_difficulty',   'type' => 'select', 'label' => 'Schwierigkeit', 'options' => [
                        ['value' => '',      'label' => '—'],
                        ['value' => 'easy',  'label' => 'Leicht'],
                        ['value' => 'medium', 'label' => 'Mittel'],
                        ['value' => 'hard',  'label' => 'Schwer'],
                    ]],
                ],
            ],
            [
                'id'    => 'owww_time',
                'title' => 'Outdoor www | Zeiten',
                'fields' => [
                    ['key' => 'star_time_relaxed',  'type' => 'int', 'label' => 'Dauer (entspannt)',    'min' => 0, 'max' => 4000, 'step' => 1, 'widget'=>'input'],
                    ['key' => 'star_time_steady',   'type' => 'int', 'label' => 'Dauer (gemächlich)',   'min' => 0, 'max' => 4000, 'step' => 1, 'widget'=>'input'],
                    ['key' => 'star_time_moderate', 'type' => 'int', 'label' => 'Dauer (mässig)',       'min' => 0, 'max' => 4000, 'step' => 1, 'widget'=>'input'],
                    ['key' => 'star_time_fast',     'type' => 'int', 'label' => 'Dauer (schnell)',      'min' => 0, 'max' => 4000, 'step' => 1, 'widget'=>'input'],
                    ['key' => 'star_time_veryfast', 'type' => 'int', 'label' => 'Dauer (sehr schnell)', 'min' => 0, 'max' => 4000, 'step' => 1, 'widget'=>'input'],
                ],
            ],
        ];
    }

    /** ----------------------------- META REGISTRIEREN ----------------------------- */
    public function register_meta()
    {
        foreach (['post', 'page'] as $ptype) {
            foreach (self::META as $meta_key => $args) {
                register_post_meta($ptype, $meta_key, [
                    'type'              => $args['type'],
                    'single'            => $args['single'],
                    'default'           => $args['default'],
                    'show_in_rest'      => true, // ✅ nötig für Gutenberg-Panels
                    'sanitize_callback' => null,
                    'auth_callback'     => function ($allowed, $key, $post_id) {
                        return current_user_can('edit_post', (int)$post_id);
                    },
                ]);
            }
        }
    }

    /** ------------------------- EDITOR-ASSETS (Gutenberg UI) ---------------------- */
    public function enqueue_editor_assets()
    {
        // 1) Script registrieren (noch nicht enqueuen)
        wp_register_script(
            'owww-sidebar',
            plugins_url('blocks/pam-sidebar.js', __FILE__),
            // ❗ KORREKT: 'wp-plugins' (Plural!) + 'wp-editor' wird für den core/editor-Store benötigt
            ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-editor', 'wp-i18n'],
            OUTDOOR_WWW_VERSION,
            true
        );

        // 2) Konfiguration vor dem Enqueue injizieren
        wp_localize_script('owww-sidebar', 'PAM_SECTIONS', $this->meta_sections_config());

        // 3) Jetzt erst enqueuen
        wp_enqueue_script('owww-sidebar');

        // (Optional) Editor-Styles
        wp_enqueue_style(
            'owww-editor-css',
            plugins_url('blocks/editor.css', __FILE__),
            ['wp-edit-blocks'],
            OUTDOOR_WWW_VERSION
        );
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

    public function save_classic_metabox($post_id)
    {
        if (!isset($_POST['star_meta_box_nonce']) || !wp_verify_nonce($_POST['star_meta_box_nonce'], 'star_meta_box_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // ⚠️ Bugfix: KEIN doppeltes "star_"-Prefix!
        foreach (array_keys(self::META) as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /** ---------------------------- BLOCK-Editor-Skripte --------------------------- */
    public function register_block_scripts()
    {
        wp_register_script(
            'pam-summary-editor',
            plugins_url('blocks/pam-summary/index.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'],
            OUTDOOR_WWW_VERSION,
            true
        );
        wp_register_script(
            'pam-stars-editor',
            plugins_url('blocks/pam-stars/index.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n'],
            OUTDOOR_WWW_VERSION,
            true
        );
        wp_register_script(
            'pam-explorer-editor',
            plugins_url('blocks/pam-explorer/index.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            OUTDOOR_WWW_VERSION,
            true
        );
    }

    public function register_blocks()
    {
        register_block_type(__DIR__ . '/blocks/pam-summary', [
            'editor_script'   => 'pam-summary-editor',
            'style'           => 'pam-summary-style',
            'render_callback' => [$this, 'render_block_summary'],
        ]);
        register_block_type(__DIR__ . '/blocks/pam-stars', [
            'editor_script'   => 'pam-stars-editor',
            'style'           => 'pam-stars-style',
            'render_callback' => [$this, 'render_block_stars'],
        ]);
        register_block_type(__DIR__ . '/blocks/pam-explorer', [
            'editor_script'   => 'pam-explorer-editor',
            'style'           => 'pam-explorer-style',
            // WICHTIG: Kein 'script' mehr – das Frontend-View kommt über block.json::viewScript
            'render_callback' => [$this, 'render_block_explorer'],
        ]);
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

    /** ------------------------------ Block Renders ------------------------------- */
    public function render_block_summary($attributes, $content, $block)
    {
        wp_enqueue_style('pam-summary-style');
        $post_id = $block->context['postId'] ?? get_the_ID();
        if (!$post_id) return '';

        $star_rating       = (int) get_post_meta($post_id, 'star_rating', true);
        $star_exclusivity  = (int) get_post_meta($post_id, 'star_exclusivity', true);
        $star_time_relaxed = (int) get_post_meta($post_id, 'star_time_relaxed', true);
        $star_difficulty   = (string) get_post_meta($post_id, 'star_difficulty', true);

        $diff_count = $star_difficulty === 'hard' ? 3 : ($star_difficulty === 'medium' ? 2 : ($star_difficulty === 'easy' ? 1 : 0));
        $mountains_html = $diff_count ? $this->icons_group($this->svg_mountain(), $diff_count) : '—';
        $suns_html      = $star_exclusivity ? $this->icons_group($this->svg_sun(), $star_exclusivity) : '—';
        $duration_html  = '<span class="pam-icongroup">' . $this->svg_stopwatch() . '</span> ' . $this->duration_text($star_time_relaxed);

        ob_start(); ?>
        <div class="pam-box pam-summary" role="group" aria-label="<?php esc_attr_e('Zusätzliche Informationen', 'outdoor-www'); ?>">
            <div class="pam-summary__title"><?php _e('Zusätzliche Informationen', 'outdoor-www'); ?></div>
            <ul class="pam-summary__list">
                <li><span class="pam-label">Rating:</span> <?php echo $this->stars_html($star_rating); ?></li>
                <li><span class="pam-label">Schwierigkeit:</span> <?php echo $mountains_html; ?></li>
                <li><span class="pam-label">Exklusivität:</span> <?php echo $suns_html; ?></li>
                <li><span class="pam-label">Dauer:</span> <?php echo $duration_html; ?></li>
            </ul>
        </div>
    <?php
        return ob_get_clean();
    }

    public function render_block_stars($attributes, $content, $block)
    {
        wp_enqueue_style('pam-stars-style');
        $post_id = $block->context['postId'] ?? get_the_ID();
        if (!$post_id) return '';
        $star_rating = (int) get_post_meta($post_id, 'star_rating', true);
        return '<div class="pam-stars">' . $this->stars_html($star_rating) . '</div>';
    }

    /** ------------------------------- Explorer (SSR) ------------------------------ */
    private function build_url($args = [])
    {
        $url  = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $base = remove_query_arg(array_keys($_GET), $url);
        $new  = add_query_arg(array_merge($_GET, $args), $base);
        return esc_url($new);
    }

    public function render_block_explorer($attributes, $content, $block)
    {
        wp_enqueue_style('pam-explorer-style');
        // ❌ Kein wp_enqueue_script('pam-explorer-view') – viewScript kommt aus block.json

        $min_rating = isset($_GET['min_rating']) ? max(0, (int)$_GET['min_rating']) : 0;
        $min_beauty = isset($_GET['min_beauty']) ? max(0, (int)$_GET['min_beauty']) : 0;

        $d_from_i = isset($_GET['diff_from_i']) ? max(1, min(3, (int)$_GET['diff_from_i'])) : 1;
        $d_to_i   = isset($_GET['diff_to_i'])   ? max(1, min(3, (int)$_GET['diff_to_i']))   : 3;
        if ($d_from_i > $d_to_i) {
            $t = $d_from_i;
            $d_from_i = $d_to_i;
            $d_to_i = $t;
        }
        $idx2lab = [1 => 'easy', 2 => 'medium', 3 => 'hard'];
        $diff_vals = [];
        for ($i = $d_from_i; $i <= $d_to_i; $i++) $diff_vals[] = $idx2lab[$i];

        $dur_from = isset($_GET['dur_from']) ? max(0, min(720, (int)$_GET['dur_from'])) : 0;
        $dur_to   = isset($_GET['dur_to'])   ? max(0, min(720, (int)$_GET['dur_to']))   : 720;
        if ($dur_from > $dur_to) {
            $t = $dur_from;
            $dur_from = $dur_to;
            $dur_to = $t;
        }

        $cats_raw = isset($_GET['cats']) ? (array)$_GET['cats'] : [];
        $cats = array_filter(array_map('intval', $cats_raw));

        $sort = isset($_GET['star_sort']) ? sanitize_text_field($_GET['star_sort']) : 'date_desc';
        $orderby = ['date' => 'DESC'];
        $meta_key = '';

        switch ($sort) {
            case 'title_asc':
                $orderby = ['title' => 'ASC'];
                break;
            case 'title_desc':
                $orderby = ['title' => 'DESC'];
                break;
            case 'date_asc':
                $orderby = ['date' => 'ASC'];
                break;
            case 'rating_desc':
                $meta_key = 'star_rating';
                $orderby = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
                break;
            case 'rating_asc':
                $meta_key = 'star_rating';
                $orderby = ['meta_value_num' => 'ASC', 'date' => 'DESC'];
                break;
            case 'beauty_desc':
                $meta_key = 'star_exclusivity';
                $orderby = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
                break;
            case 'beauty_asc':
                $meta_key = 'star_exclusivity';
                $orderby = ['meta_value_num' => 'ASC', 'date' => 'DESC'];
                break;
            case 'dur_asc':
                $meta_key = 'star_time_relaxed';
                $orderby = ['meta_value_num' => 'ASC', 'date' => 'DESC'];
                break;
            case 'dur_desc':
                $meta_key = 'star_time_relaxed';
                $orderby = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
                break;
            default:
                $orderby = ['date' => 'DESC'];
        }

        $meta_query = [
            'relation' => 'AND',
            ['key' => 'star_rating',       'value' => $min_rating, 'compare' => '>=', 'type' => 'NUMERIC'],
            ['key' => 'star_exclusivity',  'value' => $min_beauty, 'compare' => '>=', 'type' => 'NUMERIC'],
            ['key' => 'star_time_relaxed', 'value' => [$dur_from, $dur_to], 'compare' => 'BETWEEN', 'type' => 'NUMERIC'],
        ];
        if (count($diff_vals) < 3) {
            $meta_query[] = ['key' => 'star_difficulty', 'value' => $diff_vals, 'compare' => 'IN'];
        }

        $per_page = 24;
        $paged    = max(1, (int)($_GET['star_page'] ?? 1));

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'meta_query'     => $meta_query,
            'orderby'        => $orderby,
        ];
        if ($meta_key) $args['meta_key'] = $meta_key;
        if (!empty($cats)) $args['category__in'] = $cats;

        $q = new \WP_Query($args);

        // Kategorien
        $all_cats = get_categories(['hide_empty' => false]);
        $options  = '';
        foreach ($all_cats as $c) {
            $sel = in_array((int)$c->term_id, $cats, true) ? ' selected' : '';
            $options .= '<option value="' . esc_attr($c->term_id) . '"' . $sel . '>' . esc_html($c->name) . '</option>';
        }

        $idxLabel = function ($i) {
            return $i === 1 ? 'Leicht' : ($i === 2 ? 'Mittel' : 'Schwer');
        };

        // Pagination
        $total_pages = max(1, (int)$q->max_num_pages);
        $pager_html = '';
        if ($total_pages > 1) {
            $pager_html .= '<nav class="pam-pager" role="navigation" aria-label="Seitennummerierung"><ul>';
            for ($p = 1; $p <= $total_pages; $p++) {
                $cls = $p === $paged ? ' class="is-active"' : '';
                $url = esc_url($this->build_url(['star_page' => $p]));
                $pager_html .= "<li$cls><a href=\"$url\">$p</a></li>";
            }
            $pager_html .= '</ul></nav>';
        }

        // Sort Auswahl
        $sort_options = [
            'date_desc'   => __('Neueste zuerst', 'outdoor-www'),
            'date_asc'    => __('Älteste zuerst', 'outdoor-www'),
            'rating_desc' => __('Rating absteigend', 'outdoor-www'),
            'rating_asc'  => __('Rating aufsteigend', 'outdoor-www'),
            'beauty_desc' => __('Exklusivität absteigend', 'outdoor-www'),
            'beauty_asc'  => __('Exklusivität aufsteigend', 'outdoor-www'),
            'dur_asc'     => __('Dauer: kürzeste zuerst', 'outdoor-www'),
            'dur_desc'    => __('Dauer: längste zuerst', 'outdoor-www'),
        ];
        $sort_html = '<select name="star_sort">';
        foreach ($sort_options as $value => $label) {
            $sel = (isset($_GET['star_sort']) ? $_GET['star_sort'] : 'date_desc') === $value ? ' selected' : '';
            $sort_html .= '<option value="' . esc_attr($value) . '"' . $sel . '>' . esc_html($label) . '</option>';
        }
        $sort_html .= '</select>';

        ob_start(); ?>
        <div class="pam-explorer pam-explorer--sliders" data-minmax>
            <form class="pam-explorer__filters" method="get" data-minmax-form>
                <label>Min. Rating
                    <input type="range" name="min_rating" min="0" max="5" step="1" value="<?php echo esc_attr($min_rating); ?>" aria-label="Minimales Rating (0 bis 5)" />
                    <output data-out="min_rating"><?php echo esc_html($min_rating); ?></output>
                </label>

                <label>Min. Exklusivität
                    <input type="range" name="min_beauty" min="0" max="5" step="1" value="<?php echo esc_attr($min_beauty); ?>" aria-label="Minimale Exklusivität (0 bis 5)" />
                    <output data-out="min_beauty"><?php echo esc_html($min_beauty); ?></output>
                </label>

                <label>Schwierigkeit (von/bis)
                    <div class="minmax" data-field="star_difficulty" data-min="1" data-max="3" data-step="1">
                        <input type="range" name="diff_from_i" min="1" max="3" step="1" value="<?php echo esc_attr($d_from_i); ?>" aria-label="Schwierigkeit von (1=Leicht, 3=Schwer)" />
                        <input type="range" name="diff_to_i" min="1" max="3" step="1" value="<?php echo esc_attr($d_to_i); ?>" aria-label="Schwierigkeit bis (1=Leicht, 3=Schwer)" />
                        <div class="track" aria-hidden="true">
                            <div class="range"></div>
                        </div>
                    </div>
                    <div class="minmax__labels"><output data-out="diff_from_i"><?php echo esc_html($idxLabel($d_from_i)); ?></output> – <output data-out="diff_to_i"><?php echo esc_html($idxLabel($d_to_i)); ?></output></div>
                </label>

                <label>Dauer (von/bis)
                    <div class="minmax" data-field="duration" data-min="0" data-max="720" data-step="10">
                        <input type="range" name="dur_from" min="0" max="720" step="10" value="<?php echo esc_attr($dur_from); ?>" aria-label="Dauer von (0 bis 720 Minuten)" />
                        <input type="range" name="dur_to" min="0" max="720" step="10" value="<?php echo esc_attr($dur_to); ?>" aria-label="Dauer bis (0 bis 720 Minuten)" />
                        <div class="track" aria-hidden="true">
                            <div class="range"></div>
                        </div>
                    </div>
                    <div class="minmax__labels"><output data-out="dur_from"><?php echo esc_html($this->duration_text($dur_from)); ?></output> – <output data-out="dur_to"><?php echo esc_html($this->duration_text($dur_to)); ?></output></div>
                </label>

                <label>Sortierung
                    <?php echo $sort_html; ?>
                </label>

                <label>Kategorien (Regionen)
                    <select name="cats[]" multiple size="6"><?php echo $options; ?></select>
                </label>

                <button type="submit" class="pam-explorer__apply">Anwenden</button>
            </form>

            <div class="pam-explorer__results" aria-live="polite">
                <?php if ($q->have_posts()) : ?>

                    <?php
                    $toggle = function ($key) use ($sort) {
                        $pairs = [
                            'title'            => ['title_asc', 'title_desc'],
                            'date'             => ['date_asc', 'date_desc'],
                            'star_rating'      => ['rating_asc', 'rating_desc'],
                            'star_exclusivity' => ['beauty_asc', 'beauty_desc'],
                            'dur'              => ['dur_asc', 'dur_desc'],
                        ];
                        $pair = $pairs[$key] ?? ['date_asc', 'date_desc'];
                        $next = ($sort === $pair[0]) ? $pair[1] : $pair[0];
                        return esc_url(add_query_arg(['star_sort' => $next], remove_query_arg(null)));
                    };
                    ?>

                    <table class="pam-table">
                        <thead>
                            <tr>
                                <th><a href="<?php echo $toggle('title'); ?>">Titel</a></th>
                                <th><a href="<?php echo $toggle('star_rating'); ?>">Rating</a></th>
                                <th><a href="<?php echo $toggle('star_exclusivity'); ?>">Exklusivität</a></th>
                                <th>Schwierigkeit</th>
                                <th><a href="<?php echo $toggle('dur'); ?>">Dauer</a></th>
                                <th><a href="<?php echo $toggle('date'); ?>">Datum</a></th>
                                <th>Kategorien</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($q->have_posts()) : $q->the_post();
                                $pid      = get_the_ID();
                                $title    = get_the_title();
                                $perma    = get_permalink();
                                $star_rating       = (int) get_post_meta($pid, 'star_rating', true);
                                $star_exclusivity  = (int) get_post_meta($pid, 'star_exclusivity', true);
                                $star_time_relaxed = (int) get_post_meta($pid, 'star_time_relaxed', true);
                                $star_difficulty   = (string) get_post_meta($pid, 'star_difficulty', true);
                                $diff_lbl = $star_difficulty === 'hard' ? 'Schwer' : ($star_difficulty === 'medium' ? 'Mittel' : ($star_difficulty === 'easy' ? 'Leicht' : '—'));
                                $cats_arr = get_the_category($pid);
                                $cats_txt = $cats_arr ? implode(', ', wp_list_pluck($cats_arr, 'name')) : '—';
                            ?>
                                <tr>
                                    <td><a href="<?php echo esc_url($perma); ?>"><?php echo esc_html($title); ?></a></td>
                                    <td><?php echo $this->stars_html($star_rating); ?></td>
                                    <td><?php echo $star_exclusivity ? $this->icons_group($this->svg_sun(), $star_exclusivity) : '—'; ?></td>
                                    <td><?php echo esc_html($diff_lbl); ?></td>
                                    <td><?php echo esc_html($this->duration_text($star_time_relaxed)); ?></td>
                                    <td><?php echo esc_html(get_the_date()); ?></td>
                                    <td><?php echo esc_html($cats_txt); ?></td>
                                </tr>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
                        </tbody>
                    </table>

                    <?php echo $pager_html; ?>

                <?php else: ?>
                    <div class="pam-empty">Keine Treffer.</div>
                <?php endif; ?>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}

new Outdoor_www();

// --- Lifecycle: Aktivieren/Deaktivieren/Deinstallieren ---
register_activation_hook(__FILE__,   ['Outdoor-Www\\Core\\Lifecycle', 'activate']);
register_deactivation_hook(__FILE__, ['Outdoor-Www\\Core\\Lifecycle', 'deactivate']);
register_uninstall_hook(__FILE__,    ['Outdoor-Www\\Core\\Lifecycle', 'uninstall']);
