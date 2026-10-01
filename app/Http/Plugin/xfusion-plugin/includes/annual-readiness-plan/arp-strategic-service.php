<?php
/**
 * Step 4 — Strategic Priorities™: Laravel-backed save/load bridge.
 *
 * Mirrors arp-readiness-service.php's pattern. Saves to
 * wp_fusion_arp_strategic_priorities via ArpController::getStrategicPriorities
 * / saveStrategicPriorities. Reuses xfarp_picker_api_request() from
 * arp-picker.php for the HTTP bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_strategic_load', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('GET', "/{$arpId}/strategic-priorities"));
});

add_action('wp_ajax_xfarp_strategic_save', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    $items = xfarp_wizard_decode_json_post('items');

    // Laravel validates org_kpi / readiness_indicator as strings (TEXT JSON),
    // while the UI collects them as arrays. Encode arrays to JSON strings
    // here so validation passes and the column stores a JSON array.
    $stringFields = ['title', 'description', 'success_measures', 'related_readiness', 'target_date'];
    $jsonArrayFields = ['org_kpi', 'readiness_indicator'];
    $items = array_map(static function ($item) use ($stringFields, $jsonArrayFields) {
        if (! is_array($item)) {
            return $item;
        }
        foreach ($stringFields as $field) {
            if (! array_key_exists($field, $item) || $item[$field] === null || $item[$field] === '') {
                // description is optional — space survives ConvertEmptyStringsToNull
                // when the live column is still NOT NULL.
                $item[$field] = ($field === 'description') ? ' ' : '';
            } else {
                $item[$field] = (string) $item[$field];
            }
        }
        foreach ($jsonArrayFields as $field) {
            if (! array_key_exists($field, $item) || $item[$field] === null || $item[$field] === '') {
                $item[$field] = '[]';
            } elseif (is_array($item[$field])) {
                $item[$field] = wp_json_encode(array_values($item[$field]));
            } else {
                $item[$field] = (string) $item[$field];
            }
        }

        return $item;
    }, $items);

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/strategic-priorities", [], [
        'user_id' => get_current_user_id(),
        'items' => $items,
    ]));
});

/**
 * JS: fetch/save Step 4 strategic priorities against the Laravel API.
 * Exposed as window.xarLoadStrategicDraft / window.xarSaveStrategicDraft.
 */
function xfarp_wizard_strategic_service_js(): string
{
    return <<<'JS'
window.xarLoadStrategicDraft = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarp_strategic_load');
    params.set('nonce', window.XFARP_WIZARD.nonce);
    params.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !Array.isArray(json.data)) {
                return null;
            }
            return json.data;
        })
        .catch(function () { return null; });
};

window.xarSaveStrategicDraft = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    var items = window.xarStrategicCache || [];
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_strategic_save');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));
    payload.set('items', JSON.stringify(items));

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
