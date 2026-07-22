<?php
/**
 * Save Draft — QBR wizard steps backed by Laravel API.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_save_draft_js(): string
{
    return <<<'JS'
var xqbrSaveDraftBusy = false;

var xqbrUpdateAutosaveLabel = function (text, isError, allowHtml) {
    var el = root.querySelector('#xqbr-autosave-status');
    if (!el) {
        return;
    }
    if (allowHtml) {
        el.innerHTML = isError
            ? '<span style="color:#dc2626">&#9888; ' + text + '</span>'
            : text;
        return;
    }
    el.innerHTML = isError
        ? '<span style="color:#dc2626">&#9888; ' + text + '</span>'
        : '<span class="xqbr-autosave-check" aria-hidden="true">&#10003;</span> ' + text;
};

window.xqbrSetAutosaveStatus = xqbrUpdateAutosaveLabel;

var xqbrUpdateLastSaved = function (savedAt) {
    if (!savedAt) {
        return;
    }
    var savedEl = root.querySelector('#xqbr-si-saved');
    if (savedEl) {
        savedEl.textContent = savedAt;
    }
};

window.xqbrUpdateLastSaved = xqbrUpdateLastSaved;

window.xqbrSaveDraft = function () {
    if (xqbrSaveDraftBusy || !window.XFQBR_WIZARD) {
        return Promise.resolve();
    }

    if (window.XFQBR_WIZARD.canEdit === false) {
        xqbrUpdateAutosaveLabel('View only — cannot save.', true);
        return Promise.resolve();
    }

    if (!window.XFQBR_WIZARD.qbrId) {
        xqbrUpdateAutosaveLabel('No QBR selected — cannot save.', true);
        return Promise.resolve();
    }

    var stepKey = STEPS[current] ? STEPS[current].key : '';
    var saveFn = null;

    if (stepKey === 'collaboration' && typeof window.xqbrSaveCollaborationStep === 'function') {
        saveFn = window.xqbrSaveCollaborationStep;
    } else if (stepKey === 'commitments' && typeof window.xqbrSaveCommitmentsStep === 'function') {
        saveFn = window.xqbrSaveCommitmentsStep;
    }

    if (!saveFn) {
        xqbrUpdateAutosaveLabel('Save Draft is not available for this step yet.', false);
        return Promise.resolve();
    }

    xqbrSaveDraftBusy = true;
    if (typeof window.xqbrSetAutosaveLoading === 'function') {
        window.xqbrSetAutosaveLoading('Saving draft…');
    } else {
        xqbrUpdateAutosaveLabel('Saving draft...', false);
    }

    return saveFn()
        .then(function (json) {
            if (!json || !json.success) {
                var msg = (json && json.data && json.data.message)
                    ? json.data.message
                    : ((json && json.message) ? json.message : 'Save failed.');
                xqbrUpdateAutosaveLabel(msg, true);
                return json;
            }
            var savedAt = json.saved_at || (json.data && json.data.saved_at) || '';
            xqbrUpdateAutosaveLabel('Draft saved' + (savedAt ? ' ' + savedAt : ''), false);
            xqbrUpdateLastSaved(savedAt);
            return json;
        })
        .catch(function (err) {
            xqbrUpdateAutosaveLabel((err && err.message) ? err.message : 'Save failed — network error', true);
        })
        .finally(function () {
            xqbrSaveDraftBusy = false;
        });
};

['#xqbr-save-draft', '#xqbr-save-draft-2'].forEach(function (sel) {
    var btn = root.querySelector(sel);
    if (btn) {
        btn.addEventListener('click', function () {
            window.xqbrSaveDraft();
        });
    }
});
JS;
}
