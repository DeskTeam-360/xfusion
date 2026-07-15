<?php
/**
 * Step 7 — Publish ARP™ (review summary, activations, publish actions).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_publish_js(): string
{
    return <<<'JS'
publish: function () {
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';
    var root = document.getElementById('xfarp-wiz');
    function textOr(sel, fallback) {
        var el = root ? root.querySelector(sel) : null;
        var t = el ? (el.textContent || '').trim() : '';
        return t && t !== '—' ? t : fallback;
    }
    var org = textOr('#xar-si-org', 'Northwind Solar Co-op');
    var year = textOr('#xar-si-year', '2025');
    var owner = textOr('#xar-si-owner', 'James Scott');
    var version = textOr('#xar-si-version', '1.0');
    var saved = textOr('#xar-si-saved', 'May 14, 2025 10:32 AM');

    function summaryRow(label, value) {
        return '<div class="xar-summary-item"><dt>' + label + '</dt><dd>' + value + '</dd></div>';
    }

    function reviewRow(stepIndex, label) {
        return '<div class="xar-review-row">' +
            '<div class="xar-review-left">' +
            '<span class="xar-review-check" aria-hidden="true">&#10003;</span>' +
            '<div><strong>' + label + '</strong><div class="xar-review-status">Complete</div></div>' +
            '</div>' +
            '<button type="button" class="xar-btn xar-btn-outline xar-btn-sm" data-edit-step="' + stepIndex + '">Edit</button>' +
            '</div>';
    }

    function activateCard(icon, title, desc) {
        return '<div class="xar-activate-card">' +
            '<img src="' + iconBase + icon + '" alt="" width="40" height="40">' +
            '<h4>' + title + '</h4>' +
            '<p class="xar-muted">' + desc + '</p>' +
            '</div>';
    }

    function actionCard(id, icon, title, desc, btnLabel, btnClass) {
        return '<div class="xar-action-card">' +
            '<img src="' + iconBase + icon + '" alt="" width="40" height="40">' +
            '<h4>' + title + '</h4>' +
            '<p class="xar-muted">' + desc + '</p>' +
            '<button type="button" class="xar-btn ' + btnClass + '" id="' + id + '">' + btnLabel + '</button>' +
            '</div>';
    }

    return '<h2 class="xar-section-title">Step 7. Publish ARP™</h2>' +
        '<p class="xar-section-desc">Review your plan, publish to activate the FUSION Operating System™, and ensure alignment across the organization for the year ahead.</p>' +

        '<div class="xar-card">' +
        '<h3 class="xar-ai-heading">Publish Summary</h3>' +
        '<dl class="xar-summary-grid">' +
        summaryRow('Organization', org) +
        summaryRow('Plan Year', year) +
        summaryRow('Executive Owner', owner) +
        summaryRow('Version', version) +
        summaryRow('Status', '<span class="xar-badge amber">Draft</span>') +
        summaryRow('Created', 'May 14, 2025 10:12 AM') +
        summaryRow('Last Saved', saved) +
        summaryRow('Estimated Completion Time', '90 – 120 minutes') +
        '</dl></div>' +

        '<div class="xar-card">' +
        '<h3 class="xar-ai-heading">Review Your Plan</h3>' +
        '<div class="xar-review-list">' +
        reviewRow(0, 'Step 1: Organizational Foundation™') +
        reviewRow(1, 'Step 2: Future State™') +
        reviewRow(2, 'Step 3: Organizational Readiness™') +
        reviewRow(3, 'Step 4: Strategic Priorities™') +
        reviewRow(4, 'Step 5: Organizational Learning™') +
        reviewRow(5, 'Step 6: AI Readiness Review™') +
        reviewRow(5, 'Leadership Context™') +
        '</div></div>' +

        '<div class="xar-card">' +
        '<h3 class="xar-ai-heading">Publishing Will Activate</h3>' +
        '<div class="xar-activate-grid">' +
        activateCard('Calendar-Icon-Teal.svg', 'Quarterly Business Reviews™', 'Drive execution and align on priorities throughout the year.') +
        activateCard('Two-People-Green-Icon-1.svg', 'Leader Dashboards™', 'Provide leaders with real-time visibility into priorities and progress.') +
        activateCard('Progressing-Business-Chart-Icon.svg', 'Executive Dashboards™', 'Deliver organization-wide insights and strategic readiness visibility.') +
        activateCard('Clipboard-Checkmark-Icon.svg', '1-on-1 Alignment Capture™', 'Align individual goals and development with organizational priorities.') +
        activateCard('Orange-Light-Bulb-Icon.svg', 'Annual Readiness Review™', 'Evaluate progress and inform next year\'s strategic plan.') +
        '</div></div>' +

        '<div class="xar-banner warn">' +
        '<span class="xar-banner-icon" aria-hidden="true">⚠</span>' +
        '<span><b>Important:</b> Publishing will make this plan the Active ARP for ' + year +
        '. Previous versions will be archived and available for reference.</span>' +
        '</div>' +
        '<div class="xar-banner">' +
        '<span class="xar-banner-icon" aria-hidden="true">ℹ️</span>' +
        '<span><b>Before you publish:</b> We recommend reviewing all sections to ensure accuracy and completeness. Once published, you cannot edit the core content of the active ARP. Updates can be made in future versions or through Quarterly Business Reviews™.</span>' +
        '</div>' +

        '<div class="xar-action-grid">' +
        actionCard('xar-publish-save', 'Clipboard-Checkmark-Blue-Icon.svg', 'Save Draft',
            'Save your progress and continue working on your plan.', 'Save Draft', 'xar-btn-outline') +
        actionCard('xar-publish-archive', 'Database-Icon-1.svg', 'Archive Previous Version',
            'Archive Version 0.9 (Draft) before publishing this version.', 'Archive Version 0.9', 'xar-btn-outline') +
        actionCard('xar-publish-go', 'Trending-Up-Arrow-Icon-Green-1.svg', 'Publish ARP',
            'Publish this plan and activate FUSION\'s connected operating system.', 'Publish ARP', 'xar-btn-accent') +
        '</div>';
}
JS;
}

function xfarp_wizard_publish_init_js(): string
{
    return <<<'JS'
(function () {
    window.xarPublishArp = function () {
        var confirmed = window.confirm('Publish this Annual Readiness Plan™ and activate it for the plan year?');
        if (!confirmed) {
            return;
        }
        var statusBadge = document.querySelector('#xar-si-status .xar-badge');
        if (statusBadge) {
            statusBadge.textContent = 'Published';
            statusBadge.classList.remove('amber');
            statusBadge.classList.add('green');
        }
        var status = document.getElementById('xar-autosave-status');
        if (status) {
            var now = new Date();
            var time = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            status.innerHTML = '<span class="xar-autosave-check" aria-hidden="true">&#10003;</span> ARP published ' + time;
        }
        window.alert('ARP published (UI shell). Backend publish will be wired in a future update.');
    };

    window.initPublishStep = function () {
        var main = document.getElementById('xar-main');
        if (!main) {
            return;
        }
        main.querySelectorAll('[data-edit-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-edit-step'), 10);
                if (typeof window.xarGoTo === 'function') {
                    window.xarGoTo(idx);
                }
            });
        });
        var saveBtn = document.getElementById('xar-publish-save');
        if (saveBtn) {
            saveBtn.onclick = function () {
                var headerSave = document.getElementById('xar-save-draft');
                if (headerSave) {
                    headerSave.click();
                }
            };
        }
        var archiveBtn = document.getElementById('xar-publish-archive');
        if (archiveBtn) {
            archiveBtn.onclick = function () {
                window.alert('Previous version archived (UI shell).');
            };
        }
        var publishBtn = document.getElementById('xar-publish-go');
        if (publishBtn) {
            publishBtn.onclick = function () {
                window.xarPublishArp();
            };
        }
    };
})();
JS;
}
