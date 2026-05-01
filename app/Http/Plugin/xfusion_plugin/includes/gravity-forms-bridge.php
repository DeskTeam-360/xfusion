<?php
/**
 * Bridge Gravity Forms: AJAX data entry + redirect formId → dataId + filter populate.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('wp_ajax_get_form_data_gform', 'xfusion_ajax_get_form_data_gform', 20);
add_action('wp_ajax_nopriv_get_form_data_gform', 'xfusion_ajax_get_form_data_gform', 20);

function xfusion_ajax_get_form_data_gform(): void
{
    if (!class_exists('GFAPI')) {
        wp_send_json_error(['message' => 'Gravity Forms not available.'], 503);
    }

    $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
    $raw_id = isset($_GET['order_id']) ? sanitize_text_field(wp_unslash($_GET['order_id'])) : '';
    $entry_id = absint($raw_id);

    if ($form_id < 1 || $entry_id < 1) {
        wp_send_json_error(['message' => 'Missing form_id or order_id.'], 400);
    }

    /** Entry ID bukan “field filter”; pakai get_entry agar konsisten dengan wp_gf_entry.id */
    $entry = GFAPI::get_entry($entry_id);

    if (is_wp_error($entry)) {
        wp_send_json_error(['message' => $entry->get_error_message()], 404);
    }

    if ((int) ($entry['form_id'] ?? 0) !== $form_id) {
        wp_send_json_error(['message' => 'Entry does not belong to this form.'], 400);
    }

    if (($entry['status'] ?? '') !== 'active') {
        wp_send_json_error(['message' => 'Entry not active.'], 404);
    }

    wp_send_json_success([$entry]);
}

add_action('wp_ajax_get_recapitulation_form_data_gform', 'xfusion_ajax_get_recapitulation_form_data_gform', 20);

function xfusion_ajax_get_recapitulation_form_data_gform(): void
{
    if (!class_exists('GFAPI')) {
        wp_send_json_error(['message' => 'Gravity Forms not available.'], 503);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $gform = [2, 3, 4, 7, 8, 9, 10, 13, 11, 14, 15, 16, 17, 18, 19];
    $user_id = get_current_user_id();
    $user_meta = get_userdata($user_id);
    $user_roles = $user_meta ? $user_meta->roles : [];
    $results = [];
    $forms = [];

    foreach ($gform as $g) {
        $id = $g;
        if (in_array('administrator', $user_roles, true)) {
            $search_criteria = [];
        } else {
            $search_criteria = [
                'status'         => 'active',
                'field_filters'  => [
                    'mode' => 'any',
                    ['key' => 'created_by', 'value' => $user_id],
                ],
            ];
        }
        $batch = GFAPI::get_entries($id, $search_criteria);
        if (!is_wp_error($batch)) {
            $results = array_merge($results, $batch);
        }
        $form_obj = GFAPI::get_form($id);
        if (!is_wp_error($form_obj)) {
            $forms[$g] = $form_obj;
        }
    }

    $users = [];
    $user_infos = [];

    foreach ($results as $result) {
        $created_by = $result['created_by'] ?? null;
        if (!isset($users[$created_by])) {
            $users[$created_by] = [];
            if ($created_by !== null && $created_by !== '') {
                $u = get_userdata((int) $created_by);
                $user_infos[$created_by] = $u ? $u->display_name : '';
            } else {
                $user_infos[$created_by] = 'No Login User';
            }
        }
        $users[$created_by][] = $result;
    }

    wp_send_json_success([
        'users'      => $users,
        'forms'      => $forms,
        'user_infos' => $user_infos,
    ]);
}

add_filter('gform_pre_render', 'xfusion_populate_form_based_on_user_and_form_id');
add_filter('gform_pre_validation', 'xfusion_populate_form_based_on_user_and_form_id');

/**
 * Log akses form + redirect ?formId=X ke ?dataId=entry jika user punya entry aktif.
 *
 * @param array<string, mixed> $form
 * @return array<string, mixed>
 */
function xfusion_populate_form_based_on_user_and_form_id($form)
{
    if (!class_exists('GFAPI')) {
        return $form;
    }

    if (!isset($_GET['formId'])) {
        return $form;
    }

    $form_id = absint($_GET['formId']);
    $user_id = get_current_user_id();

    if ($user_id && isset($GLOBALS['wpdb'])) {
        global $wpdb;
        $log_message = 'User ID: ' . $user_id . ', Form ID: ' . $form_id;
        $suppress = $wpdb->suppress_errors(true);
        $wpdb->insert(
            'logs',
            [
                'log'        => $log_message,
                'text'       => 'Form accessed with user_id and form_id',
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );
        $wpdb->suppress_errors($suppress);
    }

    $search_criteria = [
        'status' => 'active',
        'field_filters' => [
            'mode' => 'any',
            [
                'key'   => 'created_by',
                'value' => $user_id,
            ],
        ],
    ];

    $entries = GFAPI::get_entries($form_id, $search_criteria);

    if (is_wp_error($entries) || empty($entries)) {
        return $form;
    }

    $filtered_entries = array_filter($entries, static function ($entry) use ($user_id) {
        return isset($entry['created_by']) && (int) $entry['created_by'] === (int) $user_id;
    });

    $entry = reset($filtered_entries);

    if (!$entry || empty($entry['id'])) {
        return $form;
    }

    $redirect_url = add_query_arg('dataId', $entry['id'], isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/');

    $parsed_url = wp_parse_url($redirect_url);
    $query_params = [];
    if (!empty($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
    }
    unset($query_params['formId']);

    $new_query_string = http_build_query($query_params);
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
    $new_url = $path . ($new_query_string !== '' ? '?' . $new_query_string : '');

    wp_safe_redirect($new_url);
    exit;
}
