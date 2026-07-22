<?php
/**
 * Step 2 — Individual Evidence™.
 *
 * UI-only prototype: static dummy content matching the IRR mockups (line
 * chart for Behavioral Driver Trends, donuts for Development Participation
 * and Commitment Completion, Growth Timeline, Leadership Observations,
 * strength/development/alignment bars, evidence highlight stat cards). No
 * Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_evidence_review_js(): string
{
    return <<<'JS'
evidence_review: function () {
    return '<h2 class="xirr-section-title">Step 2. Individual Evidence™</h2>' +
        '<p class="xirr-section-desc">Review the objective developmental evidence collected throughout the year.<br>This evidence reflects your growth, participation, commitments, and contributions.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>This is a fact-based view of your year. AI interpretation and insights will be provided in the next step.</span></div>' +
        '<div id="xirr-evidence-review-body"></div>';
}
JS;
}

function xfirr_wizard_evidence_review_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function donut(pct, color, label) {
        var s = Math.max(0, Math.min(100, Math.round(pct)));
        return '<div class="xirr-donut-wrap">' +
            '<div class="xirr-donut-chart">' +
            '<svg class="xirr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xirr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xirr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xirr-donut-center"><div class="xirr-donut-score">' + s + '<span>%</span></div></div>' +
            '</div>' +
            (label ? '<div class="xirr-donut-label">' + esc(label) + '</div>' : '') +
            '</div>';
    }

    function lineChart(series, months, yMin, yMax) {
        var w = 400, h = 160, padL = 28, padB = 20, padT = 8, padR = 8;
        var plotW = w - padL - padR, plotH = h - padT - padB;
        function x(i) { return padL + (i / (months.length - 1)) * plotW; }
        function y(v) { return padT + plotH - ((v - yMin) / (yMax - yMin)) * plotH; }

        var gridLines = '';
        for (var g = 0; g <= 4; g++) {
            var gv = yMin + ((yMax - yMin) * g / 4);
            var gy = y(gv);
            gridLines += '<line x1="' + padL + '" y1="' + gy + '" x2="' + (w - padR) + '" y2="' + gy + '" stroke="#eef0f3" stroke-width="1"/>' +
                '<text x="2" y="' + (gy + 3) + '" font-size="8" fill="#9ca3af">' + (Math.round(gv * 10) / 10) + '</text>';
        }

        var monthLabels = months.map(function (m, i) {
            if (i % Math.ceil(months.length / 6) !== 0 && i !== months.length - 1) return '';
            return '<text x="' + x(i) + '" y="' + (h - 4) + '" font-size="8" fill="#9ca3af" text-anchor="middle">' + m + '</text>';
        }).join('');

        var polylines = series.map(function (s) {
            var pts = s.values.map(function (v, i) { return x(i) + ',' + y(v); }).join(' ');
            var dash = s.dashed ? ' stroke-dasharray="4 3"' : '';
            var dots = s.values.map(function (v, i) {
                return '<circle cx="' + x(i) + '" cy="' + y(v) + '" r="2.3" fill="' + s.color + '"/>';
            }).join('');
            return '<polyline points="' + pts + '" fill="none" stroke="' + s.color + '" stroke-width="2"' + dash + '/>' + dots;
        }).join('');

        var legend = '<div class="xirr-row" style="gap:1.25rem;margin-top:.5rem">' + series.map(function (s) {
            return '<span style="display:inline-flex;align-items:center;gap:.4rem;font-size:13px;color:var(--muted)">' +
                '<span style="width:18px;height:2px;background:' + s.color + ';display:inline-block' + (s.dashed ? ';border-top:2px dashed ' + s.color + ';background:none' : '') + '"></span>' + esc(s.label) + '</span>';
        }).join('') + '</div>';

        return '<svg viewBox="0 0 ' + w + ' ' + h + '" style="width:100%;height:auto">' + gridLines + monthLabels + polylines + '</svg>' + legend;
    }

    function progressRow(label, value, max) {
        max = max || 5;
        var pct = Math.round((value / max) * 100);
        return '<div class="xirr-align-row xirr-progress-row">' +
            '<div class="xirr-align-label">' + esc(label) + '</div>' +
            '<div class="xirr-progress-track"><div class="xirr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="xirr-progress-pct">' + value + '</div>' +
            '</div>';
    }

    function statCard(label, value, trend) {
        return '<div class="xirr-metric-card"><p class="xirr-metric-label">' + esc(label) + '</p>' +
            '<div class="xirr-metric-value">' + value + '</div>' +
            (trend ? '<div class="xirr-metric-trend up">' + trend + '</div>' : '') +
            '</div>';
    }

    window.initEvidenceReviewStep = function () {
        var body = document.getElementById('xirr-evidence-review-body');
        if (!body) return;

        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var drivers = [
            { label: 'Get Real', you: 4.4, org: 3.7, color: '#2f6f3e' },
            { label: 'Be Intentional', you: 4.2, org: 3.6, color: '#5b6fd6' },
            { label: 'Fill Buckets', you: 4.6, org: 3.8, color: '#8b5cf6' },
            { label: 'Foster Grit', you: 4.1, org: 3.5, color: '#f0803c' },
            { label: 'Drive Growth', you: 4.3, org: 3.6, color: '#2563eb' },
        ];
        var youSeries = months.map(function (_, i) { return 3.8 + Math.sin(i / 2) * 0.5 + 0.4; });
        var orgSeries = months.map(function (_, i) { return 3.2 + Math.sin(i / 3) * 0.35; });

        body.innerHTML =
            '<div class="xirr-card"><h3 style="margin-top:0">Behavioral Driver Trends</h3>' +
            '<div style="display:grid;grid-template-columns:1fr 260px;gap:1.5rem;align-items:start">' +
            lineChart([
                { label: 'Your Score', values: youSeries, color: '#2f6f3e' },
                { label: 'Organization Average', values: orgSeries, color: '#9ca3af', dashed: true },
            ], months, 1, 6) +
            '<table class="xirr-table" style="font-size:14px"><thead><tr><th></th><th>You</th><th>Org Avg</th></tr></thead><tbody>' +
            drivers.map(function (d) {
                return '<tr><td>' + esc(d.label) + '</td><td><strong>' + d.you + '</strong></td><td>' + d.org + '</td></tr>';
            }).join('') + '</tbody></table>' +
            '</div></div>' +

            '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Development Participation</h4>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:1.25rem;align-items:center">' +
            donut(78, '#2f6f3e') +
            '<div class="xirr-stat-list">' +
            '<div class="xirr-stat-row"><span class="xirr-dot green"></span>Completed<strong>124</strong></div>' +
            '<div class="xirr-stat-row"><span class="xirr-dot amber"></span>In Progress<strong>28</strong></div>' +
            '<div class="xirr-stat-row"><span class="xirr-dot red"></span>Not Started<strong>12</strong></div>' +
            '<div class="xirr-stat-row"><span class="xirr-dot" style="background:#d1d5db"></span>Not Assigned<strong>6</strong></div>' +
            '</div></div></div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Commitment Completion</h4>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:1.25rem;align-items:center">' +
            donut(82, '#2f6f3e') +
            '<div class="xirr-stat-list">' +
            '<div class="xirr-stat-row"><span class="xirr-dot green"></span>Completed<strong>9</strong></div>' +
            '<div class="xirr-stat-row"><span class="xirr-dot amber"></span>In Progress<strong>2</strong></div>' +
            '<div class="xirr-stat-row"><span class="xirr-dot red"></span>Overdue<strong>1</strong></div>' +
            '<div class="xirr-stat-row"><span class="xirr-dot" style="background:#d1d5db"></span>Not Started<strong>6</strong></div>' +
            '</div></div></div>' +
            '</div>' +

            '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1.4fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Growth Timeline</h4>' +
            '<div class="xirr-timeline">' +
            [['Q1 Focus','Process Improvement','Jan – Mar','3 Commitments'],
             ['Q2 Focus','Team Development','Apr – Jun','2 Commitments'],
             ['Q3 Focus','Operational Excellence','Jul – Sep','2 Commitments'],
             ['Q4 Focus','Strategic Impact','Oct – Dec','2 Commitments']].map(function (q) {
                return '<div class="xirr-timeline-item"><div class="xirr-timeline-dot"></div>' +
                    '<h5>' + q[0] + '</h5><p>' + q[1] + '<br>' + q[2] + '</p>' +
                    '<p style="margin-top:.35rem;font-weight:600;color:var(--navy)">' + q[3] + '</p></div>';
            }).join('') + '</div></div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Leadership Observations</h4>' +
            '<ul class="xirr-check-list">' +
            '<li>&#128172; Consistently drives operational excellence and accountability.</li>' +
            '<li>&#128172; Respected leader who elevates team performance.</li>' +
            '<li>&#128172; Strong communicator and problem solver.</li>' +
            '</ul></div>' +
            '</div>' +

            '<div class="xirr-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Strength Trends</h4>' +
            progressRow('Leadership', 4.6) + progressRow('Accountability', 4.4) + progressRow('Problem Solving', 4.3) +
            progressRow('Communication', 4.2) + progressRow('Adaptability', 4.1) + '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Development Trends</h4>' +
            progressRow('Strategic Thinking', 3.8) + progressRow('Delegation', 3.7) + progressRow('Change Leadership', 3.6) +
            progressRow('Coaching', 3.5) + progressRow('Influencing', 3.6) + '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Organizational Alignment</h4>' +
            '<ul class="xirr-check-list">' +
            '<li>&#128101; High alignment with team and organizational priorities.</li>' +
            '<li>&#128101; Actively contributes to QBR and ARP objectives.</li>' +
            '<li>&#128101; Demonstrates values and behaviors consistently.</li>' +
            '</ul></div>' +
            '</div>' +

            '<div class="xirr-card"><h4>Evidence Highlights</h4>' +
            '<div class="xirr-metric-grid">' +
            statCard('Activities Completed', 126, '&#8593; 18% vs last year') +
            statCard('Commitments Completed', '9 of 11', '&#8593; 10% vs last year') +
            statCard('Tools & Resources Used', 34, '&#8593; 21% vs last year') +
            statCard('1-on-1s Completed', 23, '&#8593; 18% vs last year') +
            '</div></div>';
    };
})();
JS;
}
