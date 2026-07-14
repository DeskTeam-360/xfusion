<?php
/**
 * Step 1 — Generate Continuous Evidence™ (read-only accordion).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_evidence_js(): string
{
    return <<<'JS'
evidence: function () {
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';
    var items = [
        [iconBase + 'Two-People-Green-Icon-1.svg', 'Previous 1-on-1', 'Summary, commitments, and key themes from your last conversation.', 'previous_meetings', 'live'],
        [iconBase + 'Dot-and-List-Icon-Purple-1.svg', 'Previous Commitments', 'Open commitments, progress updates, and completion history.', 'commitments', 'live'],
        [iconBase + 'Profile-Avatar-Icon-1.svg', 'Individual Insights\u2122', 'Behavioral Driver trends, AI insights, and development themes.', 'individual_insights', 'live'],
        [iconBase + 'Checkmark-on-Clipboard-Icon-1.svg', 'Activities', 'Recent learning activities and engagement.', 'activities', 'live'],
        [iconBase + 'Bar-Chart-Icon-1.svg', 'Self-Assessments', 'Recent self-assessments and behavioral metrics.', 'self_assessments', 'dummy'],
        [iconBase + 'Wrench-Icon-1.svg', 'Development Tools', 'Tools completed and insights generated.', 'development_tools', 'live'],
        [iconBase + 'Trending-Arrow-Icon-1.svg', 'Behavioral Driver Trends', 'Current trends across the 5 FUSION Behavioral Drivers\u2122.', 'behavioral_drivers', 'live'],
        [iconBase + 'Brain-Icon-1.svg', 'AI Insight Trends', 'AI-generated insights and observed patterns over time.', 'ai_insight', 'live'],
        [iconBase + 'Target-Bullseye-Icon-1.svg', 'QBR Priorities', 'Current Quarterly Business Review\u2122 priorities and progress.', 'qbr_priorities', 'dummy'],
        [iconBase + 'Flag-Icon-1.svg', 'ARP Priorities', 'Annual Readiness Plan\u2122 priorities and strategic context.', 'arp_priorities', 'dummy'],
        [iconBase + 'Star-Icon-1.svg', 'Previous 360 Review\u2122', 'Most recent 360 feedback themes and insights.', 'previous_360', 'dummy'],
        [iconBase + 'Building-Icon-1.svg', 'Organizational Context', 'Role, team, organizational goals, and readiness priorities.', 'organizational_context', 'dummy'],
    ];

    var accordion = items.map(function (r) {
        var isDummy = r[4] === 'dummy';
        var dummyAttr = isDummy ? ' data-evidence-dummy="1"' : '';
        return '<div class="xfw-evidence-accordion-item" data-evidence="' + r[3] + '"' + dummyAttr + '>' +
            '<div class="xfw-evidence-row xfw-evidence-accordion-toggle" role="button" tabindex="0" aria-expanded="false">' +
            '<div class="xfw-evidence-icon"><img src="' + r[0] + '" alt="" width="50" height="50"></div>' +
            '<div><div class="xfw-evidence-title">' + r[1] + '</div><div class="xfw-evidence-desc">' + r[2] + '</div></div>' +
            '<div class="xfw-evidence-status">&#10003; Up to date</div>' +
            '</div>' +
            '<div class="xfw-evidence-accordion-panel xfw-hidden" data-evidence-key="' + r[3] + '"' + dummyAttr + '></div>' +
            '</div>';
    }).join('');

    return '<h2 class="xfw-section-title">Step 1. Generate Continuous Evidence™</h2>' +
        '<p class="xfw-section-desc">FUSION automatically assembles the most current context and evidence to support a productive 1-on-1 conversation. This evidence is used to prepare both participants and strengthen alignment.</p>' +
        '<div class="xfw-banner">ℹ️ <span>This evidence is read-only. It is generated from across the platform and cannot be edited.</span></div>' +
        '<div class="xfw-card" style="padding:0">' +
        '<div class="xfw-evidence">' + accordion + '</div>' +
        '<div class="xfw-evidence-generate">' +
        '<button type="button" class="xfw-btn xfw-btn-accent" id="xfw-generate-brief">Generate AI Meeting Brief\u2122</button>' +
        '<p class="xfw-muted" id="xfw-generate-brief-status" style="margin-top:.5rem"></p>' +
        '</div>' +
        '</div>' +
        '<p class="xfw-muted xfw-evidence-footer">&#10003; Click any section above to view evidence details</p>';
}
JS;
}
