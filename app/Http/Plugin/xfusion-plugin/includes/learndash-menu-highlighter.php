<?php
/**
 * Highlight menu LearnDash berdasarkan course / segment URL.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/**
 * @param list<string> $classes
 * @return list<string>
 */
function xfusion_highlight_nav_menu($classes, $item)
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                 || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443'))
        ? 'https://'
        : 'http://';

    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH) : '';
    $segments = explode('/', trim((string) $path, '/'));

    $firstFolder = $segments[0] ?? '';
    $url = $protocol . $host . '/' . $firstFolder;

    $path = wp_parse_url($url, PHP_URL_PATH);
    $segments = explode('/', trim((string) $path, '/'));
    $firstFolder = $segments[0] ?? '';

    if (!is_single() && !is_page()) {
        return $classes;
    }

    $current_id = get_the_ID();

    $course_id = learndash_get_course_id($current_id);
    if (!$course_id) {
        if (strcasecmp((string) $item->title, $firstFolder) === 0) {
            $classes[] = 'highlight-menu';

            if (!has_action('wp_footer', 'xfusion_inject_highlight_menu_css')) {
                add_action('wp_footer', 'xfusion_inject_highlight_menu_css', 100);
            }
        }

        return $classes;
    }

    if ($course_id === 19578) {
        $lesson_id = learndash_get_lesson_id($current_id);
        if ($lesson_id) {
            $reference_title = get_the_title($lesson_id);
        } else {
            return $classes;
        }
    } else {
        $reference_title = get_the_title($course_id);
    }

    if (strcasecmp((string) $item->title, (string) $reference_title) === 0) {
        $classes[] = 'highlight-menu';

        if (!has_action('wp_footer', 'xfusion_inject_highlight_menu_css')) {
            add_action('wp_footer', 'xfusion_inject_highlight_menu_css', 100);
        }
    }

    return $classes;
}

add_filter('nav_menu_css_class', 'xfusion_highlight_nav_menu', 10, 2);

function xfusion_inject_highlight_menu_css(): void
{
    echo '<style>
        li.highlight-menu > a.elementor-item {
            color: #E1706D !important;
            font-weight: bold !important;
        }
        li.highlight-menu > a.elementor-item:hover {
            color: #E1706D !important;
            font-weight: bold !important;
        }
    </style>';
}
