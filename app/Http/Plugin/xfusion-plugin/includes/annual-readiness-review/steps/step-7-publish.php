<?php
/**
 * Step 7 — Publish ARR™.
 *
 * UI-only prototype: static dummy content matching the ARR mockup (publish
 * actions, "publishing will activate" grid). All actions are local-only
 * (no Laravel calls) while the visual design is being finalized.
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

        '<h4 style="color:var(--navy);text-transform:uppercase;letter-spacing:.03em;font-size:15px">Publish Actions</h4>' +
        '<div class="xarr-card"><div class="xarr-row" style="justify-content:space-between;align-items:flex-start;gap:1rem">' +
        '<div class="xarr-row" style="align-items:flex-start;gap:.85rem"><div class="xarr-synth-icon">&#128203;</div>' +
        '<div><h4 style="margin:0 0 .2rem">Publish ARR™</h4>' +
        '<p class="xarr-muted" style="margin:0 0 .3rem">Finalize and publish your Annual Readiness Review™. This action cannot be undone.</p>' +
        '<span class="xarr-muted" style="font-size:13px"><span class="xarr-check" style="margin-top:0">&#10003;</span> Makes this ARR the official organizational learning record for 2025.</span></div></div>' +
        '<button type="button" class="xarr-btn xarr-btn-accent" id="xarr-publish-go" style="flex-shrink:0">Publish ARR</button>' +
        '</div></div>' +

        '<div class="xarr-card"><div class="xarr-row" style="justify-content:space-between;align-items:flex-start;gap:1rem">' +
        '<div class="xarr-row" style="align-items:flex-start;gap:.85rem"><div class="xarr-synth-icon">&#128230;</div>' +
        '<div><h4 style="margin:0 0 .2rem">Archive ARR™</h4>' +
        '<p class="xarr-muted" style="margin:0 0 .3rem">Archive this ARR as a historical record without publishing. This action can be reversed.</p>' +
        '<span class="xarr-muted" style="font-size:13px"><span class="xarr-check" style="margin-top:0">&#10003;</span> Keeps this ARR in draft and out of active organizational history.</span></div></div>' +
        '<button type="button" class="xarr-btn xarr-btn-outline" id="xarr-archive-go" style="flex-shrink:0">Archive ARR</button>' +
        '</div></div>' +

        '<div class="xarr-card"><div class="xarr-row" style="justify-content:space-between;align-items:flex-start;gap:1rem">' +
        '<div class="xarr-row" style="align-items:flex-start;gap:.85rem"><div class="xarr-synth-icon">&#128196;</div>' +
        '<div><h4 style="margin:0 0 .2rem">Save Draft</h4>' +
        '<p class="xarr-muted" style="margin:0 0 .3rem">Save your progress and return later. Your draft will be saved automatically.</p>' +
        '<span class="xarr-muted" style="font-size:13px"><span class="xarr-check" style="margin-top:0">&#10003;</span> Resume anytime to complete and publish.</span></div></div>' +
        '<button type="button" class="xarr-btn xarr-btn-outline" id="xarr-publish-save" style="flex-shrink:0">Save Draft</button>' +
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
    function setPublishStatus(text, isError) {
        var el = document.getElementById('xarr-publish-status');
        if (!el) return;
        el.style.color = isError ? '#dc2626' : '#16a34a';
        el.textContent = text;
    }

    window.initPublishStep = function () {
        var saveBtn = document.getElementById('xarr-publish-save');
        var publishBtn = document.getElementById('xarr-publish-go');
        var archiveBtn = document.getElementById('xarr-archive-go');

        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                setPublishStatus('Draft saved (UI shell — not yet connected).', false);
            });
        }
        if (publishBtn) {
            publishBtn.addEventListener('click', function () {
                if (!window.confirm('Publish this Annual Readiness Review™? This action cannot be undone.')) return;
                var statusBadge = document.querySelector('#xarr-si-status .xarr-badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Published';
                    statusBadge.classList.remove('amber');
                    statusBadge.classList.add('green');
                }
                setPublishStatus('Published (UI shell — not yet connected to Laravel).', false);
            });
        }
        if (archiveBtn) {
            archiveBtn.addEventListener('click', function () {
                if (!window.confirm('Archive this ARR? It will be kept in draft and out of active organizational history.')) return;
                setPublishStatus('Archived (UI shell — not yet connected to Laravel).', false);
            });
        }
    };

    window.xarrPublishReview = function () {
        var publishBtn = document.getElementById('xarr-publish-go');
        if (publishBtn) publishBtn.click();
    };
})();
JS;
}
