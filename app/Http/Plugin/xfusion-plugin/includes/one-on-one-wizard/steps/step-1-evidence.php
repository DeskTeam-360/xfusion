<?php
/**
 * Step 1 — Generate Continuous Evidence™ (UI shell, static dummy data).
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
    var rows = [
        ['#e9f5e1;color:#3f7d1f', '\u{1F465}', 'Previous 1-on-1', 'Summary, commitments, and key themes from your last conversation.'],
        ['#f1e9fb;color:#7c3aed', '\u{1F4CB}', 'Previous Commitments', 'Open commitments, progress updates, and completion history.'],
        ['#e0f2f7;color:#0891b2', '\u{1F464}', 'Individual Insights™', 'Behavioral Driver trends, AI insights, and development themes.'],
        ['#fde8d7;color:#ea580c', '✅', 'Activities', 'Recent learning activities and engagement.'],
        ['#dbeafe;color:#2563eb', '\u{1F4CA}', 'Self-Assessments', 'Recent self-assessments and behavioral metrics.'],
        ['#e9f5e1;color:#3f7d1f', '\u{1F527}', 'Development Tools', 'Tools completed and insights generated.'],
        ['#f1e9fb;color:#7c3aed', '\u{1F4C8}', 'Behavioral Driver Trends', 'Current trends across the 5 FUSION Behavioral Drivers.'],
        ['#fde8d7;color:#ea580c', '\u{1F4A1}', 'AI Insight Trends', 'AI-generated insights and observed patterns over time.'],
        ['#dbeafe;color:#2563eb', '\u{1F3AF}', 'QBR Priorities', 'Current Quarterly Business Review™ priorities and progress.'],
        ['#e9f5e1;color:#3f7d1f', '\u{1F6A9}', 'ARP Priorities', 'Annual Readiness Plan™ priorities and strategic context.'],
        ['#f1e9fb;color:#7c3aed', '⭐', 'Previous 360 Review™', 'Most recent 360 feedback themes and insights.'],
        ['#fde8d7;color:#ea580c', '\u{1F3E2}', 'Organizational Context', 'Role, team, organizational goals, and readiness priorities.'],
    ];
    return '<h2 class="xfw-section-title">Step 1. Generate Continuous Evidence™</h2>' +
        '<p class="xfw-section-desc">FUSION automatically assembles the most current context and evidence to support a productive 1-on-1 conversation. This evidence is used to prepare both participants and strengthen alignment.</p>' +
        '<div class="xfw-banner">ℹ️ <span>This evidence is read-only. It is generated from across the platform and cannot be edited.</span></div>' +
        '<div class="xfw-card" style="padding:0">' +
        '<div class="xfw-evidence">' + rows.map(function (r) {
            return '<div class="xfw-evidence-row">' +
                '<div class="xfw-evidence-icon" style="background:' + r[0].split(';')[0] + ';color:' + r[0].split(';')[1].replace('color:','') + '">' + r[1] + '</div>' +
                '<div><div class="xfw-evidence-title">' + r[2] + '</div><div class="xfw-evidence-desc">' + r[3] + '</div></div>' +
                '<div class="xfw-evidence-status">&#10003; Up to date</div>' +
                '</div>';
        }).join('') + '</div></div>' +
        '<p class="xfw-muted" style="margin-top:1rem">&#10003; All evidence is current through May 14, 2025 10:32 AM</p>';
}
JS;
}
