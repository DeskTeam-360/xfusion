<?php
/**
 * Step 7 — Publish ARR™.
 *
 * Real: Publish calls Laravel (POST /{arr}/publish), which requires every
 * prior step's progress flag (evidence, assessment, reflection,
 * recommendations, synthesis) to be true. Archive calls
 * POST /{arr}/archive. The wizard's own header/footer "Publish ARR →"
 * button (last-step relabel of Next Step) drives the same real publish
 * action as the in-panel Publish ARR button — no duplicate Save Draft
 * button here since this step has no step-specific fields of its own
 * (same dedup fix as IRR Step 7).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_publish_js(): string
{
    return <<<'JS'
publish: function () {
    return '<h2 class="xarr-section-title">Step 7. Publish ARR™</h2>' +
        '<p class="xarr-section-desc">You are ready to publish your Annual Readiness Review™. Publishing will activate the next Annual Readiness Plan™ workspace and update all connected dashboards and history.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>Once published, this ARR becomes part of your organization\'s official learning history. You can continue to view and reference it anytime.</span></div>' +

        '<div class="xarr-card"><h3 style="margin-top:0">Review Summary</h3>' +
        '<p class="xarr-muted" style="margin-top:-.3rem">Every step must be complete before you can publish.</p>' +
        '<div class="xarr-review-list" id="xarr-review-list"></div>' +
        '</div>' +

        '<h4 style="color:var(--navy);text-transform:uppercase;letter-spacing:.03em;font-size:15px">Publish Actions</h4>' +
        '<div class="xarr-card"><div class="xarr-row" style="justify-content:space-between;align-items:flex-start;gap:1rem">' +
        '<div class="xarr-row" style="align-items:flex-start;gap:.85rem"><div class="xarr-synth-icon">&#128203;</div>' +
        '<div><h4 style="margin:0 0 .2rem">Publish ARR™</h4>' +
        '<p class="xarr-muted" style="margin:0 0 .3rem">Finalize and publish your Annual Readiness Review™. This action cannot be undone.</p>' +
        '<span class="xarr-muted" style="font-size:13px"><span class="xarr-check" style="margin-top:0">&#10003;</span> Makes this ARR the official organizational learning record.</span></div></div>' +
        '<button type="button" class="xarr-btn xarr-btn-accent" id="xarr-publish-go" style="flex-shrink:0">Publish ARR</button>' +
        '</div></div>' +

        '<div class="xarr-card"><div class="xarr-row" style="justify-content:space-between;align-items:flex-start;gap:1rem">' +
        '<div class="xarr-row" style="align-items:flex-start;gap:.85rem"><div class="xarr-synth-icon">&#128230;</div>' +
        '<div><h4 style="margin:0 0 .2rem">Archive ARR™</h4>' +
        '<p class="xarr-muted" style="margin:0 0 .3rem">Archive this ARR as a historical record without publishing.</p>' +
        '<span class="xarr-muted" style="font-size:13px"><span class="xarr-check" style="margin-top:0">&#10003;</span> Keeps this ARR out of active organizational history until restored.</span></div></div>' +
        '<button type="button" class="xarr-btn xarr-btn-outline" id="xarr-archive-go" style="flex-shrink:0">Archive ARR</button>' +
        '</div></div>' +

        '<h4 style="color:var(--navy);text-transform:uppercase;letter-spacing:.03em;font-size:15px;margin-top:1.5rem">Publishing Will Activate</h4>' +
        '<div class="xarr-activate-grid">' +
        [['&#127942;','Next ARP Planning Workspace','Populate the next Annual Readiness Plan™ with your Strategic Renewal Recommendations™ and future focus areas as draft planning considerations.'],
         ['&#128202;','Executive Dashboard Updates','Update executive dashboards with new readiness trends, strategic intelligence, and organizational learning.'],
         ['&#128218;','Organizational Learning History','Add this ARR to your official organizational learning history for longitudinal tracking and strategic reference.'],
         ['&#128200;','Historical Readiness Timeline','Extend your multi-year readiness timeline with this year\'s progress and insights for future comparison.']].map(function (a) {
            return '<div class="xarr-activate-card"><div style="font-size:1.5rem">' + a[0] + '</div><h4>' + a[1] + '</h4><p>' + a[2] + '</p></div>';
        }).join('') + '</div>' +

        '<p class="xarr-muted" id="xarr-publish-status" style="margin-top:1rem"></p>';
}
JS;
}

function xfarr_wizard_publish_init_js(): string
{
    return <<<'JS'
(function () {
    var REVIEW_STEPS = [
        { key: 'evidence', label: 'Generate Annual Evidence™', steps: 'Steps 1–2' },
        { key: 'assessment', label: 'AI Annual Readiness Assessment™', steps: 'Step 3' },
        { key: 'reflection', label: 'Executive Strategic Reflection™', steps: 'Step 4' },
        { key: 'recommendations', label: 'Strategic Renewal Recommendations™', steps: 'Step 5' },
        { key: 'synthesis', label: 'AI Strategic Renewal Synthesis™', steps: 'Step 6' },
    ];

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function setPublishStatus(text, isError) {
        var el = document.getElementById('xarr-publish-status');
        if (!el) return;
        el.style.color = isError ? '#dc2626' : '#16a34a';
        el.textContent = text;
    }

    function stepProgressMap() {
        var progress = window.XFARR_WIZARD && window.XFARR_WIZARD.stepProgress ? window.XFARR_WIZARD.stepProgress : {};
        return progress && typeof progress === 'object' ? progress : {};
    }

    function isPublished() {
        return window.XFARR_WIZARD && String(window.XFARR_WIZARD.status).toLowerCase() === 'published';
    }

    function renderReview() {
        var list = document.getElementById('xarr-review-list');
        if (!list) return;
        var progress = stepProgressMap();
        list.innerHTML = REVIEW_STEPS.map(function (row) {
            var done = !!progress[row.key];
            return '<div class="xarr-review-row">' +
                '<div class="xarr-review-left">' +
                '<span class="xarr-review-check" style="color:' + (done ? '#16a34a' : '#d97706') + '">' + (done ? '&#10003;' : '&#9675;') + '</span>' +
                '<div><strong>' + esc(row.label) + '</strong><div class="xarr-review-status">' + (done ? 'Completed ' : 'Incomplete — ') + row.steps + '</div></div>' +
                '</div></div>';
        }).join('');
    }

    function doPublish() {
        if (isPublished()) {
            setPublishStatus('This Annual Readiness Review™ is already published.', true);
            return;
        }
        var progress = stepProgressMap();
        var missing = REVIEW_STEPS.filter(function (row) { return !progress[row.key]; });
        if (missing.length) {
            setPublishStatus('Complete all steps before publishing: ' + missing.map(function (m) { return m.label; }).join(', ') + '.', true);
            return;
        }
        if (!window.confirm('Publish this Annual Readiness Review™? This action cannot be undone.')) return;
        if (typeof window.xfarrPublishArr !== 'function') {
            setPublishStatus('Publish service unavailable.', true);
            return;
        }

        setPublishStatus('Publishing…', false);
        window.xfarrPublishArr().then(function (json) {
            if (!json || !json.success) {
                setPublishStatus((json && json.message) ? json.message : 'Publish failed.', true);
                return;
            }
            window.XFARR_WIZARD.status = (json.data && json.data.status) ? json.data.status : 'published';
            var statusBadge = document.querySelector('#xarr-si-status .xarr-badge');
            if (statusBadge) {
                statusBadge.textContent = 'Published';
                statusBadge.classList.remove('amber');
                statusBadge.classList.add('green');
            }
            setPublishStatus('Published successfully. This record is now part of your official organizational learning history.', false);
        }).catch(function () {
            setPublishStatus('Publish failed — network error.', true);
        });
    }

    window.initPublishStep = function () {
        renderReview();

        var publishBtn = document.getElementById('xarr-publish-go');
        var archiveBtn = document.getElementById('xarr-archive-go');

        if (isPublished()) {
            setPublishStatus('This Annual Readiness Review™ has been published and is locked.', false);
            if (publishBtn) publishBtn.disabled = true;
            if (archiveBtn) archiveBtn.disabled = true;
        }

        if (publishBtn && !publishBtn.dataset.wired) {
            publishBtn.dataset.wired = '1';
            publishBtn.addEventListener('click', doPublish);
        }
        if (archiveBtn && !archiveBtn.dataset.wired) {
            archiveBtn.dataset.wired = '1';
            archiveBtn.addEventListener('click', function () {
                if (isPublished()) {
                    setPublishStatus('This Annual Readiness Review™ is already published and cannot be archived.', true);
                    return;
                }
                if (!window.confirm('Archive this ARR? It will be kept out of active organizational history until restored.')) return;
                if (typeof window.xfarrArchiveArr !== 'function') {
                    setPublishStatus('Archive service unavailable.', true);
                    return;
                }
                setPublishStatus('Archiving…', false);
                window.xfarrArchiveArr().then(function (json) {
                    if (!json || !json.success) {
                        setPublishStatus((json && json.message) ? json.message : 'Archive failed.', true);
                        return;
                    }
                    window.XFARR_WIZARD.status = (json.data && json.data.status) ? json.data.status : 'archived';
                    var statusBadge = document.querySelector('#xarr-si-status .xarr-badge');
                    if (statusBadge) {
                        statusBadge.textContent = 'Archived';
                        statusBadge.classList.remove('amber', 'green');
                        statusBadge.classList.add('gray');
                    }
                    setPublishStatus('Archived. This ARR is kept in draft and out of active organizational history.', false);
                }).catch(function () {
                    setPublishStatus('Archive failed — network error.', true);
                });
            });
        }
    };

    window.xarrPublishReview = function () {
        doPublish();
    };
})();
JS;
}
