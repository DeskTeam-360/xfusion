<?php
/**
 * ARR Step 7 — Publish ARR™ Laravel bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarr_publish', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_POST['arr_id']) ? absint($_POST['arr_id']) : 0;
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }
    xfarr_picker_send(xfarr_picker_api_request('POST', "/{$arrId}/publish", [], [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarr_archive', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_POST['arr_id']) ? absint($_POST['arr_id']) : 0;
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }
    xfarr_picker_send(xfarr_picker_api_request('POST', "/{$arrId}/archive", [], [
        'user_id' => get_current_user_id(),
    ]));
});

function xfarr_wizard_publish_service_js(): string
{
    return <<<'JS'
window.xfarrPublishArr = function () {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.reject(new Error('No ARR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarr_publish');
    payload.set('nonce', window.XFARR_WIZARD.nonce);
    payload.set('arr_id', String(window.XFARR_WIZARD.arrId));
    return fetch(window.XFARR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xfarrArchiveArr = function () {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.reject(new Error('No ARR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarr_archive');
    payload.set('nonce', window.XFARR_WIZARD.nonce);
    payload.set('arr_id', String(window.XFARR_WIZARD.arrId));
    return fetch(window.XFARR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
