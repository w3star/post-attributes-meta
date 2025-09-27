<?php

namespace OutdoorWww\Blocks\Render;

use OutdoorWww\Support\RenderUtils;

class Summary
{
    public static function render(array $attributes, string $content, $block): string
    {
        wp_enqueue_style('pam-summary-style');

        $post_id = $block->context['postId'] ?? get_the_ID();
        if (!$post_id) return '';

        $star_rating       = (int) get_post_meta($post_id, 'star_rating', true);
        $star_exclusivity  = (int) get_post_meta($post_id, 'star_exclusivity', true);
        $star_time_relaxed = (int) get_post_meta($post_id, 'star_time_relaxed', true);
        $star_difficulty_hiking   = (string) get_post_meta($post_id, 'star_difficulty_hiking', true);

        $diff_count = $star_difficulty_hiking === 'hard' ? 3 : ($star_difficulty_hiking === 'medium' ? 2 : ($star_difficulty_hiking === 'easy' ? 1 : 0));
        $mountains_html = $diff_count ? RenderUtils::icons_group(RenderUtils::svg_mountain(), $diff_count) : '—';
        $suns_html      = $star_exclusivity ? RenderUtils::icons_group(RenderUtils::svg_sun(), $star_exclusivity) : '—';
        $duration_html  = '<span class="pam-icongroup">' . RenderUtils::svg_stopwatch() . '</span> ' . RenderUtils::duration_text($star_time_relaxed);

        ob_start(); ?>
        <div class="pam-box pam-summary" role="group" aria-label="<?php esc_attr_e('Zusätzliche Informationen', 'outdoor-www'); ?>">
            <div class="pam-summary__title"><?php _e('Zusätzliche Informationen', 'outdoor-www'); ?></div>
            <ul class="pam-summary__list">
                <li><span class="pam-label">Rating:</span> <?php echo RenderUtils::stars_html($star_rating); ?></li>
                <li><span class="pam-label">Schwierigkeit:</span> <?php echo $mountains_html; ?></li>
                <li><span class="pam-label">Exklusivität:</span> <?php echo $suns_html; ?></li>
                <li><span class="pam-label">Dauer:</span> <?php echo $duration_html; ?></li>
            </ul>
        </div>
<?php
        return ob_get_clean();
    }
}
