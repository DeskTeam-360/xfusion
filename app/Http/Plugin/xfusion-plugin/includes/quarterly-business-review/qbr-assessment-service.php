<?php
/**
 * Step 3 — AI Organizational Assessment™: Laravel-backed bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfqbr_assessment_load', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_GET['qbr_id']) ? absint($_GET['qbr_id']) : 0;
    xfqbr_picker_send(xfqbr_picker_api_request('GET', "/{$qbrId}/assessment"));
});

add_action('wp_ajax_xfqbr_assessment_generate', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_POST['qbr_id']) ? absint($_POST['qbr_id']) : 0;
    xfqbr_picker_send(xfqbr_picker_api_request('POST', "/{$qbrId}/assessment/generate", [], ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfqbr_assessment_save_context', function (): void {
    check_ajax_referer('xfqbr_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $qbrId = isset($_POST['qbr_id']) ? absint($_POST['qbr_id']) : 0;
    $context = sanitize_textarea_field(wp_unslash($_POST['leadership_context'] ?? ''));
    $rating  = sanitize_key($_POST['agreement_rating'] ?? '');
    xfqbr_picker_send(xfqbr_picker_api_request('POST', "/{$qbrId}/assessment/context", [], [
        'user_id' => get_current_user_id(),
        'leadership_context' => $context,
        'agreement_rating' => $rating !== '' ? $rating : null,
    ]));
});

function xfqbr_wizard_assessment_service_js(): string
{
    return <<<'JS'
window.xqbrLoadAssessment = function () {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfqbr_assessment_load');
    params.set('nonce', window.XFQBR_WIZARD.nonce);
    params.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    return fetch(window.XFQBR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success) ? json.data : null; })
        .catch(function () { return null; });
};

window.xqbrGenerateAssessment = function () {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.reject(new Error('No QBR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfqbr_assessment_generate');
    payload.set('nonce', window.XFQBR_WIZARD.nonce);
    payload.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    return fetch(window.XFQBR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xqbrSaveLeadershipContext = function (context, rating) {
    if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
        return Promise.reject(new Error('No QBR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfqbr_assessment_save_context');
    payload.set('nonce', window.XFQBR_WIZARD.nonce);
    payload.set('qbr_id', String(window.XFQBR_WIZARD.qbrId));
    payload.set('leadership_context', context || '');
    payload.set('agreement_rating', rating || '');
    return fetch(window.XFQBR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
