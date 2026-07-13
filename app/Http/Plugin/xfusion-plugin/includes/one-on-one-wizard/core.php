<?php
/**
 * Core JS state machine for the 1-on-1 wizard: step metadata, navigation,
 * sidebar (progress/about-step), and the render dispatch loop.
 *
 * Each step's panel markup lives in steps/step-N-*.php and is merged into
 * `PANELS` by the main shortcode file — this file only orchestrates.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_core_js(): string
{
    return <<<'JS'
var STEPS = [
    { key: 'evidence',     label: 'Generate\nContinuous Evidence™', title: 'Step 1. Generate Continuous Evidence™' },
    { key: 'brief',        label: 'AI Meeting\nBrief™',              title: 'Step 2. AI Meeting Brief™' },
    { key: 'preparation',  label: 'Shared\nPreparation™',            title: 'Step 3. Shared Preparation™' },
    { key: 'conversation', label: 'Alignment\nConversation™',        title: 'Step 4. Alignment Conversation™' },
    { key: 'commitments',  label: 'Shared\nCommitments™',            title: 'Step 5. Shared Commitments™' },
    { key: 'synthesis',    label: 'AI Meeting\nSynthesis™',          title: 'Step 6. AI Meeting Synthesis™' },
];

var ABOUT = [
    'This step ensures both participants enter the conversation with the most relevant and comprehensive context available. The system automatically gathers evidence so you can focus on the conversation, not the preparation.',
    'The AI Meeting Brief™ gives both participants a clear understanding of what matters most. Use these insights to prepare, reflect, and set the stage for a productive conversation. You will not see each other\'s preparation until Step 3.',
    'Preparation helps both participants enter the conversation with clarity and purpose. Take a few minutes to reflect honestly — open preparation leads to stronger alignment and better decisions.',
    'This is the heart of the 1-on-1. Use the guide to have a focused, open, and honest conversation. Take notes as needed, but spend most of your time listening and engaging.',
    'Shared Commitments™ turn conversation into action. These commitments appear in your next meeting and help track progress over time.',
    'AI Meeting Synthesis™ turns your conversation into clarity and action. This synthesis becomes the official record of your meeting and helps both participants stay aligned, accountable, and focused.',
];

var root = document.getElementById('xfoo-wiz');
if (root) {
    var current = 0;
    var wizardBooted = false;
    var navBound = false;

    var renderSteps = function () {
        var el = root.querySelector('#xfw-steps-inner');
        el.innerHTML = STEPS.map(function (s, i) {
            var cls = i === current ? 'active' : (i < current ? 'done' : '');
            var label = s.label.split('\n').join('<br>');
            return '<div class="xfw-step ' + cls + '" data-step="' + i + '">' +
                '<div class="xfw-step-line"></div>' +
                '<div class="xfw-step-circle">' + (i < current ? '&#10003;' : (i + 1)) + '</div>' +
                '<div class="xfw-step-label">' + label + '</div>' +
                (i === current ? '<div class="xfw-step-underline" style="width:100%"></div>' : '') +
                '</div>';
        }).join('');
        el.querySelectorAll('[data-step]').forEach(function (n) {
            n.addEventListener('click', function () { goTo(parseInt(n.dataset.step, 10)); });
        });
    };

    var renderSidebar = function () {
        root.querySelector('#xfw-about-step').textContent = ABOUT[current];
        root.querySelector('#xfw-progress-label').textContent = 'Step ' + (current + 1) + ' of 6';
        var pct = Math.round(((current + 1) / 6) * 100);
        root.querySelector('#xfw-progress-pct').textContent = pct + '%';
        root.querySelector('#xfw-progress-fill').style.width = pct + '%';
    };

    var renderMain = function () {
        var main = root.querySelector('#xfw-main');
        main.innerHTML = PANELS[STEPS[current].key]();
        var prevBtn = root.querySelector('#xfw-prev-step');
        prevBtn.disabled = current === 0;
        var isLast = current === STEPS.length - 1;
        ['#xfw-next-step', '#xfw-next-step-2'].forEach(function (sel) {
            var btn = root.querySelector(sel);
            btn.textContent = isLast ? 'Complete Meeting' : 'Next Step →';
        });

        // Wire up interactive bits scoped to this render only
        main.querySelectorAll('.xfw-scale-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('.xfw-scale');
                if (!group) {
                    return;
                }
                group.querySelectorAll('.xfw-scale-btn').forEach(function (b) {
                    b.classList.remove('selected', 'employee', 'leader');
                });
                btn.classList.add('selected');
            });
        });
        main.querySelectorAll('textarea[data-maxlen]').forEach(function (t) {
            var wrap = t.closest('.xfw-textarea-field') || t.closest('.xfw-guide-notes-panel');
            var counter = wrap ? wrap.querySelector('.count') : null;
            var max = parseInt(t.dataset.maxlen, 10);
            if (!counter) {
                return;
            }
            counter.textContent = t.value.length + ' / ' + max;
            t.addEventListener('input', function () {
                counter.textContent = t.value.length + ' / ' + max;
            });
        });
        main.querySelectorAll('.xfw-guide-notes-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                var item = toggle.closest('.xfw-guide-item');
                var panel = item.querySelector('.xfw-guide-notes-panel');
                var chevron = toggle.querySelector('.xfw-chevron');
                var isOpen = !panel.classList.contains('xfw-hidden');
                if (isOpen) {
                    panel.classList.add('xfw-hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                    if (chevron) {
                        chevron.innerHTML = '&#9662;';
                    }
                } else {
                    panel.classList.remove('xfw-hidden');
                    toggle.setAttribute('aria-expanded', 'true');
                    if (chevron) {
                        chevron.innerHTML = '&#9652;';
                    }
                    var textarea = panel.querySelector('textarea');
                    if (textarea) {
                        textarea.focus();
                    }
                }
            });
        });

        if (STEPS[current].key === 'commitments' && typeof initCommitmentsStep === 'function') {
            initCommitmentsStep();
        }

        if (STEPS[current].key === 'evidence' && typeof initEvidenceStep === 'function') {
            initEvidenceStep();
        }

        if (STEPS[current].key === 'brief' && typeof initBriefStep === 'function') {
            initBriefStep();
        }

        if ((STEPS[current].key === 'preparation' || STEPS[current].key === 'conversation') && typeof applyDraftForCurrentStep === 'function') {
            if (window.xfwDraftCache && window.xfwDraftCache.loaded) {
                applyDraftForCurrentStep();
            } else if (typeof loadWizardDraft === 'function') {
                loadWizardDraft().then(function () {
                    applyDraftForCurrentStep();
                });
            }
        }
    };

    var goTo = function (i) {
        current = Math.max(0, Math.min(STEPS.length - 1, i));
        renderSteps();
        renderSidebar();
        renderMain();
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    var bindNav = function () {
        if (navBound) {
            return;
        }
        navBound = true;
        root.querySelector('#xfw-next-step').addEventListener('click', function () { goTo(current + 1); });
        root.querySelector('#xfw-next-step-2').addEventListener('click', function () { goTo(current + 1); });
        root.querySelector('#xfw-prev-step').addEventListener('click', function () { goTo(current - 1); });
    };

    window.xfwBootWizard = function (resetStep) {
        if (resetStep) {
            current = 0;
        }
        bindNav();
        if (!wizardBooted) {
            wizardBooted = true;
            renderSteps();
            renderSidebar();
            renderMain();
            return;
        }
        renderSteps();
        renderSidebar();
        renderMain();
    };
}
JS;
}
