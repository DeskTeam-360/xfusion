<?php
/**
 * Step 7 — Publish.
 *
 * UI-only prototype: static dummy content, all actions are local-only
 * (no Laravel calls) while the visual design is being finalized.
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
        '<div class="xqbr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534">&#9989; <span><b>QBR Status: Ready to Publish.</b> All required steps are complete. You may publish this QBR to finalize.</span></div>' +
        '<div class="xqbr-card"><h3 style="margin-top:0">Publish Options</h3>' +
        '<div class="xqbr-action-grid">' +
        '<div class="xqbr-action-card"><h4>📄 Save Draft</h4><p>Save your progress and return to complete or review later.</p>' +
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
    var STEP_LABELS = [
        ['Organizational Evidence™', 'Steps 1–2'],
        ['AI Organizational Assessment™', 'Step 3'],
        ['Leadership Collaboration™', 'Step 4'],
        ['Quarterly Commitments™', 'Step 5'],
        ['AI Organizational Synthesis™', 'Step 6'],
    ];

    function setPublishStatus(text, isError) {
        var el = document.getElementById('xqbr-publish-status');
        if (!el) return;
        el.style.color = isError ? '#dc2626' : '#16a34a';
        el.textContent = text;
    }

    function renderReview() {
        var list = document.getElementById('xqbr-review-list');
        if (!list) return;
        list.innerHTML = STEP_LABELS.map(function (row) {
            return '<div class="xqbr-review-row">' +
                '<div class="xqbr-review-left">' +
                '<span class="xqbr-review-check">&#10003;</span>' +
                '<div><strong>' + row[0] + '</strong><div class="xqbr-review-status">Completed ' + row[1] + '</div></div>' +
                '</div></div>';
        }).join('');
    }

    window.initPublishStep = function () {
        renderReview();
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;

        var saveBtn = document.getElementById('xqbr-publish-save');
        var publishBtn = document.getElementById('xqbr-publish-go');
        var archiveBtn = document.getElementById('xqbr-archive-go');
        var previewBtn = document.getElementById('xqbr-preview-btn');

        if (!canEdit) {
            [saveBtn, publishBtn, archiveBtn].forEach(function (b) { if (b) b.style.display = 'none'; });
            setPublishStatus('You are viewing this QBR as a member — only leaders can publish or archive.', false);
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                setPublishStatus('Draft saved (UI shell — not yet connected).', false);
            });
        }
        if (publishBtn) {
            publishBtn.addEventListener('click', function () {
                if (!window.confirm('Publish this Quarterly Business Review™? Once published, this record cannot be edited.')) return;
                var statusBadge = document.querySelector('#xqbr-si-status .xqbr-badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Closed';
                    statusBadge.classList.remove('amber');
                    statusBadge.classList.add('green');
                }
                setPublishStatus('Published (UI shell — not yet connected to Laravel).', false);
            });
        }
        if (archiveBtn) {
            archiveBtn.addEventListener('click', function () {
                if (!window.confirm('Archive this QBR? It will no longer be available to dashboards.')) return;
                setPublishStatus('Archived (UI shell — not yet connected to Laravel).', false);
            });
        }
        if (previewBtn) {
            previewBtn.addEventListener('click', function () {
                window.alert('Document preview is not yet wired up — coming soon.');
            });
        }
    };
})();
JS;
}
