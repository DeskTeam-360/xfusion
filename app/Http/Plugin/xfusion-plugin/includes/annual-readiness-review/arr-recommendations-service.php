<?php
/**
 * ARR Step 5 — Strategic Renewal Recommendations™ Laravel bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarr_recommendations_load', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_GET['arr_id']) ? absint($_GET['arr_id']) : 0;
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }
    xfarr_picker_send(xfarr_picker_api_request('GET', "/{$arrId}/recommendations", [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarr_recommendations_save', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_POST['arr_id']) ? absint($_POST['arr_id']) : 0;
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }

    $itemsRaw = isset($_POST['items']) ? wp_unslash($_POST['items']) : '[]';
    $items = json_decode(is_string($itemsRaw) ? $itemsRaw : '[]', true);
    if (! is_array($items)) {
        $items = [];
    }

    xfarr_picker_send(xfarr_picker_api_request('POST', "/{$arrId}/recommendations", [], [
        'user_id' => get_current_user_id(),
        'items' => $items,
    ]));
});

function xfarr_wizard_recommendations_service_js(): string
{
    return <<<'JS'
window.xfarrLoadRecommendations = function () {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarr_recommendations_load');
    params.set('nonce', window.XFARR_WIZARD.nonce);
    params.set('arr_id', String(window.XFARR_WIZARD.arrId));
    return fetch(window.XFARR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success) ? json.data : null; })
        .catch(function () { return null; });
};

window.xfarrSaveRecommendations = function (items) {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.reject(new Error('No ARR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarr_recommendations_save');
    payload.set('nonce', window.XFARR_WIZARD.nonce);
    payload.set('arr_id', String(window.XFARR_WIZARD.arrId));
    payload.set('items', JSON.stringify(items || []));
    return fetch(window.XFARR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
