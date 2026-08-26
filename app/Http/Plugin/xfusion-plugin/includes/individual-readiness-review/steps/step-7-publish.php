<?php
/**
 * Step 7 — Publish.
 *
 * Real: publish action calls Laravel (POST /{irr}/publish), which requires
 * every prior step to be complete (step_progress). The wizard's own header/
 * footer Save Draft / "Publish Review →" buttons drive this step — no
 * duplicate in-panel buttons.
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

        '<div class="xirr-card"><h3 style="margin-top:0">Review Summary</h3>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">Every step must be complete before you can publish.</p>' +
        '<div class="xirr-review-list" id="xirr-review-list"></div>' +
        '</div>' +

        '<div class="xirr-card"><h3 style="margin-top:0">Publish Actions</h3>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">Publishing your Individual Readiness Review™ will activate the following across the platform.</p>' +
        '<div class="xirr-activate-grid" id="xirr-publish-actions"></div></div>' +

        '<div class="xirr-card"><h3 style="margin-top:0">Review Confirmation</h3>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">Please confirm the following before publishing. All three are required to publish.</p>' +
        '<label class="xirr-row" style="margin-bottom:.5rem"><input type="checkbox" id="xirr-confirm-1"> I have reviewed my Annual Development Synthesis™ in its entirety.</label>' +
        '<label class="xirr-row" style="margin-bottom:.5rem"><input type="checkbox" id="xirr-confirm-2"> I have confirmed my Development Commitments™.</label>' +
        '<label class="xirr-row" style="margin-bottom:.75rem"><input type="checkbox" id="xirr-confirm-3"> I understand that publishing will lock this review and activate follow-up processes.</label>' +
        '</div>' +

        '<p class="xirr-muted" id="xirr-publish-status" style="margin-top:.25rem"></p>';
}
JS;
}

function xfirr_wizard_publish_init_js(): string
{
    return <<<'JS'
(function () {
    var REVIEW_STEPS = [
        { key: 'evidence', label: 'Individual Evidence™', steps: 'Steps 1–2' },
        { key: 'assessment', label: 'AI Development Assessment™', steps: 'Step 3' },
        { key: 'conversation', label: 'Development Conversation™', steps: 'Step 4' },
        { key: 'commitments', label: 'Annual Development Commitments™', steps: 'Step 5' },
        { key: 'synthesis', label: 'AI Development Synthesis™', steps: 'Step 6' },
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

    function stepProgressMap() {
        var progress = window.XFIRR_WIZARD && window.XFIRR_WIZARD.stepProgress ? window.XFIRR_WIZARD.stepProgress : {};
        return progress && typeof progress === 'object' ? progress : {};
    }

    function allConfirmed() {
        return ['xirr-confirm-1', 'xirr-confirm-2', 'xirr-confirm-3'].every(function (id) {
            var el = document.getElementById(id);
            return el && el.checked;
        });
    }

    function renderReview() {
        var list = document.getElementById('xirr-review-list');
        if (!list) return;
        var progress = stepProgressMap();
        list.innerHTML = REVIEW_STEPS.map(function (row) {
            var done = !!progress[row.key];
            return '<div class="xirr-review-row">' +
                '<div class="xirr-review-left">' +
                '<span class="xirr-review-check" style="color:' + (done ? '#16a34a' : '#d97706') + '">' + (done ? '&#10003;' : '&#9675;') + '</span>' +
                '<div><strong>' + row.label + '</strong><div class="xirr-review-status">' + (done ? 'Completed ' : 'Incomplete — ') + row.steps + '</div></div>' +
                '</div></div>';
        }).join('');
    }

    function renderActions() {
        var grid = document.getElementById('xirr-publish-actions');
        if (!grid) return;
        grid.innerHTML = PUBLISH_ACTIONS.map(function (a) {
            return '<div class="xirr-activate-card"><div style="font-size:1.4rem">' + a[0] + '</div>' +
                '<h4>' + a[1] + '</h4><p>' + a[2] + '</p></div>';
        }).join('');
    }

    function isReviewPublished() {
        return window.XFIRR_WIZARD && String(window.XFIRR_WIZARD.status).toLowerCase() === 'published';
    }

    window.initPublishStep = function () {
        renderReview();
        renderActions();

        if (isReviewPublished()) {
            setPublishStatus('This review has been published and is locked.', false);
        } else {
            setPublishStatus('', false);
        }

        ['xirr-confirm-1', 'xirr-confirm-2', 'xirr-confirm-3'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && !isReviewPublished()) {
                el.addEventListener('change', function () {
                    if (!allConfirmed()) {
                        setPublishStatus('', false);
                    }
                });
            }
            if (el && isReviewPublished()) {
                el.disabled = true;
            }
        });
    };

    window.xirrPublishReview = function () {
        if (isReviewPublished()) {
            setPublishStatus('This review has already been published.', true);
            return;
        }
        var progress = stepProgressMap();
        var missing = REVIEW_STEPS.filter(function (row) { return !progress[row.key]; });
        if (missing.length) {
            setPublishStatus('Complete all steps before publishing: ' + missing.map(function (m) { return m.label; }).join(', ') + '.', true);
            return;
        }
        if (!allConfirmed()) {
            setPublishStatus('Please confirm all three items in Review Confirmation before publishing.', true);
            return;
        }
        if (!window.confirm('Publish this Individual Readiness Review™? Once published, this record cannot be edited.')) {
            return;
        }
        if (typeof window.xfirrPublishNow !== 'function') {
            setPublishStatus('Publish service unavailable.', true);
            return;
        }

        setPublishStatus('Publishing…', false);
        window.xfirrPublishNow().then(function (json) {
            if (!json || !json.success) {
                setPublishStatus((json && json.message) ? json.message : 'Publish failed.', true);
                return;
            }
            window.XFIRR_WIZARD.status = (json.data && json.data.status) ? json.data.status : 'published';
            var statusBadge = document.querySelector('#xirr-si-status .xirr-badge');
            if (statusBadge) {
                statusBadge.textContent = 'Published';
                statusBadge.classList.remove('amber');
                statusBadge.classList.add('green');
            }
            setPublishStatus('Published successfully. This record is now locked.', false);
            if (typeof window.xirrRenderCurrentStep === 'function') {
                window.xirrRenderCurrentStep();
            }
        }).catch(function () {
            setPublishStatus('Publish failed — network error.', true);
        });
    };
})();
JS;
}
