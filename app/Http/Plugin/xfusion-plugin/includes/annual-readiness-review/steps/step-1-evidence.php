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

        '<h3 class="xarr-section-title" style="margin-bottom:.3rem">Evidence Sources</h3>' +
        '<p class="xarr-muted" style="margin-top:0;margin-bottom:.85rem">The following sources are compiled to build your Annual Evidence™. Click any source to view its details.</p>' +
        '<div class="xarr-card" style="padding:0"><div class="xarr-evidence"><div class="xarr-evidence-list" id="xarr-evidence-list"><p class="xarr-muted" style="margin:0;padding:1rem">Loading evidence sources…</p></div></div></div>' +

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
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/09/';
    function evidenceIcon(file, alt) {
        return '<img src="' + iconBase + file + '" alt="' + alt + '">';
    }
    var LABELS = {
        annual_readiness_plan: [evidenceIcon('Target-Icon.svg', 'Annual Readiness Plan icon'), 'Annual Readiness Plan™', 'ARPs created for this company this year.'],
        quarterly_business_reviews: [evidenceIcon('Calendar-with-Question-Mark.svg', 'Quarterly Business Reviews icon'), 'Quarterly Business Reviews™', 'QBRs held for this company this year.'],
        one_on_one: [evidenceIcon('Geometric-Network-Team-Icon.svg', '1-on-1 Alignment Capture icon'), '1-on-1 Alignment Capture™', '1-on-1 meeting completion across the organization.'],
        individual_readiness_reviews: [evidenceIcon('Geometric-Group-Icon.svg', 'Individual Readiness Reviews icon'), 'Individual Readiness Reviews™', 'IRRs published for this company this year.'],
        individual_insights: [evidenceIcon('Geometric-Chart-Growth-Icon.svg', 'Individual Insights icon'), 'Individual Insights™', 'AI-generated Individual Insights evaluations this year.'],
        activities: [evidenceIcon('Geometric-Graduation-Cap-Icon.svg', 'Activities icon'), 'Activities', 'Learning activity submissions across the organization.'],
        self_assessments: [evidenceIcon('Geometric-Checklist-Icon.svg', 'Self-Assessments icon'), 'Self-Assessments', 'Company-wide average Behavioral Driver™ and COR capability scores.'],
        tool_usage: [evidenceIcon('Geometric-Tools-Settings-Icon.svg', 'Tool Usage icon'), 'Tool Usage', 'Development tool submissions across the organization.'],
        operational_kpis: [evidenceIcon('Geometric-Factory-Icon.svg', 'Operational KPIs icon'), 'Operational KPIs', 'QBR KPI tracking status across the year.'],
        organizational_kpis: [evidenceIcon('Geometric-Office-Building-Icon.svg', 'Organizational KPIs icon'), 'Organizational KPIs', 'ARP Related Organizational KPI(s) across the year.'],
        historical_commitments: [evidenceIcon('Geometric-Clock-Icon.svg', 'Historical Commitments icon'), 'Historical Commitments', 'Commitment completion across 1-on-1, QBR, and IRR.'],
        group_readiness_trends: [evidenceIcon('Geometric-Line-Chart-Icon.svg', 'Group Readiness Trends icon'), 'Group Readiness Trends', 'Not tracked anywhere in FUSION yet.'],
        executive_dashboard_trends: [evidenceIcon('Geometric-Monitor-Dashboard-Icon.svg', 'Executive Dashboard Trends icon'), 'Executive Dashboard Trends', 'Not tracked anywhere in FUSION yet.'],
        reflection_themes: [evidenceIcon('Geometric-Sparkle-Icon.svg', 'Reflection Themes icon'), 'Reflection Themes (AI extracted only)', 'Not tracked anywhere in FUSION yet.'],
        additional_platform_intelligence: [evidenceIcon('Geometric-Stacked-Layers-Icon.svg', 'Additional Platform Intelligence icon'), 'Additional Platform Intelligence', 'Not tracked anywhere in FUSION yet.'],
    };
    var DRIVER_LABELS = {
        get_real: 'Get Real™', fill_buckets: 'Fill Buckets™', be_intentional: 'Be Intentional™',
        foster_grit: 'Foster Grit™', drive_growth: 'Drive Growth™',
        alignment: 'Alignment', accountability: 'Accountability', communication: 'Communication',
        leadership: 'Leadership', execution: 'Execution',
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

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function noData(msg) { return '<p class="xarr-evidence-empty">' + esc(msg) + '</p>'; }

    var lastSnapshot = null;

    function renderPanel(key, snap) {
        snap = snap || {};
        var d = snap[key];
        if (key === 'annual_readiness_plan') {
            if (!d || !d.count) return noData('No Annual Readiness Plans have been created for this company this year.');
            return '<dl class="xarr-evidence-dl"><dt>Total ARPs</dt><dd>' + esc(d.count) + '</dd><dt>Published (Active)</dt><dd>' + esc(d.active_count) + '</dd></dl>';
        }
        if (key === 'quarterly_business_reviews') {
            if (!d || !d.count) return noData('No Quarterly Business Reviews have been held for this company this year.');
            return '<dl class="xarr-evidence-dl"><dt>Total QBRs</dt><dd>' + esc(d.count) + '</dd><dt>Held / Closed</dt><dd>' + esc(d.held_count) + '</dd></dl>';
        }
        if (key === 'one_on_one') {
            if (!d || !d.total) return noData('No 1-on-1 meetings recorded for this company this year.');
            return '<dl class="xarr-evidence-dl"><dt>Total Meetings</dt><dd>' + esc(d.total) + '</dd><dt>Completed</dt><dd>' + esc(d.completed) + '</dd></dl>';
        }
        if (key === 'individual_readiness_reviews') {
            if (!d || !d.count) return noData('No Individual Readiness Reviews have been created for this company this year.');
            return '<dl class="xarr-evidence-dl"><dt>Total IRRs</dt><dd>' + esc(d.count) + '</dd><dt>Published</dt><dd>' + esc(d.published_count) + '</dd></dl>';
        }
        if (key === 'individual_insights') {
            if (!d || !d.count) return noData('No Individual Insights evaluations recorded for this company this year.');
            return '<dl class="xarr-evidence-dl"><dt>Evaluations Generated</dt><dd>' + esc(d.count) + '</dd></dl>';
        }
        if (key === 'activities') {
            if (!d || !d.total_submissions) return noData('No activity submissions recorded for this company this year.');
            return '<dl class="xarr-evidence-dl"><dt>Total Submissions</dt><dd>' + esc(d.total_submissions) + '</dd>' +
                '<dt>Programs with Activity</dt><dd>' + esc(d.programs_with_data) + ' of ' + esc(d.programs_total) + '</dd></dl>';
        }
        if (key === 'self_assessments') {
            var rows = Object.keys(d || {}).filter(function (slug) { return d[slug] != null; });
            if (!rows.length) return noData('No self-assessment scores are available yet.');
            var items = rows.map(function (slug) { return '<li>' + esc(DRIVER_LABELS[slug] || slug) + ': ' + esc(d[slug]) + '</li>'; }).join('');
            return '<ul class="xarr-evidence-list-plain">' + items + '</ul>';
        }
        if (key === 'tool_usage') {
            if (!d || !d.submissions) return noData('No development tool submissions recorded for this company this year.');
            return '<dl class="xarr-evidence-dl"><dt>Submissions</dt><dd>' + esc(d.submissions) + '</dd></dl>';
        }
        if (key === 'operational_kpis') {
            if (!d || !d.count) return noData('No QBR KPIs have been tracked this year.');
            return '<dl class="xarr-evidence-dl"><dt>Total KPIs</dt><dd>' + esc(d.count) + '</dd>' +
                '<dt>On Track</dt><dd>' + esc(d.on_track) + '</dd><dt>At Risk</dt><dd>' + esc(d.at_risk) + '</dd><dt>Off Track</dt><dd>' + esc(d.off_track) + '</dd></dl>';
        }
        if (key === 'organizational_kpis') {
            if (!d || !d.count) return noData('No Related Organizational KPI(s) have been set on any ARP this year.');
            var kItems = (d.items || []).map(function (i) { return '<li>' + esc(i.title) + ': ' + esc(i.org_kpi) + '</li>'; }).join('');
            return '<ul class="xarr-evidence-list-plain">' + kItems + '</ul>';
        }
        if (key === 'historical_commitments') {
            if (!d || !d.total) return noData('No commitments recorded across 1-on-1, QBR, or IRR this year.');
            return '<dl class="xarr-evidence-dl"><dt>Total Commitments</dt><dd>' + esc(d.total) + '</dd>' +
                '<dt>Completed</dt><dd>' + esc(d.done) + '</dd><dt>In Progress</dt><dd>' + esc(d.in_progress) + '</dd><dt>Open</dt><dd>' + esc(d.open) + '</dd></dl>';
        }
        return noData('This evidence source is not tracked anywhere in FUSION yet.');
    }

    function bindAccordions() {
        var list = document.getElementById('xarr-evidence-list');
        if (!list) return;
        list.querySelectorAll('.xarr-evidence-row[role="button"]').forEach(function (r) {
            var toggle = function () {
                var item = r.closest('.xarr-evidence-item');
                var panel = item ? item.querySelector('.xarr-evidence-panel') : null;
                if (!panel) return;
                var isOpen = !panel.classList.contains('xarr-hidden');
                if (isOpen) {
                    panel.classList.add('xarr-hidden');
                    r.setAttribute('aria-expanded', 'false');
                    return;
                }
                panel.classList.remove('xarr-hidden');
                r.setAttribute('aria-expanded', 'true');
                panel.innerHTML = renderPanel(r.dataset.key, lastSnapshot);
            };
            r.addEventListener('click', toggle);
            r.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
            });
        });
    }

    function row(key, notYetBuilt, byKey) {
        var meta = LABELS[key];
        var data = byKey[key];
        var available = notYetBuilt ? false : (data ? data.available : false);
        var statusClass = available ? '' : (notYetBuilt ? 'soon' : 'pending');
        var statusText = available ? '&#10003; Included' : (notYetBuilt ? 'Coming soon' : 'No data yet');
        var clickable = !notYetBuilt;
        return '<div class="xarr-evidence-item">' +
            '<div class="xarr-evidence-row' + (notYetBuilt ? ' xarr-evidence-row-soon' : '') + '"' +
            (clickable ? ' role="button" tabindex="0" aria-expanded="false" data-key="' + key + '"' : '') + '>' +
            '<div class="xarr-evidence-icon">' + meta[0] + '</div>' +
            '<div class="xarr-evidence-body"><div class="xarr-evidence-title">' + meta[1] + '</div>' +
            '<div class="xarr-evidence-desc">' + meta[2] + '</div></div>' +
            '<div class="xarr-evidence-status ' + statusClass + '">' + statusText + '</div>' +
            '</div>' +
            (clickable ? '<div class="xarr-evidence-panel xarr-hidden"></div>' : '') +
            '</div>';
    }

    function renderChecklist(sources) {
        var byKey = {};
        (sources || []).forEach(function (s) { byKey[s.key] = s; });
        var list = document.getElementById('xarr-evidence-list');
        if (!list) return;
        list.innerHTML = ORDER.map(function (key) { return row(key, false, byKey); }).join('') +
            NOT_YET_BUILT.map(function (key) { return row(key, true, byKey); }).join('');
        bindAccordions();
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
                lastSnapshot = null;
                renderChecklist([]);
                if (statusEl) statusEl.textContent = 'No evidence snapshot yet. Click Generate Evidence to compile.';
                return;
            }
            lastSnapshot = data;
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
                lastSnapshot = res.data || null;
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
