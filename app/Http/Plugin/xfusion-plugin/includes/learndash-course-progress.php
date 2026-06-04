<?php
/**
 * Shortcodes [filtered_course_progress] dan [course_progress].
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/**
 * @return array<string, mixed>|string
 */
function xfusion_get_filtered_course_progress(int $user_id, int $lesson_id)
{
    if (!is_user_logged_in()) {
        return 'Silakan login untuk melihat progress.';
    }

    $topic_ids = [];
    $lesson_ids = [];
    $completed_topic_ids = [];

    $topics = learndash_get_topic_list($lesson_id);

    $data0 = [];
    $dt = [];
    $dr = [];
    $categories_name = [];

    foreach ($topics as $topic) {
        $topic_ids[] = $topic->ID;

        $completed = learndash_is_topic_complete($user_id, $topic->ID);
        $data0[$topic->ID] = $completed ? 1 : 0;

        if ($completed) {
            $completed_topic_ids[] = $topic->ID;
        }

        $categories = wp_get_post_terms($topic->ID, 'ld_topic_category');
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $cat_id = $cat->term_id;
                $categories_name[$cat_id] = $cat->name;

                if (!isset($dt[$cat_id])) {
                    $dt[$cat_id] = 0;
                    $dr[$cat_id] = 0;
                }

                $dt[$cat_id] += 1;

                if ($data0[$topic->ID] === 1) {
                    $dr[$cat_id] += 1;
                }
            }
        }
    }

    $t = 0;
    $session_weight = 25;

    foreach ($dt as $cat_id => $total) {
        if ($total > 0) {
            $t += ($dr[$cat_id] / $total) * $session_weight;
        }
    }

    $unique_topic_ids = array_unique($topic_ids);
    $unique_completed_topic_ids = array_unique($completed_topic_ids);

    $total_topics = count($unique_topic_ids);
    $completed_topics = count($unique_completed_topic_ids);

    $categories_progress = [];
    foreach ($dt as $cat_id => $total) {
        $done = $dr[$cat_id];
        $percent = ($total > 0) ? round(($done / $total) * 100, 2) : 0;
        $categories_progress[] = [
            'id'             => $cat_id,
            'name'           => $categories_name[$cat_id] ?? '',
            'total_topics'   => $total,
            'completed'      => $done,
            'progress'       => $percent,
        ];
    }

    return [
        'progress_total'    => round($t),
        'completed_topics'  => $completed_topics,
        'total_topics'      => $total_topics,
        'all_topics'        => $unique_topic_ids,
        'completed_ids'     => $unique_completed_topic_ids,
        'lesson_ids'        => $lesson_ids,
        'categories_total'  => $dt,
        'categories_done'   => $dr,
        'categories_detail' => $categories_progress,
    ];
}

function xfusion_shortcode_filtered_course_progress($atts): string
{
    $atts = shortcode_atts(['lesson_id' => 0], $atts, 'filtered_course_progress');
    $progress = xfusion_get_filtered_course_progress(get_current_user_id(), (int) $atts['lesson_id']);

    if (!is_array($progress)) {
        return '<p>' . esc_html((string) $progress) . '</p>';
    }

    return
        '<div class="learndash-wrapper learndash-widget">
    <div class="ld-progress ld-progress-inline">
        <div class="ld-progress-heading">
            <div class="ld-progress-stats">
                <div class="ld-progress-percentage ld-secondary-color">'
                . esc_html((string) $progress['progress_total']) . '% Complete</div>
                <div class="ld-progress-steps">
                    Last activity on February 13, 2025 9:08 am</div>
            </div>
        </div>
        <div class="ld-progress-bar">
            <div class="ld-progress-bar-percentage ld-secondary-background" style="width:' . esc_attr((string) $progress['progress_total']) . '%"></div>
        </div>
    </div>
</div>';
}

add_shortcode('filtered_course_progress', 'xfusion_shortcode_filtered_course_progress');

function xfusion_shortcode_course_progress($atts): string
{
    $atts = shortcode_atts(['course_id' => 0], $atts, 'course_progress');
    $course_id = (int) $atts['course_id'];
    $progress = 0;

    if (!is_user_logged_in() || !$course_id) {
        return '';
    }

    $user_id = get_current_user_id();

    $lessons = learndash_get_lesson_list($course_id, ['num' => 0]);
    $total_topics = 0;
    $completed_topics = 0;

    foreach ($lessons as $lesson) {
        $lesson_id = $lesson->ID;

        $topics = learndash_get_topic_list($lesson_id, $course_id);

        foreach ($topics as $topic) {
            $total_topics++;
            if (learndash_is_topic_complete($user_id, $topic->ID)) {
                $completed_topics++;
            }
        }
    }

    if ($total_topics > 0) {
        $progress = (int) round(($completed_topics / $total_topics) * 100);
    }

    return
        '<div class="learndash-wrapper learndash-widget">
    <div class="ld-progress ld-progress-inline">
        <div class="ld-progress-heading">
            <div class="ld-progress-stats">
                <div class="ld-progress-percentage ld-secondary-color">'
                . esc_html((string) $progress) . '% Complete</div>
                <div class="ld-progress-steps">
                    Last activity on February 13, 2025 9:08 am</div>
            </div>
        </div>
        <div class="ld-progress-bar">
            <div class="ld-progress-bar-percentage ld-secondary-background" style="width:' . esc_attr((string) $progress) . '%"></div>
        </div>
    </div>
</div>';
}

add_shortcode('course_progress', 'xfusion_shortcode_course_progress');
