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

var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';
var SIDEBAR = [
    {
        aboutIcon: iconBase + 'Database-Icon-1.svg',
        about: [
            'This step ensures both participants enter the conversation with the most relevant and comprehensive context available.',
            'The system automatically gathers evidence so you can focus on the conversation, not the preparation.',
        ],
        helpTitle: 'Have a question?',
        helpText: 'Learn more about how evidence is gathered and used in 1-on-1 Alignment Capture\u2122.',
        helpLink: 'View Help Article',
        middle: 'help',
    },
    {
        aboutIcon: iconBase + 'Sparkle-Icon.svg',
        about: [
            'The AI Meeting Brief\u2122 gives both participants a clear understanding of what matters most.',
            'Use these insights to prepare, reflect, and set the stage for a productive conversation.',
            'You will not see each other\'s preparation until Step 3.',
        ],
        helpTitle: 'Have a question?',
        helpText: 'Learn more about the AI Meeting Brief\u2122',
        helpLink: 'View Help Article',
        middle: 'help',
    },
    {
        aboutIcon: iconBase + 'Two-People-Green-Icon-1.svg',
        about: [
            'Preparation helps both participants enter the conversation with clarity and purpose.',
            'Take a few minutes to reflect honestly. Your preparation directly impacts the quality of your 1-on-1.',
            'Open, honest preparation leads to stronger alignment, better decisions, and meaningful commitments.',
        ],
        helpTitle: 'Need help?',
        helpText: 'Learn more about how Shared Preparation\u2122 creates better conversations.',
        helpLink: 'View Help Article',
        middle: 'help',
    },
    {
        aboutIcon: iconBase + 'Chat-Bubbles-Icon.svg',
        about: [
            'This is the heart of the 1-on-1. Use the guide to have a focused, open, and honest conversation. Take notes as needed, but spend most of your time listening and engaging.',
        ],
        tips: [
            'Listen to understand.',
            'Ask open-ended questions.',
            'Stay focused on impact.',
            'Be honest and respectful.',
            'Align on what matters most.',
        ],
        middle: 'tips',
    },
    {
        aboutIcon: iconBase + 'Arrow-on-Target-Icon.svg',
        about: [
            'Shared Commitments\u2122 turn conversation into action.',
            'These commitments appear in your next meeting and help track progress over time.',
            'You can edit or update commitments as needed.',
        ],
        middle: 'commitments',
    },
    {
        aboutIcon: iconBase + 'Sparkle-Icon.svg',
        about: [
            'AI Meeting Synthesis\u2122 turns your conversation into clarity and action.',
            'This synthesis becomes the official record of your meeting and helps both participants stay aligned, accountable, and focused.',
            'These insights will inform your next 1-on-1 and keep momentum between meetings.',
        ],
        nextText: [
            'Your next 1-on-1 is scheduled for {date}.',
            'Open commitments will appear in your next meeting.',
        ],
        nextLink: 'View Upcoming Meeting',
        middle: 'progress-first',
    },
];

var root = document.getElementById('xfoo-wiz');
if (root) {
    var current = 0;
    var wizardBooted = false;
    var navBound = false;

    var xfwCountCommitments = function () {
        var data = (window.xfwCommitmentsCache && window.xfwCommitmentsCache.data) || { employee: [], leader: [] };
        var isOpen = function (row) {
            return String((row && row.status) || 'open') !== 'done';
        };
        var employee = (data.employee || []).filter(isOpen).length;
        var leader = (data.leader || []).filter(isOpen).length;
        return { employee: employee, leader: leader, open: employee + leader };
    };

    var xfwSidebarAboutCard = function (cfg) {
        return '<div class="xfw-card">' +
            '<h4>About This Step</h4>' +
            '<div class="xfw-about-step">' +
            '<img class="xfw-about-step-icon" src="' + cfg.aboutIcon + '" alt="" width="40" height="40">' +
            '<div class="xfw-about-step-body">' +
            cfg.about.map(function (p) {
                return '<p class="xfw-muted">' + p + '</p>';
            }).join('') +
            '</div></div></div>';
    };

    var xfwSidebarProgressCard = function () {
        var pct = Math.round(((current + 1) / 6) * 100);
        return '<div class="xfw-card">' +
            '<h4>Progress</h4>' +
            '<p class="xfw-muted" id="xfw-progress-label" style="margin:0 0 .35rem">Step ' + (current + 1) + ' of 6</p>' +
            '<div class="xfw-progress-row">' +
            '<div class="xfw-progress-track"><div class="xfw-progress-fill" id="xfw-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<span class="xfw-muted" id="xfw-progress-pct">' + pct + '%</span>' +
            '</div>' +
            '<p class="xfw-muted" style="margin-top:.6rem">Estimated Completion<br><strong>25 &ndash; 40 minutes</strong></p>' +
            '</div>';
    };

    var xfwSidebarHelpCard = function (cfg) {
        return '<div class="xfw-card">' +
            '<h4>' + cfg.helpTitle + '</h4>' +
            '<div class="xfw-help-card">' +
            '<div class="xfw-help-icon" aria-hidden="true">?</div>' +
            '<div class="xfw-help-body">' +
            '<p class="xfw-muted">' + cfg.helpText + '</p>' +
            '<a href="#" class="xfw-link">' + cfg.helpLink + ' &rarr;</a>' +
            '</div></div></div>';
    };

    var xfwSidebarTipsCard = function (cfg) {
        return '<div class="xfw-card">' +
            '<h4>Tips for a Great Conversation</h4>' +
            '<ul class="xfw-tip-list">' +
            (cfg.tips || []).map(function (t) {
                return '<li><span class="xfw-tip-check" aria-hidden="true">&#10003;</span><span>' + t + '</span></li>';
            }).join('') +
            '</ul></div>';
    };

    var xfwSidebarCommitSummaryCard = function () {
        var counts = xfwCountCommitments();
        return '<div class="xfw-card">' +
            '<h4>Commitment Summary</h4>' +
            '<div class="xfw-commit-summary">' +
            '<div class="xfw-commit-summary-row">' +
            '<img src="' + iconBase + 'User-Icon-Green-Filled.svg" alt="" width="36" height="36">' +
            '<div><span class="xfw-muted">Your Commitments</span><strong>' + counts.employee + ' active</strong></div>' +
            '</div>' +
            '<div class="xfw-commit-summary-row">' +
            '<img src="' + iconBase + 'User-Icon-Dark-Blue.svg" alt="" width="36" height="36">' +
            '<div><span class="xfw-muted">Leader Commitments</span><strong>' + counts.leader + ' active</strong></div>' +
            '</div>' +
            '<div class="xfw-commit-summary-row">' +
            '<img src="' + iconBase + 'Clipboard-Checkmark-Icon.svg" alt="" width="36" height="36">' +
            '<div><span class="xfw-muted">Open Commitments</span><strong>' + counts.open + ' total</strong></div>' +
            '</div>' +
            '</div></div>';
    };

    var xfwSidebarNextCard = function (cfg) {
        var dateEl = root.querySelector('#xfw-si-date');
        var dateText = (dateEl && dateEl.textContent && dateEl.textContent !== '—') ? dateEl.textContent : 'your next meeting';
        var lines = (cfg.nextText || []).map(function (t) {
            return '<p class="xfw-muted">' + t.replace('{date}', dateText) + '</p>';
        }).join('');
        return '<div class="xfw-card">' +
            '<h4>What\'s Next?</h4>' +
            '<div class="xfw-help-card">' +
            '<div class="xfw-next-icon" aria-hidden="true">' +
            '<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><polyline points="21 3 21 9 15 9"/></svg>' +
            '</div>' +
            '<div class="xfw-help-body">' + lines +
            '<a href="#" class="xfw-link">' + (cfg.nextLink || 'View Upcoming Meeting') + ' &rarr;</a>' +
            '</div></div></div>';
    };

    var renderSidebar = function () {
        var panels = root.querySelector('#xfw-sidebar-panels');
        if (!panels) {
            return;
        }
        var cfg = SIDEBAR[current] || SIDEBAR[0];
        var html = xfwSidebarAboutCard(cfg);
        if (cfg.middle === 'tips') {
            html += xfwSidebarTipsCard(cfg);
            html += xfwSidebarProgressCard();
        } else if (cfg.middle === 'commitments') {
            html += xfwSidebarCommitSummaryCard();
            html += xfwSidebarProgressCard();
        } else if (cfg.middle === 'progress-first') {
            html += xfwSidebarProgressCard();
            html += xfwSidebarNextCard(cfg);
        } else {
            html += xfwSidebarProgressCard();
            html += xfwSidebarHelpCard(cfg);
        }
        panels.innerHTML = html;
    };
    window.xfwRenderSidebar = renderSidebar;

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

        if (STEPS[current].key === 'synthesis' && typeof initSynthesisStep === 'function') {
            initSynthesisStep();
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
