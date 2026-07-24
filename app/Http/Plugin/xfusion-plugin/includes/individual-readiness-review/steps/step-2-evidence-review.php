<?php
/**
 * Step 2 — Individual Evidence™ (review dashboard).
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
        '<div id="xirr-evidence-review-body"><p class="xirr-muted">Loading evidence…</p></div>';
}
JS;
}

function xfirr_wizard_evidence_review_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function fmtNum(v, fallback) { return v == null || v === '' ? (fallback || '—') : v; }

    function donut(pct, color, label) {
        var s = Math.max(0, Math.min(100, Math.round(Number(pct) || 0)));
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

    function progressRow(label, value, max) {
        max = max || 5;
        if (value == null) return '';
        var pct = Math.round((Number(value) / max) * 100);
        return '<div class="xirr-align-row xirr-progress-row">' +
            '<div class="xirr-align-label">' + esc(label) + '</div>' +
            '<div class="xirr-progress-track"><div class="xirr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="xirr-progress-pct">' + Number(value).toFixed(1) + '</div>' +
            '</div>';
    }

    function statCard(label, value, trend) {
        var trendHtml = '';
        if (trend && trend.percent != null) {
            trendHtml = '<div class="xirr-metric-trend up">' +
                (trend.direction === 'up' ? '&#8593;' : '&#8595;') + ' ' + trend.percent + '% vs last year</div>';
        }
        return '<div class="xirr-metric-card"><p class="xirr-metric-label">' + esc(label) + '</p>' +
            '<div class="xirr-metric-value">' + esc(String(value)) + '</div>' + trendHtml + '</div>';
    }

    function statRows(title, rows) {
        if (!rows.length) return '';
        return '<div class="xirr-stat-list">' + rows.map(function (r) {
            return '<div class="xirr-stat-row"><span class="xirr-dot ' + esc(r.dot) + '"></span>' +
                esc(r.label) + '<strong>' + esc(String(r.value)) + '</strong></div>';
        }).join('') + '</div>';
    }

    function renderSnapshot(data) {
        if (!data) {
            return '<p class="xirr-muted">No evidence snapshot available. Complete Step 1 first.</p>';
        }

        var drivers = (data.behavioral_driver_trends && data.behavioral_driver_trends.drivers) ? data.behavioral_driver_trends.drivers : [];
        var driverTable = drivers.length ? (
            '<table class="xirr-table" style="font-size:14px"><thead><tr><th></th><th>You</th><th>Org Avg</th></tr></thead><tbody>' +
            drivers.map(function (d) {
                return '<tr><td>' + esc(d.label) + '</td><td><strong>' + fmtNum(d.you) + '</strong></td><td>' + fmtNum(d.org_avg) + '</td></tr>';
            }).join('') + '</tbody></table>'
        ) : '<p class="xirr-muted">No behavioral driver scores yet.</p>';

        var participation = data.development_participation || {};
        var commitments = data.commitment_completion || {};
        var highlights = data.evidence_highlights || {};
        var selfScores = (data.self_assessment_scores || []).filter(function (s) { return s.score != null; });
        var leaderObs = data.leader_observations || [];
        var timeline = data.growth_timeline || [];

        var participationRows = [
            { dot: 'green', label: 'Submissions', value: participation.total_submissions || 0 },
            { dot: 'amber', label: 'Programs active', value: (participation.programs_with_data || 0) + ' / ' + (participation.programs_total || 3) },
        ];
        var commitmentRows = [
            { dot: 'green', label: 'Completed', value: commitments.completed || 0 },
            { dot: 'amber', label: 'In Progress', value: commitments.in_progress || 0 },
            { dot: 'red', label: 'Overdue', value: commitments.overdue || 0 },
            { dot: 'gray', label: 'Not Started', value: commitments.not_started || 0 },
        ];

        var html = '';

        html += '<div class="xirr-card"><h3 style="margin-top:0">Behavioral Driver Trends</h3>' + driverTable + '</div>';

        html += '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Development Participation</h4>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:1.25rem;align-items:center">' +
            donut(participation.rate, '#2f6f3e') + statRows('', participationRows) +
            '</div></div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Commitment Completion</h4>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:1.25rem;align-items:center">' +
            donut(commitments.rate, '#2f6f3e') + statRows('', commitmentRows) +
            '</div></div></div>';

        if (timeline.length) {
            html += '<div class="xirr-card"><h4>Growth Timeline</h4><div class="xirr-timeline">' +
                timeline.map(function (q) {
                    return '<div class="xirr-timeline-item"><div class="xirr-timeline-dot"></div>' +
                        '<h5>' + esc(q.quarter) + ' Focus</h5>' +
                        '<p>' + esc(q.focus || '—') + '<br>' + esc(q.period) + '</p>' +
                        '<p style="margin-top:.35rem;font-weight:600;color:var(--navy)">' + esc(String(q.commitment_count || 0)) + ' Commitments</p></div>';
                }).join('') + '</div></div>';
        }

        if (leaderObs.length) {
            html += '<div class="xirr-card"><h4>Leadership Observations</h4><ul class="xirr-check-list">' +
                leaderObs.map(function (item) {
                    return '<li>&#128172; ' + esc(item) + '</li>';
                }).join('') + '</ul></div>';
        }

        if (selfScores.length) {
            html += '<div class="xirr-card"><h4>Strength Trends (Self-Assessment)</h4>' +
                selfScores.map(function (s) { return progressRow(s.label, s.score, 5); }).join('') +
                '</div>';
        }

        html += '<div class="xirr-card"><h4>Evidence Highlights</h4><div class="xirr-metric-grid">' +
            statCard('Activities Completed', highlights.activities_completed || 0, highlights.activities_completed_trend) +
            statCard('Commitments Completed', highlights.commitments_completed || '0', highlights.commitments_completed_trend) +
            statCard('Tools & Resources Used', highlights.tools_used || 0, highlights.tools_used_trend) +
            statCard('1-on-1s Completed', highlights.one_on_ones_completed || 0, highlights.one_on_ones_completed_trend) +
            '</div></div>';

        var pending = [];
        if (data.behavioral_driver_monthly == null) pending.push('Monthly driver trend chart');
        if (data.development_trends == null) pending.push('Development Trends (Strategic Thinking, Delegation, …)');
        if (data.reflection_themes == null) pending.push('Reflection Themes');
        if (data.organizational_alignment == null) pending.push('Organizational Alignment narrative');
        if (data.qbr_arp_priorities == null) pending.push('QBR & ARP priority linkage');
        if (pending.length) {
            html += '<div class="xirr-banner" style="margin-top:1rem">&#8505;&#65039; <span><strong>Coming soon:</strong> ' + esc(pending.join('; ')) + '</span></div>';
        }

        return html;
    }

    window.initEvidenceReviewStep = function () {
        var body = document.getElementById('xirr-evidence-review-body');
        if (!body) return;
        body.innerHTML = '<p class="xirr-muted">Loading evidence…</p>';

        if (typeof window.xfirrLoadEvidence !== 'function') {
            body.innerHTML = '<p class="xirr-muted">Evidence service unavailable.</p>';
            return;
        }

        window.xfirrLoadEvidence().then(function (data) {
            body.innerHTML = renderSnapshot(data);
        }).catch(function () {
            body.innerHTML = '<p class="xirr-muted">Unable to load evidence.</p>';
        });
    };
})();
JS;
}
