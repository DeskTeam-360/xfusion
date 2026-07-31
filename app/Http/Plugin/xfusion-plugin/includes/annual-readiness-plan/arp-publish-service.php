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

/* -------------------------------------------------------------------
 * Version History sidebar card (between "About This Step" and
 * "Progress") — lists every archived/published snapshot for this ARP
 * and opens a read-only modal with the full snapshot content.
 * ------------------------------------------------------------------- */
function xarVersionHistoryEsc(s) {
    return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function xarVersionHistoryFormatDate(iso) {
    if (!iso) return '—';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return String(iso);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) +
        ' ' + d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

window.xarInitVersionHistoryCard = function () {
    var list = document.getElementById('xar-version-history-list');
    var emptyEl = document.getElementById('xar-version-history-empty');
    if (!list || !emptyEl || typeof window.xarLoadArpVersions !== 'function') {
        return;
    }

    emptyEl.textContent = 'Loading…';
    list.innerHTML = '';

    window.xarLoadArpVersions().then(function (versions) {
        if (!versions || !versions.length) {
            emptyEl.textContent = 'No versions yet. Archive or publish to create one.';
            return;
        }

        emptyEl.style.display = 'none';
        list.innerHTML = '<ul class="xar-version-history-list">' + versions.map(function (v) {
            var badgeClass = v.status === 'published' ? 'green' : 'amber';
            var dateLabel = v.published_at ? xarVersionHistoryFormatDate(v.published_at) : xarVersionHistoryFormatDate(v.created_at);
            return '<li>' +
                '<div class="xar-version-history-row">' +
                '<span class="xar-badge ' + badgeClass + '" style="font-size:12px">v' + xarVersionHistoryEsc(v.version) + '</span>' +
                '<span class="xar-muted" style="font-size:12px">' + xarVersionHistoryEsc(dateLabel) + '</span>' +
                '<a href="?" class="xar-link xar-version-view-btn" data-version-id="' + v.id + '" style="font-size:12px;font-weight:600;color:#5f9a3f;text-decoration:underline">View</a>' +
                '</div></li>';
        }).join('') + '</ul>';

        list.querySelectorAll('.xar-version-view-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var versionId = btn.getAttribute('data-version-id');
                var url = new URL(window.location.href);
                url.searchParams.set('version_id', versionId);
                window.location.href = url.toString();
            });
        });
    }).catch(function () {
        emptyEl.textContent = 'Unable to load version history.';
    });
};
JS;
}
