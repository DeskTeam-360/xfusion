<?php
/**
 * ARR Step 3 — AI Annual Readiness Assessment™ Laravel bridge.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarr_assessment_generate', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_POST['arr_id']) ? absint($_POST['arr_id']) : 0;
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }
    xfarr_picker_send(xfarr_picker_api_request('POST', "/{$arrId}/assessment/generate", [], [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarr_assessment_load', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_GET['arr_id']) ? absint($_GET['arr_id']) : 0;
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }
    xfarr_picker_send(xfarr_picker_api_request('GET', "/{$arrId}/assessment", [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarr_assessment_save_agreement', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_POST['arr_id']) ? absint($_POST['arr_id']) : 0;
    $rating = isset($_POST['agreement_rating']) ? sanitize_text_field(wp_unslash($_POST['agreement_rating'])) : '';
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }
    xfarr_picker_send(xfarr_picker_api_request('POST', "/{$arrId}/assessment/agreement", [], [
        'user_id' => get_current_user_id(),
        'agreement_rating' => $rating,
    ]));
});

add_action('wp_ajax_xfarr_assessment_save_context', function (): void {
    check_ajax_referer('xfarr_wizard', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
    $arrId = isset($_POST['arr_id']) ? absint($_POST['arr_id']) : 0;
    $context = isset($_POST['executive_context']) ? sanitize_textarea_field(wp_unslash($_POST['executive_context'])) : '';
    if ($arrId < 1) {
        wp_send_json_error(['message' => 'arr_id is required.'], 422);
    }
    xfarr_picker_send(xfarr_picker_api_request('POST', "/{$arrId}/assessment/context", [], [
        'user_id' => get_current_user_id(),
        'executive_context' => $context,
    ]));
});

function xfarr_wizard_assessment_service_js(): string
{
    return <<<'JS'
window.xfarrGenerateAssessment = function () {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.reject(new Error('No ARR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarr_assessment_generate');
    payload.set('nonce', window.XFARR_WIZARD.nonce);
    payload.set('arr_id', String(window.XFARR_WIZARD.arrId));
    return fetch(window.XFARR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xfarrLoadAssessment = function () {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarr_assessment_load');
    params.set('nonce', window.XFARR_WIZARD.nonce);
    params.set('arr_id', String(window.XFARR_WIZARD.arrId));
    return fetch(window.XFARR_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success) ? json.data : null; })
        .catch(function () { return null; });
};

window.xfarrSaveAssessmentAgreement = function (rating) {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.reject(new Error('No ARR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarr_assessment_save_agreement');
    payload.set('nonce', window.XFARR_WIZARD.nonce);
    payload.set('arr_id', String(window.XFARR_WIZARD.arrId));
    payload.set('agreement_rating', rating);
    return fetch(window.XFARR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xfarrSaveAssessmentContext = function (context) {
    if (!window.XFARR_WIZARD || !window.XFARR_WIZARD.arrId) {
        return Promise.reject(new Error('No ARR selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarr_assessment_save_context');
    payload.set('nonce', window.XFARR_WIZARD.nonce);
    payload.set('arr_id', String(window.XFARR_WIZARD.arrId));
    payload.set('executive_context', context);
    return fetch(window.XFARR_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};
JS;
}
