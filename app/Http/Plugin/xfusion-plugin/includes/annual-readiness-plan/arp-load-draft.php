<?php
/**
 * Load Draft — Steps 1, 2, 5 from Laravel (canonical storage).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_wizard_load_draft', 'xfarp_wizard_ajax_load_draft');

function xfarp_wizard_ajax_load_draft(): void
{
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    $result = xfarp_picker_api_request('GET', "/{$arpId}/plan", [
        'user_id' => get_current_user_id(),
    ]);

    if (! $result['ok']) {
        wp_send_json_error([
            'message' => is_string($result['error'] ?? null) ? $result['error'] : 'Failed to load draft.',
        ], $result['code'] ?: 500);
    }

    $body = is_array($result['body'] ?? null) ? $result['body'] : [];
    $data = is_array($body['data'] ?? null) ? $body['data'] : [];

    wp_send_json_success([
        'company_id' => (int) ($data['company_id'] ?? 0),
        'plan_year' => (int) ($data['plan_year'] ?? 0),
        'arp_id' => (int) ($data['arp_id'] ?? $arpId),
        'foundation' => is_array($data['foundation'] ?? null) ? $data['foundation'] : [],
        'future_state' => is_array($data['future_state'] ?? null) ? $data['future_state'] : [],
        'learning' => is_array($data['learning'] ?? null) ? $data['learning'] : [],
    ]);
}

/**
 * JS: fetch draft from Laravel via admin-ajax, cache, and apply to step UI.
 */
function xfarp_wizard_load_draft_js(): string
{
    return <<<'JS'
window.xarDraftCache = { loaded: false, loading: false, data: null, _promise: null };

var xarApplyTextareaDraft = function (values) {
    if (!values || !Object.keys(values).length) {
        return;
    }
    var main = root.querySelector('#xar-main');
    if (!main) {
        return;
    }
    main.querySelectorAll('textarea[data-key]').forEach(function (ta) {
        var key = ta.getAttribute('data-key');
        if (values[key] === undefined) {
            return;
        }
        ta.value = values[key];
        var wrap = ta.closest('.xar-field');
        var counter = wrap ? wrap.querySelector('.xar-field-count') : null;
        if (counter) {
            var max = parseInt(ta.getAttribute('data-maxlen'), 10) || 0;
            counter.textContent = ta.value.length + ' / ' + max;
        }
    });
};

var xarApplyDraftForCurrentStep = function () {
    if (!window.xarDraftCache || !window.xarDraftCache.data) {
        return;
    }
    var stepKey = STEPS[current] ? STEPS[current].key : '';
    var values = window.xarDraftCache.data[stepKey];
    if (!values) {
        return;
    }
    if (stepKey === 'foundation') {
        window.xarFoundationCache = values;
    }
    if (stepKey === 'future_state') {
        window.xarFutureStateCache = values;
    }
    if (stepKey === 'learning') {
        window.xarLearningCache = values;
    }
    xarApplyTextareaDraft(values);
};

var xarLoadDraft = function (force) {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.resolve(null);
    }
    if (window.xarDraftCache.loaded && !force) {
        return Promise.resolve(window.xarDraftCache.data);
    }
    if (window.xarDraftCache._promise && !force) {
        return window.xarDraftCache._promise;
    }

    var params = new URLSearchParams();
    params.set('action', 'xfarp_wizard_load_draft');
    params.set('nonce', window.XFARP_WIZARD.nonce);
    params.set('arp_id', String(window.XFARP_WIZARD.arpId));

    window.xarDraftCache.loading = true;
    window.xarDraftCache._promise = fetch(window.XFARP_WIZARD.ajaxUrl + '?' + params.toString(), {
        credentials: 'same-origin',
    })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !json.data) {
                return null;
            }
            window.xarDraftCache.data = json.data;
            window.xarDraftCache.loaded = true;
            window.xarFoundationCache = json.data.foundation || {};
            window.xarFutureStateCache = json.data.future_state || {};
            window.xarLearningCache = json.data.learning || {};
            return json.data;
        })
        .catch(function () {
            return null;
        })
        .finally(function () {
            window.xarDraftCache.loading = false;
            window.xarDraftCache._promise = null;
        });

    return window.xarDraftCache._promise;
};

window.xarLoadDraft = xarLoadDraft;
window.xarApplyDraftForCurrentStep = xarApplyDraftForCurrentStep;

if (window.XFARP_WIZARD && window.XFARP_WIZARD.arpId) {
    xarLoadDraft(false).then(function () {
        if (typeof xarApplyDraftForCurrentStep === 'function') {
            xarApplyDraftForCurrentStep();
        }
    });
}
JS;
}
