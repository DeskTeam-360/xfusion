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
        '<div class="xirr-card" id="xirr-evidence-resnapshot-card" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">' +
        '<div><h4 style="margin:0 0 .2rem">Re-collect latest data</h4><p class="xirr-muted" style="margin:0">Refresh this snapshot with the most up-to-date evidence from across the platform.</p></div>' +
        '<div style="text-align:right"><button type="button" class="xirr-btn xirr-btn-outline" id="xirr-resnapshot-btn">Re-snapshot Evidence</button>' +
        '<p class="xirr-muted" id="xirr-resnapshot-status" style="margin:.4rem 0 0"></p></div>' +
        '</div>' +
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

    var DRIVER_COLORS = {
        get_real: '#16a34a',
        be_intentional: '#7c3aed',
        fill_buckets: '#ea580c',
        foster_grit: '#f59e0b',
        drive_growth: '#0891b2',
    };
    var MONTH_LABELS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    // No month-by-month score history exists yet (scores reflect the latest
    // Gravity Forms submission only, not a time series) - each driver is
    // drawn as a flat line at its real current value rather than inventing
    // monthly ups/downs that were never actually measured.
    function driverTrendChart(drivers) {
        var withScores = drivers.filter(function (d) { return d.you != null; });
        if (!withScores.length) {
            return '<p class="xirr-muted">No behavioral driver scores yet.</p>';
        }

        var maxScale = 6;
        var w = 640, h = 220, padL = 28, padR = 12, padT = 10, padB = 24;
        var plotW = w - padL - padR, plotH = h - padT - padB;
        var xStep = plotW / (MONTH_LABELS.length - 1);
        var yFor = function (v) { return padT + plotH - (Math.max(0, Math.min(maxScale, v)) / maxScale) * plotH; };

        var gridLines = '';
        for (var g = 0; g <= maxScale; g += 2) {
            var gy = yFor(g);
            gridLines += '<line x1="' + padL + '" y1="' + gy + '" x2="' + (w - padR) + '" y2="' + gy + '" stroke="#e5e7eb" stroke-width="1"/>' +
                '<text x="2" y="' + (gy + 4) + '" font-size="10" fill="#9ca3af">' + g + '</text>';
        }

        var xLabels = MONTH_LABELS.map(function (m, i) {
            return '<text x="' + (padL + i * xStep) + '" y="' + (h - 6) + '" font-size="10" fill="#9ca3af" text-anchor="middle">' + m + '</text>';
        }).join('');

        var lines = withScores.map(function (d) {
            var y = yFor(Number(d.you));
            var pts = MONTH_LABELS.map(function (_, i) { return (padL + i * xStep) + ',' + y; }).join(' ');
            var color = DRIVER_COLORS[d.slug] || '#6b7280';
            return '<polyline points="' + pts + '" fill="none" stroke="' + color + '" stroke-width="2"/>';
        }).join('');

        var svg = '<svg viewBox="0 0 ' + w + ' ' + h + '" style="width:100%;height:auto">' +
            gridLines + xLabels + lines + '</svg>';

        var legend = '<div class="xirr-driver-legend">' + withScores.map(function (d) {
            var color = DRIVER_COLORS[d.slug] || '#6b7280';
            return '<div class="xirr-driver-legend-row">' +
                '<span class="xirr-driver-dot" style="background:' + color + '"></span>' +
                '<span class="xirr-driver-legend-label">' + esc(d.label) + '</span>' +
                '<span class="xirr-driver-legend-values"><strong>' + fmtNum(d.you) + '</strong><span class="xirr-muted"> / ' + fmtNum(d.org_avg) + ' org avg</span></span>' +
                '</div>';
        }).join('') + '</div>';

        return '<p class="xirr-muted" style="margin:0 0 .5rem;font-size:12px">Reflects your current score, held flat across the year — monthly history isn’t tracked yet.</p>' +
            '<div class="xirr-driver-trend"><div class="xirr-driver-trend-chart">' + svg + '</div>' + legend + '</div>';
    }

    function renderSnapshot(data) {
        if (!data) {
            return '<p class="xirr-muted">No evidence snapshot available. Complete Step 1 first.</p>';
        }

        var drivers = (data.behavioral_driver_trends && data.behavioral_driver_trends.drivers) ? data.behavioral_driver_trends.drivers : [];
        var driverTable = driverTrendChart(drivers);

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

    function bindResnapshotButton(body) {
        var btn = document.getElementById('xirr-resnapshot-btn');
        var statusEl = document.getElementById('xirr-resnapshot-status');
        var card = document.getElementById('xirr-evidence-resnapshot-card');
        if (!btn) return;

        if (window.XFIRR_WIZARD && window.XFIRR_WIZARD.canEdit === false) {
            if (card) card.style.display = 'none';
            return;
        }

        if (btn.dataset.wired) return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', function () {
            if (btn.dataset.busy === '1' || typeof window.xfirrGenerateEvidence !== 'function') return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Re-snapshotting…';
            if (statusEl) statusEl.textContent = 'Collecting the most up-to-date data. This may take a few seconds.';
            window.xfirrGenerateEvidence().then(function (res) {
                btn.disabled = false;
                btn.dataset.busy = '';
                btn.textContent = 'Re-snapshot Evidence';
                if (!res || !res.success) {
                    if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to re-snapshot evidence.';
                    return;
                }
                body.innerHTML = renderSnapshot(res.data);
                if (statusEl) statusEl.textContent = '✓ Evidence re-snapshotted.';
            }).catch(function () {
                btn.disabled = false;
                btn.dataset.busy = '';
                btn.textContent = 'Re-snapshot Evidence';
                if (statusEl) statusEl.textContent = 'Failed to re-snapshot evidence — network error.';
            });
        });
    }

    window.initEvidenceReviewStep = function () {
        var body = document.getElementById('xirr-evidence-review-body');
        if (!body) return;
        body.innerHTML = '<p class="xirr-muted">Loading evidence…</p>';
        bindResnapshotButton(body);

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
