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

    // RPM-style semicircle gauge (0–100), same visual language as the
    // Course Scoring Overview gauges elsewhere in FUSION (red/amber/green
    // zones + needle), just re-derived for a 0–100 scale instead of 0–5.
    function point(fraction) {
        var theta = (-90 + fraction * 180) * Math.PI / 180;
        return { x: 110 + 75 * Math.sin(theta), y: 110 - 75 * Math.cos(theta) };
    }
    function arcPath(f1, f2) {
        var p1 = point(f1), p2 = point(f2);
        return 'M ' + p1.x.toFixed(2) + ' ' + p1.y.toFixed(2) + ' A 75 75 0 0 1 ' + p2.x.toFixed(2) + ' ' + p2.y.toFixed(2);
    }
    function rpmGauge(value, zoneLabel, zoneColor) {
        var v = Math.max(0, Math.min(100, value));
        var needleDeg = -90 + (v / 100) * 180;
        return '<div style="text-align:center">' +
            '<svg viewBox="0 0 220 130" style="width:100%;max-width:220px">' +
            '<path fill="none" stroke="#dc2626" stroke-width="10" stroke-linecap="round" d="' + arcPath(0, 0.5) + '"/>' +
            '<path fill="none" stroke="#ca8a04" stroke-width="10" stroke-linecap="round" d="' + arcPath(0.5, 0.7) + '"/>' +
            '<path fill="none" stroke="#16a34a" stroke-width="10" stroke-linecap="round" d="' + arcPath(0.7, 1) + '"/>' +
            '<text x="35" y="126" text-anchor="middle" fill="#9ca3af" font-size="11" font-weight="600">0</text>' +
            '<text x="110" y="26" text-anchor="middle" fill="#9ca3af" font-size="11" font-weight="600">50</text>' +
            '<text x="185" y="126" text-anchor="middle" fill="#9ca3af" font-size="11" font-weight="600">100</text>' +
            '<g transform="rotate(' + needleDeg + ' 110 110)"><line x1="110" y1="112" x2="110" y2="36" stroke="#1f2937" stroke-width="4" stroke-linecap="round"/></g>' +
            '<circle cx="110" cy="110" r="7" fill="#1f2937"/><circle cx="110" cy="110" r="4" fill="#fff"/>' +
            '</svg>' +
            '<p style="font-size:1.4rem;font-weight:800;color:#1e2a52;margin:.25rem 0 0">' + v + '<span style="font-size:1rem;font-weight:400;color:#6b7280">/100</span></p>' +
            '<p style="font-size:.85rem;font-weight:600;color:' + zoneColor + ';margin:0">' + esc(zoneLabel) + '</p>' +
            '</div>';
    }

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
        var cls = k.good ? 'up' : 'down';
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

    // Simple inline SVG line chart — width 100%, viewBox 0 0 400 180.
    // series: [{ label, color, values: [n,n,n] }], months: ['Apr','May','Jun']
    function lineChart(series, months, yMax) {
        var W = 400, H = 160, padL = 30, padR = 10, padT = 10, padB = 24;
        var plotW = W - padL - padR, plotH = H - padT - padB;
        var stepX = plotW / (months.length - 1);

        function xy(i, v) {
            var x = padL + i * stepX;
            var y = padT + plotH - (v / yMax) * plotH;
            return x.toFixed(1) + ',' + y.toFixed(1);
        }

        var gridLines = '';
        [0, 0.25, 0.5, 0.75, 1].forEach(function (f) {
            var y = padT + plotH - f * plotH;
            gridLines += '<line x1="' + padL + '" y1="' + y + '" x2="' + (W - padR) + '" y2="' + y + '" stroke="#e5e7eb" stroke-width="1"/>';
        });

        var axisLabels = months.map(function (m, i) {
            var x = padL + i * stepX;
            return '<text x="' + x + '" y="' + (H - 6) + '" text-anchor="middle" font-size="10" fill="#9ca3af">' + m + '</text>';
        }).join('');

        var lines = series.map(function (s) {
            var pts = s.values.map(function (v, i) { return xy(i, v); }).join(' ');
            var dots = s.values.map(function (v, i) {
                var parts = xy(i, v).split(',');
                return '<circle cx="' + parts[0] + '" cy="' + parts[1] + '" r="3" fill="' + s.color + '"/>';
            }).join('');
            return '<polyline points="' + pts + '" fill="none" stroke="' + s.color + '" stroke-width="2"/>' + dots;
        }).join('');

        var legend = series.map(function (s) {
            return '<span style="display:inline-flex;align-items:center;gap:.3rem;margin-right:1rem;font-size:12px;color:#374151">' +
                '<span style="width:10px;height:10px;border-radius:50%;background:' + s.color + ';display:inline-block"></span>' + esc(s.label) + '</span>';
        }).join('');

        return '<div>' +
            '<svg viewBox="0 0 ' + W + ' ' + H + '" style="width:100%;max-width:420px;height:auto">' + gridLines + lines + axisLabels + '</svg>' +
            '<div style="margin-top:.4rem">' + legend + '</div>' +
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
            '<div style="display:grid;grid-template-columns:auto 1fr 1fr;gap:1.5rem;align-items:center;margin-bottom:1.25rem">' +
            rpmGauge(72, 'Moderate Strength', '#ca8a04') +
            '<div class="xqbr-metric-grid" style="grid-template-columns:1fr 1fr">' +
            statCard('1-on-1 Completion Rate', 81, '%', { dir: 'up', text: '7% vs last quarter' }) +
            statCard('Activity Participation', 76, '%', { dir: 'up', text: '5% vs last quarter' }) +
            statCard('Assessment Completion', 74, '%', { dir: 'up', text: '9% vs last quarter' }) +
            statCard('Tool Utilization Rate', 69, '%', { dir: 'up', text: '11% vs last quarter' }) +
            '</div>' +
            '<div style="display:flex;gap:1.5rem;justify-content:center">' +
            donut(68, 100, 'QBR Objectives Progress (↑8%)', '#2563eb') +
            donut(63, 100, 'Commitment Completion (↑12%)', '#ca8a04') +
            '</div>' +
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
            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">Participation Trends</h3>' +
            lineChart([
                { label: 'Activities', color: '#16a34a', values: [55, 65, 75] },
                { label: '1-on-1s', color: '#2563eb', values: [70, 76, 82] },
                { label: 'Assessments', color: '#7c3aed', values: [40, 48, 62] },
            ], ['Apr', 'May', 'Jun'], 100) +
            '</div>' +

            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">Behavioral Driver Trends</h3>' +
            lineChart([
                { label: 'Get Real', color: '#1e2a52', values: [3.9, 4.0, 4.1] },
                { label: 'Be Intentional', color: '#7c3aed', values: [3.8, 3.9, 4.0] },
                { label: 'Fill Buckets', color: '#2563eb', values: [3.2, 3.1, 3.3] },
                { label: 'Foster Grit', color: '#dc2626', values: [2.9, 3.2, 3.6] },
                { label: 'Drive Growth', color: '#0891b2', values: [3.0, 3.1, 3.0] },
            ], ['Apr', 'May', 'Jun'], 5) +
            '</div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:start">' +
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
            '</div>';
    };
})();
JS;
}
