<?php
/**
 * Save Draft — ARP steps 1, 2, 5 → Gravity Forms.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_wizard_save_draft', 'xfarp_wizard_ajax_save_draft');

function xfarp_wizard_ajax_save_draft(): void
{
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    if (! class_exists('GFAPI')) {
        wp_send_json_error(['message' => 'Gravity Forms not available.'], 503);
    }

    $step = isset($_POST['step']) ? sanitize_key(wp_unslash($_POST['step'])) : '';
    if (! in_array($step, xfarp_gf_step_keys(), true)) {
        wp_send_json_error(['message' => 'Invalid or unsupported step for GF save.'], 422);
    }

    if (! xfarp_gf_step_is_configured($step)) {
        wp_send_json_error([
            'message' => 'GF mapping for this step is not configured yet. Fill in arp-gf-mapping.php.',
            'step' => $step,
        ], 503);
    }

    $planYear = isset($_POST['plan_year']) ? absint($_POST['plan_year']) : 0;
    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    $companyId = isset($_POST['company_id']) ? absint($_POST['company_id']) : 0;

    $context = xfarp_wizard_session_context($planYear, $arpId);
    if ($companyId > 0) {
        $context['company_id'] = $companyId;
    }

    if ((int) $context['company_id'] < 1) {
        wp_send_json_error(['message' => 'company_id is required. Link the user to a company or pass company_id on the shortcode.'], 422);
    }

    $values = xfarp_wizard_decode_json_post('values');
    if ($values === []) {
        wp_send_json_error(['message' => 'No field values received.'], 422);
    }

    $result = xfarp_gf_save_step($step, $values, $context);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message(), 'step' => $step], 500);
    }

    wp_send_json_success([
        'message' => 'Draft saved.',
        'saved' => [$result],
        'saved_at' => current_time('g:i A'),
        'step' => $step,
    ]);
}

/**
 * @return array<string, mixed>
 */
function xfarp_wizard_decode_json_post(string $key): array
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

/**
 * JS: collect textarea values + POST to admin-ajax save-draft endpoint.
 */
function xfarp_wizard_save_draft_js(): string
{
    return <<<'JS'
var xarSaveDraftBusy = false;

var xarStepCacheKey = function (stepKey) {
    if (stepKey === 'foundation') {
        return 'xarFoundationCache';
    }
    if (stepKey === 'future_state') {
        return 'xarFutureStateCache';
    }
    if (stepKey === 'learning') {
        return 'xarLearningCache';
    }
    return '';
};

var xarCollectStepValues = function (stepKey) {
    var out = {};
    var main = root.querySelector('#xar-main');
    if (!main) {
        return out;
    }
    main.querySelectorAll('textarea[data-key]').forEach(function (ta) {
        out[ta.getAttribute('data-key')] = ta.value;
    });
    var cacheKey = xarStepCacheKey(stepKey);
    if (cacheKey) {
        window[cacheKey] = out;
    }
    return out;
};

var xarUpdateAutosaveLabel = function (text, isError) {
    var el = root.querySelector('#xar-autosave-status');
    if (!el) {
        return;
    }
    el.innerHTML = isError
        ? '<span style="color:#dc2626">&#9888; ' + text + '</span>'
        : '<span class="xar-autosave-check" aria-hidden="true">&#10003;</span> ' + text;
};

var xarSaveDraft = function () {
    if (xarSaveDraftBusy || !window.XFARP_WIZARD) {
        return;
    }

    var stepKey = STEPS[current] ? STEPS[current].key : '';

    var laravelBackedSteps = {
        readiness: window.xarSaveReadinessDraft,
        priorities: window.xarSaveStrategicDraft,
        ai_review: function () {
            var ta = root.querySelector('#xar-leadership-context-card textarea[data-key="leadership_context"]');
            var ctx = ta ? ta.value : (window.xarLeadershipContext || '');
            return window.xarSaveLeadershipContext(ctx);
        },
    };

    if (laravelBackedSteps[stepKey]) {
        if (!window.XFARP_WIZARD.arpId) {
            xarUpdateAutosaveLabel('No ARP selected — cannot save.', true);
            return;
        }
        xarSaveDraftBusy = true;
        xarUpdateAutosaveLabel('Saving draft...', false);
        laravelBackedSteps[stepKey]()
            .then(function (json) {
                if (!json || !json.success) {
                    xarUpdateAutosaveLabel((json && json.message) ? json.message : 'Save failed.', true);
                    return;
                }
                xarUpdateAutosaveLabel('Draft saved' + (json.saved_at ? ' ' + json.saved_at : ''), false);
                var savedEl = root.querySelector('#xar-si-saved');
                if (savedEl && json.saved_at) {
                    savedEl.textContent = json.saved_at;
                }
            })
            .catch(function () {
                xarUpdateAutosaveLabel('Save failed — network error', true);
            })
            .finally(function () {
                xarSaveDraftBusy = false;
            });
        return;
    }

    if (['foundation', 'future_state', 'learning'].indexOf(stepKey) === -1 && stepKey !== 'ai_review') {
        xarUpdateAutosaveLabel('Draft save for this step will use Laravel API (coming soon).', false);
        return;
    }

    if (!window.XFARP_WIZARD.gfConfigured || !window.XFARP_WIZARD.gfConfigured[stepKey]) {
        xarUpdateAutosaveLabel('GF mapping belum diisi untuk step ini (arp-gf-mapping.php).', true);
        return;
    }

    if (!window.XFARP_WIZARD.companyId) {
        xarUpdateAutosaveLabel('User belum terhubung ke company — draft tidak bisa disimpan.', true);
        return;
    }

    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_wizard_save_draft');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('step', stepKey);
    payload.set('company_id', String(window.XFARP_WIZARD.companyId));
    payload.set('plan_year', String(window.XFARP_WIZARD.planYear || ''));
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId || '0'));
    payload.set('values', JSON.stringify(xarCollectStepValues(stepKey)));

    xarSaveDraftBusy = true;
    xarUpdateAutosaveLabel('Saving draft...', false);

    fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success) {
                var msg = (json && json.data && json.data.message) ? json.data.message : 'Save failed.';
                xarUpdateAutosaveLabel(msg, true);
                return;
            }
            var savedAt = (json.data && json.data.saved_at) ? json.data.saved_at : '';
            xarUpdateAutosaveLabel('Draft saved' + (savedAt ? ' ' + savedAt : ''), false);
            var savedEl = root.querySelector('#xar-si-saved');
            if (savedEl && savedAt) {
                savedEl.textContent = savedAt;
            }
            if (window.xarDraftCache && window.xarDraftCache.data) {
                window.xarDraftCache.data[stepKey] = xarCollectStepValues(stepKey);
            }
        })
        .catch(function () {
            xarUpdateAutosaveLabel('Save failed — network error', true);
        })
        .finally(function () {
            xarSaveDraftBusy = false;
        });
};

window.xarSaveDraft = xarSaveDraft;

['#xar-save-draft', '#xar-save-draft-2'].forEach(function (sel) {
    var btn = root.querySelector(sel);
    if (btn) {
        btn.addEventListener('click', xarSaveDraft);
    }
});
JS;
}
