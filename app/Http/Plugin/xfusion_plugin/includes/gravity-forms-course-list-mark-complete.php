<?php
/**
 * After Gravity Forms submission: look up wp_course_lists by wp_gf_form_id,
 * then mark the linked LearnDash topic (lms_topic_id) complete for the user.
 *
 * Replaces a hardcoded form_id → lesson_id map: mapping lives in Laravel admin (course list + LMS topic).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('gform_after_submission', 'xfusion_gform_after_submission_mark_ld_topic_from_course_list', 10, 2);

/**
 * @param array<string, mixed> $entry
 * @param array<string, mixed> $form
 */
function xfusion_gform_after_submission_mark_ld_topic_from_course_list(array $entry, array $form): void
{
    if (!isset($form['id'])) {
        return;
    }

    $form_id = (int) $form['id'];
    if ($form_id < 1) {
        return;
    }

    global $wpdb;

    /** @see App\Models\CourseList — table name matches Laravel */
    $row = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT lms_topic_id FROM wp_course_lists WHERE wp_gf_form_id = %d AND lms_topic_id IS NOT NULL AND lms_topic_id > 0 ORDER BY id ASC LIMIT 1',
            $form_id
        ),
        ARRAY_A
    );

    if (!$row || empty($row['lms_topic_id'])) {
        return;
    }

    $topic_id = (int) $row['lms_topic_id'];
    if ($topic_id < 1) {
        return;
    }

    $user_id = get_current_user_id();
    if ($user_id < 1 && !empty($entry['created_by'])) {
        $user_id = (int) $entry['created_by'];
    }

    if ($user_id < 1) {
        return;
    }

    if (!function_exists('learndash_process_mark_complete')) {
        return;
    }

    if (function_exists('learndash_is_topic_complete') && learndash_is_topic_complete($user_id, $topic_id)) {
        return;
    }

    learndash_process_mark_complete($user_id, $topic_id);
}
