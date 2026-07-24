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
        '<div class="xirr-banner">&#8505;&#65039; <span>No action is required. The system is collecting and organizing your evidence. You will review this evidence in the next step.</span></div>' +
        '<div class="xirr-card"><h3 style="margin-top:0">Evidence Being Compiled</h3>' +
        '<div class="xirr-evidence-list" id="xirr-evidence-list"><p class="xirr-muted">Loading evidence sources…</p></div></div>' +
        '<div class="xirr-card" id="xirr-evidence-generate-card">' +
        '<button type="button" class="xirr-btn xirr-btn-accent" id="xirr-generate-evidence-btn">Generate Evidence</button>' +
        '<p class="xirr-muted" id="xirr-evidence-status" style="margin-top:.6rem"></p>' +
        '</div>' +
        '<div class="xirr-card"><h4 style="margin-top:0">What\'s Next?</h4>' +
        '<p class="xirr-muted" style="margin:0">In Step 2, you will review your objective evidence, including trends, participation, commitments, and growth over the past year.</p></div>';
}
JS;
}

function xfirr_wizard_evidence_init_js(): string
{
    return <<<'JS'
(function () {
    var LABELS = {
        individual_insights: ['&#9673;', 'Individual Insights™', 'Behavioral Driver trends, energy patterns and personal insights'],
        previous_irr: ['&#9673;', 'Previous Individual Readiness Review™', 'Insights, commitments and progress from prior reviews'],
        activities: ['&#9673;', 'Activities', 'Completed activities and learning engagement throughout the year'],
        commitment_completion: ['&#9673;', 'Commitment Completion', 'Status of your development commitments'],
        self_assessments: ['&#9673;', 'Self-Assessments', 'Assessment results and self-ratings over time'],
        behavioral_driver_trends: ['&#9673;', 'Behavioral Driver Trends', 'Behavioral Driver performance and growth trends'],
        reflection_themes: ['&#9673;', 'Reflection Themes', 'AI-extracted themes from private reflections and journals'],
        leader_observations: ['&#9673;', 'Leader Observations', 'Leader feedback and observed behaviors throughout the year'],
        tool_usage: ['&#9673;', 'Tool Usage', 'Development tools used and key insights generated'],
        organizational_context: ['&#9673;', 'Organizational Context', 'Organizational events, priorities and context'],
        one_on_one: ['&#9673;', '1-on-1 Alignment Capture™', 'Key discussion themes and alignment insights'],
        qbr_arp_priorities: ['&#9673;', 'QBR & ARP Priorities', 'Quarterly priorities and strategic objectives alignment'],
    };
    var ORDER = [
        'individual_insights', 'previous_irr', 'activities', 'commitment_completion', 'self_assessments',
        'behavioral_driver_trends', 'reflection_themes', 'leader_observations', 'tool_usage',
        'organizational_context', 'one_on_one', 'qbr_arp_priorities'
    ];

    function renderChecklist(sources) {
        var byKey = {};
        (sources || []).forEach(function (s) { byKey[s.key] = s; });
        var list = document.getElementById('xirr-evidence-list');
        if (!list) return;
        list.innerHTML = ORDER.map(function (key) {
            var meta = LABELS[key];
            var row = byKey[key];
            var available = row ? row.available : false;
            var statusClass = available ? 'ok' : 'pending';
            var statusText = available ? '&#10003; Collected' : 'No data yet';
            return '<div class="xirr-evidence-row">' +
                '<div class="xirr-evidence-icon">' + meta[0] + '</div>' +
                '<div><div class="xirr-evidence-title">' + meta[1] + '</div>' +
                '<div class="xirr-evidence-desc">' + meta[2] + '</div></div>' +
                '<div class="xirr-evidence-status ' + statusClass + '">' + statusText + '</div>' +
                '</div>';
        }).join('');
    }

    window.initEvidenceStep = function () {
        var btn = document.getElementById('xirr-generate-evidence-btn');
        var statusEl = document.getElementById('xirr-evidence-status');
        if (window.XFIRR_WIZARD && window.XFIRR_WIZARD.canEdit === false && btn) {
            btn.style.display = 'none';
        }

        if (typeof window.xfirrLoadEvidence !== 'function') {
            renderChecklist([]);
            if (statusEl) statusEl.textContent = 'Evidence service unavailable.';
            return;
        }

        if (statusEl) statusEl.textContent = 'Loading evidence…';
        window.xfirrLoadEvidence().then(function (data) {
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
            if (btn.dataset.busy === '1' || typeof window.xfirrGenerateEvidence !== 'function') return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Generating…';
            if (statusEl) statusEl.textContent = 'Collecting the most up-to-date data. This may take a few seconds.';
            window.xfirrGenerateEvidence().then(function (res) {
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
