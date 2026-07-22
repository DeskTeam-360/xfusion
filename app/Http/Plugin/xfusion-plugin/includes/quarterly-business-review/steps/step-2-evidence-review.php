<?php
/**
 * Step 2 — Organizational Evidence™ (review dashboard).
 *
 * UI-only prototype: all data below is static dummy content matching the
 * QBR mockups. No Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_evidence_review_js(): string
{
    return <<<'JS'
evidence_review: function () {
    return '<h2 class="xqbr-section-title">Step 2. Organizational Evidence™</h2>' +
        '<p class="xqbr-section-desc">Review the objective evidence for the current review period. This data is pulled from across the platform and provides the factual foundation for leadership analysis and discussion.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>This is objective evidence only. Interpretation and assessment will be provided in Step 3 by AI.</span></div>' +
        '<div id="xqbr-evidence-review-body"></div>';
}
JS;
}

function xfqbr_wizard_evidence_review_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function donut(score, max, label, color) {
        var s = Math.max(0, Math.min(100, Math.round((score / max) * 100)));
        return '<div class="xqbr-donut-wrap">' +
            '<div class="xqbr-donut-chart">' +
            '<svg class="xqbr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xqbr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xqbr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xqbr-donut-center"><div class="xqbr-donut-score">' + score + '<span>/' + max + '</span></div></div>' +
            '</div>' +
            '<div class="xqbr-donut-label">' + esc(label) + '</div>' +
            '</div>';
    }

    function statCard(label, value, unit, trend) {
        var trendHtml = trend ? '<div class="xqbr-metric-trend ' + trend.dir + '">' +
            (trend.dir === 'up' ? '&#8593;' : '&#8595;') + ' ' + trend.text + '</div>' : '';
        return '<div class="xqbr-metric-card"><div class="xqbr-metric-label">' + label + '</div>' +
            '<div class="xqbr-metric-value">' + value + '<span class="unit">' + unit + '</span></div>' + trendHtml + '</div>';
    }

    function kpiRow(k) {
        var cls = k.trend.indexOf('+') === 0 && k.good ? 'up' : (k.trend.indexOf('-') === 0 && !k.good ? 'down' : (k.good ? 'up' : 'down'));
        return '<tr><td>' + esc(k.name) + '</td><td>' + esc(k.current) + '</td><td>' + esc(k.target) + '</td>' +
            '<td><span class="xqbr-dot ' + k.dot + '"></span></td>' +
            '<td class="xqbr-metric-trend ' + cls + '" style="margin:0">' + esc(k.trend) + '</td></tr>';
    }

    function goalRow(name, pct) {
        return '<div class="xqbr-align-row xqbr-progress-row">' +
            '<span class="xqbr-align-label">' + esc(name) + '</span>' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<strong class="xqbr-progress-pct">' + pct + '%</strong>' +
            '</div>';
    }

    function capabilityRow(label, score, trend) {
        return '<div class="xqbr-align-row xqbr-progress-row">' +
            '<span class="xqbr-align-label">' + esc(label) + '</span>' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + (score / 5 * 100) + '%"></div></div>' +
            '<strong class="xqbr-progress-pct">' + score.toFixed(1) + ' <span class="xqbr-metric-trend ' + (trend >= 0 ? 'up' : 'down') + '" style="display:inline">' + (trend >= 0 ? '↑' : '↓') + Math.abs(trend).toFixed(1) + '</span></strong>' +
            '</div>';
    }

    window.initEvidenceReviewStep = function () {
        var body = document.getElementById('xqbr-evidence-review-body');
        if (!body) return;

        var kpis = [
            { name: 'Revenue Growth', current: '$4.2M', target: '$4.5M', trend: '-3%', good: false, dot: 'amber' },
            { name: 'Customer Retention', current: '92%', target: '90%', trend: '+2%', good: true, dot: 'green' },
            { name: 'Project On-Time Delivery', current: '88%', target: '90%', trend: '-2%', good: false, dot: 'amber' },
            { name: 'Safety Incident Rate', current: '1.2', target: '1.0', trend: '+0.2', good: false, dot: 'amber' },
            { name: 'Employee Engagement', current: '78%', target: '80%', trend: '-2%', good: false, dot: 'amber' },
            { name: 'Cost per Project', current: '$12.4K', target: '$12.0K', trend: '+3%', good: false, dot: 'amber' },
        ];

        body.innerHTML =
            '<div class="xqbr-card"><h3 style="margin-top:0">Organizational Evidence Summary</h3>' +
            '<div class="xqbr-ai-split" style="display:grid;grid-template-columns:repeat(3,auto);gap:1rem;justify-content:start;margin-bottom:1.25rem">' +
            donut(72, 100, 'Overall Readiness Score (↑6 vs last quarter)', '#5f9a3f') +
            donut(68, 100, 'QBR Objectives Progress (↑8% vs last quarter)', '#2563eb') +
            donut(63, 100, 'Commitment Completion (↑12% vs last quarter)', '#ca8a04') +
            '</div>' +
            '<div class="xqbr-metric-grid">' +
            statCard('1-on-1 Completion Rate', 81, '%', { dir: 'up', text: '7% vs last quarter' }) +
            statCard('Activity Participation', 76, '%', { dir: 'up', text: '5% vs last quarter' }) +
            statCard('Assessment Completion', 74, '%', { dir: 'up', text: '9% vs last quarter' }) +
            statCard('Tool Utilization Rate', 69, '%', { dir: 'up', text: '11% vs last quarter' }) +
            '</div></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;align-items:start">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">KPI Summary (vs Target)</h3>' +
            '<div class="xqbr-table-scroll"><table class="xqbr-table"><thead><tr><th>KPI</th><th>Current</th><th>Target</th><th>Status</th><th>Trend</th></tr></thead><tbody>' +
            kpis.map(kpiRow).join('') +
            '</tbody></table></div></div>' +

            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">Goal Progress (QBR Objectives)</h3>' +
            '<div class="xqbr-align-list">' +
            goalRow('1. Expand Community Solar Program', 75) +
            goalRow('2. Improve Project Delivery Efficiency', 62) +
            goalRow('3. Strengthen Member Engagement', 80) +
            goalRow('4. Optimize Operational Costs', 55) +
            goalRow('5. Build Leadership Bench Strength', 70) +
            '</div></div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;align-items:start">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">COR Capability Trends</h3>' +
            '<div class="xqbr-align-list">' +
            capabilityRow('Alignment', 3.8, 0.3) +
            capabilityRow('Accountability', 3.6, 0.2) +
            capabilityRow('Communication', 3.7, 0.4) +
            capabilityRow('Leadership', 3.9, 0.3) +
            capabilityRow('Execution', 3.5, -0.1) +
            '</div></div>' +

            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">Readiness Indicators</h3>' +
            '<div class="xqbr-metric-grid" style="grid-template-columns:1fr">' +
            statCard('People Readiness', 71, '%', { dir: 'up', text: '6 vs last quarter' }) +
            statCard('Process Readiness', 68, '%', { dir: 'up', text: '5 vs last quarter' }) +
            statCard('System Readiness', 76, '%', { dir: 'up', text: '7 vs last quarter' }) +
            '</div></div>' +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">Participation &amp; Behavioral Driver Trends</h3>' +
            '<p class="xqbr-muted">Activities, 1-on-1s, and Assessments trended upward across Apr &ndash; Jun. Get Real and Be Intentional remain the strongest Behavioral Drivers™ this quarter; Foster Grit is showing the most improvement.</p>' +
            '</div>';
    };
})();
JS;
}
