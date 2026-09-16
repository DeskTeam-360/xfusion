<?php
/**
 * IRR Step 7 — Publish™ Laravel bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfirr_publish_now', function (): void {
    check_ajax_referer('xfirr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $irrId = isset($_POST['irr_id']) ? absint($_POST['irr_id']) : 0;
    if ($irrId < 1) {
        wp_send_json_error(['message' => 'irr_id is required.'], 422);
    }
    xfirr_picker_send(xfirr_picker_api_request('POST', "/{$irrId}/publish", [], [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfirr_refresh', function (): void {
    check_ajax_referer('xfirr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $irrId = isset($_GET['irr_id']) ? absint($_GET['irr_id']) : 0;
    if ($irrId < 1) {
        wp_send_json_error(['message' => 'irr_id is required.'], 422);
    }
    xfirr_picker_send(xfirr_picker_api_request('GET', "/{$irrId}", [
        'user_id' => get_current_user_id(),
    ]));
});

function xfirr_wizard_publish_service_js(): string
{
    return <<<'JS'
window.xfirrPublishNow = function () {
    if (!window.XFIRR_WIZARD || !window.XFIRR_WIZARD.irrId) {
        return Promise.reject(new Error('No review selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfirr_publish_now');
    payload.set('nonce', window.XFIRR_WIZARD.nonce);
    payload.set('irr_id', String(window.XFIRR_WIZARD.irrId));
    return fetch(window.XFIRR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xfirrRefreshStepProgress = function () {
    if (!window.XFIRR_WIZARD || !window.XFIRR_WIZARD.irrId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfirr_refresh');
    params.set('nonce', window.XFIRR_WIZARD.nonce);
    params.set('irr_id', String(window.XFIRR_WIZARD.irrId));
    return fetch(window.XFIRR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json && json.success && json.data) {
                window.XFIRR_WIZARD.stepProgress = json.data.step_progress || {};
                window.XFIRR_WIZARD.status = json.data.status || window.XFIRR_WIZARD.status;
            }
            return (json && json.success) ? json.data : null;
        })
        .catch(function () { return null; });
};
JS;
}
