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
        arp_objectives: ['🎯', 'Annual Readiness Plan™ Objectives', 'Progress and alignment to ARP objectives for the year.'],
        previous_commitments: ['📋', 'Previous Quarterly Commitments', 'Completion status and historical commitment performance.'],
        individual_insight_trends: ['👤', 'Individual Insight Trends', 'Aggregated themes and sentiment from Individual Insights™.'],
        one_on_one_summaries: ['🧑‍🤝‍🧑', '1-on-1 Alignment Capture™ Summaries', 'Alignment trends and key themes from 1-on-1 conversations.'],
        activity_participation: ['📈', 'Activity Participation', 'Participation rates and engagement with learning activities.'],
        assessment_trends: ['📊', 'Assessment Trends', 'Assessment score trends and development benchmarks.'],
        tool_usage: ['🛠️', 'Tool Usage', 'Utilization of development tools and resources.'],
        ai_insight_themes: ['✨', 'AI Insight Themes', 'AI-identified themes and organizational patterns.'],
        organizational_kpis: ['🏢', 'Organizational KPIs', 'Key performance indicators and target progress.'],
        operational_metrics: ['⚙️', 'Operational Metrics', 'Operational performance and efficiency metrics.'],
        historical_qbr_data: ['🕒', 'Historical QBR Data', 'Trends and learnings from previous quarterly reviews.'],
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

    window.initEvidenceStep = function () {
        var btn = document.getElementById('xqbr-generate-evidence-btn');
        var statusEl = document.getElementById('xqbr-evidence-status');
        if (window.XFQBR_WIZARD && window.XFQBR_WIZARD.canEdit === false && btn) {
            btn.style.display = 'none';
        }

        renderChecklist([]);

        window.xqbrLoadEvidence().then(function (data) {
            if (data && data.evidence_sources) {
                renderChecklist(data.evidence_sources);
                if (statusEl) statusEl.textContent = 'Evidence already generated for this quarter. Click Generate Evidence to refresh it.';
            }
        });

        if (btn) {
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.textContent = 'Generating…';
                if (statusEl) statusEl.textContent = 'Collecting the most up-to-date data. This may take a few seconds.';

                window.xqbrGenerateEvidence().then(function (res) {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate Evidence';
                    if (!res || !res.success) {
                        if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate evidence.';
                        return;
                    }
                    renderChecklist(res.data.evidence_sources);
                    if (statusEl) statusEl.textContent = '✓ Evidence generation complete — captured ' + (res.captured_at || 'just now') + '.';
                }).catch(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate Evidence';
                    if (statusEl) statusEl.textContent = 'Failed to generate evidence — network error.';
                });
            });
        }
    };
})();
JS;
}
