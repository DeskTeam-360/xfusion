<?php
/**
 * Steps 1, 2, 5 — Laravel-backed save/load bridge (foundation, future state, learning).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_plan_load', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    $result = xfarp_picker_api_request('GET', "/{$arpId}/plan", [
        'user_id' => get_current_user_id(),
    ]);

    if (! $result['ok']) {
        wp_send_json_error([
            'message' => is_string($result['error'] ?? null) ? $result['error'] : 'Failed to load plan.',
        ], $result['code'] ?: 500);
    }

    $body = is_array($result['body'] ?? null) ? $result['body'] : [];
    $data = is_array($body['data'] ?? null) ? $body['data'] : [];

    wp_send_json_success($data);
});

add_action('wp_ajax_xfarp_plan_save', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    $step = isset($_POST['step']) ? sanitize_key(wp_unslash($_POST['step'])) : '';

    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    $endpoints = [
        'foundation' => 'foundation',
        'future_state' => 'future-state',
        'learning' => 'learning',
    ];

    if (! isset($endpoints[$step])) {
        wp_send_json_error(['message' => 'Invalid step.'], 422);
    }

    $values = xfarp_wizard_decode_json_post('values');
    if ($values === []) {
        wp_send_json_error(['message' => 'No field values received.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/{$endpoints[$step]}", [], [
        'user_id' => get_current_user_id(),
        'values' => $values,
    ]));
});

function xfarp_wizard_plan_service_js(): string
{
    return <<<'JS'
window.xarSavePlanStep = function (stepKey) {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }

    var values = {};
    var main = root.querySelector('#xar-main');
    if (main) {
        main.querySelectorAll('textarea[data-key]').forEach(function (ta) {
            values[ta.getAttribute('data-key')] = ta.value;
        });
    }

    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_plan_save');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));
    payload.set('step', stepKey);
    payload.set('values', JSON.stringify(values));

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
