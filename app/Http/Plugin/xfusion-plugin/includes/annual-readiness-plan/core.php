<?php
/**
 * Core JS state machine for the ARP wizard: step metadata, navigation,
 * sidebar (ARP info / progress / about-step), and the render dispatch loop.
 *
 * Each step's panel markup lives in steps/step-N-*.php and is merged into
 * `PANELS` by the main shortcode file — this file only orchestrates.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_core_js(): string
{
    return <<<'JS'
var STEPS = [
    { key: 'foundation',   label: 'Organizational\nFoundation™',   title: 'Step 1. Organizational Foundation™' },
    { key: 'future_state', label: 'Future State™',                  title: 'Step 2. Future State™' },
    { key: 'readiness',    label: 'Organizational\nReadiness™',     title: 'Step 3. Organizational Readiness™' },
    { key: 'priorities',   label: 'Strategic\nPriorities™',         title: 'Step 4. Strategic Priorities™' },
    { key: 'learning',     label: 'Organizational\nLearning™',      title: 'Step 5. Organizational Learning™' },
    { key: 'ai_review',    label: 'AI Readiness\nReview™',          title: 'Step 6. AI Readiness Review™' },
    { key: 'publish',      label: 'Publish ARP™',                   title: 'Step 7. Publish ARP™' },
];

var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';
var SIDEBAR = [
    {
        aboutIcon: iconBase + 'ARP-1.svg',
        about: [
            'Define the strategic context of your organization. These foundational elements establish the framework for all future planning and alignment.',
        ],
    },
    {
        aboutIcon: iconBase + 'Green-Light-Bulb-Icon-1.svg',
        about: [
            'Articulate the future your organization intends to create. A clear future state aligns everyone and guides all strategic decisions throughout the year.',
            'AI assistance helps you think broader and communicate clearer.',
        ],
    },
    {
        aboutIcon: iconBase + 'Target-Crosshairs-Green-Icon.svg',
        about: [
            'Identify the organizational capabilities you must strengthen to achieve your future state.',
            'Each priority links to a COR Organizational Capability™ and Behavioral Drivers™ to ensure alignment across people, process, and systems.',
            'These priorities will become the foundation for your strategic execution.',
        ],
    },
    {
        aboutIcon: iconBase + 'Target-Crosshairs-Green-Icon.svg',
        about: [
            'Define the strategic priorities that will transform your readiness into measurable results.',
            'Each priority is linked to a readiness priority, aligned with KPIs and indicators, and owned by an executive.',
            'These priorities will drive your Quarterly Business Reviews™ and leader accountability throughout the year.',
        ],
    },
    {
        aboutIcon: iconBase + 'Brain-Green-Icon.svg',
        about: [
            'Documenting assumptions, risks, opportunities, and learning objectives ensures leadership stays aware of what could impact success and what must be learned along the way.',
            'These insights become critical reference points during the Annual Readiness Review™.',
        ],
    },
    {
        aboutIcon: iconBase + 'Sparkle-Icon.svg',
        about: [
            'FUSION AI analyzes your strategic plan across all completed sections to identify alignment, gaps, and recommended focus areas.',
            'You cannot edit the AI-generated analysis. Add leadership context to enrich future conversations.',
        ],
    },
    {
        aboutIcon: iconBase + 'Clipboard-Checkmark-Icon.svg',
        about: [
            'Publishing activates your Annual Readiness Plan™ across the FUSION Operating System™.',
            'Only one Active ARP can exist per organization per year. Previous versions remain available for reference.',
        ],
    },
];

var root = document.getElementById('xfarp-wiz');
if (root) {
    var current = 0;
    var wizardBooted = false;
    var navBound = false;

    var xarSidebarAboutCard = function (cfg) {
        return '<div class="xar-card">' +
            '<h4>About This Step</h4>' +
            '<div class="xar-about-step">' +
            '<img class="xar-about-step-icon" src="' + cfg.aboutIcon + '" alt="" width="50" height="50">' +
            '<div class="xar-about-step-body">' +
            cfg.about.map(function (p) {
                return '<p class="xar-muted">' + p + '</p>';
            }).join('') +
            '</div></div></div>';
    };

    var xarSidebarProgressCard = function () {
        var pct = Math.round(((current + 1) / STEPS.length) * 100);
        return '<div class="xar-card">' +
            '<h4>Progress</h4>' +
            '<p class="xar-muted" style="margin:0 0 .35rem">Step ' + (current + 1) + ' of ' + STEPS.length + '</p>' +
            '<div class="xar-progress-row">' +
            '<div class="xar-progress-track"><div class="xar-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<span class="xar-muted xar-progress-pct">' + pct + '%</span>' +
            '</div>' +
            '<p class="xar-muted" style="margin-top:.6rem">Estimated Completion<br><strong>90 &ndash; 120 minutes</strong></p>' +
            '</div>';
    };

    var renderSidebar = function () {
        var panels = root.querySelector('#xar-sidebar-panels');
        if (!panels) {
            return;
        }
        var cfg = SIDEBAR[current] || SIDEBAR[0];
        panels.innerHTML = xarSidebarProgressCard() + xarSidebarAboutCard(cfg);
    };
    window.xarRenderSidebar = renderSidebar;

    var renderSteps = function () {
        var el = root.querySelector('#xar-steps-inner');
        el.innerHTML = STEPS.map(function (s, i) {
            var cls = i === current ? 'active' : (i < current ? 'done' : '');
            var label = s.label.split('\n').join('<br>');
            return '<div class="xar-step ' + cls + '" data-step="' + i + '">' +
                '<div class="xar-step-line"></div>' +
                '<div class="xar-step-circle">' + (i < current ? '&#10003;' : (i + 1)) + '</div>' +
                '<div class="xar-step-label">' + label + '</div>' +
                (i === current ? '<div class="xar-step-underline" style="width:100%"></div>' : '') +
                '</div>';
        }).join('');
        el.querySelectorAll('[data-step]').forEach(function (n) {
            n.addEventListener('click', function () { goTo(parseInt(n.dataset.step, 10)); });
        });
    };

    var wireFieldCounters = function (main) {
        main.querySelectorAll('textarea[data-maxlen]').forEach(function (t) {
            var wrap = t.closest('.xar-field');
            var counter = wrap ? wrap.querySelector('.xar-field-count') : null;
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
        main.querySelectorAll('.xar-ai-assist').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                // Placeholder until AI assist endpoint is wired.
                window.alert('AI assistance for this field will be available in a future update.');
            });
        });
    };

    var updateFooterNav = function () {
        var prevBtn = root.querySelector('#xar-prev-step');
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
        ['#xar-next-step', '#xar-next-step-2'].forEach(function (sel) {
            var btn = root.querySelector(sel);
            if (btn) {
                btn.textContent = isLast ? 'Publish ARP →' : 'Next Step →';
            }
        });
    };

    var renderMain = function () {
        var main = root.querySelector('#xar-main');
        var panelFn = PANELS[STEPS[current].key];
        main.innerHTML = typeof panelFn === 'function' ? panelFn() : '<div class="xar-card xar-placeholder"><p class="xar-muted">This step is coming soon.</p></div>';
        updateFooterNav();
        wireFieldCounters(main);
        if (STEPS[current].key === 'readiness' && typeof initReadinessStep === 'function') {
            initReadinessStep();
        }
        if (STEPS[current].key === 'priorities' && typeof initStrategicStep === 'function') {
            initStrategicStep();
        }
        if (STEPS[current].key === 'learning' && typeof initLearningStep === 'function') {
            initLearningStep();
        }
        if (STEPS[current].key === 'ai_review' && typeof initAiReviewStep === 'function') {
            initAiReviewStep();
        }
        if (STEPS[current].key === 'publish' && typeof initPublishStep === 'function') {
            initPublishStep();
        }
    };

    var goTo = function (i) {
        current = Math.max(0, Math.min(STEPS.length - 1, i));
        renderSteps();
        renderSidebar();
        renderMain();
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    window.xarGoTo = goTo;

    var bindNav = function () {
        if (navBound) {
            return;
        }
        navBound = true;
        root.querySelector('#xar-next-step').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xarPublishArp === 'function') {
                    window.xarPublishArp();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xar-next-step-2').addEventListener('click', function () {
            if (current === STEPS.length - 1) {
                if (typeof window.xarPublishArp === 'function') {
                    window.xarPublishArp();
                }
                return;
            }
            goTo(current + 1);
        });
        root.querySelector('#xar-prev-step').addEventListener('click', function () {
            var prevBtn = root.querySelector('#xar-prev-step');
            if (prevBtn && prevBtn.dataset.action === 'close') {
                return;
            }
            goTo(current - 1);
        });
        ['#xar-save-draft', '#xar-save-draft-2'].forEach(function (sel) {
            var btn = root.querySelector(sel);
            if (!btn) {
                return;
            }
            btn.addEventListener('click', function () {
                var status = root.querySelector('#xar-autosave-status');
                if (status) {
                    var now = new Date();
                    var time = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                    status.innerHTML = '<span class="xar-autosave-check" aria-hidden="true">&#10003;</span> Draft autosaved ' + time;
                }
            });
        });
    };

    window.xarBootWizard = function (resetStep) {
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

    window.xarBootWizard(false);
}
JS;
}
