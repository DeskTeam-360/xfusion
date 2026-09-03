<?php
/**
 * Step 2 — Organizational Evidence™ (review dashboard).
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
        '<div id="xqbr-evidence-review-body"><div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading organizational evidence…</div></div>';
}
JS;
}

function xfqbr_wizard_evidence_review_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function pct(v) { return (v === null || v === undefined) ? '—' : v; }

    var DRIVER_LABELS = {
        get_real: 'Get Real™', fill_buckets: 'Fill Buckets™', be_intentional: 'Be Intentional™',
        foster_grit: 'Foster Grit™', drive_growth: 'Drive Growth™',
    };
    var CAPABILITY_LABELS = {
        alignment: 'Alignment', accountability: 'Accountability', communication: 'Communication',
        leadership: 'Leadership', execution: 'Execution',
    };
    var OBJECTIVE_STATUS_WEIGHT = { done: 100, in_progress: 50, at_risk: 25, not_started: 0 };

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
        var v = Math.max(0, Math.min(100, value || 0));
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
            '<p style="font-size:1.4rem;font-weight:800;color:#1e2a52;margin:.25rem 0 0">' + (value === null || value === undefined ? '—' : value) + '<span style="font-size:1rem;font-weight:400;color:#6b7280">/100</span></p>' +
            '<p style="font-size:.85rem;font-weight:600;color:' + zoneColor + ';margin:0">' + esc(zoneLabel) + '</p>' +
            '</div>';
    }

    function donut(score, max, label, color) {
        var v = (score === null || score === undefined) ? 0 : score;
        var s = Math.max(0, Math.min(100, Math.round((v / max) * 100)));
        return '<div class="xqbr-donut-wrap">' +
            '<div class="xqbr-donut-chart">' +
            '<svg class="xqbr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xqbr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xqbr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xqbr-donut-center"><div class="xqbr-donut-score">' + (score === null || score === undefined ? '—' : score) + '<span>/' + max + '</span></div></div>' +
            '</div>' +
            '<div class="xqbr-donut-label">' + esc(label) + '</div>' +
            '</div>';
    }

    function statCard(label, value, unit) {
        var display = (value === null || value === undefined) ? '—' : value;
        return '<div class="xqbr-metric-card"><div class="xqbr-metric-label">' + esc(label) + '</div>' +
            '<div class="xqbr-metric-value">' + display + (value === null || value === undefined ? '' : '<span class="unit">' + unit + '</span>') + '</div></div>';
    }

    function kpiRow(k) {
        var dot = k.status === 'on_track' ? 'green' : (k.status === 'off_track' ? 'red' : 'amber');
        return '<tr><td>' + esc(k.name) + '</td><td>' + esc(k.current) + '</td><td>' + esc(k.target) + '</td>' +
            '<td><span class="xqbr-dot ' + dot + '"></span> ' + esc((k.status || '').replace(/_/g, ' ')) + '</td>' +
            '<td>' + esc(k.trend || '—') + '</td></tr>';
    }

    function goalRow(name, statusPct, statusLabel) {
        return '<div class="xqbr-align-row xqbr-progress-row">' +
            '<span class="xqbr-align-label">' + esc(name) + '</span>' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + statusPct + '%"></div></div>' +
            '<strong class="xqbr-progress-pct">' + esc(statusLabel) + '</strong>' +
            '</div>';
    }

    function coverageRow(label, ratePct) {
        return '<div class="xqbr-align-row xqbr-progress-row">' +
            '<span class="xqbr-align-label">' + esc(label) + '</span>' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + ratePct + '%"></div></div>' +
            '<strong class="xqbr-progress-pct">' + ratePct + '%</strong>' +
            '</div>';
    }

    function capabilityRow(label, score) {
        var pct = score === null || score === undefined ? 0 : (score / 5 * 100);
        return '<div class="xqbr-align-row xqbr-progress-row">' +
            '<span class="xqbr-align-label">' + esc(label) + '</span>' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<strong class="xqbr-progress-pct">' + (score === null || score === undefined ? '—' : score.toFixed(1)) + '</strong>' +
            '</div>';
    }

    function render(evidence, kpis) {
        var body = document.getElementById('xqbr-evidence-review-body');
        if (!body) return;
        evidence = evidence || {};
        kpis = kpis || [];

        var readinessScore = evidence.overall_readiness_score;
        var readinessLabel = readinessScore === null || readinessScore === undefined ? 'No data'
            : (readinessScore >= 70 ? 'Strong' : (readinessScore >= 50 ? 'Moderate Strength' : 'Needs Attention'));
        var readinessColor = readinessScore === null || readinessScore === undefined ? '#9ca3af'
            : (readinessScore >= 70 ? '#16a34a' : (readinessScore >= 50 ? '#ca8a04' : '#dc2626'));

        var objectives = (evidence.qbr_objectives_progress || {}).objectives || [];
        var driverTrends = evidence.behavioral_driver_trends || [];
        var capTrends = evidence.cor_capability_trends || [];
        var readiness = evidence.readiness_indicators || {};

        body.innerHTML =
            '<div class="xqbr-card"><h3 style="margin-top:0">Organizational Evidence Summary</h3>' +
            '<div style="display:grid;grid-template-columns:auto 1fr 1fr;gap:1.5rem;align-items:center;margin-bottom:1.25rem">' +
            rpmGauge(readinessScore, readinessLabel, readinessColor) +
            '<div class="xqbr-metric-grid" style="grid-template-columns:1fr 1fr">' +
            statCard('1-on-1 Completion Rate', pct((evidence.one_on_one_completion || {}).rate), '%') +
            statCard('Activity Participation', pct((evidence.activity_participation || {}).rate), '%') +
            statCard('Assessment Completion', pct((evidence.assessment_completion || {}).rate), '%') +
            statCard('Tool Utilization Rate', pct((evidence.tool_utilization || {}).rate), '%') +
            '</div>' +
            '<div style="display:flex;gap:1.5rem;justify-content:center">' +
            donut((evidence.qbr_objectives_progress || {}).progress, 100, 'QBR Objectives Progress', '#2563eb') +
            donut((evidence.commitment_completion || {}).rate, 100, 'Commitment Completion', '#ca8a04') +
            '</div>' +
            '</div></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;align-items:start">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">KPI Summary (vs Target)</h3>' +
            (kpis.length
                ? ('<div class="xqbr-table-scroll"><table class="xqbr-table"><thead><tr><th>KPI</th><th>Current</th><th>Target</th><th>Status</th><th>Trend</th></tr></thead><tbody>' +
                    kpis.map(kpiRow).join('') + '</tbody></table></div>')
                : '<p class="xqbr-muted">No KPIs have been added for this quarter yet — add them in Step 1.</p>') +
            '</div>' +

            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">Goal Progress (QBR Objectives)</h3>' +
            '<div class="xqbr-align-list">' +
            (objectives.length
                ? objectives.map(function (o) {
                    return goalRow(o.title, OBJECTIVE_STATUS_WEIGHT[o.status] || 0, String(o.status || '').replace(/_/g, ' '));
                }).join('')
                : '<p class="xqbr-muted">No Annual Readiness Plan™ objectives are available for this organization yet.</p>') +
            '</div></div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:start">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">COR Capability Trends</h3>' +
            '<div class="xqbr-align-list">' +
            (capTrends.length
                ? capTrends.map(function (c) { return capabilityRow(CAPABILITY_LABELS[c.capability] || c.capability, c.score); }).join('')
                : '<p class="xqbr-muted">No evaluation data is available for this group yet.</p>') +
            '</div></div>' +

            '<div class="xqbr-card" style="margin-bottom:0"><h3 style="margin-top:0">Behavioral Driver Trends</h3>' +
            '<div class="xqbr-align-list">' +
            (driverTrends.length
                ? driverTrends.map(function (d) { return coverageRow(DRIVER_LABELS[d.driver] || d.driver, Math.round((d.coverage_rate || 0) * 100)); }).join('')
                : '<p class="xqbr-muted">No behavioral driver data is available yet.</p>') +
            '</div></div>' +
            '</div>' +

            '<div class="xqbr-card" style="margin-top:1rem"><h3 style="margin-top:0">Readiness Indicators</h3>' +
            '<div class="xqbr-metric-grid" style="grid-template-columns:repeat(3,1fr)">' +
            statCard('People Readiness', pct(readiness.people_readiness), '%') +
            statCard('Process Readiness', pct(readiness.process_readiness), '%') +
            statCard('System Readiness', pct(readiness.system_readiness), '%') +
            '</div></div>';
    }

    window.initEvidenceReviewStep = function () {
        var body = document.getElementById('xqbr-evidence-review-body');
        if (body) body.innerHTML = '<div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading organizational evidence…</div>';

        Promise.all([window.xqbrLoadEvidence(), window.xqbrLoadKpis()]).then(function (results) {
            render(results[0], results[1] || []);
        });
    };
})();
JS;
}
