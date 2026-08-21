<?php
/**
 * IRR Step 4 — Development Conversation™ notes + digital signatures.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfirr_conversation_load', function (): void {
    check_ajax_referer('xfirr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $irrId = isset($_GET['irr_id']) ? absint($_GET['irr_id']) : 0;
    if ($irrId < 1) {
        wp_send_json_error(['message' => 'irr_id is required.'], 422);
    }
    xfirr_picker_send(xfirr_picker_api_request('GET', "/{$irrId}/conversation-agreement", [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfirr_conversation_save', function (): void {
    check_ajax_referer('xfirr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $irrId = isset($_POST['irr_id']) ? absint($_POST['irr_id']) : 0;
    if ($irrId < 1) {
        wp_send_json_error(['message' => 'irr_id is required.'], 422);
    }

    $body = ['user_id' => get_current_user_id()];
    if (isset($_POST['conversation_notes'])) {
        $body['conversation_notes'] = sanitize_textarea_field(wp_unslash((string) $_POST['conversation_notes']));
    }
    if (! empty($_POST['conversation_date'])) {
        $body['conversation_date'] = sanitize_text_field(wp_unslash((string) $_POST['conversation_date']));
    }
    if (! empty($_POST['sign_role'])) {
        $body['sign_role'] = sanitize_key((string) $_POST['sign_role']);
    }

    xfirr_picker_send(xfirr_picker_api_request('POST', "/{$irrId}/conversation-agreement", [], $body));
});

function xfirr_wizard_conversation_service_js(): string
{
    return <<<'JS'
window.xfirrLoadConversation = function () {
    if (!window.XFIRR_WIZARD || !window.XFIRR_WIZARD.irrId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfirr_conversation_load');
    params.set('nonce', window.XFIRR_WIZARD.nonce);
    params.set('irr_id', String(window.XFIRR_WIZARD.irrId));
    return fetch(window.XFIRR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success) ? json.data : null; })
        .catch(function () { return null; });
};

window.xfirrSaveConversation = function (fields) {
    if (!window.XFIRR_WIZARD || !window.XFIRR_WIZARD.irrId) {
        return Promise.reject(new Error('No review selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfirr_conversation_save');
    payload.set('nonce', window.XFIRR_WIZARD.nonce);
    payload.set('irr_id', String(window.XFIRR_WIZARD.irrId));
    if (fields.conversation_notes !== undefined) payload.set('conversation_notes', fields.conversation_notes);
    if (fields.conversation_date) payload.set('conversation_date', fields.conversation_date);
    if (fields.sign_role) payload.set('sign_role', fields.sign_role);
    return fetch(window.XFIRR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
