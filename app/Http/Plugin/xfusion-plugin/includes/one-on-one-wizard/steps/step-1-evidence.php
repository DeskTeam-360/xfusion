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
    var items = [
        ['#e9f5e1;color:#3f7d1f', '\u{1F465}', 'Previous 1-on-1', 'Summary, commitments, and key themes from your last conversation.', 'previous_meetings', 'live'],
        ['#f1e9fb;color:#7c3aed', '\u{1F4CB}', 'Previous Commitments', 'Open commitments, progress updates, and completion history.', 'commitments', 'live'],
        ['#e0f2f7;color:#0891b2', '\u{1F464}', 'Individual Insights\u2122', 'Behavioral Driver trends, AI insights, and development themes.', 'individual_insights', 'live'],
        ['#fde8d7;color:#ea580c', '\u2705', 'Activities', 'Recent learning activities and engagement.', 'activities', 'live'],
        ['#dbeafe;color:#2563eb', '\u{1F4CA}', 'Self-Assessments', 'Recent self-assessments and behavioral metrics.', 'self_assessments', 'dummy'],
        ['#e9f5e1;color:#3f7d1f', '\u{1F527}', 'Development Tools', 'Tools completed and insights generated.', 'development_tools', 'live'],
        ['#f1e9fb;color:#7c3aed', '\u{1F4C8}', 'Behavioral Driver Trends', 'Current trends across the 5 FUSION Behavioral Drivers\u2122.', 'behavioral_drivers', 'live'],
        ['#fde8d7;color:#ea580c', '\u{1F4A1}', 'AI Insight Trends', 'AI-generated insights and observed patterns over time.', 'ai_insight', 'live'],
        ['#dbeafe;color:#2563eb', '\u{1F3AF}', 'QBR Priorities', 'Current Quarterly Business Review\u2122 priorities and progress.', 'qbr_priorities', 'dummy'],
        ['#e9f5e1;color:#3f7d1f', '\u{1F6A9}', 'ARP Priorities', 'Annual Readiness Plan\u2122 priorities and strategic context.', 'arp_priorities', 'dummy'],
        ['#f1e9fb;color:#7c3aed', '\u2B50', 'Previous 360 Review\u2122', 'Most recent 360 feedback themes and insights.', 'previous_360', 'dummy'],
        ['#fde8d7;color:#ea580c', '\u{1F3E2}', 'Organizational Context', 'Role, team, organizational goals, and readiness priorities.', 'organizational_context', 'dummy'],
    ];

    var accordion = items.map(function (r) {
        var iconBg = r[0].split(';')[0];
        var iconColor = r[0].split(';')[1].replace('color:', '');
        var isDummy = r[5] === 'dummy';
        var dummyAttr = isDummy ? ' data-evidence-dummy="1"' : '';
        return '<div class="xfw-evidence-accordion-item" data-evidence="' + r[4] + '"' + dummyAttr + '>' +
            '<div class="xfw-evidence-row xfw-evidence-accordion-toggle" role="button" tabindex="0" aria-expanded="false">' +
            '<div class="xfw-evidence-icon" style="background:' + iconBg + ';color:' + iconColor + '">' + r[1] + '</div>' +
            '<div><div class="xfw-evidence-title">' + r[2] + '</div><div class="xfw-evidence-desc">' + r[3] + '</div></div>' +
            '<div class="xfw-evidence-status">&#10003; Up to date</div>' +
            '</div>' +
            '<div class="xfw-evidence-accordion-panel xfw-hidden" data-evidence-key="' + r[4] + '"' + dummyAttr + '></div>' +
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
