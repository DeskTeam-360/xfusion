<?php
/**
 * Shortcode [topic_category_progress_bar category="slug"]
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_custom_topic_category_progress_bar($atts): string
{
    $atts = shortcode_atts([
        'category' => '',
    ], $atts, 'topic_category_progress_bar');

    $term = get_term_by('slug', sanitize_title($atts['category']), 'ld_topic_category');

    if (!$term || is_wp_error($term)) {
        return 'Category not found.';
    }

    $category_id = (int) $term->term_id;

    $topics = get_posts([
        'post_type'      => 'sfwd-topic',
        'tax_query'      => [[
            'taxonomy' => 'ld_topic_category',
            'field'    => 'term_id',
            'terms'    => $category_id,
        ]],
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ]);

    if (empty($topics)) {
        return 'No topics in this category.';
    }

    $total_topics = count($topics);
    $completed_topics = 0;
    $user_id = get_current_user_id();

    foreach ($topics as $topic_id) {
        if (learndash_is_topic_complete($user_id, $topic_id)) {
            $completed_topics++;
        }
    }

    $progress = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0;

    return sprintf(
        '<div class="progress-bar">
            <div class="progress-bar-fill" style="width: %.0f%%;">%.0f%%</div>
        </div>',
        $progress,
        $progress
    );
}

add_shortcode('topic_category_progress_bar', 'xfusion_custom_topic_category_progress_bar');
