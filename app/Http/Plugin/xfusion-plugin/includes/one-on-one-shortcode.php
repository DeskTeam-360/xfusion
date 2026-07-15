<?php
/**
 * XFusion — 1-on-1 Alignment Capture™ AJAX bridge (WordPress)
 *
 * Used by [fusion_one_on_one_wizard]. Bridges to Laravel /api/v1/one-on-one/*
 * via WP admin-ajax so the FUSION_API_TOKEN bearer token never reaches the browser.
 *
 * Pairings come from Company Groups (Laravel admin) — not managed here.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('XFUSION_LARAVEL_API_BASE')) {
    define('XFUSION_LARAVEL_API_BASE', 'https://admin.sandbox.xperiencefusion.com');
}

if (! defined('XFUSION_API_BEARER_TOKEN')) {
    define('XFUSION_API_BEARER_TOKEN', '');
}

/**
 * @return array{ok: bool, code: int, body: mixed, error: ?string}
 */
function xfusion_oo_api_request(string $method, string $path, array $query = [], array $body = []): array
{
    $base = rtrim(XFUSION_LARAVEL_API_BASE, '/');
    $url  = $base . '/api/v1/one-on-one' . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $headers = ['Accept' => 'application/json'];
    if (XFUSION_API_BEARER_TOKEN !== '') {
        $headers['Authorization'] = 'Bearer ' . XFUSION_API_BEARER_TOKEN;
    }

    $args = [
        'method'    => $method,
        'timeout'   => 20,
        'sslverify' => true,
        'headers'   => $headers,
    ];

    if ($method !== 'GET') {
        $headers['Content-Type'] = 'application/json';
        $args['headers']         = $headers;
        $args['body']            = wp_json_encode($body);
    }

    $res = wp_remote_request($url, $args);

    if (is_wp_error($res)) {
        return ['ok' => false, 'code' => 0, 'body' => null, 'error' => $res->get_error_message()];
    }

    $code    = (int) wp_remote_retrieve_response_code($res);
    $decoded = json_decode(wp_remote_retrieve_body($res), true);

    return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'body' => $decoded, 'error' => null];
}

function xfusion_oo_send(array $result): void
{
    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json(array_merge(['success' => false, 'message' => $result['error'] ?? ($body['message'] ?? 'Request failed.')], $body), 200);
    }
    wp_send_json($result['body']);
}

function xfusion_oo_require_login(): void
{
    if (! is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => __('You must be logged in.', 'xfusion')], 401);
    }
    check_ajax_referer('xfusion_one_on_one', 'nonce');
}

// ---------------------------------------------------------------------------
// AJAX handlers
// ---------------------------------------------------------------------------

add_action('wp_ajax_xfusion_oo_pairs', function (): void {
    xfusion_oo_require_login();
    xfusion_oo_send(xfusion_oo_api_request('GET', '/pairs', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfusion_oo_leader_team', function (): void {
    xfusion_oo_require_login();
    xfusion_oo_send(xfusion_oo_api_request('GET', '/leader-team', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfusion_oo_meeting_dashboard', function (): void {
    xfusion_oo_require_login();
    xfusion_oo_send(xfusion_oo_api_request('GET', '/meeting-dashboard', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfusion_oo_schedule_for_employee', function (): void {
    xfusion_oo_require_login();
    $employeeUserId = (int) ($_POST['employee_user_id'] ?? 0);
    $scheduledAt = sanitize_text_field($_POST['scheduled_at'] ?? '');
    $meetingLink = esc_url_raw(wp_unslash($_POST['meeting_link'] ?? ''));
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $body = [
        'leader_user_id' => get_current_user_id(),
        'employee_user_id' => $employeeUserId,
        'scheduled_at' => $scheduledAt,
    ];
    if ($meetingLink !== '') {
        $body['meeting_link'] = $meetingLink;
    }
    if ($groupId > 0) {
        $body['group_id'] = $groupId;
    }
    xfusion_oo_send(xfusion_oo_api_request('POST', '/schedule-for-employee', [], $body));
});

add_action('wp_ajax_xfusion_oo_employee_scoring', function (): void {
    xfusion_oo_require_login();
    $pairId = (int) ($_POST['pair_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/{$pairId}/employee-scoring"));
});

add_action('wp_ajax_xfusion_oo_conversations', function (): void {
    xfusion_oo_require_login();
    $pairId = (int) ($_POST['pair_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/{$pairId}/conversations"));
});

add_action('wp_ajax_xfusion_oo_schedule', function (): void {
    xfusion_oo_require_login();
    $pairId      = (int) ($_POST['pair_id'] ?? 0);
    $scheduledAt = sanitize_text_field($_POST['scheduled_at'] ?? '');
    $meetingLink = esc_url_raw(wp_unslash($_POST['meeting_link'] ?? ''));
    $body = ['scheduled_at' => $scheduledAt];
    if ($meetingLink !== '') {
        $body['meeting_link'] = $meetingLink;
    }
    xfusion_oo_send(xfusion_oo_api_request('POST', "/{$pairId}/conversations", [], $body));
});

add_action('wp_ajax_xfusion_oo_my_preparation', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/my-preparation", ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfusion_oo_save_preparation', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $role           = sanitize_text_field($_POST['author_role'] ?? '');
    $content        = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/preparation", [], [
        'author_role'    => $role,
        'author_user_id' => get_current_user_id(),
        'content'        => $content,
    ]));
});

add_action('wp_ajax_xfusion_oo_preparation_status', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/preparation-status"));
});

add_action('wp_ajax_xfusion_oo_reveal', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/reveal"));
});

add_action('wp_ajax_xfusion_oo_brief', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/brief"));
});

add_action('wp_ajax_xfusion_oo_get_notes', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/notes"));
});

add_action('wp_ajax_xfusion_oo_save_note', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/notes", [], [
        'section'    => sanitize_text_field($_POST['section'] ?? 'general'),
        'note'       => sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')),
        'created_by' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfusion_oo_get_commitments', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/commitments"));
});

add_action('wp_ajax_xfusion_oo_save_commitment', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/commitments", [], [
        'title'       => sanitize_text_field($_POST['title'] ?? ''),
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'owner_role'  => sanitize_text_field($_POST['owner_role'] ?? 'shared'),
        'due_date'    => sanitize_text_field($_POST['due_date'] ?? '') ?: null,
    ]));
});

add_action('wp_ajax_xfusion_oo_complete', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/complete"));
});
