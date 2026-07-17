<?php
/**
 * Step 7 — Publish ARP™: versioning bridge (archive / publish / version history).
 *
 * Reuses xfarp_picker_api_request() from arp-picker.php.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_publish_versions', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('GET', "/{$arpId}/versions"));
});

add_action('wp_ajax_xfarp_publish_archive', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/archive-version", [], [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarp_publish_now', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/publish", [], [
        'user_id' => get_current_user_id(),
    ]));
});

/**
 * JS: real archive/publish calls + version history fetch, replacing the
 * Step 7 UI-shell alerts with actual Laravel-backed actions.
 */
function xfarp_wizard_publish_service_js(): string
{
    return <<<'JS'
window.xarLoadArpVersions = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.resolve([]);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarp_publish_versions');
    params.set('nonce', window.XFARP_WIZARD.nonce);
    params.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success && Array.isArray(json.data)) ? json.data : []; })
        .catch(function () { return []; });
};

window.xarArchiveArpVersion = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_publish_archive');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xarPublishArpNow = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_publish_now');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
