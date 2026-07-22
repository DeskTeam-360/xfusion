<?php
/**
 * Step 7 — Publish.
 *
 * Publish / archive via Laravel API (xqbrPublishNow / xqbrArchiveNow).
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
        '<div id="xqbr-publish-ready-banner"></div>' +
        '<div class="xqbr-card"><h3 style="margin-top:0">Publish Options</h3>' +
        '<div class="xqbr-action-grid">' +
        '<div class="xqbr-action-card"><h4>📄 Save Draft</h4><p>Save progress on Steps 4–5 before publishing. This step has no draft of its own.</p>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline" id="xqbr-publish-save">Save Draft</button></div>' +
        '<div class="xqbr-action-card"><h4>📨 Publish QBR</h4><p>Finalize and publish this QBR. This will lock the record and make it available across the FUSION platform.</p>' +
        '<button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-publish-go">Publish QBR</button></div>' +
        '<div class="xqbr-action-card"><h4>🗄️ Archive</h4><p>Archive this QBR without publishing. This record will be stored but not available to dashboards.</p>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline" id="xqbr-archive-go">Archive QBR</button></div>' +
        '</div>' +
        '<p class="xqbr-muted" id="xqbr-publish-status" style="margin-top:.75rem"></p>' +
        '</div>' +
        '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">' +
        '<div class="xqbr-card" style="margin-bottom:0"><h4>Publish Impact</h4>' +
        '<ul class="xqbr-check-list">' +
        '<li><span class="xqbr-check">&#10003;</span>Makes this QBR available to all relevant dashboards and reports.</li>' +
        '<li><span class="xqbr-check">&#10003;</span>Feeds data into the Annual Readiness Review™.</li>' +
        '<li><span class="xqbr-check">&#10003;</span>Supports organizational learning and trend analysis.</li>' +
        '</ul></div>' +
        '<div class="xqbr-card" style="margin-bottom:0"><h4>QBR Document Preview</h4>' +
        '<p class="xqbr-muted">View the full AI Organizational Synthesis™ that will be included in the published QBR.</p>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-preview-btn">View Preview</button></div>' +
        '</div>';
}
JS;
}

function xfqbr_wizard_publish_init_js(): string
{
    return <<<'JS'
(function () {
    var REVIEW_STEPS = [
        { key: 'evidence', label: 'Organizational Evidence™', steps: 'Steps 1–2', stepIndex: 1 },
        { key: 'assessment', label: 'AI Organizational Assessment™', steps: 'Step 3', stepIndex: 2 },
        { key: 'collaboration', label: 'Leadership Collaboration™', steps: 'Step 4', stepIndex: 3 },
        { key: 'commitments', label: 'Quarterly Commitments™', steps: 'Step 5', stepIndex: 4 },
        { key: 'synthesis', label: 'AI Organizational Synthesis™', steps: 'Step 6', stepIndex: 5 },
    ];

    function qbrStatus() {
        return window.XFQBR_WIZARD && window.XFQBR_WIZARD.status
            ? String(window.XFQBR_WIZARD.status).toLowerCase()
            : 'draft';
    }

    function isLocked() {
        var s = qbrStatus();
        return s === 'closed' || s === 'archived';
    }

    function setPublishStatus(text, isError, isLoading) {
        var el = document.getElementById('xqbr-publish-status');
        if (!el) return;
        el.style.color = isError ? '#dc2626' : '#16a34a';
        if (isLoading && window.xqbrSpinnerHtml) {
            el.innerHTML = window.xqbrSpinnerHtml(text);
            el.style.color = 'var(--muted)';
            return;
        }
        el.textContent = text;
    }

    function updateSidebarStatus(statusLabel, badgeClass) {
        var statusBadge = document.querySelector('#xqbr-si-status .xqbr-badge');
        if (statusBadge) {
            statusBadge.textContent = statusLabel;
            statusBadge.classList.remove('amber', 'green', 'gray');
            if (badgeClass) {
                statusBadge.classList.add(badgeClass);
            }
        }
    }

    function stepProgressMap() {
        var progress = window.XFQBR_WIZARD && window.XFQBR_WIZARD.stepProgress
            ? window.XFQBR_WIZARD.stepProgress
            : {};
        return progress && typeof progress === 'object' ? progress : {};
    }

    function renderReview() {
        var list = document.getElementById('xqbr-review-list');
        if (!list) return;
        var progress = stepProgressMap();
        list.innerHTML = REVIEW_STEPS.map(function (row) {
            var done = !!progress[row.key];
            return '<button type="button" class="xqbr-review-row xqbr-review-row-link" data-step-index="' + row.stepIndex + '" aria-label="Go to ' + row.label + '">' +
                '<div class="xqbr-review-left">' +
                '<span class="xqbr-review-check" style="color:' + (done ? '#16a34a' : '#d97706') + '">' +
                (done ? '&#10003;' : '&#9675;') + '</span>' +
                '<div><strong>' + row.label + '</strong>' +
                '<div class="xqbr-review-status">' + (done ? 'Completed ' : 'Incomplete — ') + row.steps + '</div></div>' +
                '</div>' +
                '<span class="xqbr-review-go" aria-hidden="true">&rarr;</span>' +
                '</button>';
        }).join('');
    }

    function bindReviewNavigation() {
        var list = document.getElementById('xqbr-review-list');
        if (!list) return;
        list.querySelectorAll('[data-step-index]').forEach(function (btn) {
            btn.onclick = function () {
                var idx = parseInt(btn.getAttribute('data-step-index'), 10);
                if (typeof window.xqbrGoTo === 'function' && !isNaN(idx)) {
                    window.xqbrGoTo(idx);
                }
            };
        });
    }

    function allStepsComplete() {
        var progress = stepProgressMap();
        return REVIEW_STEPS.every(function (row) { return !!progress[row.key]; });
    }

    function renderReadyBanner() {
        var wrap = document.getElementById('xqbr-publish-ready-banner');
        if (!wrap) return;
        var status = qbrStatus();
        if (status === 'closed') {
            wrap.innerHTML = '<div class="xqbr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534">&#9989; <span><b>QBR Status: Published.</b> This QBR is locked and available across the FUSION platform.</span></div>';
            return;
        }
        if (status === 'archived') {
            wrap.innerHTML = '<div class="xqbr-banner warn">&#128451; <span><b>QBR Status: Archived.</b> This record is stored but not available to dashboards.</span></div>';
            return;
        }
        if (allStepsComplete()) {
            wrap.innerHTML = '<div class="xqbr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534">&#9989; <span><b>QBR Status: Ready to Publish.</b> All required steps are complete. You may publish this QBR to finalize.</span></div>';
            return;
        }
        wrap.innerHTML = '<div class="xqbr-banner warn">&#9888; <span><b>QBR Status: In Progress.</b> Complete all wizard steps before publishing. You can still save a draft or archive this QBR.</span></div>';
    }

    function setActionButtonsDisabled(disabled) {
        ['xqbr-publish-save', 'xqbr-publish-go', 'xqbr-archive-go'].forEach(function (id) {
            var btn = document.getElementById(id);
            if (btn) btn.disabled = !!disabled;
        });
    }

    window.xqbrPublishQbr = function () {
        if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
            setPublishStatus('No QBR selected — cannot publish.', true);
            return;
        }
        if (isLocked()) {
            setPublishStatus('This QBR is already published or archived.', true);
            return;
        }
        if (!window.confirm('Publish this Quarterly Business Review™? Once published, this record cannot be edited.')) {
            return;
        }

        var publishBtn = document.getElementById('xqbr-publish-go');
        if (publishBtn) publishBtn.disabled = true;
        setPublishStatus('Publishing QBR…', false, true);

        window.xqbrPublishNow().then(function (json) {
            if (publishBtn) publishBtn.disabled = false;
            if (!json || !json.success) {
                var msg = (json && json.message) ? json.message : 'Publish failed.';
                setPublishStatus(msg, true);
                return;
            }

            window.XFQBR_WIZARD.status = (json.data && json.data.status) ? json.data.status : 'closed';
            window.XFQBR_WIZARD.canEdit = false;
            updateSidebarStatus('Closed', 'green');
            renderReadyBanner();
            setActionButtonsDisabled(true);
            var archiveBtn = document.getElementById('xqbr-archive-go');
            var saveBtn = document.getElementById('xqbr-publish-save');
            if (publishBtn) publishBtn.style.display = 'none';
            if (archiveBtn) archiveBtn.style.display = 'none';
            if (saveBtn) saveBtn.style.display = 'none';

            if (typeof window.xqbrSetAutosaveStatus === 'function') {
                window.xqbrSetAutosaveStatus('QBR published', false);
            }
            setPublishStatus('QBR published successfully. This record is now locked.', false);
            if (typeof window.xqbrRenderCurrentStep === 'function') {
                window.xqbrRenderCurrentStep();
            }
        }).catch(function () {
            if (publishBtn) publishBtn.disabled = false;
            setPublishStatus('Publish failed — network error.', true);
        });
    };

    window.xqbrArchiveQbr = function () {
        if (!window.XFQBR_WIZARD || !window.XFQBR_WIZARD.qbrId) {
            setPublishStatus('No QBR selected — cannot archive.', true);
            return;
        }
        if (isLocked()) {
            setPublishStatus('This QBR is already published or archived.', true);
            return;
        }
        if (!window.confirm('Archive this QBR? It will no longer be available to dashboards.')) {
            return;
        }

        var archiveBtn = document.getElementById('xqbr-archive-go');
        if (archiveBtn) archiveBtn.disabled = true;
        setPublishStatus('Archiving QBR…', false, true);

        window.xqbrArchiveNow().then(function (json) {
            if (archiveBtn) archiveBtn.disabled = false;
            if (!json || !json.success) {
                var msg = (json && json.message) ? json.message : 'Archive failed.';
                setPublishStatus(msg, true);
                return;
            }

            window.XFQBR_WIZARD.status = (json.data && json.data.status) ? json.data.status : 'archived';
            window.XFQBR_WIZARD.canEdit = false;
            updateSidebarStatus('Archived', 'gray');
            renderReadyBanner();
            setActionButtonsDisabled(true);
            var publishBtn = document.getElementById('xqbr-publish-go');
            var saveBtn = document.getElementById('xqbr-publish-save');
            if (publishBtn) publishBtn.style.display = 'none';
            if (archiveBtn) archiveBtn.style.display = 'none';
            if (saveBtn) saveBtn.style.display = 'none';

            if (typeof window.xqbrSetAutosaveStatus === 'function') {
                window.xqbrSetAutosaveStatus('QBR archived', false);
            }
            setPublishStatus('QBR archived successfully.', false);
            if (typeof window.xqbrRenderCurrentStep === 'function') {
                window.xqbrRenderCurrentStep();
            }
        }).catch(function () {
            if (archiveBtn) archiveBtn.disabled = false;
            setPublishStatus('Archive failed — network error.', true);
        });
    };

    window.initPublishStep = function () {
        renderReview();
        bindReviewNavigation();
        renderReadyBanner();

        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var locked = isLocked();

        var saveBtn = document.getElementById('xqbr-publish-save');
        var publishBtn = document.getElementById('xqbr-publish-go');
        var archiveBtn = document.getElementById('xqbr-archive-go');
        var previewBtn = document.getElementById('xqbr-preview-btn');

        if (!canEdit || locked) {
            [saveBtn, publishBtn, archiveBtn].forEach(function (b) { if (b) b.style.display = 'none'; });
            if (locked) {
                setPublishStatus(
                    qbrStatus() === 'closed'
                        ? 'This QBR has been published and is locked.'
                        : 'This QBR has been archived.',
                    false
                );
            } else {
                setPublishStatus('You are viewing this QBR as a member — only leaders can publish or archive.', false);
            }
        } else {
            setPublishStatus('', false);
        }

        if (saveBtn) {
            saveBtn.onclick = function () {
                if (typeof window.xqbrGoTo === 'function') {
                    window.xqbrGoTo(4);
                }
                setPublishStatus('Use Save Draft (header or footer) to persist Steps 4–5 before publishing.', false);
            };
        }
        if (publishBtn) {
            publishBtn.onclick = function () {
                window.xqbrPublishQbr();
            };
        }
        if (archiveBtn) {
            archiveBtn.onclick = function () {
                window.xqbrArchiveQbr();
            };
        }
        if (previewBtn) {
            previewBtn.onclick = function () {
                if (typeof window.xqbrGoTo === 'function') {
                    window.xqbrGoTo(5);
                }
            };
        }
    };
})();
JS;
}
