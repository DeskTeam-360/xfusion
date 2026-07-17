<?php
/**
 * Load Draft — fetch saved GF values for ARP steps 1, 2, 5.
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

    $planYear = isset($_GET['plan_year']) ? absint($_GET['plan_year']) : 0;
    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    $companyId = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;

    $context = xfarp_wizard_session_context($planYear, $arpId);
    if ($companyId > 0) {
        $context['company_id'] = $companyId;
    }

    if ((int) $context['company_id'] < 1) {
        wp_send_json_error(['message' => 'company_id is required.'], 422);
    }

    $data = xfarp_gf_load_all_steps($context);

    wp_send_json_success([
        'company_id' => (int) $context['company_id'],
        'plan_year' => (int) $context['plan_year'],
        'arp_id' => (int) $context['arp_id'],
        'foundation' => $data['foundation'],
        'future_state' => $data['future_state'],
        'learning' => $data['learning'],
    ]);
}

/**
 * JS: fetch draft from server, cache, and apply to step UI.
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
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.companyId) {
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
    params.set('company_id', String(window.XFARP_WIZARD.companyId));
    params.set('plan_year', String(window.XFARP_WIZARD.planYear || ''));
    params.set('arp_id', String(window.XFARP_WIZARD.arpId || '0'));

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

if (window.XFARP_WIZARD && window.XFARP_WIZARD.companyId) {
    xarLoadDraft(false).then(function () {
        if (typeof xarApplyDraftForCurrentStep === 'function') {
            xarApplyDraftForCurrentStep();
        }
    });
}
JS;
}
