<?php
/**
 * Step 2 — AI Meeting Brief™ (UI shell, static dummy data).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_brief_js(): string
{
    return <<<'JS'
brief: function () {
    function card(color, icon, title, items) {
        return '<div class="xfw-insight-card"><div class="icon" style="background:' + color + '22;color:' + color + '">' + icon + '</div>' +
            '<h3>' + title + '</h3><ul>' + items.map(function (i) { return '<li>' + i + '</li>'; }).join('') + '</ul>' +
            '<a href="#" class="xfw-link">View Details &rarr;</a></div>';
    }
    var discussion = [
        'Review progress on critical QBR priorities and related commitments.',
        'Discuss current obstacle impacting Project Phoenix timeline.',
        'Explore support needed for upcoming cross-functional initiative.',
        'Talk about development focus: strategic communication.',
        'Align on expectations for upcoming customer launch.',
    ];
    return '<h2 class="xfw-section-title">Step 2. AI Meeting Brief™</h2>' +
        '<p class="xfw-section-desc">FUSION AI analyzes your continuous evidence and prepares insights to help both participants have a more meaningful and productive conversation.</p>' +
        '<div class="xfw-banner">ℹ️ <span>This brief is AI-generated and read-only. Use these insights to guide your conversation. <b>The AI prepares &mdash; people converse.</b></span></div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        card('#16a34a', '\u{1F3AF}', 'Alignment Snapshot™', ['Priorities show strong alignment with QBR and ARP strategic themes.', 'Employee is making steady progress on key commitments.', 'Development focus aligns with role expectations.', 'Overall alignment rating trending upward over last 3 meetings.']) +
        card('#7c3aed', '\u{1F464}', 'Development Snapshot™', ['Strengths in problem solving and cross-functional collaboration.', 'Growth opportunity in strategic communication.', 'Recent learning activities support current development goals.', 'Development momentum is on track.']) +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        card('#ea580c', '\u{1F4CB}', 'Commitment Review™', ['6 active commitments (3 by employee, 3 by leader).', '83% of commitments are on track.', '2 commitments due within the next 2 weeks.', 'No overdue commitments.']) +
        card('#0891b2', '\u{1F4C8}', 'Behavioral Trends™', ['Foster Grit trending up over last 60 days.', 'Be Intentional remains strong and consistent.', 'Drive Growth shows improvement in goal-setting behavior.', 'Insights based on activities, reflections, and assessments.']) +
        '</div>' +
        '<div class="xfw-card" style="margin-bottom:1rem">' +
        '<h3>Suggested Discussion Areas™</h3>' +
        '<ol class="xfw-numbered">' + discussion.map(function (d, i) { return '<li><span class="n">' + (i + 1) + '</span>' + d + '</li>'; }).join('') + '</ol>' +
        '<a href="#" class="xfw-link">View Details &rarr;</a>' +
        '</div>' +
        '<div class="xfw-grid-2">' +
        card('#ca8a04', '\u{1F4A1}', 'Emerging Opportunities™', ['Opportunity to lead cross-departmental planning session.', 'Potential to expand influence with stakeholder communication.', 'New stretch assignment aligns with career goals.']) +
        card('#dc2626', '⚠️', 'Potential Barriers™', ['Competing priorities may impact commitment completion.', 'Resource constraints noted in recent reflections.', 'Communication bottlenecks with other teams.']) +
        '</div>';
}
JS;
}
