<?php

namespace OutdoorWww\Blocks\Render;

use OutdoorWww\Support\RenderUtils;

class Summary
{
    public static function render(array $attributes, string $content, $block): string
    {
        wp_enqueue_style('owww-summary-style');

        $post_id = $block->context['postId'] ?? get_the_ID();
        if (!$post_id) return '';

        $owww_rating       = (int) get_post_meta($post_id, 'owww_rating', true);
        $owww_exclusivity  = (int) get_post_meta($post_id, 'owww_exclusivity', true);
        $owww_time_relaxed = (int) get_post_meta($post_id, 'owww_time_relaxed', true);
        $owww_difficulty_hiking   = (string) get_post_meta($post_id, 'owww_difficulty_hiking', true);

        $diff_count = Html::difficultyHikingCount($star_difficulty);
        $mountains  = $diff_count ? Html::iconGroup(Icons::mountain(), $diff_count) : '—';
        $suns       = $star_exclusivity ? Html::iconGroup(Icons::sun(), $star_exclusivity) : '—';
        $duration   = '<span class="owww-icongroup">' . Icons::stopwatch() . '</span> ' . Html::durationText($star_time_relaxed);

        $mountains_html = $diff_count ? RenderUtils::icons_group(RenderUtils::svg_mountain(), $diff_count) : '—';
        $suns_html      = $owww_exclusivity ? RenderUtils::icons_group(RenderUtils::svg_sun(), $owww_exclusivity) : '—';
        $duration_html  = '<span class="owww-icongroup">' . RenderUtils::svg_stopwatch() . '</span> ' . RenderUtils::duration_text($owww_time_relaxed);

        ob_start(); ?>
        <div class="owww-box owww-summary" role="group" aria-label="<?php esc_attr_e('Zusätzliche Informationen', 'outdoor-www'); ?>">
            <div class="owww-summary__title"><?php _e('Zusätzliche Informationen', 'outdoor-www'); ?></div>
            <ul class="owww-summary__list">
                <li><span class="owww-label">Rating:</span> <?php echo RenderUtils::stars_html($owww_rating); ?></li>
                <li><span class="owww-label">Schwierigkeit:</span> <?php echo $mountains_html; ?></li>
                <li><span class="owww-label">Exklusivität:</span> <?php echo $suns_html; ?></li>
                <li><span class="owww-label">Dauer:</span> <?php echo $duration_html; ?></li>
            </ul>
        </div>
<?php
        return ob_get_clean();
    }
}
