<?php

namespace OutdoorWww\Blocks\Render;

use OutdoorWww\Support\RenderUtils;
use OutdoorWww\Support\Html;
use OutdoorWww\Config\Fields;

class Stars
{
    public static function render(array $attributes, string $content, $block): string
    {
        wp_enqueue_style('pam-stars-style');

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

        $rating = $post_id ? (int) get_post_meta($post_id, Fields::rating(), true) : 0;
        return '<div class="owww-stars">' . Html::stars($rating) . '</div>';
    }
}
