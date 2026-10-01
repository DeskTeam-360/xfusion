<?php
/**
 * Step 4 — Key Performance Indicators™: Laravel-backed save/load bridge.
 *
 * Saves directly to wp_fusion_arp_kpis via the Laravel API.
 * Reuses xfarp_picker_api_request() from arp-picker.php for the HTTP bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_kpi_load', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('GET', "/{$arpId}/kpis"));
});

add_action('wp_ajax_xfarp_kpi_save', function (): void {
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
    $optionalText = ['description', 'why_it_matters', 'notes'];
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

        return $item;
    }, $items);

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/kpis", [], [
        'user_id' => get_current_user_id(),
        'items' => $items,
    ]));
});

/**
 * JS: fetch/save Step 4 KPIs against the Laravel API.
 * Exposed as window.xarLoadKpiDraft / window.xarSaveKpiDraft so
 * step-4-kpis.php (window.initKpiStep) and the save-draft button
 * handler (arp-save-draft.php) can both call in.
 */
function xfarp_wizard_kpi_service_js(): string
{
    return <<<'JS'
window.xarLoadKpiDraft = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarp_kpi_load');
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

window.xarSaveKpiDraft = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    // Optional text fields must be strings (never null) so MySQL NOT NULL
    // columns / Laravel inserts do not reject blank draft textareas.
    var items = (window.xarKpiCache || []).map(function (item) {
        var next = Object.assign({}, item);
        ['name', 'description', 'why_it_matters', 'notes', 'current_baseline', 'target_value', 'data_source'].forEach(function (key) {
            next[key] = (next[key] === null || next[key] === undefined) ? '' : String(next[key]);
        });
        if (!Array.isArray(next.readiness_priority_ids)) {
            next.readiness_priority_ids = [];
        }
        return next;
    });
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_kpi_save');
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
