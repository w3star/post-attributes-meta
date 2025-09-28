<?php

namespace OutdoorWww\Blocks\Render;

use OutdoorWww\Config\Fields;
use OutdoorWww\Support\Html;
use OutdoorWww\Support\Icons;

class Summary
{
    public static function render(array $attributes, string $content, $block): string
    {
        wp_enqueue_style('pam-summary-style');

        // robuste Post-ID
        $post_id = 0;
        if (is_object($block) && !empty($block->context['postId'])) $post_id = (int)$block->context['postId'];
        if (!$post_id) {
            $qid = get_queried_object_id();
            if ($qid) $post_id = (int)$qid;
        }
        if (!$post_id && isset($GLOBALS['post']->ID)) $post_id = (int)$GLOBALS['post']->ID;
        if (!$post_id) {
            $tmp = get_the_ID();
            if ($tmp) $post_id = (int)$tmp;
        }

        if (!$post_id) {
            return '<div class="owww-box owww-summary"><div class="owww-summary__title">Zusätzliche Informationen</div><ul class="owww-summary__list"><li>–</li></ul></div>';
        }

        $star_rating       = (int) get_post_meta($post_id, 'star_rating', true);
        $star_exclusivity  = (int) get_post_meta($post_id, 'star_exclusivity', true);
        $star_time_relaxed = (int) get_post_meta($post_id, 'star_time_relaxed', true);
        $star_difficulty   = (string) get_post_meta($post_id, 'star_difficulty', true);

        $diff_count   = Html::difficultyHiking_count($star_difficulty);
        $difficulty   = $diff_count ? Html::iconGroup(Icons::mountain(), $diff_count) : '—';
        $exclusivity       = $star_exclusivity ? Html::iconGroup(Icons::sun(), $star_exclusivity) : '—';
        $durationText = '<span class="owww-icongroup">' . Icons::stopwatch() . '</span> ' . Html::durationText($star_time_relaxed);

        ob_start(); ?>
        <div class="owww-box owww-summary" role="group" aria-label="<?php esc_attr_e('Zusätzliche Informationen', 'outdoor-www'); ?>">
            <div class="owww-summary__title"><?php _e('Zusätzliche Informationen', 'outdoor-www'); ?></div>
            <ul class="owww-summary__list">
                <li><span class="owww-label">Rating:</span> <?php echo Html::stars($star_rating); ?></li>
                <li><span class="owww-label">Schwierigkeit:</span> <?php echo $difficulty; ?></li>
                <li><span class="owww-label">Exklusivität:</span> <?php echo $exclusivity; ?></li>
                <li><span class="owww-label">Dauer:</span> <?php echo $durationText; ?></li>
            </ul>
        </div>
<?php
        return ob_get_clean();
    }
}
