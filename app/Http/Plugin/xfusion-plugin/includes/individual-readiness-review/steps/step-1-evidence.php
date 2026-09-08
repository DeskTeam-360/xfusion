<?php
/**
 * Step 1 — Generate Individual Evidence™.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_evidence_js(): string
{
    return <<<'JS'
evidence: function () {
    return '<h2 class="xirr-section-title">Step 1. Generate Individual Evidence™</h2>' +
        '<p class="xirr-section-desc">FUSION will automatically compile a complete year of developmental evidence for you.<br>This evidence is gathered from across the platform and will be used to create your AI Development Assessment™.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>No action is required. The system is collecting and organizing your evidence. You will review — and can re-collect the latest data — in the next step.</span></div>' +
        '<div class="xirr-card"><h3 style="margin-top:0">Evidence Being Compiled</h3>' +
        '<p class="xirr-muted" style="margin-top:-.4rem">Click any source below to view its details.</p>' +
        '<div class="xirr-evidence-list" id="xirr-evidence-list"><p class="xirr-muted">Loading evidence sources…</p></div>' +
        '<p class="xirr-muted" id="xirr-evidence-status" style="margin-top:.6rem"></p>' +
        '</div>' +
        '<div class="xirr-callout xirr-callout-success">' +
        '<span class="xirr-callout-icon" aria-hidden="true">' +
        '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9.2 16.6 4.8 12.2l1.4-1.4 3 3 8.6-8.6 1.4 1.4z"/></svg>' +
        '</span>' +
        '<div class="xirr-callout-body">' +
        '<p class="xirr-callout-title">Evidence Compilation Complete</p>' +
        '<p class="xirr-callout-text">All available evidence has been collected for your Individual Readiness Review™.</p>' +
        '</div></div>' +
        '<div class="xirr-callout xirr-callout-next">' +
        '<span class="xirr-callout-icon" aria-hidden="true">' +
        '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M9 18h6M10 21h4"/>' +
        '<path d="M12 3a6 6 0 0 0-3.4 10.8c.5.5.9 1.2 1 1.9h4.8c.1-.7.5-1.4 1-1.9A6 6 0 0 0 12 3z"/>' +
        '</svg>' +
        '</span>' +
        '<div class="xirr-callout-body">' +
        '<p class="xirr-callout-title">What\'s Next?</p>' +
        '<p class="xirr-callout-text">In Step 2, you will review your objective evidence, including trends, participation, commitments, and growth over the past year.</p>' +
        '</div></div>';
}
JS;
}

function xfirr_wizard_evidence_init_js(): string
{
    return <<<'JS'
(function () {
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/';
    function evidenceIcon(file, alt) {
        return '<img src="' + iconBase + file + '" alt="' + alt + '">';
    }
    var LABELS = {
        individual_insights: [evidenceIcon('individual-insight-icon.svg', 'Individual Insights icon'), 'Individual Insights™', 'Behavioral Driver trends, energy patterns and personal insights'],
        previous_irr: [evidenceIcon('Previous-icon.svg', 'Previous Individual Readiness Review icon'), 'Previous Individual Readiness Review™', 'Insights, commitments and progress from prior reviews'],
        activities: [evidenceIcon('Activities-icon.svg', 'Activities icon'), 'Activities', 'Completed activities and learning engagement throughout the year'],
        commitment_completion: [evidenceIcon('Commitment-icon.svg', 'Commitment Completion icon'), 'Commitment Completion', 'Status of your development commitments'],
        self_assessments: [evidenceIcon('Self-Assessment-icon.svg', 'Self-Assessments icon'), 'Self-Assessments', 'Assessment results and self-ratings over time'],
        behavioral_driver_trends: [evidenceIcon('Behavioral-Driver-icon.svg', 'Behavioral Driver Trends icon'), 'Behavioral Driver Trends', 'Behavioral Driver performance and growth trends'],
        reflection_themes: [evidenceIcon('Reflection-themes-icon.svg', 'Reflection Themes icon'), 'Reflection Themes', 'AI-extracted themes from private reflections and journals'],
        leader_observations: [evidenceIcon('Leader-icon.svg', 'Leader Observations icon'), 'Leader Observations', 'Leader feedback and observed behaviors throughout the year'],
        tool_usage: [evidenceIcon('Tool-Usgae-icon.svg', 'Tool Usage icon'), 'Tool Usage', 'Development tools used and key insights generated'],
        organizational_context: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/09/organizational-context-icon-orange.svg" alt="Organizational Context icon">', 'Organizational Context', 'Organizational events, priorities and context'],
        one_on_one: ['<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/09/two-people-talking-icon-green.svg" alt="1-on-1 Alignment Capture icon">', '1-on-1 Alignment Capture™', 'Key discussion themes and alignment insights'],
        qbr_arp_priorities: [evidenceIcon('QBR-and-ARP-icon.svg', 'QBR and ARP Priorities icon'), 'QBR & ARP Priorities', 'Quarterly priorities and strategic objectives alignment'],
    };
    var DRIVER_LABELS = {
        get_real: 'Get Real™', fill_buckets: 'Fill Buckets™', be_intentional: 'Be Intentional™',
        foster_grit: 'Foster Grit™', drive_growth: 'Drive Growth™',
    };
    // Live sources first (backend actually computes these). The two below
    // are permanently hardcoded unavailable in IrrEvidenceService — no
    // pipeline exists yet (reflection extraction, org-context tracking) —
    // kept at the bottom with a distinct label so it's clear they're not
    // "no data yet" but "not built yet".
    var ORDER = [
        'individual_insights', 'previous_irr', 'activities', 'commitment_completion', 'self_assessments',
        'behavioral_driver_trends', 'leader_observations', 'tool_usage', 'one_on_one', 'qbr_arp_priorities'
    ];
    var NOT_YET_BUILT = ['reflection_themes', 'organizational_context'];

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function noData(msg) { return '<p class="xirr-evidence-empty">' + esc(msg) + '</p>'; }
    function fmtDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        return isNaN(d.getTime()) ? '—' : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    var lastSnapshot = null;

    function renderPanel(key, snap) {
        snap = snap || {};
        if (key === 'individual_insights') {
            var ii = snap.individual_insights;
            if (!ii || ii.score == null) return noData('No Individual Insights available for this review period yet.');
            return '<dl class="xirr-evidence-dl">' +
                '<dt>Score</dt><dd>' + esc(ii.score) + '/100</dd>' +
                '<dt>Evaluated</dt><dd>' + esc(fmtDate(ii.evaluated_at)) + '</dd>' +
                (ii.key_observation ? '<dt>Key Observation</dt><dd>' + esc(ii.key_observation) + '</dd>' : '') +
                '</dl>';
        }
        if (key === 'previous_irr') {
            var pi = snap.previous_irr;
            if (!pi) return noData('No previous Individual Readiness Review™ is available yet.');
            return '<dl class="xirr-evidence-dl"><dt>Year</dt><dd>' + esc(pi.year) + '</dd><dt>Status</dt><dd>' + esc(pi.status) + '</dd></dl>';
        }
        if (key === 'activities') {
            var act = snap.development_participation || {};
            if (!act.total_submissions) return noData('No activity submissions recorded for this review period yet.');
            var byProgram = act.by_program || [];
            var pItems = byProgram.map(function (p) { return '<li>' + esc(p.label) + ': ' + esc(p.submissions) + ' submission(s)</li>'; }).join('');
            return '<dl class="xirr-evidence-dl"><dt>Programs with Activity</dt><dd>' + esc(act.programs_with_data) + ' of ' + esc(act.programs_total) + '</dd></dl>' +
                '<ul class="xirr-evidence-list-plain">' + pItems + '</ul>';
        }
        if (key === 'commitment_completion') {
            var cc = snap.commitment_completion || {};
            if (!cc.total) return noData('No commitments recorded for this review period yet.');
            return '<dl class="xirr-evidence-dl">' +
                '<dt>Completion Rate</dt><dd>' + esc(cc.rate) + '%</dd>' +
                '<dt>Completed</dt><dd>' + esc(cc.completed) + ' of ' + esc(cc.total) + '</dd>' +
                '<dt>In Progress</dt><dd>' + esc(cc.in_progress) + '</dd>' +
                '<dt>Overdue</dt><dd>' + esc(cc.overdue) + '</dd>' +
                '</dl>';
        }
        if (key === 'self_assessments') {
            var self = (snap.self_assessment_scores || []).filter(function (s) { return s.score != null; });
            if (!self.length) return noData('No self-assessment scores are available yet.');
            var sItems = self.map(function (s) { return '<li>' + esc(s.label) + ': ' + esc(s.score) + ' / 5</li>'; }).join('');
            return '<ul class="xirr-evidence-list-plain">' + sItems + '</ul>';
        }
        if (key === 'behavioral_driver_trends') {
            var drivers = ((snap.behavioral_driver_trends || {}).drivers || []).filter(function (d) { return d.you != null; });
            if (!drivers.length) return noData('No behavioral driver scores are available yet.');
            var dItems = drivers.map(function (d) {
                return '<li>' + esc(DRIVER_LABELS[d.slug] || d.label) + ': ' + esc(d.you) +
                    (d.org_avg != null ? ' <span class="xirr-muted">(org avg ' + esc(d.org_avg) + ')</span>' : '') + '</li>';
            }).join('');
            return '<ul class="xirr-evidence-list-plain">' + dItems + '</ul>';
        }
        if (key === 'leader_observations') {
            var obs = snap.leader_observations || [];
            if (!obs.length) return noData('No leader observations recorded for this review period yet.');
            var oItems = obs.map(function (o) { return '<li>' + esc(o) + '</li>'; }).join('');
            return '<ul class="xirr-evidence-list-plain">' + oItems + '</ul>';
        }
        if (key === 'tool_usage') {
            var tu = snap.tool_utilization || {};
            if (!tu.submissions) return noData('No development tool submissions recorded for this review period yet.');
            return '<dl class="xirr-evidence-dl">' +
                '<dt>Submissions</dt><dd>' + esc(tu.submissions) + '</dd>' +
                '<dt>Tools Used</dt><dd>' + esc(tu.tools_used) + ' of ' + esc(tu.tools_available) + '</dd>' +
                '</dl>';
        }
        if (key === 'one_on_one') {
            var oo = snap.one_on_one || {};
            var summaries = snap.one_on_one_summaries || [];
            if (!oo.total) return noData('No 1-on-1 meetings recorded for this review period yet.');
            var html = '<dl class="xirr-evidence-dl"><dt>Completion Rate</dt><dd>' + esc(oo.rate) + '%</dd>' +
                '<dt>Completed</dt><dd>' + esc(oo.completed) + ' of ' + esc(oo.total) + '</dd></dl>';
            if (summaries.length) {
                html += '<ul class="xirr-evidence-list-plain" style="margin-top:.5rem">' + summaries.map(function (s) {
                    return '<li>' + esc(fmtDate(s.held_at)) + (s.leader_name ? ' with ' + esc(s.leader_name) : '') + '</li>';
                }).join('') + '</ul>';
            }
            return html;
        }
        if (key === 'qbr_arp_priorities') {
            var qa = snap.qbr_arp_priorities || {};
            var arpItems = qa.arp_strategic_priorities || [];
            var qbrItems = qa.qbr_commitments || [];
            if (!arpItems.length && !qbrItems.length) return noData('No ARP or QBR priorities are owned by you for this review period yet.');
            var html2 = '';
            if (arpItems.length) {
                html2 += '<div class="xirr-evidence-dl"><dt style="margin-bottom:.3rem">ARP Strategic Priorities</dt></div>' +
                    '<ul class="xirr-evidence-list-plain">' + arpItems.map(function (p) {
                        return '<li>' + esc(p.title) + ' — <span class="xirr-muted">' + esc(String(p.status || '').replace(/_/g, ' ')) + '</span></li>';
                    }).join('') + '</ul>';
            }
            if (qbrItems.length) {
                html2 += '<div class="xirr-evidence-dl" style="margin-top:.5rem"><dt style="margin-bottom:.3rem">QBR Commitments</dt></div>' +
                    '<ul class="xirr-evidence-list-plain">' + qbrItems.map(function (c) {
                        return '<li>' + esc(c.title) + ' — <span class="xirr-muted">' + esc(String(c.status || '').replace(/_/g, ' ')) + '</span></li>';
                    }).join('') + '</ul>';
            }
            return html2;
        }
        return noData('No data is available for this section yet.');
    }

    function bindAccordions() {
        var list = document.getElementById('xirr-evidence-list');
        if (!list) return;
        list.querySelectorAll('.xirr-evidence-row[role="button"]').forEach(function (row) {
            var toggle = function () {
                var item = row.closest('.xirr-evidence-item');
                var panel = item ? item.querySelector('.xirr-evidence-panel') : null;
                if (!panel) return;
                var isOpen = !panel.classList.contains('xirr-hidden');
                if (isOpen) {
                    panel.classList.add('xirr-hidden');
                    row.setAttribute('aria-expanded', 'false');
                    return;
                }
                panel.classList.remove('xirr-hidden');
                row.setAttribute('aria-expanded', 'true');
                panel.innerHTML = renderPanel(row.dataset.key, lastSnapshot);
            };
            row.addEventListener('click', toggle);
            row.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
            });
        });
    }

    function renderChecklist(sources) {
        var byKey = {};
        (sources || []).forEach(function (s) { byKey[s.key] = s; });
        var list = document.getElementById('xirr-evidence-list');
        if (!list) return;

        function row(key, notYetBuilt) {
            var meta = LABELS[key];
            var data = byKey[key];
            var available = notYetBuilt ? false : (data ? data.available : false);
            var statusClass = available ? 'ok' : (notYetBuilt ? 'soon' : 'pending');
            var statusText = available ? '&#10003; Collected' : (notYetBuilt ? 'Coming soon' : 'No data yet');
            var clickable = !notYetBuilt;
            return '<div class="xirr-evidence-item">' +
                '<div class="xirr-evidence-row' + (notYetBuilt ? ' xirr-evidence-row-soon' : '') + '"' +
                (clickable ? ' role="button" tabindex="0" aria-expanded="false" data-key="' + key + '"' : '') + '>' +
                '<div class="xirr-evidence-icon">' + meta[0] + '</div>' +
                '<div class="xirr-evidence-body"><div class="xirr-evidence-title">' + meta[1] + '</div>' +
                '<div class="xirr-evidence-desc">' + meta[2] + '</div></div>' +
                '<div class="xirr-evidence-status ' + statusClass + '">' + statusText + '</div>' +
                '</div>' +
                (clickable ? '<div class="xirr-evidence-panel xirr-hidden"></div>' : '') +
                '</div>';
        }

        list.innerHTML = ORDER.map(function (key) { return row(key, false); }).join('') +
            NOT_YET_BUILT.map(function (key) { return row(key, true); }).join('');
        bindAccordions();
    }

    // No manual "Generate" trigger here — Laravel's getEvidence() auto-builds
    // a snapshot on first view if none exists, so this step only ever shows
    // status. Re-collecting the latest data (re-snapshot) lives on Step 2,
    // next to the evidence it actually affects.
    window.initEvidenceStep = function () {
        var statusEl = document.getElementById('xirr-evidence-status');

        if (typeof window.xfirrLoadEvidence !== 'function') {
            renderChecklist([]);
            if (statusEl) statusEl.textContent = 'Evidence service unavailable.';
            return;
        }

        if (statusEl) statusEl.textContent = 'Loading evidence…';
        window.xfirrLoadEvidence().then(function (data) {
            if (!data) {
                renderChecklist([]);
                if (statusEl) statusEl.textContent = 'No evidence snapshot yet.';
                return;
            }
            lastSnapshot = data;
            renderChecklist(data.evidence_sources || []);
            if (statusEl) statusEl.textContent = '';
        });
    };
})();
JS;
}
