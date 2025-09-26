<?php

namespace OutdoorWww\Blocks\Render;

use OutdoorWww\Support\RenderUtils;

class Stars
{
    public static function render(array $attributes, string $content, $block): string
    {
        wp_enqueue_style('pam-stars-style');

        // robust Post-ID (Editor, Frontend, Query-Loop)
        $post_id = 0;

        if (is_object($block) && !empty($block->context['postId'])) {
            $post_id = (int) $block->context['postId'];
        }
        
        if (!$post_id) {
            $qid = get_queried_object_id();
            if ($qid) $post_id = (int) $qid;
        }
        
        if (!$post_id) {
            global $post;
            if ($post && !empty($post->ID)) $post_id = (int) $post->ID;
        }
        
        if (!$post_id) {
            $tmp = get_the_ID();
            if ($tmp) $post_id = (int) $tmp;
        }

        if (!$post_id) {
            return '<div class="pam-stars">' . RenderUtils::stars_html(0) . '</div>';
        }

        $rating = (int) get_post_meta($post_id, 'star_rating', true);

        return '<div class="pam-stars">' . RenderUtils::stars_html($rating) . '</div>';
    }
}
