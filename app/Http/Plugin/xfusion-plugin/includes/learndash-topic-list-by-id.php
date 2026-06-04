<?php
/**
 * Shortcode [show_topic_with_id topic_id="" btn_close="" order=""]
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_show_topic_with_id($atts): string
{
    $atts = shortcode_atts([
        'topic_id' => '',
        'order' => '',
    ], $atts, 'show_topic_with_id');

    $topic_id = (int) $atts['topic_id'];
    $order = $atts['order'] !== '' ? (string) ((int) $atts['order']) . '. ' : '';

    if (!$topic_id) {
        return 'Invalid topic ID.';
    }

    $topic = get_post($topic_id);
    if (!$topic || $topic->post_type !== 'sfwd-topic') {
        return 'No valid topic found.';
    }

    $completed = learndash_is_topic_complete(get_current_user_id(), $topic->ID);
    $class = $completed ? 'class="topic-completed"' : 'class="not-completed"';

    $style = $completed ? 'color: #FFC807 !important;' : 'color: #FFF !important;';

    $output = '<ul class="ld-topic-list">';

    if ($order !== '') {
        $output .= '<li ' . $class . '>
            <a href="' . esc_url(get_permalink($topic->ID)) . '" style="background: none;
            padding: 5px; text-wrap: wrap; text-align: left;
            border: none; ' . esc_attr($style) . '" >'
            . esc_html($order . $topic->post_title) . '</a>
        </li>';
    } else {
        $onclick_url = esc_url(get_permalink($topic->ID)) . '/?btn-close=true';
        $output .= '<li ' . $class . '>
            <a href="#" style="background: none;
            padding: 5px; text-wrap: wrap; text-align: left;
            border: none; ' . esc_attr($style) . '" onclick="openWindowXfusion(\'' . esc_js($onclick_url) . '\')">'
            . esc_html($order . $topic->post_title) . '</a>
        </li>';
    }

    $output .= '</ul>';

    return $output;
}

add_shortcode('show_topic_with_id', 'xfusion_show_topic_with_id');
