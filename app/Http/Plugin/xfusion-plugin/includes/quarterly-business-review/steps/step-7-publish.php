<?php
/**
 * Step 7 — Publish.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_publish_js(): string
{
    return <<<'JS'
publish: function () {
    return '<h2 class="xqbr-section-title">Step 7. Publish</h2>' +
        '<p class="xqbr-section-desc">Review your Quarterly Business Review™ and finalize to make it available across the FUSION platform. Publishing will lock this QBR and make it accessible to downstream dashboards and reports.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>Please review the QBR summary below. Once published, this record cannot be edited. You may create a new QBR for the next quarter.</span></div>' +
        '<div class="xqbr-card"><h3 style="margin-top:0">QBR Summary Review</h3>' +
        '<div class="xqbr-review-list" id="xqbr-review-list"></div></div>' +
        '<div class="xqbr-card"><h3 style="margin-top:0">Publish Options</h3>' +
        '<div class="xqbr-action-grid">' +
        '<div class="xqbr-action-card"><h4>Save Draft</h4><p>Save your progress and return to complete or review later.</p>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline" id="xqbr-publish-save">Save Draft</button></div>' +
        '<div class="xqbr-action-card"><h4>Publish QBR</h4><p>Finalize and publish this QBR. This will lock the record and make it available across the FUSION platform.</p>' +
        '<button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-publish-go">Publish QBR</button></div>' +
        '<div class="xqbr-action-card"><h4>Archive</h4><p>Archive this QBR without publishing. This record will be stored but not available to dashboards.</p>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline" id="xqbr-archive-go">Archive QBR</button></div>' +
        '</div>' +
        '<p class="xqbr-muted" id="xqbr-publish-status" style="margin-top:.75rem"></p>' +
        '</div>';
}
JS;
}

function xfqbr_wizard_publish_init_js(): string
{
    return <<<'JS'
(function () {
    var STEP_LABELS = {
        evidence: 'Organizational Evidence™ (Steps 1–2)',
        assessment: 'AI Organizational Assessment™ (Step 3)',
        collaboration: 'Leadership Collaboration™ (Step 4)',
        commitments: 'Quarterly Commitments™ (Step 5)',
        synthesis: 'AI Organizational Synthesis™ (Step 6)',
    };

    function setPublishStatus(text, isError) {
        var el = document.getElementById('xqbr-publish-status');
        if (!el) return;
        el.style.color = isError ? '#dc2626' : '#6b7280';
        el.textContent = text;
    }

    function renderReview() {
        var list = document.getElementById('xqbr-review-list');
        if (!list) return;
        var progress = (window.XFQBR_WIZARD && window.XFQBR_WIZARD.stepProgress) || {};
        list.innerHTML = Object.keys(STEP_LABELS).map(function (key) {
            var done = !!progress[key];
            return '<div class="xqbr-review-row">' +
                '<div class="xqbr-review-left">' +
                '<span class="xqbr-review-check" style="' + (done ? '' : 'background:#f3f4f6;color:#9ca3af') + '">' + (done ? '&#10003;' : '&#9675;') + '</span>' +
                '<div><strong>' + STEP_LABELS[key] + '</strong><div class="xqbr-review-status">' + (done ? 'Complete' : 'Not started') + '</div></div>' +
                '</div></div>';
        }).join('');
    }

    window.xqbrPublishQbr = function () {
        if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
            setPublishStatus('No QBR selected — cannot publish.', true);
            return;
        }
        var confirmed = window.confirm('Publish this Quarterly Business Review™? Once published, this record cannot be edited.');
        if (!confirmed) return;

        var btn = document.getElementById('xqbr-publish-go');
        if (btn) btn.disabled = true;
        setPublishStatus('Publishing…', false);

        window.xqbrPublishNow().then(function (res) {
            if (btn) btn.disabled = false;
            if (!res || !res.success) {
                setPublishStatus((res && res.message) ? res.message : 'Publish failed.', true);
                return;
            }
            var statusBadge = document.querySelector('#xqbr-si-status .xqbr-badge');
            if (statusBadge) {
                statusBadge.textContent = 'Closed';
                statusBadge.classList.remove('amber');
                statusBadge.classList.add('green');
            }
            setPublishStatus('Published successfully.', false);
        }).catch(function () {
            if (btn) btn.disabled = false;
            setPublishStatus('Publish failed — network error.', true);
        });
    };

    window.initPublishStep = function () {
        renderReview();
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;

        var saveBtn = document.getElementById('xqbr-publish-save');
        var publishBtn = document.getElementById('xqbr-publish-go');
        var archiveBtn = document.getElementById('xqbr-archive-go');

        if (!canEdit) {
            [saveBtn, publishBtn, archiveBtn].forEach(function (b) { if (b) b.style.display = 'none'; });
            setPublishStatus('You are viewing this QBR as a member — only leaders can publish or archive.', false);
            return;
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var headerSave = document.getElementById('xqbr-save-draft');
                if (headerSave) headerSave.click();
                setPublishStatus('Draft saved.', false);
            });
        }

        if (publishBtn) {
            publishBtn.addEventListener('click', function () { window.xqbrPublishQbr(); });
        }

        if (archiveBtn) {
            archiveBtn.addEventListener('click', function () {
                if (!window.confirm('Archive this QBR? It will no longer be available to dashboards.')) return;
                archiveBtn.disabled = true;
                setPublishStatus('Archiving…', false);
                window.xqbrArchiveNow().then(function (res) {
                    archiveBtn.disabled = false;
                    if (!res || !res.success) {
                        setPublishStatus((res && res.message) ? res.message : 'Archive failed.', true);
                        return;
                    }
                    var statusBadge = document.querySelector('#xqbr-si-status .xqbr-badge');
                    if (statusBadge) {
                        statusBadge.textContent = 'Archived';
                        statusBadge.classList.remove('amber');
                    }
                    setPublishStatus('Archived.', false);
                }).catch(function () {
                    archiveBtn.disabled = false;
                    setPublishStatus('Archive failed — network error.', true);
                });
            });
        }
    };
})();
JS;
}
