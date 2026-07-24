<?php
/**
 * Core JS state machine for the Individual Readiness Review™ (IRR) wizard:
 * step metadata, navigation, sidebar (review info / progress / about-step),
 * and the render dispatch loop.
 *
 * Each step's panel markup lives in steps/step-N-*.php and is merged into
 * `PANELS` by the main shortcode file — this file only orchestrates.
 * UI-only prototype: no Laravel calls anywhere in this wizard yet.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_core_js(): string
{
    return <<<'JS'
var STEPS = [
    { key: 'evidence',       label: 'Generate\nIndividual\nEvidence™', title: 'Step 1. Generate Individual Evidence™' },
    { key: 'evidence_review', label: 'Individual\nEvidence™',          title: 'Step 2. Individual Evidence™' },
    { key: 'assessment',     label: 'AI Development\nAssessment™',     title: 'Step 3. AI Development Assessment™' },
    { key: 'conversation',   label: 'Development\nConversation™',      title: 'Step 4. Development Conversation™' },
    { key: 'commitments',    label: 'Annual Development\nCommitments™', title: 'Step 5. Annual Development Commitments™' },
    { key: 'synthesis',      label: 'AI Development\nSynthesis™',      title: 'Step 6. AI Development Synthesis™' },
    { key: 'publish',        label: 'Publish',                         title: 'Step 7. Publish' },
];

var SIDEBAR = [
    {
        about: [
            'FUSION compiles a full year of developmental evidence from across the platform.',
            'This ensures your review is objective, comprehensive, and based on real activity — not memory.',
        ],
    },
    {
        about: [
            'Review your year of objective evidence across behaviors, participation, commitments, and growth.',
            'This forms the foundation for the AI assessment in the next step.',
        ],
    },
    {
        about: [
            'AI analyzes your entire year of evidence to identify strengths, opportunities, and readiness indicators.',
            'Review these insights before adding your reflections in the next step.',
        ],
    },
    {
        about: [
            'This is the heart of the Individual Readiness Review™.',
            'A strong conversation leads to greater clarity, alignment, and intentional growth.',
        ],
    },
    {
        about: [
            'Development commitments turn insights into action.',
            'These commitments will guide your focus for the year, strengthen your skills and behaviors, and be reinforced through future 1-on-1 conversations.',
        ],
    },
    {
        about: [
            'AI synthesizes your evidence, conversations, and commitments into your official annual development record.',
            'This synthesis will guide your future development, inform 1-on-1 conversations, and feed the Annual Readiness Review™.',
        ],
    },
    {
        about: [
            'Review your Annual Development Synthesis™ and publish your Individual Readiness Review™.',
            'Publishing activates follow-up processes and solidifies your official annual developmental record.',
        ],
    },
];

var root = document.getElementById('xfirr-wiz');
if (root) {
    var current = 0;
    var wizardBooted = false;
    var navBound = false;

    var xirrSidebarAboutCard = function (cfg) {
        return '<div class="xirr-card">' +
            '<h4>About This Step</h4>' +
            '<div class="xirr-about-step">' +
            '<div class="xirr-about-step-body">' +
            cfg.about.map(function (p) {
                return '<p class="xirr-muted">' + p + '</p>';
            }).join('') +
            '</div></div></div>';
    };

    var xirrSidebarProgressCard = function () {
        var pct = Math.round(((current + 1) / STEPS.length) * 100);
        return '<div class="xirr-card">' +
            '<h4>Progress</h4>' +
            '<p class="xirr-muted" style="margin:0 0 .35rem">Step ' + (current + 1) + ' of ' + STEPS.length + '</p>' +
            '<div class="xirr-progress-row">' +
            '<div class="xirr-progress-track"><div class="xirr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<span class="xirr-muted xirr-progress-pct">' + pct + '%</span>' +
            '</div>' +
            '<p class="xirr-muted" style="margin-top:.6rem">Estimated Completion<br><strong>45 &ndash; 60 minutes</strong></p>' +
            '</div>';
    };

    var renderSidebar = function () {
        var panels = root.querySelector('#xirr-sidebar-panels');
        if (!panels) {
            return;
        }
        var cfg = SIDEBAR[current] || SIDEBAR[0];
        panels.innerHTML = xirrSidebarProgressCard() + xirrSidebarAboutCard(cfg);
    };
    window.xirrRenderSidebar = renderSidebar;

    var renderSteps = function () {
        var el = root.querySelector('#xirr-steps-inner');
        el.innerHTML = STEPS.map(function (s, i) {
            var cls = i === current ? 'active' : (i < current ? 'done' : '');
            var label = s.label.split('\n').join('<br>');
            return '<div class="xirr-step ' + cls + '" data-step="' + i + '">' +
                '<div class="xirr-step-line"></div>' +
                '<div class="xirr-step-circle">' + (i < current ? '&#10003;' : (i + 1)) + '</div>' +
                '<div class="xirr-step-label">' + label + '</div>' +
                (i === current ? '<div class="xirr-step-underline" style="width:100%"></div>' : '') +
                '</div>';
        }).join('');
        el.querySelectorAll('[data-step]').forEach(function (n) {
            n.addEventListener('click', function () { goTo(parseInt(n.dataset.step, 10)); });
        });
    };

    var wireFieldCounters = function (main) {
        main.querySelectorAll('textarea[data-maxlen]').forEach(function (t) {
            var wrap = t.closest('.xirr-field');
            var counter = wrap ? wrap.querySelector('.xirr-field-count') : null;
            var max = parseInt(t.dataset.maxlen, 10);
            if (!counter) {
                return;
            }
            counter.textContent = t.value.length + ' / ' + max;
            t.addEventListener('input', function () {
                if (t.value.length > max) {
                    t.value = t.value.slice(0, max);
                }
                counter.textContent = t.value.length + ' / ' + max;
            });
        });
    };

    var updateFooterNav = function () {
        var prevBtn = root.querySelector('#xirr-prev-step');
        if (!prevBtn) {
            return;
        }
        if (current === 0) {
            prevBtn.textContent = 'Close';
            prevBtn.disabled = false;
            prevBtn.dataset.action = 'close';
        } else {
            prevBtn.innerHTML = '&larr; Previous Step';
            prevBtn.disabled = false;
            prevBtn.dataset.action = 'prev';
        }
        var isLast = current === STEPS.length - 1;
        ['#xirr-next-step', '#xirr-next-step-2'].forEach(function (sel) {
            var btn = root.querySelector(sel);
            if (btn) {
                btn.textContent = isLast ? 'Publish Review →' : 'Next Step →';
            }
        });
    };

    var renderMain = function () {
        var main = root.querySelector('#xirr-main');
        var panelFn = PANELS[STEPS[current].key];
        main.innerHTML = typeof panelFn === 'function' ? panelFn() : '<div class="xirr-card xirr-placeholder"><p class="xirr-muted">This step is coming soon.</p></div>';
        updateFooterNav();
        wireFieldCounters(main);
        if (STEPS[current].key === 'evidence' && typeof window.initEvidenceStep === 'function') {
            window.initEvidenceStep();
        }
        if (STEPS[current].key === 'evidence_review' && typeof window.initEvidenceReviewStep === 'function') {
            window.initEvidenceReviewStep();
        }
        if (STEPS[current].key === 'assessment' && typeof window.initAssessmentStep === 'function') {
            window.initAssessmentStep();
        }
        if (STEPS[current].key === 'conversation' && typeof window.initConversationStep === 'function') {
            window.initConversationStep();
        }
        if (STEPS[current].key === 'commitments' && typeof window.initCommitmentsStep === 'function') {
            window.initCommitmentsStep();
        }
        if (STEPS[current].key === 'synthesis' && typeof window.initSynthesisStep === 'function') {
            window.initSynthesisStep();
        }
        if (STEPS[current].key === 'publish' && typeof window.initPublishStep === 'function') {
            window.initPublishStep();
        }
    };

    var goToInner = function (i) {
        current = Math.max(0, Math.min(STEPS.length - 1, i));
        renderSteps();
        renderSidebar();
        renderMain();
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    var goTo = function (i) {
        goToInner(i);
    };
    window.xirrGoTo = goTo;
    window.xirrRenderCurrentStep = renderMain;

    window.xirrBackToPicker = function () {
        var url = new URL(window.location.href);
        url.searchParams.delete('irr_id');
        window.location.href = url.toString();
    };

    var bindNav = function () {
        if (navBound) {
            return;
        }
        navBound = true;
        root.querySelector('#xirr-next-step').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xirrPublishReview === 'function') {
                    window.xirrPublishReview();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xirr-next-step-2').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xirrPublishReview === 'function') {
                    window.xirrPublishReview();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xirr-prev-step').addEventListener('click', function () {
            var prevBtn = root.querySelector('#xirr-prev-step');
            if (prevBtn && prevBtn.dataset.action === 'close') {
                window.xirrBackToPicker();
                return;
            }
            goTo(current - 1);
        });
        var changeLink = root.querySelector('#xirr-change-irr');
        if (changeLink) {
            changeLink.addEventListener('click', function () {
                window.xirrBackToPicker();
            });
        }
    };

    window.xirrBootWizard = function (resetStep) {
        if (resetStep) {
            current = 0;
        }
        bindNav();
        if (!wizardBooted) {
            wizardBooted = true;
        }
        renderSteps();
        renderSidebar();
        renderMain();
    };

    window.xirrBootWizard(false);
}
JS;
}
