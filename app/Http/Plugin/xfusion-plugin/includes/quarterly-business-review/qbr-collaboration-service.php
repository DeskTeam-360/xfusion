<?php
/**
 * Step 4 — Leadership Collaboration™ (discussion notes + key decisions):
 * Laravel-backed bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfqbr_discussion_notes_save', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_POST['qbr_id']) ? absint($_POST['qbr_id']) : 0;
    $notes = wp_kses_post(wp_unslash($_POST['discussion_notes'] ?? ''));
    xfqbr_picker_send(xfqbr_picker_api_request('POST', "/{$qbrId}/discussion-notes", [], [
        'user_id' => get_current_user_id(),
        'discussion_notes' => $notes,
    ]));
});

add_action('wp_ajax_xfqbr_decisions_load', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_GET['qbr_id']) ? absint($_GET['qbr_id']) : 0;
    xfqbr_picker_send(xfqbr_picker_api_request('GET', "/{$qbrId}/decisions"));
});

add_action('wp_ajax_xfqbr_decisions_save', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_POST['qbr_id']) ? absint($_POST['qbr_id']) : 0;
    $items = xfqbr_wizard_decode_json_post('items');
    xfqbr_picker_send(xfqbr_picker_api_request('POST', "/{$qbrId}/decisions", [], [
        'user_id' => get_current_user_id(),
        'items' => $items,
    ]));
});

function xfqbr_wizard_collaboration_service_js(): string
{
    return <<<'JS'
window.xqbrSaveDiscussionNotes = function (notes) {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.reject(new Error('No QBR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfqbr_discussion_notes_save');
    payload.set('nonce', window.XFQBR_WIZARD.nonce);
    payload.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    payload.set('discussion_notes', notes || '');
    return fetch(window.XFQBR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xqbrLoadDecisions = function () {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.resolve([]);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfqbr_decisions_load');
    params.set('nonce', window.XFQBR_WIZARD.nonce);
    params.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    return fetch(window.XFQBR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success && Array.isArray(json.data)) ? json.data : []; })
        .catch(function () { return []; });
};

window.xqbrSaveDecisions = function (items) {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.reject(new Error('No QBR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfqbr_decisions_save');
    payload.set('nonce', window.XFQBR_WIZARD.nonce);
    payload.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    payload.set('items', JSON.stringify(items || []));
    return fetch(window.XFQBR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xqbrCollectCollaborationDecisions = function () {
    var list = document.getElementById('xqbr-decisions-list');
    if (!list) {
        return [];
    }
    var items = [];
    list.querySelectorAll('.xqbr-prio-card').forEach(function (card) {
        var item = {};
        card.querySelectorAll('[data-key]').forEach(function (el) {
            item[el.getAttribute('data-key')] = el.value;
        });
        items.push({
            decision: (item.decision || '').trim(),
            owner_user_id: null,
            owner_name: (item.owner_name || '').trim() || null,
            impact_area: (item.impact_area || '').trim() || null,
            next_step: (item.next_step || '').trim() || null,
            target_date: (item.target_date || '').trim() || null,
        });
    });
    return items;
};

window.xqbrSaveCollaborationStep = function (snapshot) {
    var notesEl = document.getElementById('xqbr-discussion-notes');
    var notes = snapshot ? snapshot.notes : (notesEl ? notesEl.value : '');
    var items = snapshot ? snapshot.decisions : window.xqbrCollectCollaborationDecisions();

    return Promise.all([
        window.xqbrSaveDiscussionNotes(notes),
        window.xqbrSaveDecisions(items),
    ]).then(function (results) {
        var notesJson = results[0];
        var decisionsJson = results[1];
        if (!notesJson || !notesJson.success) {
            return notesJson || { success: false, message: 'Failed to save discussion notes.' };
        }
        if (window.XFQBR_WIZARD) {
            window.XFQBR_WIZARD.discussionNotes = notes;
        }
        if (!decisionsJson || !decisionsJson.success) {
            return decisionsJson || { success: false, message: 'Failed to save key decisions.' };
        }
        if (typeof window.xqbrCollaborationMarkSaved === 'function') {
            window.xqbrCollaborationMarkSaved();
        }
        return {
            success: true,
            saved_at: decisionsJson.saved_at || decisionsJson.data && decisionsJson.data.saved_at || '',
        };
    });
};
JS;
}
