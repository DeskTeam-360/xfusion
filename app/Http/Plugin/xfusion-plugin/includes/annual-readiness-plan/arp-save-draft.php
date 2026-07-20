<?php
/**
 * Save Draft — all ARP wizard steps via Laravel API.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
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
 * JS: collect textarea values + POST to Laravel-backed save endpoints.
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

    if (!window.XFARP_WIZARD.arpId) {
        xarUpdateAutosaveLabel('No ARP selected — cannot save.', true);
        return;
    }

    var stepKey = STEPS[current] ? STEPS[current].key : '';

    var laravelBackedSteps = {
        foundation: function () { return window.xarSavePlanStep('foundation'); },
        future_state: function () { return window.xarSavePlanStep('future_state'); },
        learning: function () { return window.xarSavePlanStep('learning'); },
        readiness: window.xarSaveReadinessDraft,
        priorities: window.xarSaveStrategicDraft,
        ai_review: function () {
            var ta = root.querySelector('#xar-leadership-context-card textarea[data-key="leadership_context"]');
            var ctx = ta ? ta.value : (window.xarLeadershipContext || '');
            return window.xarSaveLeadershipContext(ctx);
        },
    };

    var saveFn = laravelBackedSteps[stepKey];
    if (!saveFn) {
        xarUpdateAutosaveLabel('Draft save for this step is not available yet.', false);
        return;
    }

    xarSaveDraftBusy = true;
    xarUpdateAutosaveLabel('Saving draft...', false);

    saveFn()
        .then(function (json) {
            if (!json || !json.success) {
                var msg = (json && json.data && json.data.message) ? json.data.message : ((json && json.message) ? json.message : 'Save failed.');
                xarUpdateAutosaveLabel(msg, true);
                return;
            }
            var savedAt = json.saved_at || (json.data && json.data.saved_at) || '';
            xarUpdateAutosaveLabel('Draft saved' + (savedAt ? ' ' + savedAt : ''), false);
            var savedEl = root.querySelector('#xar-si-saved');
            if (savedEl && savedAt) {
                savedEl.textContent = savedAt;
            }
            if (window.xarDraftCache && window.xarDraftCache.data && ['foundation', 'future_state', 'learning'].indexOf(stepKey) !== -1) {
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
