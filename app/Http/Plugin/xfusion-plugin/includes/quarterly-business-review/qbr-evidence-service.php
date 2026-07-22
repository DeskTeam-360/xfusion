<?php
/**
 * Steps 1/2 — Organizational Evidence™ + custom KPIs: Laravel-backed bridge.
 * Reuses xfqbr_picker_api_request() from qbr-picker.php.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfqbr_evidence_generate', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_POST['qbr_id']) ? absint($_POST['qbr_id']) : 0;
    if ($qbrId < 1) {
        wp_send_json_error(['message' => 'qbr_id is required.'], 422);
    }
    xfqbr_picker_send(xfqbr_picker_api_request('POST', "/{$qbrId}/evidence/generate", [], ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfqbr_evidence_load', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_GET['qbr_id']) ? absint($_GET['qbr_id']) : 0;
    if ($qbrId < 1) {
        wp_send_json_error(['message' => 'qbr_id is required.'], 422);
    }
    xfqbr_picker_send(xfqbr_picker_api_request('GET', "/{$qbrId}/evidence"));
});

add_action('wp_ajax_xfqbr_kpis_load', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_GET['qbr_id']) ? absint($_GET['qbr_id']) : 0;
    xfqbr_picker_send(xfqbr_picker_api_request('GET', "/{$qbrId}/kpis"));
});

add_action('wp_ajax_xfqbr_kpis_save', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_POST['qbr_id']) ? absint($_POST['qbr_id']) : 0;
    $items = xfqbr_wizard_decode_json_post('items');
    xfqbr_picker_send(xfqbr_picker_api_request('POST', "/{$qbrId}/kpis", [], [
        'user_id' => get_current_user_id(),
        'items' => $items,
    ]));
});

/**
 * @return array<string, mixed>
 */
function xfqbr_wizard_decode_json_post(string $key): array
{
    if (! isset($_POST[$key])) {
        return [];
    }
    $raw = wp_unslash($_POST[$key]);
    if (is_array($raw)) {
        return $raw;
    }
    $decoded = json_decode((string) $raw, true);

    return is_array($decoded) ? $decoded : [];
}

function xfqbr_wizard_evidence_service_js(): string
{
    return <<<'JS'
window.xqbrGenerateEvidence = function () {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.reject(new Error('No QBR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfqbr_evidence_generate');
    payload.set('nonce', window.XFQBR_WIZARD.nonce);
    payload.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    return fetch(window.XFQBR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xqbrLoadEvidence = function () {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfqbr_evidence_load');
    params.set('nonce', window.XFQBR_WIZARD.nonce);
    params.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    return fetch(window.XFQBR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success) ? json.data : null; })
        .catch(function () { return null; });
};

window.xqbrLoadKpis = function () {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.resolve([]);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfqbr_kpis_load');
    params.set('nonce', window.XFQBR_WIZARD.nonce);
    params.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    return fetch(window.XFQBR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success && Array.isArray(json.data)) ? json.data : []; })
        .catch(function () { return []; });
};

window.xqbrSaveKpis = function (items) {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.reject(new Error('No QBR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfqbr_kpis_save');
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
