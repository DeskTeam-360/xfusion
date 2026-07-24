<?php
/**
 * Core JS state machine for the QBR wizard: step metadata, navigation,
 * sidebar (QBR info / progress / about-step), and the render dispatch loop.
 *
 * Each step's panel markup lives in steps/step-N-*.php and is merged into
 * `PANELS` by the main shortcode file — this file only orchestrates.
 * Structurally identical to the ARP wizard's core.php.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_core_js(): string
{
    return <<<'JS'
var STEPS = [
    { key: 'evidence',       label: 'Generate\nOrganizational\nEvidence™', title: 'Step 1. Generate Organizational Evidence™' },
    { key: 'evidence_review', label: 'Organizational\nEvidence™',          title: 'Step 2. Organizational Evidence™' },
    { key: 'assessment',     label: 'AI Organizational\nAssessment™',      title: 'Step 3. AI Organizational Assessment™' },
    { key: 'collaboration',  label: 'Leadership\nCollaboration™',          title: 'Step 4. Leadership Collaboration™' },
    { key: 'commitments',    label: 'Quarterly\nCommitments™',             title: 'Step 5. Quarterly Commitments™' },
    { key: 'synthesis',      label: 'AI Organizational\nSynthesis™',       title: 'Step 6. AI Organizational Synthesis™' },
    { key: 'publish',        label: 'Publish',                             title: 'Step 7. Publish' },
];

var SIDEBAR = [
    {
        about: [
            'FUSION automatically gathers comprehensive evidence from across the platform.',
            'This ensures your QBR is based on complete, objective, and up-to-date data.',
            'You will review this evidence in Step 2.',
        ],
    },
    {
        about: [
            'This step presents the factual organizational evidence for the quarter.',
            'Use this data to understand where the organization stands before reviewing the AI assessment.',
        ],
    },
    {
        about: [
            'AI Organizational Assessment™ provides an objective analysis of your organization\'s current state based on comprehensive evidence.',
            'Review the assessment, indicate your agreement, and add any context the AI should consider before leadership discussion.',
        ],
    },
    {
        about: [
            'This is the primary collaborative portion of the QBR.',
            'Capture your leadership discussion, context, and key decisions.',
            'These insights will be used to create your quarterly commitments in Step 5.',
        ],
    },
    {
        about: [
            'Quarterly Commitments™ translate your leadership discussions and priorities into clear, actionable commitments for the upcoming quarter.',
            'These commitments drive accountability and will be used in Step 6 to generate the organizational synthesis.',
        ],
    },
    {
        about: [
            'This AI Organizational Synthesis™ provides your official quarterly readiness summary.',
            'This synthesis will be published in Step 7 and used across FUSION dashboards and reports.',
        ],
    },
    {
        about: [
            'Review your Quarterly Business Review™ and finalize to make it available across the FUSION platform.',
            'Publishing will lock this QBR and make it accessible to downstream dashboards and reports.',
        ],
    },
];

var root = document.getElementById('xfqbr-wiz');
if (root) {
    var current = 0;
    var wizardBooted = false;
    var navBound = false;

    var xqbrSidebarAboutCard = function (cfg) {
        return '<div class="xqbr-card">' +
            '<h4>About This Step</h4>' +
            '<div class="xqbr-about-step">' +
            '<div class="xqbr-about-step-body">' +
            cfg.about.map(function (p) {
                return '<p class="xqbr-muted">' + p + '</p>';
            }).join('') +
            '</div></div></div>';
    };

    var xqbrSidebarProgressCard = function () {
        var pct = Math.round(((current + 1) / STEPS.length) * 100);
        return '<div class="xqbr-card">' +
            '<h4>Progress</h4>' +
            '<p class="xqbr-muted" style="margin:0 0 .35rem">Step ' + (current + 1) + ' of ' + STEPS.length + '</p>' +
            '<div class="xqbr-progress-row">' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<span class="xqbr-muted xqbr-progress-pct">' + pct + '%</span>' +
            '</div>' +
            '<p class="xqbr-muted" style="margin-top:.6rem">Estimated Completion<br><strong>45 &ndash; 60 minutes</strong></p>' +
            '</div>';
    };

    var renderSidebar = function () {
        var panels = root.querySelector('#xqbr-sidebar-panels');
        if (!panels) {
            return;
        }
        var cfg = SIDEBAR[current] || SIDEBAR[0];
        panels.innerHTML = xqbrSidebarProgressCard() + xqbrSidebarAboutCard(cfg);
    };
    window.xqbrRenderSidebar = renderSidebar;

    var renderSteps = function () {
        var el = root.querySelector('#xqbr-steps-inner');
        el.innerHTML = STEPS.map(function (s, i) {
            var cls = i === current ? 'active' : (i < current ? 'done' : '');
            var label = s.label.split('\n').join('<br>');
            return '<div class="xqbr-step ' + cls + '" data-step="' + i + '">' +
                '<div class="xqbr-step-line"></div>' +
                '<div class="xqbr-step-circle">' + (i < current ? '&#10003;' : (i + 1)) + '</div>' +
                '<div class="xqbr-step-label">' + label + '</div>' +
                (i === current ? '<div class="xqbr-step-underline" style="width:100%"></div>' : '') +
                '</div>';
        }).join('');
        el.querySelectorAll('[data-step]').forEach(function (n) {
            n.addEventListener('click', function () { goTo(parseInt(n.dataset.step, 10)); });
        });
    };

    var wireFieldCounters = function (main) {
        main.querySelectorAll('textarea[data-maxlen]').forEach(function (t) {
            var wrap = t.closest('.xqbr-field');
            var counter = wrap ? wrap.querySelector('.xqbr-field-count') : null;
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
        var prevBtn = root.querySelector('#xqbr-prev-step');
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
        ['#xqbr-next-step', '#xqbr-next-step-2'].forEach(function (sel) {
            var btn = root.querySelector(sel);
            if (btn) {
                btn.textContent = isLast ? 'Publish QBR →' : 'Next Step →';
            }
        });
    };

    var renderMain = function () {
        var main = root.querySelector('#xqbr-main');
        var panelFn = PANELS[STEPS[current].key];
        main.innerHTML = typeof panelFn === 'function' ? panelFn() : '<div class="xqbr-card xqbr-placeholder"><p class="xqbr-muted">This step is coming soon.</p></div>';
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
        if (STEPS[current].key === 'collaboration' && typeof window.initCollaborationStep === 'function') {
            window.initCollaborationStep();
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
        xqbrLoadCurrentStepData();
    };

    var goToInner = function (i) {
        current = Math.max(0, Math.min(STEPS.length - 1, i));
        renderSteps();
        renderSidebar();
        renderMain();
        root.scrollIntoView({ behavior: 'auto', block: 'start' });
    };

    var STEP_DATA_LOADERS = {
        collaboration: 'xqbrLoadCollaborationData',
        commitments: 'xqbrLoadCommitmentsData',
    };

    var xqbrSetMainLoading = function (active) {
        var main = root.querySelector('#xqbr-main');
        if (!main) {
            return;
        }
        if (active) {
            main.classList.add('xqbr-main-loading');
            if (!main.querySelector('.xqbr-step-loading-bar')) {
                var bar = document.createElement('div');
                bar.className = 'xqbr-step-loading-bar';
                bar.setAttribute('aria-hidden', 'true');
                main.appendChild(bar);
            }
            return;
        }
        main.classList.remove('xqbr-main-loading');
        var bar = main.querySelector('.xqbr-step-loading-bar');
        if (bar) {
            bar.remove();
        }
    };

    var xqbrLoadCurrentStepData = function () {
        var stepKey = STEPS[current] ? STEPS[current].key : '';
        var loaderName = STEP_DATA_LOADERS[stepKey];
        if (!loaderName || typeof window[loaderName] !== 'function') {
            return;
        }
        xqbrSetMainLoading(true);
        try {
            var result = window[loaderName]();
            if (result && typeof result.finally === 'function') {
                result.finally(function () {
                    xqbrSetMainLoading(false);
                });
                return;
            }
        } catch (err) {
            xqbrSetMainLoading(false);
            return;
        }
        window.setTimeout(function () {
            xqbrSetMainLoading(false);
        }, 400);
    };

    var goTo = function (i) {
        var target = Math.max(0, Math.min(STEPS.length - 1, i));
        if (target === current) {
            return;
        }
        goToInner(target);
    };
    window.xqbrGoTo = goTo;
    window.xqbrRenderCurrentStep = renderMain;

    window.xqbrBackToPicker = function () {
        var url = new URL(window.location.href);
        url.searchParams.delete('qbr_id');
        window.location.href = url.toString();
    };

    var bindNav = function () {
        if (navBound) {
            return;
        }
        navBound = true;
        root.querySelector('#xqbr-next-step').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xqbrPublishQbr === 'function') {
                    window.xqbrPublishQbr();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xqbr-next-step-2').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xqbrPublishQbr === 'function') {
                    window.xqbrPublishQbr();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xqbr-prev-step').addEventListener('click', function () {
            var prevBtn = root.querySelector('#xqbr-prev-step');
            if (prevBtn && prevBtn.dataset.action === 'close') {
                window.xqbrBackToPicker();
                return;
            }
            goTo(current - 1);
        });
        var changeLink = root.querySelector('#xqbr-change-qbr');
        if (changeLink) {
            changeLink.addEventListener('click', function () {
                window.xqbrBackToPicker();
            });
        }
    };

    window.xqbrBootWizard = function (resetStep) {
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

    window.xqbrBootWizard(false);
}
JS;
}
