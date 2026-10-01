<?php
/**
 * Step 3 — Organizational Readiness™: Laravel-backed save/load bridge.
 *
 * Saves directly to wp_fusion_arp_readiness_priorities via the Laravel API.
 * Reuses xfarp_picker_api_request() from arp-picker.php for the HTTP bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_readiness_load', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('GET', "/{$arpId}/readiness-priorities"));
});

add_action('wp_ajax_xfarp_readiness_save', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    $items = xfarp_wizard_decode_json_post('items');

    // Optional text fields: Laravel ConvertEmptyStringsToNull turns "" into
    // null, and the live MySQL column may still be NOT NULL. Use a single
    // space for blank optional text so the insert succeeds; the UI treats
    // whitespace-only as empty on render.
    $requiredStrings = ['name'];
    $optionalText = ['description', 'business_rationale', 'expected_impact'];
    $items = array_map(static function ($item) use ($requiredStrings, $optionalText) {
        if (! is_array($item)) {
            return $item;
        }
        foreach ($requiredStrings as $field) {
            $item[$field] = isset($item[$field]) && $item[$field] !== null ? (string) $item[$field] : '';
        }
        foreach ($optionalText as $field) {
            if (! array_key_exists($field, $item) || $item[$field] === null || $item[$field] === '') {
                $item[$field] = ' ';
            } else {
                $item[$field] = (string) $item[$field];
            }
        }
        if (! isset($item['secondary_driver']) || $item['secondary_driver'] === null || $item['secondary_driver'] === '') {
            $item['secondary_driver'] = 'drive_growth';
        } else {
            $item['secondary_driver'] = (string) $item['secondary_driver'];
        }

        return $item;
    }, $items);

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/readiness-priorities", [], [
        'user_id' => get_current_user_id(),
        'items' => $items,
    ]));
});

/**
 * JS: fetch/save Step 3 readiness priorities against the Laravel API.
 * Exposed as window.xarLoadReadinessDraft / window.xarSaveReadinessDraft so
 * step-3-readiness.php (window.initReadinessStep) and the save-draft button
 * handler (arp-save-draft.php) can both call in.
 */
function xfarp_wizard_readiness_service_js(): string
{
    return <<<'JS'
window.xarLoadReadinessDraft = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarp_readiness_load');
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

window.xarSaveReadinessDraft = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    // Optional text fields must be strings (never null) so MySQL NOT NULL
    // columns / Laravel inserts do not reject blank draft textareas.
    var items = (window.xarReadinessCache || []).map(function (item) {
        var next = Object.assign({}, item);
        ['name', 'description', 'business_rationale', 'expected_impact', 'secondary_driver'].forEach(function (key) {
            next[key] = (next[key] === null || next[key] === undefined) ? '' : String(next[key]);
        });
        if (!Array.isArray(next.executive_owner_user_ids)) {
            next.executive_owner_user_ids = [];
        }
        return next;
    });
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_readiness_save');
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
