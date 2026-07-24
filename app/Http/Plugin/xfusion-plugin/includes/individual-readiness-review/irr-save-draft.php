<?php
/**
 * Save Draft — IRR wizard steps backed by Laravel API.
 *
 * Dispatches to a per-step save function based on the current step, same
 * pattern as the QBR wizard's qbr-save-draft.php. Steps without a real save
 * function yet just show "not available for this step".
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_save_draft_js(): string
{
    return <<<'JS'
var xirrSaveDraftBusy = false;

var xirrUpdateAutosaveLabel = function (text, isError) {
    var el = root.querySelector('#xirr-autosave-status');
    if (!el) {
        return;
    }
    el.innerHTML = isError
        ? '<span style="color:#dc2626">&#9888; ' + text + '</span>'
        : '<span class="xirr-autosave-check" aria-hidden="true">&#10003;</span> ' + text;
};

window.xirrSetAutosaveStatus = xirrUpdateAutosaveLabel;

window.xirrSaveDraft = function () {
    if (xirrSaveDraftBusy || !window.XFIRR_WIZARD) {
        return Promise.resolve();
    }

    if (window.XFIRR_WIZARD.canEdit === false) {
        xirrUpdateAutosaveLabel('View only — cannot save.', true);
        return Promise.resolve();
    }

    if (!window.XFIRR_WIZARD.irrId) {
        xirrUpdateAutosaveLabel('No review selected — cannot save.', true);
        return Promise.resolve();
    }

    var stepKey = STEPS[current] ? STEPS[current].key : '';
    var saveFn = null;

    if (stepKey === 'commitments' && typeof window.xirrSaveCommitmentsStep === 'function') {
        saveFn = window.xirrSaveCommitmentsStep;
    }

    if (!saveFn) {
        xirrUpdateAutosaveLabel('Save Draft is not available for this step yet.', false);
        return Promise.resolve();
    }

    xirrSaveDraftBusy = true;
    xirrUpdateAutosaveLabel('Saving draft...', false);

    return saveFn()
        .then(function (json) {
            if (!json || !json.success) {
                var msg = (json && json.data && json.data.message)
                    ? json.data.message
                    : ((json && json.message) ? json.message : 'Save failed.');
                xirrUpdateAutosaveLabel(msg, true);
                return json;
            }
            var savedAt = json.saved_at || (json.data && json.data.saved_at) || '';
            xirrUpdateAutosaveLabel('Draft saved' + (savedAt ? ' ' + savedAt : ''), false);
            return json;
        })
        .catch(function (err) {
            xirrUpdateAutosaveLabel((err && err.message) ? err.message : 'Save failed — network error', true);
        })
        .finally(function () {
            xirrSaveDraftBusy = false;
        });
};

['#xirr-save-draft', '#xirr-save-draft-2'].forEach(function (sel) {
    var btn = root.querySelector(sel);
    if (btn) {
        btn.addEventListener('click', function () {
            window.xirrSaveDraft();
        });
    }
});
JS;
}
