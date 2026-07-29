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
        '<p class="xqbr-muted" style="margin-top:-.5rem">The platform is automatically pulling evidence from the following sources:</p>' +
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
        organizational_kpis: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Organizational-KPIs.svg" alt="Organizational KPIs icon">', 'Organizational KPIs', 'Key performance indicators and target progress.'],
        operational_metrics: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Operational-Metrics.svg" alt="Operational Metrics icon">', 'Operational Metrics', 'Operational performance and efficiency metrics.'],
        historical_qbr_data: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Historical-QBR-Data.svg" alt="Historical QBR Data icon">', 'Historical QBR Data', 'Trends and learnings from previous quarterly reviews.'],
        group: ['👥', 'Group', 'Confirms this QBR is correctly scoped to your company group.'],
    };
    var ORDER = ['arp_objectives', 'previous_commitments', 'individual_insight_trends', 'one_on_one_summaries',
        'activity_participation', 'assessment_trends', 'tool_usage', 'ai_insight_themes',
        'organizational_kpis', 'operational_metrics', 'historical_qbr_data'];

    function renderChecklist(sources) {
        var byKey = {};
        (sources || []).forEach(function (s) { byKey[s.key] = s; });
        var list = document.getElementById('xqbr-evidence-list');
        if (!list) return;
        list.innerHTML = ORDER.map(function (key) {
            var meta = LABELS[key];
            var row = byKey[key];
            var available = row ? row.available : false;
            var statusClass = available ? 'ok' : 'pending';
            var statusText = available ? '&#10003; Pulling data' : 'No data yet';
            return '<div class="xqbr-evidence-row">' +
                '<div class="xqbr-evidence-icon">' + meta[0] + '</div>' +
                '<div><div class="xqbr-evidence-title">' + meta[1] + '</div><div class="xqbr-evidence-desc">' + meta[2] + '</div></div>' +
                '<div class="xqbr-evidence-status ' + statusClass + '">' + statusText + '</div>' +
                '</div>';
        }).join('');
    }

    // TEMPORARY: Steps 1–3 render static dummy data while the real Laravel
    // evidence/assessment aggregation is being debugged (some sources were
    // returning empty for real groups). The Laravel endpoints and
    // window.xqbrLoadEvidence / xqbrGenerateEvidence are untouched — only
    // this step's init stops calling them for now. Revert by restoring the
    // fetch-based version once data issues are resolved.
    var DUMMY_SOURCES = ['arp_objectives', 'previous_commitments', 'individual_insight_trends', 'one_on_one_summaries',
        'activity_participation', 'assessment_trends', 'tool_usage', 'ai_insight_themes',
        'organizational_kpis', 'operational_metrics', 'historical_qbr_data'].map(function (key) {
        return { key: key, available: true };
    });

    window.initEvidenceStep = function () {
        var btn = document.getElementById('xqbr-generate-evidence-btn');
        var statusEl = document.getElementById('xqbr-evidence-status');
        if (window.XFQBR_WIZARD && window.XFQBR_WIZARD.canEdit === false && btn) {
            btn.style.display = 'none';
        }

        renderChecklist(DUMMY_SOURCES);
        if (statusEl) statusEl.textContent = 'All evidence is current through today (dummy data).';

        if (btn) {
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.textContent = 'Generating…';
                if (statusEl) statusEl.textContent = 'Collecting the most up-to-date data. This may take a few seconds.';

                setTimeout(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate Evidence';
                    renderChecklist(DUMMY_SOURCES);
                    if (statusEl) statusEl.textContent = '✓ Evidence generation complete (dummy data).';
                }, 600);
            });
        }
    };
})();
JS;
}
