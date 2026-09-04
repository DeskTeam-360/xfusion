<?php
/**
 * Step 1 — Generate Organizational Evidence™.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_evidence_js(): string
{
    return <<<'JS'
evidence: function () {
    return '<h2 class="xqbr-section-title">Step 1. Generate Organizational Evidence™</h2>' +
        '<p class="xqbr-section-desc">FUSION automatically gathers evidence from across the platform for the current review period. This evidence provides the foundation for leadership analysis and decision-making.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>Evidence is system generated and read-only. No manual entry is required. The data below represents the current review period.</span></div>' +
        '<div class="xqbr-card"><h3 style="margin-top:0">Evidence Sources</h3>' +
        '<p class="xqbr-muted" style="margin-top:-.5rem">The platform is automatically pulling evidence from the following sources. Click any source to view its details.</p>' +
        '<div class="xqbr-evidence-list" id="xqbr-evidence-list"></div>' +
        '</div>' +
        '<div class="xqbr-card" id="xqbr-evidence-generate-card">' +
        '<button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-generate-evidence-btn">Generate Evidence</button>' +
        '<p class="xqbr-muted" id="xqbr-evidence-status" style="margin-top:.6rem"></p>' +
        '</div>';
}
JS;
}

function xfqbr_wizard_evidence_init_js(): string
{
    return <<<'JS'
(function () {
    var LABELS = {
        arp_objectives: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Annual-Readiness-Plan™-Objectives.svg" alt="Annual Readiness Plan Objectives icon">', 'Annual Readiness Plan™ Objectives', 'Progress and alignment to ARP objectives for the year.'],
        previous_commitments: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Previous-Quarterly-Commitments.svg" alt="Previous Quarterly Commitments icon">', 'Previous Quarterly Commitments', 'Completion status and historical commitment performance.'],
        individual_insight_trends: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Individual-Insight-Trends.svg" alt="Individual Insight Trends icon">', 'Individual Insight Trends', 'Aggregated themes and sentiment from Individual Insights™.'],
        one_on_one_summaries: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/1-on-1-Alignment-Capture™-Summaries.svg" alt="1-on-1 Alignment Capture Summaries icon">', '1-on-1 Alignment Capture™ Summaries', 'Alignment trends and key themes from 1-on-1 conversations.'],
        activity_participation: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Activity-Participation.svg" alt="Activity Participation icon">', 'Activity Participation', 'Participation rates and engagement with learning activities.'],
        assessment_trends: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Assessment-Trends.svg" alt="Assessment Trends icon">', 'Assessment Trends', 'Assessment score trends and development benchmarks.'],
        tool_usage: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Tool-Usage.svg" alt="Tool Usage icon">', 'Tool Usage', 'Utilization of development tools and resources.'],
        ai_insight_themes: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/AI-Insight-Themes.svg" alt="AI Insight Themes icon">', 'AI Insight Themes', 'AI-identified themes and organizational patterns.'],
        organizational_kpis: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Organizational-KPIs.svg" alt="Organizational KPIs icon">', 'Organizational KPIs', 'Related Organizational KPI(s) from the latest Annual Readiness Plan™ Step 4.'],
        operational_metrics: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Operational-Metrics.svg" alt="Operational Metrics icon">', 'Operational Metrics', 'Operational performance and efficiency metrics.'],
        historical_qbr_data: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Historical-QBR-Data.svg" alt="Historical QBR Data icon">', 'Historical QBR Data', 'Trends and learnings from previous quarterly reviews.'],
        group: ['👥', 'Group', 'Confirms this QBR is correctly scoped to your company group.'],
    };
    var ORDER = ['arp_objectives', 'previous_commitments', 'individual_insight_trends', 'one_on_one_summaries',
        'activity_participation', 'assessment_trends', 'tool_usage', 'ai_insight_themes',
        'organizational_kpis', 'operational_metrics', 'historical_qbr_data'];

    var DRIVER_LABELS = {
        get_real: 'Get Real™', fill_buckets: 'Fill Buckets™', be_intentional: 'Be Intentional™',
        foster_grit: 'Foster Grit™', drive_growth: 'Drive Growth™',
    };
    var CAPABILITY_LABELS = {
        alignment: 'Alignment', accountability: 'Accountability', communication: 'Communication',
        leadership: 'Leadership', execution: 'Execution',
    };

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function noData(msg) {
        return '<p class="xqbr-evidence-empty">' + esc(msg) + '</p>';
    }

    function pct(v) {
        return (v === null || v === undefined) ? '—' : (v + '%');
    }

    function formatDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return '—';
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderPanel(key, snap) {
        snap = snap || {};
        if (key === 'arp_objectives') {
            var op = snap.qbr_objectives_progress || {};
            if (!op.objective_count) return noData('No Annual Readiness Plan™ strategic priorities are available for this organization yet.');
            var oItems = (op.objectives || []).map(function (o) {
                return '<li>' + esc(o.title) + ' — <span class="xqbr-muted">' + esc(String(o.status || '').replace(/_/g, ' ')) + '</span></li>';
            }).join('');
            return '<dl class="xqbr-evidence-dl"><dt>Overall Progress</dt><dd>' + pct(op.progress) + '</dd></dl>' +
                '<ul class="xqbr-evidence-list-plain">' + oItems + '</ul>';
        }
        if (key === 'previous_commitments') {
            var cc = snap.commitment_completion || {};
            if (!cc.total) return noData('No commitments were recorded for the previous quarter.');
            return '<dl class="xqbr-evidence-dl"><dt>Completion Rate</dt><dd>' + pct(cc.rate) + '</dd>' +
                '<dt>Completed</dt><dd>' + cc.done + ' of ' + cc.total + '</dd></dl>';
        }
        if (key === 'individual_insight_trends') {
            var driverRows = snap.behavioral_driver_trends || [];
            if (!driverRows.length) return noData('No behavioral driver data is available yet.');
            var dItems = driverRows.map(function (r) {
                return '<li>' + esc(DRIVER_LABELS[r.driver] || r.driver) + ' — ' + Math.round((r.coverage_rate || 0) * 100) + '% coverage</li>';
            }).join('');
            return '<ul class="xqbr-evidence-list-plain">' + dItems + '</ul>';
        }
        if (key === 'one_on_one_summaries') {
            var summaries = snap.one_on_one_summaries || [];
            if (!summaries.length) return noData('No completed 1-on-1 meeting summaries are available for this period.');
            return summaries.map(function (r) {
                var ms = r.meeting_summary || {};
                var items = (ms.items || []).map(function (i) { return '<li>' + esc(i) + '</li>'; }).join('');
                return '<dl class="xqbr-evidence-dl" style="margin-bottom:.75rem"><dt>' + esc(formatDate(r.held_at)) + '</dt><dd>' +
                    (items ? ('<ul class="xqbr-evidence-list-plain">' + items + '</ul>') : '') +
                    (ms.details ? ('<div>' + esc(ms.details) + '</div>') : '') + '</dd></dl>';
            }).join('');
        }
        if (key === 'activity_participation') {
            var ap = snap.activity_participation || {};
            if (!ap.total_members) return noData('No group members were found for activity participation.');
            return '<dl class="xqbr-evidence-dl"><dt>Participation Rate</dt><dd>' + pct(ap.rate) + '</dd>' +
                '<dt>Participated</dt><dd>' + ap.participated + ' of ' + ap.total_members + '</dd></dl>';
        }
        if (key === 'assessment_trends') {
            var ac = snap.assessment_completion || {};
            if (!ac.total_members) return noData('No group members were found for assessment tracking.');
            return '<dl class="xqbr-evidence-dl"><dt>Assessment Completion</dt><dd>' + pct(ac.rate) + '</dd>' +
                '<dt>Evaluated</dt><dd>' + ac.evaluated + ' of ' + ac.total_members + '</dd></dl>';
        }
        if (key === 'tool_usage') {
            var tu = snap.tool_utilization || {};
            if (!tu.total_tools) return noData('No development tools are configured for this group yet.');
            return '<dl class="xqbr-evidence-dl"><dt>Utilization Rate</dt><dd>' + pct(tu.rate) + '</dd>' +
                '<dt>Members Submitted</dt><dd>' + tu.members_submitted + ' of ' + tu.total_members + '</dd></dl>';
        }
        if (key === 'ai_insight_themes') {
            var caps = snap.cor_capability_trends || [];
            var available = caps.some(function (c) { return c.score !== null; });
            if (!available) return noData('No AI evaluation data is available for this group yet.');
            var cItems = caps.map(function (c) {
                return '<li>' + esc(CAPABILITY_LABELS[c.capability] || c.capability) + ' — ' + c.score + ' / 5</li>';
            }).join('');
            return '<ul class="xqbr-evidence-list-plain">' + cItems + '</ul>';
        }
        if (key === 'organizational_kpis') {
            var arpKpis = snap.arp_organizational_kpis || [];
            if (!arpKpis.length) return noData('No Related Organizational KPI(s) have been set on the latest Annual Readiness Plan™ Step 4 yet.');
            var aItems = arpKpis.map(function (k) { return '<li>' + esc(k) + '</li>'; }).join('');
            return '<ul class="xqbr-evidence-list-plain">' + aItems + '</ul>';
        }
        if (key === 'operational_metrics') {
            var kpis = snap.kpis || [];
            if (!kpis.length) return noData('No KPIs have been added for this quarter yet.');
            var kItems = kpis.map(function (k) {
                return '<li>' + esc(k.name) + ': ' + esc(k.current) + ' / ' + esc(k.target) + ' <span class="xqbr-muted">(' + esc(k.status) + ')</span></li>';
            }).join('');
            return '<ul class="xqbr-evidence-list-plain">' + kItems + '</ul>';
        }
        if (key === 'historical_qbr_data') {
            var hist = snap.historical_qbr_data || {};
            if (!hist.available) return noData('No previous quarter QBR is available yet.');
            var periodLabel = (hist.quarter && hist.year) ? ('Q' + hist.quarter + ' ' + hist.year) : 'Previous quarter';
            if (hist.overall_readiness_score === null || hist.overall_readiness_score === undefined) {
                return noData(periodLabel + ' has no Organizational Readiness Summary generated yet — visit that QBR\'s Step 1/2 to generate it.');
            }
            return '<dl class="xqbr-evidence-dl">' +
                '<dt>' + esc(periodLabel) + ' — Organizational Readiness Score</dt><dd>' + esc(hist.overall_readiness_score) + '/100</dd>' +
                '<dt>Trend</dt><dd>' + esc(hist.overall_readiness_trend || 'No prior data') + '</dd>' +
                (hist.qbr_objectives_progress !== null && hist.qbr_objectives_progress !== undefined
                    ? ('<dt>QBR Objectives Progress</dt><dd>' + esc(hist.qbr_objectives_progress) + '%</dd>')
                    : '') +
                '</dl>';
        }
        return noData('No data is available for this section yet.');
    }

    var lastSnapshot = null;

    function bindAccordions() {
        var list = document.getElementById('xqbr-evidence-list');
        if (!list) return;
        list.querySelectorAll('.xqbr-evidence-row').forEach(function (row) {
            row.addEventListener('click', function () { toggleRow(row); });
            row.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                toggleRow(row);
            });
        });
    }

    function toggleRow(row) {
        var item = row.closest('.xqbr-evidence-item');
        var panel = item ? item.querySelector('.xqbr-evidence-panel') : null;
        if (!panel) return;
        var isOpen = item.classList.contains('open');
        if (isOpen) {
            item.classList.remove('open');
            panel.classList.add('xqbr-hidden');
            row.setAttribute('aria-expanded', 'false');
            return;
        }
        item.classList.add('open');
        panel.classList.remove('xqbr-hidden');
        row.setAttribute('aria-expanded', 'true');
        panel.innerHTML = renderPanel(row.dataset.key, lastSnapshot);
    }

    function renderChecklist(sources, loading) {
        var byKey = {};
        (sources || []).forEach(function (s) { byKey[s.key] = s; });
        var list = document.getElementById('xqbr-evidence-list');
        if (!list) return;
        list.innerHTML = ORDER.map(function (key) {
            var meta = LABELS[key];
            var row = byKey[key];
            var available = row ? row.available : false;
            var statusClass = loading ? 'pending' : (available ? 'ok' : 'pending');
            var statusText = loading
                ? '<span class="xqbr-spinner xqbr-spinner-inline"></span> Pulling data…'
                : (available ? '&#10003; Pulling data' : 'No data yet');
            return '<div class="xqbr-evidence-item" data-key="' + key + '">' +
                '<div class="xqbr-evidence-row" role="button" tabindex="0" aria-expanded="false" data-key="' + key + '">' +
                '<div class="xqbr-evidence-icon">' + meta[0] + '</div>' +
                '<div><div class="xqbr-evidence-title">' + meta[1] + '</div><div class="xqbr-evidence-desc">' + meta[2] + '</div></div>' +
                '<div class="xqbr-evidence-status ' + statusClass + '">' + statusText + '</div>' +
                '</div>' +
                '<div class="xqbr-evidence-panel xqbr-hidden"></div>' +
                '</div>';
        }).join('');
        bindAccordions();
    }

    window.initEvidenceStep = function () {
        var btn = document.getElementById('xqbr-generate-evidence-btn');
        var statusEl = document.getElementById('xqbr-evidence-status');
        if (window.XFQBR_WIZARD && window.XFQBR_WIZARD.canEdit === false && btn) {
            btn.style.display = 'none';
        }

        renderChecklist([], true);

        window.xqbrLoadEvidence().then(function (data) {
            if (data && data.evidence_sources) {
                lastSnapshot = data;
                renderChecklist(data.evidence_sources, false);
                if (statusEl) statusEl.textContent = 'Evidence already generated for this quarter. Click Generate Evidence to refresh it.';
            } else {
                renderChecklist([], false);
            }
        });

        if (btn) {
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.textContent = 'Generating…';
                if (statusEl) statusEl.textContent = 'Collecting the most up-to-date data. This may take a few seconds.';
                renderChecklist((lastSnapshot && lastSnapshot.evidence_sources) || [], true);

                window.xqbrGenerateEvidence().then(function (res) {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate Evidence';
                    if (!res || !res.success) {
                        renderChecklist((lastSnapshot && lastSnapshot.evidence_sources) || [], false);
                        if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate evidence.';
                        return;
                    }
                    lastSnapshot = res.data;
                    renderChecklist(res.data.evidence_sources, false);
                    if (statusEl) statusEl.textContent = '✓ Evidence generation complete — captured ' + (res.captured_at || 'just now') + '.';
                }).catch(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate Evidence';
                    renderChecklist((lastSnapshot && lastSnapshot.evidence_sources) || [], false);
                    if (statusEl) statusEl.textContent = 'Failed to generate evidence — network error.';
                });
            });
        }
    };
})();
JS;
}
