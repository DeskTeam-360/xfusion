<?php
/**
 * Core JS state machine for the Annual Readiness Review™ (ARR) wizard: step
 * metadata, navigation, sidebar (review info / progress / about-step), and
 * the render dispatch loop.
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

function xfarr_wizard_core_js(): string
{
    return <<<'JS'
var STEPS = [
    { key: 'evidence',       label: 'Generate\nAnnual\nEvidence™',        title: 'Step 1. Generate Annual Evidence™' },
    { key: 'dashboard',      label: 'Organizational\nLearning Dashboard™', title: 'Step 2. Organizational Learning Dashboard™' },
    { key: 'assessment',     label: 'AI Annual\nReadiness Assessment™',   title: 'Step 3. AI Annual Readiness Assessment™' },
    { key: 'reflection',     label: 'Executive\nStrategic Reflection™',   title: 'Step 4. Executive Strategic Reflection™' },
    { key: 'recommendations', label: 'Strategic Renewal\nRecommendations™', title: 'Step 5. Strategic Renewal Recommendations™' },
    { key: 'synthesis',      label: 'AI Strategic Renewal\nSynthesis™',   title: 'Step 6. AI Strategic Renewal Synthesis™' },
    { key: 'publish',        label: 'Publish\nARR™',                      title: 'Step 7. Publish ARR™' },
];

var SIDEBAR = [
    {
        about: [
            'FUSION automatically gathers one full year of organizational evidence from across the platform.',
            'This evidence will be used to generate AI insights, identify patterns, and support executive strategic reflection.',
        ],
    },
    {
        about: [
            'The Organizational Learning Dashboard™ provides a data-driven view of your organization\'s performance, people, and readiness across the year.',
            'Use these insights to prepare for the AI Annual Readiness Assessment™ in Step 3.',
        ],
    },
    {
        about: [
            'The AI Annual Readiness Assessment™ converts one year of organizational evidence into strategic intelligence.',
            'Review the assessment, provide your agreement, and add any strategic context before moving to executive reflection in Step 4.',
        ],
    },
    {
        about: [
            'This is the primary executive learning conversation.',
            'The AI informs. Leadership decides.',
        ],
    },
    {
        about: [
            'Strategic Renewal Recommendations™ translate your learning and insights into actionable priorities for the year ahead.',
            'These recommendations will help shape the next Annual Readiness Plan™ and guide organizational focus.',
            'Leadership decides.',
        ],
    },
    {
        about: [
            'FUSION AI evaluates all evidence, assessments, executive context, and recommendations to generate organizational intelligence and strategic synthesis.',
            'This becomes your official organizational learning record and informs the next Annual Readiness Plan™.',
            'AI informs. Leadership decides.',
        ],
    },
    {
        about: [
            'Publishing your ARR™ finalizes one year of organizational learning and transforms it into strategic intelligence that drives future planning, leadership alignment, and organizational readiness.',
            'Leadership decides. AI informs.',
        ],
    },
];

var root = document.getElementById('xfarr-wiz');
if (root) {
    var current = 0;
    var wizardBooted = false;
    var navBound = false;

    var xarrSidebarAboutCard = function (cfg) {
        return '<div class="xarr-card">' +
            '<h4>About This Step</h4>' +
            '<div class="xarr-about-step">' +
            '<div class="xarr-about-step-body">' +
            cfg.about.map(function (p) {
                return '<p class="xarr-muted">' + p + '</p>';
            }).join('') +
            '</div></div></div>';
    };

    var xarrSidebarProgressCard = function () {
        var pct = Math.round(((current + 1) / STEPS.length) * 100);
        return '<div class="xarr-card">' +
            '<h4>Progress</h4>' +
            '<p class="xarr-muted" style="margin:0 0 .35rem">Step ' + (current + 1) + ' of ' + STEPS.length + '</p>' +
            '<div class="xarr-progress-row">' +
            '<div class="xarr-progress-track"><div class="xarr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<span class="xarr-muted xarr-progress-pct">' + pct + '%</span>' +
            '</div>' +
            '<p class="xarr-muted" style="margin-top:.6rem">Estimated Completion<br><strong>45 &ndash; 60 minutes</strong></p>' +
            '</div>';
    };

    var renderSidebar = function () {
        var panels = root.querySelector('#xarr-sidebar-panels');
        if (!panels) {
            return;
        }
        var cfg = SIDEBAR[current] || SIDEBAR[0];
        panels.innerHTML = xarrSidebarProgressCard() + xarrSidebarAboutCard(cfg);
    };
    window.xarrRenderSidebar = renderSidebar;

    var renderSteps = function () {
        var el = root.querySelector('#xarr-steps-inner');
        el.innerHTML = STEPS.map(function (s, i) {
            var cls = i === current ? 'active' : (i < current ? 'done' : '');
            var label = s.label.split('\n').join('<br>');
            return '<div class="xarr-step ' + cls + '" data-step="' + i + '">' +
                '<div class="xarr-step-line"></div>' +
                '<div class="xarr-step-circle">' + (i < current ? '&#10003;' : (i + 1)) + '</div>' +
                '<div class="xarr-step-label">' + label + '</div>' +
                (i === current ? '<div class="xarr-step-underline" style="width:100%"></div>' : '') +
                '</div>';
        }).join('');
        el.querySelectorAll('[data-step]').forEach(function (n) {
            n.addEventListener('click', function () { goTo(parseInt(n.dataset.step, 10)); });
        });
    };

    var wireFieldCounters = function (main) {
        main.querySelectorAll('textarea[data-maxlen]').forEach(function (t) {
            var wrap = t.closest('.xarr-field');
            var counter = wrap ? wrap.querySelector('.xarr-field-count') : null;
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
        var prevBtn = root.querySelector('#xarr-prev-step');
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
        ['#xarr-next-step', '#xarr-next-step-2'].forEach(function (sel) {
            var btn = root.querySelector(sel);
            if (btn) {
                btn.textContent = isLast ? 'Publish ARR →' : 'Next Step →';
            }
        });
    };

    var renderMain = function () {
        var main = root.querySelector('#xarr-main');
        var panelFn = PANELS[STEPS[current].key];
        main.innerHTML = typeof panelFn === 'function' ? panelFn() : '<div class="xarr-card xarr-placeholder"><p class="xarr-muted">This step is coming soon.</p></div>';
        updateFooterNav();
        wireFieldCounters(main);
        if (STEPS[current].key === 'evidence' && typeof window.initEvidenceStep === 'function') {
            window.initEvidenceStep();
        }
        if (STEPS[current].key === 'dashboard' && typeof window.initDashboardStep === 'function') {
            window.initDashboardStep();
        }
        if (STEPS[current].key === 'assessment' && typeof window.initAssessmentStep === 'function') {
            window.initAssessmentStep();
        }
        if (STEPS[current].key === 'reflection' && typeof window.initReflectionStep === 'function') {
            window.initReflectionStep();
        }
        if (STEPS[current].key === 'recommendations' && typeof window.initRecommendationsStep === 'function') {
            window.initRecommendationsStep();
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
        var target = Math.max(0, Math.min(STEPS.length - 1, i));
        if (target === current) {
            return;
        }
        goToInner(target);
    };
    window.xarrGoTo = goTo;
    window.xarrRenderCurrentStep = renderMain;

    window.xarrBackToPicker = function () {
        var url = new URL(window.location.href);
        url.searchParams.delete('arr_id');
        window.location.href = url.toString();
    };

    var bindNav = function () {
        if (navBound) {
            return;
        }
        navBound = true;
        root.querySelector('#xarr-next-step').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xarrPublishReview === 'function') {
                    window.xarrPublishReview();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xarr-next-step-2').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xarrPublishReview === 'function') {
                    window.xarrPublishReview();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xarr-prev-step').addEventListener('click', function () {
            var prevBtn = root.querySelector('#xarr-prev-step');
            if (prevBtn && prevBtn.dataset.action === 'close') {
                window.xarrBackToPicker();
                return;
            }
            goTo(current - 1);
        });
        var changeLink = root.querySelector('#xarr-change-arr');
        if (changeLink) {
            changeLink.addEventListener('click', function () {
                window.xarrBackToPicker();
            });
        }
    };

    window.xarrBootWizard = function (resetStep) {
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

    window.xarrBootWizard(false);
}
JS;
}
