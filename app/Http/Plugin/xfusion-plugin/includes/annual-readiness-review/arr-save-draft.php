<?php
/**
 * Save Draft — ARR wizard steps backed by Laravel API.
 *
 * Dispatches to a per-step save function based on the current step, same
 * pattern as the IRR/QBR wizards' save-draft.php. Steps without a real save
 * function yet just show "not available for this step".
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_save_draft_js(): string
{
    return <<<'JS'
var xarrSaveDraftBusy = false;

var xarrUpdateAutosaveLabel = function (text, isError) {
    var el = root.querySelector('#xarr-autosave-status');
    if (!el) return;
    el.innerHTML = isError
        ? '<span style="color:#dc2626">&#9888; ' + text + '</span>'
        : '<span class="xarr-autosave-check" aria-hidden="true">&#10003;</span> ' + text;
};

window.xarrSetAutosaveStatus = xarrUpdateAutosaveLabel;

window.xarrSaveDraft = function () {
    if (xarrSaveDraftBusy || !window.XFARR_WIZARD) {
        return Promise.resolve();
    }

    if (window.XFARR_WIZARD.canEdit === false) {
        xarrUpdateAutosaveLabel('View only — cannot save.', true);
        return Promise.resolve();
    }

    if (!window.XFARR_WIZARD.arrId) {
        xarrUpdateAutosaveLabel('No ARR selected — cannot save.', true);
        return Promise.resolve();
    }

    var stepKey = STEPS[current] ? STEPS[current].key : '';
    var saveFn = null;

    if (stepKey === 'reflection' && typeof window.xarrSaveReflectionStep === 'function') {
        saveFn = window.xarrSaveReflectionStep;
    }

    if (stepKey === 'recommendations' && typeof window.xarrSaveRecommendationsStep === 'function') {
        saveFn = window.xarrSaveRecommendationsStep;
    }

    if (!saveFn) {
        xarrUpdateAutosaveLabel('Save Draft is not available for this step yet.', false);
        return Promise.resolve();
    }

    xarrSaveDraftBusy = true;
    xarrUpdateAutosaveLabel('Saving draft...', false);

    return saveFn()
        .then(function (json) {
            if (!json || !json.success) {
                var msg = (json && json.message) ? json.message : 'Save failed.';
                xarrUpdateAutosaveLabel(msg, true);
                return json;
            }
            var savedAt = json.saved_at ? new Date(json.saved_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '';
            xarrUpdateAutosaveLabel('Draft autosaved' + (savedAt ? ' ' + savedAt : ''), false);
            return json;
        })
        .catch(function (err) {
            xarrUpdateAutosaveLabel((err && err.message) ? err.message : 'Save failed — network error', true);
        })
        .finally(function () {
            xarrSaveDraftBusy = false;
        });
};

['#xarr-save-draft', '#xarr-save-draft-2'].forEach(function (sel) {
    var btn = root.querySelector(sel);
    if (btn) {
        btn.addEventListener('click', function () {
            window.xarrSaveDraft();
        });
    }
});
JS;
}
