<?php

/**
 * Plugin Name: Post Attributes Meta (Explorer + Summary) — v1.10.1
 * Description: Meta-Felder (Rating, Schwierigkeit, Schönheit, Dauer) + Admin-Panel & Fallback-Metabox, Summary-/Sterne-Blöcke, Explorer (SSR) mit Dual-Slider (Fix: aktiver Griff), Sortierung & Pagination.
 * Version: 1.10.1
 * Author: Du
 * Text Domain: post-attributes-meta
 */

if (! defined('ABSPATH')) exit;

class PAM_Explorer_v1101
{
    const META = [
        'rating'       => ['type' => 'integer', 'single' => true, 'default' => 0],
        'difficulty'   => ['type' => 'string',  'single' => true, 'default' => ''],
        'beauty'       => ['type' => 'integer', 'single' => true, 'default' => 0],
        'duration_min' => ['type' => 'integer', 'single' => true, 'default' => 0],
    ];

    public function __construct()
    {
        add_action('init', [$this, 'register_meta']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_panel']);
        add_action('add_meta_boxes', [$this, 'add_classic_metabox']); // Fallback
        add_action('save_post', [$this, 'save_classic_metabox']);     // Fallback
        add_action('init', [$this, 'register_block_scripts'], 9);
        add_action('init', [$this, 'register_blocks'], 10);
        add_action('wp_enqueue_scripts', [$this, 'register_view_assets']);
    }

    public function register_meta()
    {
        foreach (['post', 'page'] as $ptype) {
            register_post_meta($ptype, 'pam_rating', [
                'type'              => 'integer',
                'single'            => true,
                'default'           => 0,
                'show_in_rest'      => true,
                'sanitize_callback' => null,
                // KORREKT: Post-ID kommt sicher an; prüfe edit_post-Recht des aktuellen Users
                'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', (int) $post_id);
                },
            ]);
            register_post_meta($ptype, 'pam_difficulty', [
                'type'              => 'string',
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => true,
                'sanitize_callback' => null,
                'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', (int) $post_id);
                },
            ]);
            register_post_meta($ptype, 'pam_beauty', [
                'type'              => 'integer',
                'single'            => true,
                'default'           => 0,
                'show_in_rest'      => true,
                'sanitize_callback' => null,
                'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', (int) $post_id);
                },
            ]);
            register_post_meta($ptype, 'pam_duration_min', [
                'type'              => 'integer',
                'single'            => true,
                'default'           => 0,
                'show_in_rest'      => true,
                'sanitize_callback' => null,
                'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', (int) $post_id);
                },
            ]);
        }
    }

    public function sanitize_rating($val)
    {
        $val = (int) $val;
        return max(0, min(5, $val));
    }
    public function sanitize_beauty($val)
    {
        $val = (int) $val;
        return max(0, min(5, $val));
    }
    public function sanitize_duration_min($val)
    {
        $val = (int) $val;
        return max(0, $val);
    }
    public function sanitize_difficulty($val)
    {
        $val = sanitize_text_field($val);
        $allowed = ['', 'easy', 'medium', 'hard'];
        return in_array($val, $allowed, true) ? $val : '';
    }

    // ------- Classic Fallback Metabox (für Schwierigkeit / alle Metas) -------
    public function add_classic_metabox()
    {
        foreach (['post', 'page'] as $ptype) {
            add_meta_box('pam_meta_box', __('Zusatzinfos (Fallback)', 'post-attributes-meta'), [$this, 'render_classic_metabox'], $ptype, 'side', 'default');
        }
    }
    public function render_classic_metabox($post)
    {
        wp_nonce_field('pam_meta_box_save', 'pam_meta_box_nonce');
        $rating   = (int) get_post_meta($post->ID, 'pam_rating', true);
        $diff     = (string) get_post_meta($post->ID, 'pam_difficulty', true);
        $beauty   = (int) get_post_meta($post->ID, 'pam_beauty', true);
        $duration = (int) get_post_meta($post->ID, 'pam_duration_min', true);
?>
        <p><label>Rating (0–5)<br><input type="number" name="pam_rating" value="<?php echo esc_attr($rating); ?>" min="0" max="5" step="1"></label></p>
        <p><label>Schwierigkeit<br>
                <select name="pam_difficulty">
                    <option value="" <?php selected($diff, ''); ?>>—</option>
                    <option value="easy" <?php selected($diff, 'easy'); ?>>Leicht</option>
                    <option value="medium" <?php selected($diff, 'medium'); ?>>Mittel</option>
                    <option value="hard" <?php selected($diff, 'hard'); ?>>Schwer</option>
                </select>
            </label></p>
        <p><label>Schönheit (0–5)<br><input type="number" name="pam_beauty" value="<?php echo esc_attr($beauty); ?>" min="0" max="5" step="1"></label></p>
        <p><label>Dauer (Minuten)<br><input type="number" name="pam_duration_min" value="<?php echo esc_attr($duration); ?>" min="0" step="10"></label></p>
    <?php
    }
    public function save_classic_metabox($post_id)
    {
        if (! isset($_POST['pam_meta_box_nonce']) || ! wp_verify_nonce($_POST['pam_meta_box_nonce'], 'pam_meta_box_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (! current_user_can('edit_post', $post_id)) return;
        foreach (array_keys(self::META) as $key) {
            $field = "pam_$key";
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, $_POST[$field]);
            }
        }
    }

    // ------- Editor Sidebar Panel -------
    public function enqueue_editor_panel()
    {
        wp_register_script(
            'pam-editor-panel',
            plugins_url('assets/editor-panel.js', __FILE__),
            ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'],
            '1.10.1',
            true
        );
        wp_enqueue_script('pam-editor-panel');
    }

    // ------- Blocks registration -------
    public function register_block_scripts()
    {
        wp_register_script(
            'pam-summary-editor',
            plugins_url('blocks/pam-summary/index.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'],
            '1.10.1',
            true
        );
        wp_register_script(
            'pam-stars-editor',
            plugins_url('blocks/pam-stars/index.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n'],
            '1.10.1',
            true
        );
        wp_register_script(
            'pam-explorer-editor',
            plugins_url('blocks/pam-explorer/index.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            '1.10.1',
            true
        );
    }

    public function register_blocks()
    {
        register_block_type(__DIR__ . '/blocks/pam-summary', [
            'editor_script'  => 'pam-summary-editor',
            'style'          => 'pam-summary-style',
            'render_callback' => [$this, 'render_block_summary'],
        ]);
        register_block_type(__DIR__ . '/blocks/pam-stars', [
            'editor_script'  => 'pam-stars-editor',
            'style'          => 'pam-stars-style',
            'render_callback' => [$this, 'render_block_stars'],
        ]);
        register_block_type(__DIR__ . '/blocks/pam-explorer', [
            'editor_script'  => 'pam-explorer-editor',
            'style'          => 'pam-explorer-style',
            //'script'         => 'pam-explorer-view',
            'render_callback' => [$this, 'render_block_explorer'],
        ]);
    }

    public function register_view_assets()
    {
        wp_register_style('pam-summary-style', plugins_url('blocks/pam-summary/style.css', __FILE__), [], '1.10.1');
        wp_register_style('pam-stars-style', plugins_url('blocks/pam-stars/style.css', __FILE__), [], '1.10.1');
        wp_register_style('pam-explorer-style', plugins_url('blocks/pam-explorer/style.css', __FILE__), [], '1.10.1');
        //wp_register_script('pam-explorer-view', plugins_url('blocks/pam-explorer/view.js', __FILE__), [], '1.10.1', true);
    }

    // ---------- SVG ICONS ----------
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
    private function stars_html($rating)
    {
        $rating = max(0, min(5, (int)$rating));
        $out = '<span class="pam-icongroup" aria-label="Rating ' . $rating . ' von 5">';
        for ($i = 1; $i <= 5; $i++) $out .= ($i <= $rating) ? $this->svg_star_filled() : $this->svg_star_empty();
        return $out . '</span>';
    }
    private function duration_text($minutes)
    {
        $minutes = (int) $minutes;
        if ($minutes <= 0) return '—';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return $h . ' h ' . $m . ' min';
        if ($h > 0) return $h . ' h';
        return $m . ' min';
    }

    // ---------- Block Renders ----------
    public function render_block_summary($attributes, $content, $block)
    {
        wp_enqueue_style('pam-summary-style');
        $post_id = $block->context['postId'] ?? get_the_ID();
        if (! $post_id) return '';
        $rating     = (int) get_post_meta($post_id, 'pam_rating', true);
        $beauty     = (int) get_post_meta($post_id, 'pam_beauty', true);
        $duration   = (int) get_post_meta($post_id, 'pam_duration_min', true);
        $difficulty = (string) get_post_meta($post_id, 'pam_difficulty', true);
        $diff_count = $difficulty === 'hard' ? 3 : ($difficulty === 'medium' ? 2 : ($difficulty === 'easy' ? 1 : 0));

        $mountains_html = $diff_count ? $this->icons_group($this->svg_mountain(), $diff_count) : '—';
        $suns_html      = $beauty ? $this->icons_group($this->svg_sun(), $beauty) : '—';
        $duration_html  = '<span class="pam-icongroup">' . $this->svg_stopwatch() . '</span> ' . $this->duration_text($duration);

        ob_start(); ?>
        <div class="pam-box pam-summary" role="group" aria-label="<?php esc_attr_e('Zusätzliche Informationen', 'post-attributes-meta'); ?>">
            <div class="pam-summary__title"><?php _e('Zusätzliche Informationen', 'post-attributes-meta'); ?></div>
            <ul class="pam-summary__list">
                <li><span class="pam-label">Rating:</span> <?php echo $this->stars_html($rating); ?></li>
                <li><span class="pam-label">Schwierigkeit:</span> <?php echo $mountains_html; ?></li>
                <li><span class="pam-label">Schönheit:</span> <?php echo $suns_html; ?></li>
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
        if (! $post_id) return '';
        $rating = (int) get_post_meta($post_id, 'pam_rating', true);
        return '<div class="pam-stars">' . $this->stars_html($rating) . '</div>';
    }

    // ---------- Explorer (SSR) ----------
    private function build_url($args = [])
    {
        $url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $base = remove_query_arg(array_keys($_GET), $url);
        $new  = add_query_arg(array_merge($_GET, $args), $base);
        return esc_url($new);
    }

    public function render_block_explorer($attributes, $content, $block)
    {
        wp_enqueue_style('pam-explorer-style');
        wp_enqueue_script('pam-explorer-view');

        $min_rating = isset($_GET['min_rating']) ? max(0, (int) $_GET['min_rating']) : 0;
        $min_beauty = isset($_GET['min_beauty']) ? max(0, (int) $_GET['min_beauty']) : 0;

        $d_from_i   = isset($_GET['diff_from_i']) ? max(1, min(3, (int) $_GET['diff_from_i'])) : 1;
        $d_to_i     = isset($_GET['diff_to_i'])   ? max(1, min(3, (int) $_GET['diff_to_i']))   : 3;
        if ($d_from_i > $d_to_i) {
            $t = $d_from_i;
            $d_from_i = $d_to_i;
            $d_to_i = $t;
        }
        $idx2lab = [1 => 'easy', 2 => 'medium', 3 => 'hard'];
        $diff_vals = [];
        for ($i = $d_from_i; $i <= $d_to_i; $i++) $diff_vals[] = $idx2lab[$i];

        $dur_from = isset($_GET['dur_from']) ? max(0, min(720, (int) $_GET['dur_from'])) : 0;
        $dur_to   = isset($_GET['dur_to'])   ? max(0, min(720, (int) $_GET['dur_to']))   : 720;
        if ($dur_from > $dur_to) {
            $t = $dur_from;
            $dur_from = $dur_to;
            $dur_to = $t;
        }

        $cats_raw = isset($_GET['cats']) ? (array) $_GET['cats'] : [];
        $cats = array_filter(array_map('intval', $cats_raw));

        $sort = isset($_GET['pam_sort']) ? sanitize_text_field($_GET['pam_sort']) : 'date_desc';
        $orderby = ['date' => 'DESC'];
        $meta_key = '';

        switch ($sort) {
            case 'title_asc':  $orderby = [ 'title' => 'ASC'  ]; break;
            case 'title_desc': $orderby = [ 'title' => 'DESC' ]; break;
            case 'date_asc':
                $orderby = ['date' => 'ASC'];
                break;
            case 'rating_desc':
                $meta_key = 'pam_rating';
                $orderby = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
                break;
            case 'rating_asc':
                $meta_key = 'pam_rating';
                $orderby = ['meta_value_num' => 'ASC',  'date' => 'DESC'];
                break;
            case 'beauty_desc':
                $meta_key = 'pam_beauty';
                $orderby = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
                break;
            case 'beauty_asc':
                $meta_key = 'pam_beauty';
                $orderby = ['meta_value_num' => 'ASC',  'date' => 'DESC'];
                break;
            case 'dur_asc':
                $meta_key = 'pam_duration_min';
                $orderby = ['meta_value_num' => 'ASC',  'date' => 'DESC'];
                break;
            case 'dur_desc':
                $meta_key = 'pam_duration_min';
                $orderby = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
                break;
            default:
                $orderby = ['date' => 'DESC'];
        }

        $meta_query = [
            'relation' => 'AND',
            ['key' => 'pam_rating', 'value' => $min_rating, 'compare' => '>=', 'type' => 'NUMERIC'],
            ['key' => 'pam_beauty', 'value' => $min_beauty, 'compare' => '>=', 'type' => 'NUMERIC'],
            ['key' => 'pam_duration_min', 'value' => [$dur_from, $dur_to], 'compare' => 'BETWEEN', 'type' => 'NUMERIC'],
        ];
        if (count($diff_vals) < 3) {
            $meta_query[] = ['key' => 'pam_difficulty', 'value' => $diff_vals, 'compare' => 'IN'];
        }

        $per_page = 24;
        $paged    = max(1, (int) ($_GET['pam_page'] ?? 1));

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

        // Categories options
        $all_cats = get_categories(['hide_empty' => false]);
        $options = '';
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
                $url = esc_url($this->build_url(['pam_page' => $p]));
                $pager_html .= "<li$cls><a href=\"$url\">$p</a></li>";
            }
            $pager_html .= '</ul></nav>';
        }

        // Sort options
        $sort_options = [
            'date_desc'   => __('Neueste zuerst', 'post-attributes-meta'),
            'date_asc'    => __('Älteste zuerst', 'post-attributes-meta'),
            'rating_desc' => __('Rating absteigend', 'post-attributes-meta'),
            'rating_asc'  => __('Rating aufsteigend', 'post-attributes-meta'),
            'beauty_desc' => __('Schönheit absteigend', 'post-attributes-meta'),
            'beauty_asc'  => __('Schönheit aufsteigend', 'post-attributes-meta'),
            'dur_asc'     => __('Dauer: kürzeste zuerst', 'post-attributes-meta'),
            'dur_desc'    => __('Dauer: längste zuerst', 'post-attributes-meta'),
        ];
        $sort_html = '<select name="pam_sort">';
        foreach ($sort_options as $value => $label) {
            $sel = (isset($_GET['pam_sort']) ? $_GET['pam_sort'] : 'date_desc') === $value ? ' selected' : '';
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

                <label>Min. Schönheit
                    <input type="range" name="min_beauty" min="0" max="5" step="1" value="<?php echo esc_attr($min_beauty); ?>" aria-label="Minimale Schönheit (0 bis 5)" />
                    <output data-out="min_beauty"><?php echo esc_html($min_beauty); ?></output>
                </label>

                <label>Schwierigkeit (von/bis)
                    <div class="minmax" data-field="difficulty" data-min="1" data-max="3" data-step="1">
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
                    // kleine Helfer für Sortier-Links (toggle ASC/DESC)
                    $toggle = function ($key) use ($sort) {
                        $pairs = [
                            'title' => ['title_asc', 'title_desc'],
                            'date'  => ['date_asc', 'date_desc'],
                            'rating' => ['rating_asc', 'rating_desc'],
                            'beauty' => ['beauty_asc', 'beauty_desc'],
                            'dur'   => ['dur_asc', 'dur_desc'],
                        ];
                        $pair = $pairs[$key] ?? ['date_asc', 'date_desc'];
                        $next = ($sort === $pair[0]) ? $pair[1] : $pair[0];
                        return esc_url(add_query_arg(['pam_sort' => $next], remove_query_arg(null)));
                    };
                    ?>

                    <table class="pam-table">
                        <thead>
                            <tr>
                                <th><a href="<?php echo $toggle('title'); ?>">Titel</a></th>
                                <th><a href="<?php echo $toggle('rating'); ?>">Rating</a></th>
                                <th><a href="<?php echo $toggle('beauty'); ?>">Schönheit</a></th>
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
                                $rating   = (int) get_post_meta($pid, 'pam_rating', true);
                                $beauty   = (int) get_post_meta($pid, 'pam_beauty', true);
                                $duration = (int) get_post_meta($pid, 'pam_duration_min', true);
                                $diff     = (string) get_post_meta($pid, 'pam_difficulty', true);
                                $diff_lbl = $diff === 'hard' ? 'Schwer' : ($diff === 'medium' ? 'Mittel' : ($diff === 'easy' ? 'Leicht' : '—'));
                                $cats_arr = get_the_category($pid);
                                $cats_txt = $cats_arr ? implode(', ', wp_list_pluck($cats_arr, 'name')) : '—';
                            ?>
                                <tr>
                                    <td><a href="<?php echo esc_url($perma); ?>"><?php echo esc_html($title); ?></a></td>
                                    <td><?php echo $this->stars_html($rating); ?></td>
                                    <td><?php echo $beauty ? $this->icons_group($this->svg_sun(), $beauty) : '—'; ?></td>
                                    <td><?php echo esc_html($diff_lbl); ?></td>
                                    <td><?php echo esc_html($this->duration_text($duration)); ?></td>
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

new PAM_Explorer_v1101();
