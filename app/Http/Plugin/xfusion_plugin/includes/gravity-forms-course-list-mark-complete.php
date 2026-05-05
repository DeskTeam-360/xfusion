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

    if (!learndash_process_mark_complete($user_id, $topic_id)) {
        return;
    }

    $entry_id = isset($entry['id']) ? (int) $entry['id'] : 0;
    if ($entry_id > 0) {
        xfusion_post_next_course_api($entry_id);
    }
}

/**
 * Admin Laravel untuk /api/next-course: sandbox WP → admin.sandbox, produksi WP → admin (tanpa sandbox).
 * Fallback: XFUSION_LARAVEL_API_BASE jika host tidak dikenali.
 */
function xfusion_next_course_admin_base_url(): string
{
    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $host = $host ? strtolower((string) $host) : '';
    $host = (string) preg_replace('/^www\./', '', $host);

    if ($host !== '' && str_contains($host, 'sandbox.xperiencefusion.com')) {
        return 'https://admin.sandbox.xperiencefusion.com';
    }

    if ($host !== '' && str_ends_with($host, 'xperiencefusion.com') && !str_contains($host, 'sandbox')) {
        return 'https://admin.xperiencefusion.com';
    }

    if (defined('XFUSION_LARAVEL_API_BASE') && XFUSION_LARAVEL_API_BASE !== '') {
        return rtrim((string) XFUSION_LARAVEL_API_BASE, '/');
    }

    return 'https://admin.sandbox.xperiencefusion.com';
}

/**
 * Memanggil Laravel /api/next-course setelah topic LearnDash ditandai selesai (sinkronisasi keap/progress).
 *
 * @param int $entry_id Gravity Forms entry ID (mis. ?entry_id=2938).
 */
function xfusion_post_next_course_api(int $entry_id): void
{
    if ($entry_id < 1 || !function_exists('wp_remote_post')) {
        return;
    }

    $base = xfusion_next_course_admin_base_url();

    $url = $base . '/api/next-course?entry_id=' . $entry_id;

    $args = [
        'timeout' => 15,
        'sslverify' => true,
        'blocking' => false,
        'headers' => [
            'Accept' => 'application/json',
        ],
        'body' => [
            'entry_id' => $entry_id,
        ],
    ];

    if (defined('XFUSION_API_BEARER_TOKEN') && XFUSION_API_BEARER_TOKEN !== '') {
        $args['headers']['Authorization'] = 'Bearer ' . XFUSION_API_BEARER_TOKEN;
    }

    wp_remote_post($url, $args);
}
