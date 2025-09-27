<?php
namespace OutdoorWww\Admin;

use OutdoorWww\Config\Meta as MetaConfig;
use OutdoorWww\Meta\Registrar as MetaRegistrar;

class ClassicMetaBox
{
    /** @var array */
    private array $defs;



    public function __construct(?array $defs = null)
    {
        $this->defs = $defs ?? MetaConfig::defaults();

        // Nur anzeigen, wenn der Block-Editor NICHT aktiv ist
        add_action('add_meta_boxes', [$this, 'registerBox']);
        add_action('save_post',      [$this, 'save'], 10, 1);
    }



    public function registerBox(): void
    {
        if (function_exists('use_block_editor_for_post_type') && use_block_editor_for_post_type('post')) {
            return; // Gutenberg aktiv -> keine Fallback-Box
        }
        foreach (['post','page'] as $ptype) {
            add_meta_box(
                'owww_meta_fallback',
                __('Zusatzinfos (Fallback)', 'outdoor-www'),
                [$this, 'renderBox'],
                $ptype,
                'side',
                'default'
            );
        }
    }



    public function renderBox(\WP_Post $post): void
    {
        wp_nonce_field('owww_fallback_save','owww_fallback_nonce');

        foreach ($this->defs as $key => $def) {
            $type    = $def['type']   ?? 'string';
            $label   = $this->labelFromKey($key);
            $value   = get_post_meta($post->ID, $key, true);

            echo '<p><label>'.esc_html($label).'<br>';

            if ($key === 'star_difficulty') {
                // Spezifische Auswahl für Difficulty
                $cur = (string)$value;
                echo '<select name="'.esc_attr($key).'">';
                echo '<option value="" '.selected($cur,'', false).'>—</option>';
                echo '<option value="easy" '.selected($cur,'easy', false).'>'.esc_html__('Leicht','outdoor-www').'</option>';
                echo '<option value="medium" '.selected($cur,'medium', false).'>'.esc_html__('Mittel','outdoor-www').'</option>';
                echo '<option value="hard" '.selected($cur,'hard', false).'>'.esc_html__('Schwer','outdoor-www').'</option>';
                echo '</select>';
            } elseif ($type === 'integer') {
                echo '<input type="number" name="'.esc_attr($key).'" value="'.esc_attr((int)$value).'" step="1">';
            } else {
                echo '<input type="text" name="'.esc_attr($key).'" value="'.esc_attr((string)$value).'">';
            }

            echo '</label></p>';
        }
    }



    public function save(int $post_id): void
    {
        if (!isset($_POST['owww_fallback_nonce']) || !wp_verify_nonce($_POST['owww_fallback_nonce'],'owww_fallback_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        foreach ($this->defs as $key => $def) {
            if (!isset($_POST[$key])) continue;
            $type  = $def['type'] ?? 'string';
            $clean = MetaRegistrar::sanitizeForKey($key, $type, $_POST[$key]);
            update_post_meta($post_id, $key, $clean);
        }
    }



    private function labelFromKey(string $key): string
    {
        // kleine Heuristik → sprechende Labels ohne Übersetzungsaufwand
        $map = [
            'star_rating'       => __('Rating','outdoor-www'),
            'star_exclusivity'  => __('Exklusivität','outdoor-www'),
            'star_difficulty'   => __('Schwierigkeit','outdoor-www'),
            'star_time_relaxed' => __('Dauer (Minuten)','outdoor-www'),
        ];
        if (isset($map[$key])) return $map[$key];

        $k = preg_replace('/^star_/', '', $key);
        $k = str_replace('_', ' ', $k);
        return ucfirst($k);
    }
}
