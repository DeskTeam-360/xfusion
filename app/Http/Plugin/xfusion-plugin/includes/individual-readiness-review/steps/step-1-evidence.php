<?php
/**
 * Step 1 — Generate Individual Evidence™.
 *
 * UI-only prototype: static dummy content matching the IRR mockups. No
 * Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_evidence_js(): string
{
    return <<<'JS'
evidence: function () {
    return '<h2 class="xirr-section-title">Step 1. Generate Individual Evidence™</h2>' +
        '<p class="xirr-section-desc">FUSION will automatically compile a complete year of developmental evidence for you.<br>This evidence is gathered from across the platform and will be used to create your AI Development Assessment™.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>No action is required. The system is collecting and organizing your evidence. You will review this evidence in the next step.</span></div>' +
        '<div class="xirr-card"><h3 style="margin-top:0">Evidence Being Compiled</h3>' +
        '<div class="xirr-evidence-list" id="xirr-evidence-list"></div></div>' +
        '<div class="xirr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534">&#9989; <span><b>Evidence Compilation Complete.</b> All available evidence has been collected for your Individual Readiness Review™.</span></div>' +
        '<div class="xirr-card"><h4 style="margin-top:0">What\'s Next?</h4>' +
        '<p class="xirr-muted" style="margin:0">In Step 2, you will review your objective evidence, including trends, participation, commitments, and growth over the past year.</p></div>';
}
JS;
}

function xfirr_wizard_evidence_init_js(): string
{
    return <<<'JS'
(function () {
    var DUMMY_SOURCES = [
        { title: 'Individual Insights™', desc: 'Behavioral Driver trends, energy patterns and personal insights' },
        { title: 'Previous Individual Readiness Review™', desc: 'Insights, commitments and progress from prior reviews' },
        { title: 'Activities', desc: 'Completed activities and learning engagement throughout the year' },
        { title: 'Commitment Completion', desc: 'Status of your development commitments' },
        { title: 'Self-Assessments', desc: 'Assessment results and self-ratings over time' },
        { title: 'Behavioral Driver Trends', desc: 'Behavioral Driver performance and growth trends' },
        { title: 'Reflection Themes', desc: 'AI-extracted themes from private reflections and journals' },
        { title: 'Leader Observations', desc: 'Leader feedback and observed behaviors throughout the year' },
        { title: 'Tool Usage', desc: 'Development tools used and key insights generated' },
        { title: 'Organizational Context', desc: 'Organizational events, priorities and context' },
        { title: '1-on-1 Alignment Capture™', desc: 'Key discussion themes and alignment insights' },
        { title: 'QBR & ARP Priorities', desc: 'Quarterly priorities and strategic objectives alignment' },
    ];

    function renderSources() {
        var list = document.getElementById('xirr-evidence-list');
        if (!list) return;
        list.innerHTML = DUMMY_SOURCES.map(function (s) {
            return '<div class="xirr-evidence-row">' +
                '<div class="xirr-evidence-icon">&#9673;</div>' +
                '<div><div class="xirr-evidence-title">' + s.title + '</div>' +
                '<div class="xirr-evidence-desc">' + s.desc + '</div></div>' +
                '<div class="xirr-evidence-status ok">&#10003; Collected</div>' +
                '</div>';
        }).join('');
    }

    window.initEvidenceStep = function () {
        renderSources();
    };
})();
JS;
}
