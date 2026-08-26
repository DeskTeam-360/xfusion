<?php
/**
 * Step 1 — Generate Annual Evidence™.
 *
 * Real: loads/generates via Laravel (ArrEvidenceService::buildSnapshot() ->
 * organization-wide aggregation across every group's ARP/QBR/1-on-1/IRR
 * activity for the ARR's company + year). 11 of 15 checklist sources are
 * computed from real data; the remaining 4 (Group Readiness Trends,
 * Executive Dashboard Trends, Reflection Themes, Additional Platform
 * Intelligence) have no tracked source anywhere in FUSION yet and are
 * pinned to the bottom with a distinct "Coming soon" label — same pattern
 * as the IRR wizard's Step 1.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_evidence_js(): string
{
    return <<<'JS'
evidence: function () {
    return '<h2 class="xarr-section-title">Step 1. Generate Annual Evidence™</h2>' +
        '<p class="xarr-section-desc">FUSION automatically assembles organizational evidence from across the platform for your Annual Readiness Review™.<br>This evidence forms the foundation for organizational learning and strategic renewal.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>No action is required. The system is collecting and organizing your evidence.</span></div>' +

        '<div class="xarr-card"><h3 style="margin-top:0">Evidence Sources</h3>' +
        '<p class="xarr-muted" style="margin-top:-.3rem">The following sources are compiled to build your Annual Evidence™.</p>' +
        '<div class="xarr-evidence-list" id="xarr-evidence-list" style="display:grid;grid-template-columns:repeat(3,1fr);gap:0 1rem"><p class="xarr-muted">Loading evidence sources…</p></div></div>' +

        '<div class="xarr-card" id="xarr-evidence-generate-card">' +
        '<button type="button" class="xarr-btn xarr-btn-accent" id="xarr-generate-evidence-btn">Generate Evidence</button>' +
        '<p class="xarr-muted" id="xarr-evidence-status" style="margin-top:.6rem"></p>' +
        '</div>' +

        '<div class="xarr-card"><h4 style="margin-top:0">Data Quality &amp; Privacy</h4>' +
        '<div class="xarr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">' +
        '<div><p style="font-weight:700;color:var(--navy);margin:0 0 .3rem">Data Quality</p>' +
        '<p class="xarr-muted" style="margin-top:0">All included sources meet FUSION data quality standards for accuracy and completeness.</p>' +
        '<ul class="xarr-check-list">' +
        '<li>Source Validation</li><li>Data Integrity</li><li>Recency Check</li><li>Completeness Check</li>' +
        '</ul></div>' +
        '<div><p style="font-weight:700;color:var(--navy);margin:0 0 .3rem">Privacy Protection</p>' +
        '<p class="xarr-muted" style="margin-top:0">All individual and team data is aggregated and anonymized following the FUSION Privacy Principle.</p>' +
        '<div class="xarr-privacy-flow">' +
        '<div class="xarr-privacy-step">Private Reflection</div>' +
        '<div class="xarr-privacy-arrow">&#8595;</div>' +
        '<div class="xarr-privacy-step">AI Pattern Extraction</div>' +
        '<div class="xarr-privacy-arrow">&#8595;</div>' +
        '<div class="xarr-privacy-step highlight">Organizational Intelligence</div>' +
        '</div>' +
        '<p class="xarr-muted" style="font-size:12px;margin-top:.5rem">Raw reflections and private journals are never displayed.</p>' +
        '</div></div></div>';
}
JS;
}

function xfarr_wizard_evidence_init_js(): string
{
    return <<<'JS'
(function () {
    var LABELS = {
        annual_readiness_plan: ['&#127919;', 'Annual Readiness Plan™'],
        quarterly_business_reviews: ['&#128197;', 'Quarterly Business Reviews™'],
        one_on_one: ['&#128101;', '1-on-1 Alignment Capture™'],
        individual_readiness_reviews: ['&#128101;', 'Individual Readiness Reviews™'],
        individual_insights: ['&#128200;', 'Individual Insights™'],
        activities: ['&#127891;', 'Activities'],
        self_assessments: ['&#128203;', 'Self-Assessments'],
        tool_usage: ['&#128295;', 'Tool Usage'],
        operational_kpis: ['&#128200;', 'Operational KPIs'],
        organizational_kpis: ['&#127970;', 'Organizational KPIs'],
        historical_commitments: ['&#128337;', 'Historical Commitments'],
        group_readiness_trends: ['&#128202;', 'Group Readiness Trends'],
        executive_dashboard_trends: ['&#128187;', 'Executive Dashboard Trends'],
        reflection_themes: ['&#10024;', 'Reflection Themes (AI extracted only)'],
        additional_platform_intelligence: ['&#128218;', 'Additional Platform Intelligence'],
    };
    // Live sources first (backend actually computes these). The 4 below are
    // permanently unavailable — no tracked source exists anywhere in FUSION
    // yet — kept at the bottom with a distinct "Coming soon" label.
    var ORDER = [
        'annual_readiness_plan', 'quarterly_business_reviews', 'one_on_one',
        'individual_readiness_reviews', 'individual_insights', 'activities',
        'self_assessments', 'tool_usage', 'operational_kpis', 'organizational_kpis',
        'historical_commitments',
    ];
    var NOT_YET_BUILT = ['group_readiness_trends', 'executive_dashboard_trends', 'reflection_themes', 'additional_platform_intelligence'];

    function row(key, notYetBuilt, byKey) {
        var meta = LABELS[key];
        var data = byKey[key];
        var available = notYetBuilt ? false : (data ? data.available : false);
        var statusClass = available ? 'ok' : (notYetBuilt ? 'soon' : 'pending');
        var statusText = available ? '&#10003; Included' : (notYetBuilt ? 'Coming soon' : 'No data yet');
        return '<div class="xarr-evidence-row' + (notYetBuilt ? ' xarr-evidence-row-soon' : '') + '">' +
            '<div class="xarr-evidence-icon">' + meta[0] + '</div>' +
            '<div><div class="xarr-evidence-title">' + meta[1] + '</div></div>' +
            '<div class="xarr-evidence-status ' + statusClass + '">' + statusText + '</div>' +
            '</div>';
    }

    function renderChecklist(sources) {
        var byKey = {};
        (sources || []).forEach(function (s) { byKey[s.key] = s; });
        var list = document.getElementById('xarr-evidence-list');
        if (!list) return;
        list.innerHTML = ORDER.map(function (key) { return row(key, false, byKey); }).join('') +
            NOT_YET_BUILT.map(function (key) { return row(key, true, byKey); }).join('');
    }

    window.initEvidenceStep = function () {
        var btn = document.getElementById('xarr-generate-evidence-btn');
        var statusEl = document.getElementById('xarr-evidence-status');
        if (window.XFARR_WIZARD && window.XFARR_WIZARD.canEdit === false && btn) {
            btn.style.display = 'none';
        }

        if (typeof window.xfarrLoadEvidence !== 'function') {
            renderChecklist([]);
            if (statusEl) statusEl.textContent = 'Evidence service unavailable.';
            return;
        }

        if (statusEl) statusEl.textContent = 'Loading evidence…';
        window.xfarrLoadEvidence().then(function (data) {
            if (!data) {
                renderChecklist([]);
                if (statusEl) statusEl.textContent = 'No evidence snapshot yet. Click Generate Evidence to compile.';
                return;
            }
            renderChecklist(data.evidence_sources || []);
            if (statusEl) statusEl.textContent = 'Evidence snapshot loaded.';
        });

        if (!btn || btn.dataset.wired) return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', function () {
            if (btn.dataset.busy === '1' || typeof window.xfarrGenerateEvidence !== 'function') return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Generating…';
            if (statusEl) statusEl.textContent = 'Collecting organization-wide evidence. This may take a few seconds.';
            window.xfarrGenerateEvidence().then(function (res) {
                btn.disabled = false;
                btn.dataset.busy = '';
                btn.textContent = 'Generate Evidence';
                if (!res || !res.success) {
                    if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate evidence.';
                    return;
                }
                renderChecklist((res.data && res.data.evidence_sources) ? res.data.evidence_sources : []);
                if (statusEl) statusEl.textContent = '✓ Evidence generation complete.';
            }).catch(function () {
                btn.disabled = false;
                btn.dataset.busy = '';
                btn.textContent = 'Generate Evidence';
                if (statusEl) statusEl.textContent = 'Failed to generate evidence.';
            });
        });
    };
})();
JS;
}
