<?php
/**
 * Step 5 — Quarterly Commitments™: Laravel-backed bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfqbr_commitments_load', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_GET['qbr_id']) ? absint($_GET['qbr_id']) : 0;
    xfqbr_picker_send(xfqbr_picker_api_request('GET', "/{$qbrId}/commitments"));
});

add_action('wp_ajax_xfqbr_commitments_save', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_POST['qbr_id']) ? absint($_POST['qbr_id']) : 0;
    $items = xfqbr_wizard_decode_json_post('items');
    xfqbr_picker_send(xfqbr_picker_api_request('POST', "/{$qbrId}/commitments", [], [
        'user_id' => get_current_user_id(),
        'items' => $items,
    ]));
});

function xfqbr_wizard_commitments_service_js(): string
{
    return <<<'JS'
window.xqbrLoadCommitments = function () {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.resolve([]);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfqbr_commitments_load');
    params.set('nonce', window.XFQBR_WIZARD.nonce);
    params.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    return fetch(window.XFQBR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success && Array.isArray(json.data)) ? json.data : []; })
        .catch(function () { return []; });
};

window.xqbrSaveCommitments = function (items) {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.reject(new Error('No QBR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfqbr_commitments_save');
    payload.set('nonce', window.XFQBR_WIZARD.nonce);
    payload.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    payload.set('items', JSON.stringify(items || []));
    return fetch(window.XFQBR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
