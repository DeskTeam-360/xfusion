<?php
/**
 * Bridge Gravity Forms: AJAX data entry + redirect formId → dataId + filter populate.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/** The ?dataId=NNN carried in the "param" the page posts to get_company_info (0 if none). */
function xfusion_requested_data_id(): int
{
    parse_str((string) wp_unslash($_POST['param'] ?? ''), $query);

    return isset($query['dataId']) ? absint($query['dataId']) : 0;
}

/**
 * May the signed-in person see this Gravity Forms entry (their raw answers)?
 *  - the person who made it;
 *  - a WordPress administrator or a Super Admin;
 *  - an editor / Company Admin, for entries of people in their own company.
 * Everyone else — including signed-out visitors — no. Override with the xfusion_gf_entry_viewable filter.
 *
 * @param array<string, mixed> $entry
 */
function xfusion_can_view_gf_entry(array $entry): bool
{
    $allowed = false;

    if (is_user_logged_in()) {
        $me = wp_get_current_user();
        $owner = (int) ($entry['created_by'] ?? 0);
        $rawRole = get_user_meta($me->ID, 'user_role', true);
        $role = is_array($rawRole) ? (string) ($rawRole[0] ?? '') : (string) $rawRole;

        if ($owner > 0 && $owner === (int) $me->ID) {
            $allowed = true;
        } elseif (in_array('administrator', (array) $me->roles, true) || $role === 'Super Admin') {
            $allowed = true;
        } elseif ($owner > 0 && (in_array('editor', (array) $me->roles, true) || $role === 'Company Admin')) {
            $mine = (int) get_user_meta($me->ID, 'company', true);
            $allowed = $mine > 0 && $mine === (int) get_user_meta($owner, 'company', true);
        }
    }

    return (bool) apply_filters('xfusion_gf_entry_viewable', $allowed, $entry);
}

/** The active entry $entryId of form $formId if the signed-in person may see it, else null. */
function xfusion_viewable_entry_for_form(int $entryId, int $formId): ?array
{
    if (! class_exists('GFAPI') || $entryId < 1 || $formId < 1) {
        return null;
    }

    $entry = GFAPI::get_entry($entryId);
    if (is_wp_error($entry) || (int) ($entry['form_id'] ?? 0) !== $formId || ($entry['status'] ?? '') !== 'active') {
        return null;
    }

    return xfusion_can_view_gf_entry($entry) ? $entry : null;
}

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

    // This endpoint is also registered for signed-out visitors (nopriv) and used to hand any entry's
    // answers to anyone who knew its number. Only people who may see the entry get it now.
    if (! xfusion_can_view_gf_entry($entry)) {
        wp_send_json_error(['message' => 'Not allowed.'], 403);
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
