<?php
/**
 * Step 2 — Organizational Learning Dashboard™.
 *
 * UI-only prototype: static dummy content matching the ARR mockup (filter
 * bar, future state progress donut, ARP objective progress bars, quarterly
 * readiness trends line chart, behavioral driver / COR capability trends,
 * leadership trends gauge, stat cards with sparklines, KPI trend lists,
 * historical comparison bar chart, trend highlights). No Laravel calls are
 * made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_dashboard_js(): string
{
    return <<<'JS'
dashboard: function () {
    return '<h2 class="xarr-section-title">Step 2. Organizational Learning Dashboard™</h2>' +
        '<p class="xarr-section-desc">Explore one full year of organizational evidence. These trends and metrics provide an objective view of your readiness progression and organizational performance.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>This dashboard presents evidence only. Interpretation and strategic analysis will occur in Step 3.</span></div>' +

        '<div class="xarr-filter-bar">' +
        '<div class="xarr-filter-field"><label>Time Frame</label><input class="xarr-input" value="Jan 1 – Dec 31, 2025" readonly style="width:14rem"></div>' +
        '<div class="xarr-filter-field"><label>Compare To</label><select class="xarr-input" style="width:11rem"><option>2024 (Previous Year)</option><option>2023</option></select></div>' +
        '<div class="xarr-filter-field"><label>Group View</label><select class="xarr-input" style="width:9rem"><option>All Groups</option></select></div>' +
        '<button type="button" class="xarr-btn xarr-btn-outline" style="margin-left:auto">&#8681; Export</button>' +
        '</div>' +

        '<div id="xarr-dashboard-body"></div>';
}
JS;
}

function xfarr_wizard_dashboard_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function donut(score, max, label, sub, color) {
        var s = Math.max(0, Math.min(100, Math.round((score / max) * 100)));
        return '<div class="xarr-donut-wrap">' +
            '<div class="xarr-donut-chart">' +
            '<svg class="xarr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xarr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xarr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xarr-donut-center"><div class="xarr-donut-score">' + score + '<span>' + (sub || '') + '</span></div></div>' +
            '</div><div class="xarr-donut-label">' + esc(label) + '</div></div>';
    }

    function lineChart(series, labels, yMin, yMax) {
        var w = 400, h = 140, padL = 26, padB = 18, padT = 6, padR = 8;
        var plotW = w - padL - padR, plotH = h - padT - padB;
        function x(i) { return padL + (i / (labels.length - 1)) * plotW; }
        function y(v) { return padT + plotH - ((v - yMin) / (yMax - yMin)) * plotH; }
        var grid = '';
        for (var g = 0; g <= 3; g++) {
            var gv = yMin + ((yMax - yMin) * g / 3);
            var gy = y(gv);
            grid += '<line x1="' + padL + '" y1="' + gy + '" x2="' + (w - padR) + '" y2="' + gy + '" stroke="#eef0f3" stroke-width="1"/>' +
                '<text x="0" y="' + (gy + 3) + '" font-size="8" fill="#9ca3af">' + Math.round(gv) + '</text>';
        }
        var xLabels = labels.map(function (m, i) {
            return '<text x="' + x(i) + '" y="' + (h - 2) + '" font-size="8" fill="#9ca3af" text-anchor="middle">' + m + '</text>';
        }).join('');
        var lines = series.map(function (s) {
            var pts = s.values.map(function (v, i) { return x(i) + ',' + y(v); }).join(' ');
            var dash = s.dashed ? ' stroke-dasharray="4 3"' : '';
            var dots = s.values.map(function (v, i) { return '<circle cx="' + x(i) + '" cy="' + y(v) + '" r="2.3" fill="' + s.color + '"/>'; }).join('');
            return '<polyline points="' + pts + '" fill="none" stroke="' + s.color + '" stroke-width="2"' + dash + '/>' + dots;
        }).join('');
        var legend = '<div class="xarr-row" style="gap:1.25rem;margin-top:.4rem">' + series.map(function (s) {
            return '<span style="display:inline-flex;align-items:center;gap:.4rem;font-size:12px;color:var(--muted)">' +
                '<span style="width:16px;height:2px;background:' + s.color + ';display:inline-block"></span>' + esc(s.label) + '</span>';
        }).join('') + '</div>';
        return '<svg viewBox="0 0 ' + w + ' ' + h + '" style="width:100%;height:auto">' + grid + xLabels + lines + '</svg>' + legend;
    }

    function sparkline(values, color) {
        var w = 90, h = 26;
        var min = Math.min.apply(null, values), max = Math.max.apply(null, values);
        var pts = values.map(function (v, i) {
            var x = (i / (values.length - 1)) * w;
            var y = h - ((v - min) / ((max - min) || 1)) * h;
            return x + ',' + y;
        }).join(' ');
        return '<svg viewBox="0 0 ' + w + ' ' + h + '" style="width:80px;height:22px"><polyline points="' + pts + '" fill="none" stroke="' + color + '" stroke-width="2"/></svg>';
    }

    function progressRow(label, value, color) {
        return '<div class="xarr-align-row xarr-progress-row" style="grid-template-columns:minmax(0,9rem) 1fr 3rem">' +
            '<div class="xarr-align-label">' + esc(label) + '</div>' +
            '<div class="xarr-progress-track"><div class="xarr-progress-fill" style="width:' + value + '%;background:' + color + '"></div></div>' +
            '<div class="xarr-progress-pct">' + value + '%</div>' +
            '</div>';
    }

    function barChart(items) {
        var max = Math.max.apply(null, items.map(function (i) { return i.value; }));
        return '<div class="xarr-bar-chart">' + items.map(function (i) {
            var h = Math.round((i.value / max) * 100);
            return '<div class="xarr-bar-col"><div class="xarr-bar-value">' + i.value + '</div>' +
                '<div class="xarr-bar" style="height:' + h + '%"></div>' +
                '<div class="xarr-bar-label">' + esc(i.label) + '</div></div>';
        }).join('') + '</div>';
    }

    function kpiRow(label, delta, up) {
        return '<div class="xarr-kpi-row"><span class="name">' + esc(label) + '</span>' +
            '<span class="delta ' + (up ? 'up' : 'down') + '">' + (up ? '&#8593;' : '&#8595;') + ' ' + delta + '</span></div>';
    }

    function statCard(label, value, sub, trendData) {
        return '<div class="xarr-metric-card"><p class="xarr-metric-label">' + esc(label) + '</p>' +
            '<div class="xarr-metric-value">' + value + '</div>' +
            '<div class="xarr-metric-trend up" style="margin-bottom:.4rem">' + esc(sub) + '</div>' +
            sparkline(trendData, '#2f6f3e') +
            '</div>';
    }

    window.initDashboardStep = function () {
        var body = document.getElementById('xarr-dashboard-body');
        if (!body) return;

        var quarters = ['Q1 2025', 'Q2 2025', 'Q3 2025', 'Q4 2025'];

        body.innerHTML =
            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Future State Progress</h4>' +
            donut(72, 100, 'On Track', '%', '#2f6f3e') +
            '<p class="xarr-metric-trend up" style="text-align:center">&#8593; 12% vs 2024</p>' +
            '<p style="text-align:center;color:var(--muted);font-size:13px">Progress toward 3-Year Future State</p>' +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>ARP Objective Progress</h4>' +
            progressRow('Financial Excellence', 78, '#16a34a') +
            progressRow('Operational Excellence', 65, '#ca8a04') +
            progressRow('People Excellence', 72, '#16a34a') +
            progressRow('Customer Excellence', 66, '#dc2626') +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Quarterly Readiness Trends</h4>' +
            lineChart([
                { label: '2025', values: [62, 68, 71, 76], color: '#2f6f3e' },
                { label: '2024', values: [58, 60, 62, 64], color: '#9ca3af', dashed: true },
            ], quarters, 0, 100) +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +
            '</div>' +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Behavioral Driver Trends</h4>' +
            lineChart([
                { label: 'Get Real', values: [3.6, 3.8, 4.0, 4.2], color: '#2f6f3e' },
                { label: 'Be Intentional', values: [3.4, 3.5, 3.7, 3.9], color: '#5b6fd6' },
                { label: 'Fill Buckets', values: [3.2, 3.3, 3.4, 3.5], color: '#8b5cf6' },
            ], quarters, 0, 6) +
            '<div class="xarr-row" style="gap:1rem;margin-top:.2rem">' +
            '<span style="font-size:12px;color:var(--muted)">&#8226; Foster Grit &#8226; Drive Growth</span></div>' +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>COR Capability Trends</h4>' +
            barChart([
                { label: 'Alignment', value: 3.4 }, { label: 'Accountability', value: 3.3 },
                { label: 'Communication', value: 2.9 }, { label: 'Leadership', value: 3.6 },
                { label: 'Execution', value: 3.1 },
            ]) +
            '<div class="xarr-row" style="gap:1rem;margin-top:.4rem;font-size:12px;color:var(--muted)">' +
            '<span><span style="display:inline-block;width:10px;height:10px;background:#2f6f3e;border-radius:2px;margin-right:.3rem"></span>2025</span>' +
            '<span><span style="display:inline-block;width:10px;height:10px;background:#d1d5db;border-radius:2px;margin-right:.3rem"></span>2024</span></div>' +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Leadership Trends</h4>' +
            donut(4.3, 6, 'Leadership Effectiveness Index', ' of 6', '#2f6f3e') +
            '<p class="xarr-metric-trend up" style="text-align:center">&#8593; 0.5 vs 2024</p>' +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +
            '</div>' +

            '<div class="xarr-metric-grid" style="margin-bottom:1rem">' +
            statCard('Commitment Completion', '68%', '&#8593; 9% vs 2024', [58, 60, 63, 68]) +
            statCard('Development Participation', '82%', '&#8593; 11% vs 2024', [70, 74, 78, 82]) +
            statCard('1-on-1 Alignment Capture™', '91%', '&#8593; 7% vs 2024', [82, 85, 88, 91]) +
            statCard('IRR Completion', '88%', '&#8593; 10% vs 2024', [76, 80, 84, 88]) +
            '</div>' +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Organizational KPI Trends <span style="float:right;font-size:11px;color:var(--muted);font-weight:400">vs 2024</span></h4>' +
            '<div class="xarr-kpi-list">' +
            kpiRow('Revenue Growth', '8.2%', true) + kpiRow('Gross Margin', '3.6%', true) +
            kpiRow('Operational Efficiency', '6.1%', true) + kpiRow('Customer Retention', '4.4%', true) +
            kpiRow('Safety (TRIR)', '12.5%', false) +
            '</div><a href="javascript:void(0)" class="xarr-link" style="display:block;margin-top:.5rem">View all KPI trends &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Operational KPI Trends <span style="float:right;font-size:11px;color:var(--muted);font-weight:400">vs 2024</span></h4>' +
            '<div class="xarr-kpi-list">' +
            kpiRow('On-Time Delivery', '7.4%', true) + kpiRow('Quality Index', '5.2%', true) +
            kpiRow('Cycle Time', '6.3%', false) + kpiRow('Cost per Unit', '4.8%', false) +
            '</div><a href="javascript:void(0)" class="xarr-link" style="display:block;margin-top:.5rem">View all KPI trends &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Historical Comparison</h4>' +
            barChart([
                { label: '2022', value: 62 }, { label: '2023', value: 67 },
                { label: '2024', value: 74 }, { label: '2025', value: 82 },
            ]) +
            '<a href="javascript:void(0)" class="xarr-link">View full history &rarr;</a></div>' +
            '</div>' +

            '<div class="xarr-card"><h4>Trend Highlights</h4>' +
            '<div class="xarr-highlight-grid">' +
            '<div class="xarr-highlight-item"><span class="icon">&#128200;</span><p>Readiness improved across all key areas compared to last year.</p></div>' +
            '<div class="xarr-highlight-item"><span class="icon">&#128101;</span><p>Leadership effectiveness continues to strengthen quarter over quarter.</p></div>' +
            '<div class="xarr-highlight-item"><span class="icon">&#9881;&#65039;</span><p>Operational efficiency gains are driving stronger KPI performance.</p></div>' +
            '<div class="xarr-highlight-item"><span class="icon">&#127891;</span><p>Development participation reached a new annual high.</p></div>' +
            '</div></div>';
    };
})();
JS;
}
