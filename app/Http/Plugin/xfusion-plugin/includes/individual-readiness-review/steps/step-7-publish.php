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

function xfirr_wizard_step_publish_js(): string
{
    return <<<'JS'
publish: function () {
    return '<h2 class="xirr-section-title">Step 7. Publish</h2>' +
        '<p class="xirr-section-desc">Review your Annual Development Synthesis™ and publish your Individual Readiness Review™.<br>Publishing activates follow-up processes and solidifies your official annual developmental record.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>Once published, this review cannot be edited.</span></div>' +

        '<div class="xirr-card"><h3 style="margin-top:0">Annual Development Synthesis™ Preview</h3>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">This is your official annual developmental record.</p>' +
        '<div class="xirr-review-list" id="xirr-review-list"></div>' +
        '<a href="javascript:void(0)" class="xirr-link">View full synthesis preview &rarr;</a>' +
        '</div>' +

        '<div class="xirr-card"><h3 style="margin-top:0">Publish Actions</h3>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">Publishing your Individual Readiness Review™ will activate the following across the platform.</p>' +
        '<div class="xirr-activate-grid" id="xirr-publish-actions"></div></div>' +

        '<div class="xirr-card"><h3 style="margin-top:0">Review Confirmation</h3>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">Please confirm the following before publishing.</p>' +
        '<label class="xirr-row" style="margin-bottom:.5rem"><input type="checkbox" id="xirr-confirm-1"> I have reviewed my Annual Development Synthesis™ in its entirety.</label>' +
        '<label class="xirr-row" style="margin-bottom:.5rem"><input type="checkbox" id="xirr-confirm-2"> I have confirmed my Development Commitments™.</label>' +
        '<label class="xirr-row" style="margin-bottom:.75rem"><input type="checkbox" id="xirr-confirm-3"> I understand that publishing will lock this review and activate follow-up processes.</label>' +
        '<p class="xirr-field-label" style="text-transform:none;font-size:14px">Add a final note (optional)</p>' +
        '<textarea class="xirr-input" rows="3" placeholder="Add any final notes or reflections before publishing..."></textarea>' +
        '</div>' +

        '<p class="xirr-muted" id="xirr-publish-status" style="margin-top:.25rem"></p>' +
        '<div class="xirr-row" style="justify-content:flex-end;gap:.6rem">' +
        '<button type="button" class="xirr-btn xirr-btn-outline" id="xirr-publish-save">Save Draft</button>' +
        '<button type="button" class="xirr-btn xirr-btn-accent" id="xirr-publish-go">Publish Review &rarr;</button>' +
        '</div>';
}
JS;
}

function xfirr_wizard_publish_init_js(): string
{
    return <<<'JS'
(function () {
    var STEP_LABELS = [
        ['Individual Evidence™', 'Steps 1–2'],
        ['AI Development Assessment™', 'Step 3'],
        ['Development Conversation™', 'Step 4'],
        ['Annual Development Commitments™', 'Step 5'],
        ['AI Development Synthesis™', 'Step 6'],
    ];

    var PUBLISH_ACTIONS = [
        ['&#10024;', '1-on-1 Alignment Capture™', 'Your commitments will appear in future 1-on-1 conversations.'],
        ['&#128101;', 'ARR Inputs', 'Key insights and commitments will feed into the Annual Readiness Review™.'],
        ['&#128203;', 'Historical Development Timeline', 'This review will be added to your historical development record.'],
        ['&#128200;', 'Individual Dashboard Update', 'Your development trends, roadmap, and progress will be updated.'],
        ['&#128101;', 'Leader Dashboard Update', 'Your team\'s development data will be updated for leadership visibility.'],
        ['&#128202;', 'Executive Dashboard Update', 'Aggregated organizational development intelligence will be updated.'],
    ];

    function setPublishStatus(text, isError) {
        var el = document.getElementById('xirr-publish-status');
        if (!el) return;
        el.style.color = isError ? '#dc2626' : '#16a34a';
        el.textContent = text;
    }

    function renderReview() {
        var list = document.getElementById('xirr-review-list');
        if (!list) return;
        list.innerHTML = STEP_LABELS.map(function (row) {
            return '<div class="xirr-review-row">' +
                '<div class="xirr-review-left">' +
                '<span class="xirr-review-check">&#10003;</span>' +
                '<div><strong>' + row[0] + '</strong><div class="xirr-review-status">Completed ' + row[1] + '</div></div>' +
                '</div></div>';
        }).join('');
    }

    function renderActions() {
        var grid = document.getElementById('xirr-publish-actions');
        if (!grid) return;
        grid.innerHTML = PUBLISH_ACTIONS.map(function (a) {
            return '<div class="xirr-activate-card"><div style="font-size:1.4rem">' + a[0] + '</div>' +
                '<h4>' + a[1] + ' <span style="color:#16a34a">&#10003;</span></h4><p>' + a[2] + '</p></div>';
        }).join('');
    }

    window.initPublishStep = function () {
        renderReview();
        renderActions();

        var saveBtn = document.getElementById('xirr-publish-save');
        var publishBtn = document.getElementById('xirr-publish-go');

        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                setPublishStatus('Draft saved (UI shell — not yet connected).', false);
            });
        }
        if (publishBtn) {
            publishBtn.addEventListener('click', function () {
                if (!window.confirm('Publish this Individual Readiness Review™? Once published, this record cannot be edited.')) return;
                var statusBadge = document.querySelector('#xirr-si-status .xirr-badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Published';
                    statusBadge.classList.remove('amber');
                    statusBadge.classList.add('green');
                }
                setPublishStatus('Published (UI shell — not yet connected to Laravel).', false);
            });
        }
    };

    window.xirrPublishReview = function () {
        if (typeof window.initPublishStep === 'function') {
            var publishBtn = document.getElementById('xirr-publish-go');
            if (publishBtn) publishBtn.click();
        }
    };
})();
JS;
}
