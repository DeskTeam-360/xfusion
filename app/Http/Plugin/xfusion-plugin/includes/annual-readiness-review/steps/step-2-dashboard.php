<?php
/**
 * Step 2 — Organizational Learning Dashboard™.
 *
 * Real: loads via Laravel (ArrEvidenceService::buildDashboard()). Only
 * what's genuinely computable from dated records is shown as real data:
 * COR Capability Trends, Leadership Trends, and the 4 stat cards (all
 * this-year vs last-year, via an as-of-date cutoff on real scoring/
 * commitment/participation records) plus the real KPI name/status lists.
 *
 * Everything the original mockup showed that has no tracked history
 * anywhere in FUSION (Future State Progress, ARP Objective Progress by
 * named category, Quarterly Readiness Trends, Behavioral Driver quarterly
 * trend, stat-card sparklines, KPI % deltas, a 2022-2025 historical bar
 * chart, and narrative "Trend Highlights") is left in place in the layout
 * but rendered as an explicit "Not available yet" note instead of being
 * removed or fabricated — per explicit product decision.
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
        '<div class="xarr-filter-field"><label>Time Frame</label><input class="xarr-input" id="xarr-dash-timeframe" value="" readonly style="width:14rem"></div>' +
        '<div class="xarr-filter-field"><label>Compare To</label><input class="xarr-input" id="xarr-dash-compare" value="" readonly style="width:11rem"></div>' +
        '</div>' +

        '<div id="xarr-dashboard-body"><p class="xarr-muted">Loading dashboard…</p></div>';
}
JS;
}

function xfarr_wizard_dashboard_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function notAvailable(note) {
        return '<div class="xarr-banner" style="background:#f8f7f4;border-color:#e7e4dc">' +
            '<span class="xarr-evidence-status soon" style="font-style:italic">Not available yet.</span> ' +
            '<span class="xarr-muted" style="font-size:13px">' + esc(note) + '</span></div>';
    }

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

    function barChart(items) {
        var max = Math.max.apply(null, items.map(function (i) { return i.value; })) || 1;
        return '<div class="xarr-bar-chart">' + items.map(function (i) {
            var h = Math.round((i.value / max) * 100);
            return '<div class="xarr-bar-col"><div class="xarr-bar-value">' + i.value + '</div>' +
                '<div class="xarr-bar" style="height:' + h + '%;background:' + (i.color || '#2f6f3e') + '"></div>' +
                '<div class="xarr-bar-label">' + esc(i.label) + '</div></div>';
        }).join('') + '</div>';
    }

    function kpiRow(label, status) {
        var cls = status === 'on_track' ? 'up' : (status === 'off_track' ? 'down' : '');
        var text = status ? status.replace('_', ' ') : '—';
        return '<div class="xarr-kpi-row"><span class="name">' + esc(label) + '</span>' +
            '<span class="delta ' + cls + '">' + esc(text) + '</span></div>';
    }

    function statCard(label, stat) {
        var hasCurrent = stat && stat.current_rate !== null && stat.current_rate !== undefined;
        var value = hasCurrent ? stat.current_rate + '%' : '—';
        var trend;
        if (!hasCurrent) {
            trend = '<span class="xarr-muted" style="font-size:12px">No data yet</span>';
        } else if (stat.delta === null || stat.delta === undefined) {
            trend = '<span class="xarr-muted" style="font-size:12px">No prior-year data to compare</span>';
        } else {
            var up = stat.delta >= 0;
            trend = '<span class="xarr-metric-trend ' + (up ? 'up' : 'down') + '">' + (up ? '&#8593;' : '&#8595;') + ' ' + Math.abs(stat.delta) + 'pt vs last year</span>';
        }
        return '<div class="xarr-metric-card"><p class="xarr-metric-label">' + esc(label) + '</p>' +
            '<div class="xarr-metric-value">' + value + '</div>' +
            '<div style="margin-top:.3rem">' + trend + '</div>' +
            '</div>';
    }

    window.initDashboardStep = function () {
        var body = document.getElementById('xarr-dashboard-body');
        if (!body) return;

        if (typeof window.xfarrLoadDashboard !== 'function') {
            body.innerHTML = '<p class="xarr-muted">Dashboard service unavailable.</p>';
            return;
        }

        window.xfarrLoadDashboard().then(function (data) {
            if (!data) {
                body.innerHTML = '<p class="xarr-muted">No dashboard data yet.</p>';
                return;
            }
            renderDashboard(body, data);
        });
    };

    function renderDashboard(body, data) {
        var period = data.review_period || {};
        var tf = document.getElementById('xarr-dash-timeframe');
        var cmp = document.getElementById('xarr-dash-compare');
        if (tf) tf.value = 'Jan 1 – Dec 31, ' + (period.year || '');
        if (cmp) cmp.value = (period.prior_year || '') + ' (Previous Year)';

        var unavailable = data.unavailable || {};
        var corTrend = data.cor_capability_trend || [];
        var leadership = data.leadership_trend || {};
        var stats = data.stat_cards || {};

        body.innerHTML =
            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Future State Progress</h4>' +
            notAvailable(unavailable.future_state_progress) + '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>ARP Objective Progress</h4>' +
            notAvailable(unavailable.arp_objective_progress_by_category) + '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Quarterly Readiness Trends</h4>' +
            notAvailable(unavailable.quarterly_readiness_trends) + '</div>' +
            '</div>' +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Behavioral Driver Trends</h4>' +
            notAvailable(unavailable.behavioral_driver_quarterly_trend) + '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>COR Capability Trends</h4>' +
            (corTrend.length && corTrend.some(function (c) { return c.current !== null; }) ?
                barChart(corTrend.map(function (c) { return { label: c.label, value: c.current !== null ? c.current : 0, color: '#2f6f3e' }; })) +
                '<div class="xarr-row" style="gap:1rem;margin-top:.4rem;font-size:12px;color:var(--muted)">' +
                '<span><span style="display:inline-block;width:10px;height:10px;background:#2f6f3e;border-radius:2px;margin-right:.3rem"></span>' + esc(period.year) + '</span>' +
                '<span>vs ' + esc(period.prior_year) + ': ' + corTrend.map(function (c) {
                    return esc(c.label) + ' ' + (c.prior !== null ? c.prior : '—');
                }).join(', ') + '</span></div>'
                : notAvailable('No Gravity Forms scoring submissions found for these COR capabilities yet.')) +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Leadership Trends</h4>' +
            (leadership.current !== null && leadership.current !== undefined ?
                donut(leadership.current, leadership.scale_max || 5, 'Leadership Effectiveness Index', ' of ' + (leadership.scale_max || 5), '#2f6f3e') +
                '<p class="xarr-metric-trend ' + ((leadership.prior !== null && leadership.current >= leadership.prior) ? 'up' : 'down') + '" style="text-align:center">' +
                (leadership.prior !== null && leadership.prior !== undefined ?
                    ((leadership.current >= leadership.prior ? '&#8593; ' : '&#8595; ') + Math.abs(Math.round((leadership.current - leadership.prior) * 100) / 100) + ' vs ' + period.prior_year)
                    : 'No prior-year data to compare') +
                '</p>'
                : notAvailable('No Leadership scoring submissions found yet.')) +
            '</div>' +
            '</div>' +

            '<div class="xarr-metric-grid" style="margin-bottom:1rem">' +
            statCard('Commitment Completion', stats.commitment_completion) +
            statCard('Development Participation', stats.development_participation) +
            statCard('1-on-1 Alignment Capture™', stats.one_on_one_alignment) +
            statCard('IRR Completion', stats.irr_completion) +
            '</div>' +
            notAvailable(unavailable.stat_card_sparklines) +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem;margin-top:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Organizational KPI Trends</h4>' +
            ((data.organizational_kpis && data.organizational_kpis.items && data.organizational_kpis.items.length) ?
                '<div class="xarr-kpi-list">' + data.organizational_kpis.items.map(function (i) { return kpiRow(i.title, i.status); }).join('') + '</div>'
                : '<p class="xarr-muted">No organizational KPIs recorded for this year\'s ARP(s) yet.</p>') +
            notAvailable(unavailable.kpi_percent_deltas) + '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Operational KPI Trends</h4>' +
            (data.operational_kpis && data.operational_kpis.count ?
                '<div class="xarr-kpi-list">' +
                kpiRow('On Track', 'on_track').replace(/<span class="delta[^>]*>[^<]*<\/span>/, '<span class="delta up">' + data.operational_kpis.on_track + '</span>') +
                kpiRow('At Risk', 'at_risk').replace(/<span class="delta[^>]*>[^<]*<\/span>/, '<span class="delta">' + data.operational_kpis.at_risk + '</span>') +
                kpiRow('Off Track', 'off_track').replace(/<span class="delta[^>]*>[^<]*<\/span>/, '<span class="delta down">' + data.operational_kpis.off_track + '</span>') +
                '</div>'
                : '<p class="xarr-muted">No operational KPIs recorded for this year\'s QBR(s) yet.</p>') +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Historical Comparison</h4>' +
            notAvailable(unavailable.historical_comparison_2022_2025) + '</div>' +
            '</div>' +

            '<div class="xarr-card"><h4>Trend Highlights</h4>' +
            notAvailable(unavailable.trend_highlights) + '</div>';
    }
})();
JS;
}
