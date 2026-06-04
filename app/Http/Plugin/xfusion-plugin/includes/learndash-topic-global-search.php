<?php
/**
 * LearnDash: indeks pencarian topic + shortcode [ld_topic_search] dan [simple_search_bar].
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('save_post', 'xfusion_ld_build_topic_search_index', 20, 3);
add_action('save_post_sfwd-topic', 'xfusion_ld_build_topic_search_index', 20, 3);
add_action('wp_after_insert_post', 'xfusion_ld_build_topic_search_index_fallback', 20, 4);

/**
 * @param mixed $post_before
 */
function xfusion_ld_build_topic_search_index_fallback($post_id, $post, $update, $post_before): void
{
    if (!$post instanceof WP_Post || $post->post_type !== 'sfwd-topic') {
        return;
    }

    xfusion_ld_build_topic_search_index($post_id, $post, $update);
}

/**
 * Rekursif ambil teks dari struktur JSON Elementor (panel/data).
 *
 * @param array<string, mixed>|mixed $node
 */
function xfusion_ld_extract_elementor_text($node): string
{
    if (!is_array($node)) {
        return '';
    }

    $text = '';

    if (isset($node['settings']) && is_array($node['settings'])) {
        foreach ($node['settings'] as $value) {
            if (is_string($value)) {
                $text .= ' ' . wp_strip_all_tags($value);
            }
        }
    }

    if (isset($node['elements']) && is_array($node['elements'])) {
        foreach ($node['elements'] as $child) {
            $text .= ' ' . xfusion_ld_extract_elementor_text($child);
        }
    }

    return trim($text);
}

/**
 * @param int|WP_Post $post_id
 */
function xfusion_ld_build_topic_search_index($post_id, $post = null, $update = null): void
{
    $post = get_post($post_id);
    if (!$post) {
        return;
    }

    $title = get_the_title($post_id);
    $content = get_post_field('post_content', $post_id);

    $final_text = $title;

    preg_match_all('/\[elementor-template id="(\d+)"\]/', $content, $tpl_matches);

    if (!empty($tpl_matches[1])) {
        foreach ($tpl_matches[1] as $tpl_id) {
            $tpl_content = get_post_field('post_content', $tpl_id);

            if ($tpl_content) {
                if (strpos($tpl_content, '"widgetType"') !== false) {
                    $decoded = json_decode($tpl_content, true);
                    if (is_array($decoded)) {
                        $final_text .= ' ' . xfusion_ld_extract_elementor_text($decoded);
                    }
                } else {
                    $final_text .= ' ' . wp_strip_all_tags($tpl_content);
                }
            }
        }
    }

    preg_match_all('/\[gravityform.*id="(\d+)".*\]/', $content, $gf_matches);

    if (!empty($gf_matches[1]) && class_exists('GFAPI')) {
        foreach ($gf_matches[1] as $form_id) {
            $form = GFAPI::get_form((int) $form_id);

            if ($form && !empty($form['fields'])) {
                foreach ($form['fields'] as $field) {
                    if (!empty($field->label)) {
                        $final_text .= ' ' . $field->label;
                    }
                    if (!empty($field->placeholder)) {
                        $final_text .= ' ' . $field->placeholder;
                    }
                }
            }
        }
    }

    $final_text = preg_replace('/\[elementor-template id="(\d+)"\]/', '', $final_text);
    $final_text = preg_replace('/\[gravityform.*id="(\d+)".*\]/', '', $final_text);
    $final_text = strtolower($final_text);
    $final_text = html_entity_decode($final_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $final_text = preg_replace('/[^a-z0-9\s\-]/u', ' ', $final_text);
    $final_text = preg_replace('/\s+/', ' ', $final_text);
    $final_text = trim($final_text);

    update_post_meta($post_id, '_search_index', $final_text);
}

/**
 * @param list<string> $keywords
 * @return list<string>
 */
function xfusion_ld_generate_snippets($text, $keywords, $radius = 10, $max_snippets = 3): array
{
    $text = strtolower(wp_strip_all_tags((string) $text));
    $words = explode(' ', $text);
    $total = count($words);

    $snippets = [];
    $used_indexes = [];

    foreach ($words as $index => $word) {
        foreach ($keywords as $keyword) {
            if ($keyword === '' || strpos($word, $keyword) === false || in_array($index, $used_indexes, true)) {
                continue;
            }

            $start = max(0, $index - $radius);
            $end = min($total - 1, $index + $radius);

            $slice = array_slice($words, $start, $end - $start + 1);
            $snippet = implode(' ', $slice);

            foreach ($keywords as $kw) {
                if ($kw === '') {
                    continue;
                }
                $snippet = preg_replace(
                    '/(' . preg_quote($kw, '/') . ')/i',
                    '<strong>$1</strong>',
                    $snippet
                );
            }

            $snippets[] = '...' . $snippet . '...';

            for ($i = $start; $i <= $end; $i++) {
                $used_indexes[] = $i;
            }

            if (count($snippets) >= $max_snippets) {
                return $snippets;
            }
        }
    }

    return $snippets;
}

function xfusion_ld_topic_search_results(): string
{
    $keyword = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

    if ($keyword === '') {
        return '<p>Enter a keyword to search...</p>';
    }

    $keywords = array_filter(explode(' ', strtolower($keyword)));

    $meta_query = ['relation' => 'AND'];

    foreach ($keywords as $word) {
        $meta_query[] = [
            'key'     => '_search_index',
            'value'   => $word,
            'compare' => 'LIKE',
        ];
    }

    $args = [
        'post_type'      => ['sfwd-courses', 'sfwd-lessons', 'sfwd-topic'],
        'posts_per_page' => 20,
        'meta_query'     => $meta_query,
    ];

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        echo '<div class="ld-search-results">';

        while ($query->have_posts()) {
            $query->the_post();

            $post_id = get_the_ID();
            $post_type = get_post_type();

            $index_text = get_post_meta($post_id, '_search_index', true);

            $snippets = xfusion_ld_generate_snippets((string) $index_text, $keywords, 8, 5);

            echo '<div style="margin-bottom:20px; padding:15px; border:1px solid #1c1c1c; border-radius:8px;">';

            echo '<a href="' . esc_url(get_permalink()) . '"><strong>' . esc_html(get_the_title()) . '</strong></a>';

            if ($post_type === 'sfwd-lessons' || $post_type === 'sfwd-topic') {
                $course_id = learndash_get_course_id($post_id);
                if ($course_id) {
                    echo '<div style="font-size:12px; color:#999;">
                        <a href="' . esc_url(get_permalink($course_id)) . '">' . esc_html(get_the_title($course_id)) . '</a>
                    </div>';
                }
            }

            if (!empty($snippets)) {
                foreach ($snippets as $snippet) {
                    echo '<p style="margin:5px 0; color:#555;">' . wp_kses($snippet, [
                        'strong' => [],
                        'b'      => [],
                        'em'     => [],
                        'mark'   => [],
                    ]) . '</p>';
                }
            }

            echo '</div>';
        }

        echo '</div>';
    } else {
        echo '<p>No results found for: <strong>' . esc_html($keyword) . '</strong></p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode('ld_topic_search', 'xfusion_ld_topic_search_results');

function xfusion_simple_search_bar_shortcode(): string
{
    ob_start();
    ?>
    <div class="simple-search-bar">
        <p class="form-title">Search</p>
        <form role="search" method="get" action="<?php echo esc_url(home_url('/result/')); ?>">
            <input
                type="search"
                name="q"
                placeholder="Search your LMS topic keyword..."
                value="<?php echo isset($_GET['q']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['q']))) : ''; ?>"
                required
            >
            <button type="submit">
                <i class="fa fa-search"></i>
            </button>
        </form>
    </div>
    <style>
        .form-title{
            font-family: "Bebas Neue", Sans-serif;
            font-size: 28px;
            font-weight: 400;
            letter-spacing: 2px;
        }
        .simple-search-bar form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .simple-search-bar input {
            border: none;
            border-bottom: 2px solid #000;
            outline: none;
            background: #0000000d;
            padding: 10px;
            font-size: 22px;
            width: 100%;
            transition: 0.3s;
            border-radius: 10px;
        }
        .simple-search-bar input:focus {
            border-bottom: 2px solid #0073ff;
        }
        .simple-search-bar button {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #000;
            transition: 0.3s;
            padding: 10px!important;
        }
        .simple-search-bar button:hover {
            background: transparent;
            color: #0073ff;
        }
        .simple-search-bar button:focus {
            background: transparent;
            color: #000;
        }
    </style>
    <?php

    return ob_get_clean();
}

add_shortcode('simple_search_bar', 'xfusion_simple_search_bar_shortcode');
