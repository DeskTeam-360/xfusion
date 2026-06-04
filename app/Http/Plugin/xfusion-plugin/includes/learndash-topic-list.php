<?php
/**
 * Shortcode [ld_topic_list_with_class lesson_id=""]
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_ld_topic_list_with_class($atts): string
{
    if (!function_exists('learndash_get_topic_list')) {
        return 'LearnDash is not active.';
    }

    $atts = shortcode_atts(['lesson_id' => 0], $atts, 'ld_topic_list_with_class');
    $lesson_id = (int) $atts['lesson_id'];

    if (!$lesson_id) {
        return 'Invalid lesson ID.';
    }

    $topics = learndash_get_topic_list($lesson_id);

    $lesson_title = get_the_title($lesson_id);
    $week = null;
    if (preg_match('/week\s*(\d+)/i', $lesson_title, $matches)) {
        $week = $matches[1];
    }

    if (empty($topics)) {
        return 'No topics found.';
    }

    static $xfusion_ld_topic_list_styles_printed = false;
    if (!$xfusion_ld_topic_list_styles_printed) {
        $xfusion_ld_topic_list_styles_printed = true;
        add_action('wp_footer', static function (): void {
            echo '<style>
                .ld-topic-list > li.topic-completed > a {
                    color: #FFC807 !important;
                }
            </style>';
        }, 20);
    }

    $output = '<ul class="ld-topic-list">';

    foreach ($topics as $index => $topic) {
        $c = '';
        if ($week) {
            $c = (($week - 1) * 2) + $index;
            if ($c == 0) {
                $c = 1;
            }
            $c = $c . '. ';
        }

        $completed = learndash_is_topic_complete(get_current_user_id(), $topic->ID);

        $class = $completed ? 'topic-completed' : 'not-completed';
        $style = $completed ? 'color: #FFC807 !important;' : '';

        $output .= '<li class="' . esc_attr($class) . '">
                        <a style="' . esc_attr($style) . '" href="' . esc_url(get_permalink($topic->ID)) . '">' . esc_html($c . $topic->post_title) . '</a>
                    </li>';
    }

    $output .= '</ul>';

    return $output;
}

add_shortcode('ld_topic_list_with_class', 'xfusion_ld_topic_list_with_class');
