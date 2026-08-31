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

    // Multi-segment donut: segments = [{ value, color }]. Center shows the
    // rate (first segment's share of the total) unless centerOverride is given.
    function multiDonut(segments, centerLabel, centerOverride) {
        var total = segments.reduce(function (sum, s) { return sum + (Number(s.value) || 0); }, 0);
        var arcs = '';
        if (total > 0) {
            var cursor = 0;
            segments.forEach(function (s) {
                var v = Number(s.value) || 0;
                if (v <= 0) return;
                var len = (v / total) * 100;
                arcs += '<circle class="xirr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + s.color +
                    '" stroke-dasharray="' + len + ' ' + (100 - len) + '" stroke-dashoffset="' + (-cursor) + '" style="stroke-linecap:butt"></circle>';
                cursor += len;
            });
        }
        var centerPct = centerOverride != null ? centerOverride : (total > 0 ? Math.round((Number(segments[0].value) || 0) / total * 100) : 0);
        return '<div class="xirr-donut-wrap">' +
            '<div class="xirr-donut-chart">' +
            '<svg class="xirr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xirr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            arcs +
            '</svg>' +
            '<div class="xirr-donut-center"><div class="xirr-donut-score">' + centerPct + '<span>%</span></div></div>' +
            '</div>' +
            (centerLabel ? '<div class="xirr-donut-label">' + esc(centerLabel) + '</div>' : '') +
            '</div>';
    }

    function progressRow(label, value, max, fillClass) {
        max = max || 5;
        if (value == null) return '';
        var pct = Math.round((Number(value) / max) * 100);
        var fill = 'xirr-progress-fill' + (fillClass ? ' ' + fillClass : '');
        return '<div class="xirr-align-row xirr-progress-row">' +
            '<div class="xirr-align-label">' + esc(label) + '</div>' +
            '<div class="xirr-progress-track"><div class="' + fill + '" style="width:' + pct + '%"></div></div>' +
            '<div class="xirr-progress-pct">' + Number(value).toFixed(1) + '</div>' +
            '</div>';
    }

    function trendItems(source, fallback) {
        var list = Array.isArray(source) ? source : (source && Array.isArray(source.items) ? source.items : []);
        if (!list.length) return fallback.slice();
        return list.map(function (s) {
            if (typeof s === 'string') return { label: s, score: null };
            return { label: s.label || s.title || '', score: s.score != null ? s.score : s.value };
        }).filter(function (s) { return s.label; });
    }

    function alignmentItems(source, fallback) {
        var list = Array.isArray(source) ? source : (source && Array.isArray(source.items) ? source.items : []);
        if (!list.length) return fallback.slice();
        return list.map(function (s) {
            return typeof s === 'string' ? s : (s.text || s.label || s.title || '');
        }).filter(Boolean);
    }

    function trendCard(title, bodyHtml, linkLabel) {
        return '<div class="xirr-card xirr-trend-card">' +
            '<h4>' + esc(title) + '</h4>' +
            bodyHtml +
            '<span class="xirr-link xirr-trend-link">' + esc(linkLabel) + ' →</span>' +
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

    var DRIVER_ICONS = {
        get_real: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Get-Real.svg',
        be_intentional: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Be-Intentional.svg',
        fill_buckets: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Fill-Buckets.svg',
        foster_grit: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Foster-Grit.svg',
        drive_growth: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Drive-Growth.svg',
    };
    var DRIVER_COLORS = {
        get_real: '#16a34a',
        be_intentional: '#2563eb',
        fill_buckets: '#7c3aed',
        foster_grit: '#ea580c',
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
            var icon = DRIVER_ICONS[d.slug];
            var color = DRIVER_COLORS[d.slug] || '#6b7280';
            var mark = icon
                ? '<img class="xirr-driver-icon" src="' + icon + '" alt="' + esc(d.label) + '">'
                : '<span class="xirr-driver-dot" style="background:' + color + '"></span>';
            return '<div class="xirr-driver-legend-row">' +
                mark +
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
        var DEFAULT_DEV_TRENDS = [
            { label: 'Strategic Thinking', score: 3.8 },
            { label: 'Delegation', score: 3.7 },
            { label: 'Change Leadership', score: 3.6 },
            { label: 'Coaching', score: 3.5 },
            { label: 'Influencing', score: 3.6 },
        ];
        var DEFAULT_ALIGNMENT = [
            'High alignment with team and organizational priorities',
            'Actively contributes to QBR and ARP objectives',
            'Demonstrates values and behaviors consistently',
        ];
        var ALIGN_ICONS = [
            'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/ORGANIZATIONAL-ALIGNMENT-1.svg',
            'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/ORGANIZATIONAL-ALIGNMENT-2.svg',
            'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/ORGANIZATIONAL-ALIGNMENT-1.svg',
        ];
        var developmentTrends = trendItems(data.development_trends, DEFAULT_DEV_TRENDS);
        var alignmentPoints = alignmentItems(data.organizational_alignment, DEFAULT_ALIGNMENT);

        var participationRows = [
            { dot: 'green', label: 'Active', value: participation.active || 0 },
            { dot: 'gray', label: 'No activity yet', value: participation.no_activity || 0 },
        ];
        var commitmentRows = [
            { dot: 'green', label: 'Completed', value: commitments.completed || 0 },
            { dot: 'blue', label: 'In Progress', value: commitments.in_progress || 0 },
            { dot: 'amber', label: 'Overdue', value: commitments.overdue || 0 },
            { dot: 'gray', label: 'Not Started', value: commitments.not_started || 0 },
        ];

        var html = '';

        html += '<div class="xirr-card"><h3 style="margin-top:0">Behavioral Driver Trends</h3>' + driverTable + '</div>';

        html += '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Development Participation</h4>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:1.25rem;align-items:center">' +
            multiDonut([
                { value: participation.active || 0, color: '#16a34a' },
                { value: participation.no_activity || 0, color: '#e5e7eb' },
            ], 'Participation Rate', participation.rate) + statRows('', participationRows) +
            '</div></div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Commitment Completion</h4>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:1.25rem;align-items:center">' +
            multiDonut([
                { value: commitments.completed || 0, color: '#16a34a' },
                { value: commitments.in_progress || 0, color: '#2563eb' },
                { value: commitments.overdue || 0, color: '#ca8a04' },
                { value: commitments.not_started || 0, color: '#9ca3af' },
            ], 'Completion Rate', commitments.rate) + statRows('', commitmentRows) +
            '</div></div></div>';

        if (timeline.length || leaderObs.length) {
            html += '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1.5fr 1fr;gap:1rem;margin-bottom:1rem">';

            if (timeline.length) {
                var TIMELINE_ICONS = [
                    'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Q1-FOCUS.svg',
                    'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Q2-FOCUS.svg',
                    'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Q3-FOCUS.svg',
                    'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Q4-FOCUS.svg',
                ];
                html += '<div class="xirr-card" style="margin-bottom:0"><h4>Growth Timeline</h4><div class="xirr-timeline"><div class="xirr-timeline-track"></div>' +
                    timeline.map(function (q, i) {
                        var qMatch = String(q.quarter || '').match(/Q([1-4])/i);
                        var idx = qMatch ? (parseInt(qMatch[1], 10) - 1) : (i % TIMELINE_ICONS.length);
                        return '<div class="xirr-timeline-item">' +
                            '<div class="xirr-timeline-icon"><img src="' + TIMELINE_ICONS[idx] + '" alt="' + esc(q.quarter || 'Q' + (idx + 1)) + ' Focus icon"></div>' +
                            '<h5>' + esc(q.quarter) + ' Focus</h5>' +
                            '<p>' + esc(q.focus || '—') + '<br>' + esc(q.period) + '</p>' +
                            '<span class="xirr-timeline-badge">' + esc(String(q.commitment_count || 0)) + ' Commitments</span></div>';
                    }).join('') + '</div></div>';
            }

            if (leaderObs.length) {
                html += '<div class="xirr-card" style="margin-bottom:0"><h4>Leadership Observations</h4><div class="xirr-leader-obs-list">' +
                    leaderObs.map(function (item) {
                        return '<div class="xirr-leader-obs-row"><span class="xirr-leader-obs-icon"><img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/Leadership-Observations.svg" alt=""></span><p>' + esc(item) + '</p></div>';
                    }).join('') + '</div></div>';
            }

            html += '</div>';
        }

        var strengthBody = selfScores.length
            ? '<div class="xirr-align-list">' + selfScores.map(function (s) { return progressRow(s.label, s.score, 5); }).join('') + '</div>'
            : '<p class="xirr-muted" style="margin:0">No self-assessment scores yet.</p>';
        var developmentBody = '<div class="xirr-align-list">' + developmentTrends.map(function (s) {
            return progressRow(s.label, s.score, 5, 'xirr-progress-fill-blue');
        }).join('') + '</div>';
        var alignmentBody = '<div class="xirr-align-points">' + alignmentPoints.map(function (text, i) {
            return '<div class="xirr-align-point"><span class="xirr-align-point-icon" aria-hidden="true">' +
                '<img src="' + ALIGN_ICONS[i % ALIGN_ICONS.length] + '" alt="">' +
                '</span><p>' + esc(text) + '</p></div>';
        }).join('') + '</div>';

        html += '<div class="xirr-trend-grid">' +
            trendCard('Strength Trends', strengthBody, 'View strength details') +
            trendCard('Development Trends', developmentBody, 'View development details') +
            trendCard('Organizational Alignment', alignmentBody, 'View alignment details') +
            '</div>';

        html += '<div class="xirr-card"><h4>Evidence Highlights</h4><div class="xirr-metric-grid">' +
            statCard('Activities Completed', highlights.activities_completed || 0, highlights.activities_completed_trend) +
            statCard('Commitments Completed', highlights.commitments_completed || '0', highlights.commitments_completed_trend) +
            statCard('Tools & Resources Used', highlights.tools_used || 0, highlights.tools_used_trend) +
            statCard('1-on-1s Completed', highlights.one_on_ones_completed || 0, highlights.one_on_ones_completed_trend) +
            '</div></div>';

        var pending = [];
        if (data.behavioral_driver_monthly == null) pending.push('Monthly driver trend chart');
        if (data.reflection_themes == null) pending.push('Reflection Themes');
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
