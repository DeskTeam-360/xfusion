<?php
/**
 * Step 3 — AI Organizational Assessment™.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_assessment_js(): string
{
    return <<<'JS'
assessment: function () {
    return '<h2 class="xqbr-section-title">Step 3. AI Organizational Assessment™</h2>' +
        '<p class="xqbr-section-desc">FUSION\'s AI analyzes all available evidence to provide an objective organizational assessment. Review the AI assessment and provide your agreement rating and leadership context before proceeding.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>This assessment is AI-generated and read-only. Leadership agreement and context are captured below.</span></div>' +
        '<div id="xqbr-assessment-body"><div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading AI assessment…</div></div>';
}
JS;
}

function xfqbr_wizard_assessment_init_js(): string
{
    return <<<'JS'
(function () {
    function escHtml(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function labelClass(label) {
        if (label === 'Strength') return 'green';
        if (label === 'Developing') return 'amber';
        if (label === 'Opportunity') return 'red';
        return '';
    }

    function renderAssessment(payload) {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var body = document.getElementById('xqbr-assessment-body');
        if (!body) return;

        if (!payload || !payload.assessment || !Object.keys(payload.assessment).length) {
            body.innerHTML =
                (canEdit ? '<div class="xqbr-card"><button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-generate-assessment-btn">Generate AI Assessment</button>' +
                    '<p class="xqbr-muted" id="xqbr-assessment-status" style="margin-top:.6rem">No assessment has been generated yet for this quarter.</p></div>'
                    : '<div class="xqbr-card"><p class="xqbr-muted">No assessment has been generated yet for this quarter.</p></div>');
            wireGenerate();
            return;
        }

        var a = payload.assessment;
        var overall = a.overall_readiness || {};
        var confidence = a.confidence_level || {};
        var capRows = (a.cor_capability_assessment || []).map(function (c) {
            return '<tr><td>' + escHtml((c.capability || '').charAt(0).toUpperCase() + (c.capability || '').slice(1)) + '</td>' +
                '<td>' + (c.score != null ? c.score + '/100' : 'No data') + '</td>' +
                '<td><span class="xqbr-badge-pill ' + labelClass(c.label) + '">' + escHtml(c.label) + '</span></td></tr>';
        }).join('');

        function list(items) {
            return '<ul class="xqbr-check-list">' + (items || []).map(function (i) {
                return '<li><span class="xqbr-check">&#10003;</span>' + escHtml(i) + '</li>';
            }).join('') + '</ul>';
        }

        body.innerHTML =
            '<div class="xqbr-card"><h3 style="margin-top:0">AI Organizational Assessment Summary</h3>' +
            '<div class="xqbr-metric-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">' +
            '<div class="xqbr-metric-card"><div class="xqbr-metric-label">Overall Readiness Score</div>' +
            '<div class="xqbr-metric-value">' + (overall.score != null ? overall.score + '<span class="unit">/100</span>' : 'No data') + '</div>' +
            '<div class="xqbr-metric-trend">' + escHtml(overall.label || '') + '</div></div>' +
            '<div class="xqbr-metric-card"><div class="xqbr-metric-label">Trend</div><div class="xqbr-metric-value" style="font-size:1.1rem">' + escHtml(overall.trend || 'No data') + '</div></div>' +
            '<div class="xqbr-metric-card"><div class="xqbr-metric-label">Confidence Level</div><div class="xqbr-metric-value">' + (confidence.percent != null ? confidence.percent + '<span class="unit">%</span>' : 'No data') + '</div>' +
            '<div class="xqbr-metric-trend">' + escHtml(confidence.label || '') + '</div></div>' +
            '</div></div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">AI Assessment by COR Capability</h3>' +
            '<div class="xqbr-table-scroll"><table class="xqbr-table"><thead><tr><th>Capability</th><th>Score</th><th>Status</th></tr></thead><tbody>' + capRows + '</tbody></table></div></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Top Strengths</h4>' + list(a.top_strengths) + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Top Opportunities</h4>' + list(a.top_opportunities) + '</div>' +
            '</div>' +
            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Emerging Risks</h4>' + list(a.emerging_risks) + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Emerging Opportunities</h4>' + list(a.emerging_opportunities) + '</div>' +
            '</div>' +

            '<div class="xqbr-card">' +
            (canEdit ? '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-regenerate-assessment-btn">Regenerate AI Assessment</button>' : '') +
            '<p class="xqbr-muted" id="xqbr-assessment-status" style="margin-top:.6rem"></p>' +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">Leadership Agreement</h3>' +
            '<p class="xqbr-muted">To what extent do you agree with this AI assessment?</p>' +
            '<div class="xqbr-row" id="xqbr-agreement-options">' +
            ['strongly_agree', 'agree', 'neutral', 'disagree', 'strongly_disagree'].map(function (v) {
                var label = v.split('_').map(function (w) { return w.charAt(0).toUpperCase() + w.slice(1); }).join(' ');
                var checked = payload.agreement_rating === v ? 'checked' : '';
                return '<label style="display:flex;align-items:center;gap:.35rem;font-size:14px">' +
                    '<input type="radio" name="xqbr-agreement" value="' + v + '" ' + checked + (canEdit ? '' : ' disabled') + '> ' + label + '</label>';
            }).join('') +
            '</div>' +
            '<h4 style="margin-top:1rem">Leadership Context</h4>' +
            '<p class="xqbr-muted" style="margin-top:-.4rem">What organizational context should be considered in addition to the evidence presented?</p>' +
            '<textarea class="xqbr-input" id="xqbr-leadership-context" rows="3" maxlength="2000" ' + (canEdit ? '' : 'disabled') + '>' + escHtml(payload.leadership_context || '') + '</textarea>' +
            '<p class="xqbr-muted" style="margin-top:.3rem">Use the <b>Save Draft</b> button below to save your agreement rating and context.</p>' +
            '</div>';

        wireGenerate();
    }

    function wireGenerate() {
        ['xqbr-generate-assessment-btn', 'xqbr-regenerate-assessment-btn'].forEach(function (id) {
            var btn = document.getElementById(id);
            if (!btn) return;
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                var originalText = btn.textContent;
                btn.textContent = 'Generating… (may take up to a minute)';
                var statusEl = document.getElementById('xqbr-assessment-status');
                window.xqbrGenerateAssessment().then(function (res) {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = originalText;
                    if (!res || !res.success) {
                        if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate assessment.';
                        return;
                    }
                    if (res.meta && res.meta.llm_fallback && statusEl) {
                        statusEl.textContent = 'Generated from evidence directly — Xfusion-llm was unavailable (' + (res.meta.llm_error || 'unknown error') + ').';
                    }
                    renderAssessment(res.data);
                }).catch(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = originalText;
                });
            });
        });
    }

    // TEMPORARY: shown immediately instead of fetching — see the note in
    // step-1-evidence.php. window.xqbrLoadAssessment / xqbrGenerateAssessment
    // are untouched; Regenerate still calls the real endpoint if clicked.
    var DUMMY_ASSESSMENT = {
        leadership_context: '',
        agreement_rating: null,
        assessment: {
            overall_readiness: { score: 68, label: 'Moderate Strength', trend: 'Improving' },
            confidence_level: { percent: 82, label: 'High Confidence — based on data completeness and consistency.' },
            cor_capability_assessment: [
                { capability: 'alignment', score: 72, label: 'Strength' },
                { capability: 'accountability', score: 64, label: 'Developing' },
                { capability: 'communication', score: 61, label: 'Developing' },
                { capability: 'leadership', score: 69, label: 'Strength' },
                { capability: 'execution', score: 57, label: 'Opportunity' },
            ],
            top_strengths: [
                'Strong leadership bench and emerging leaders.',
                'Consistent improvement in project delivery efficiency.',
                'High engagement in development activities.',
                'Clear strategic direction and aligned priorities.',
                'Effective cross-functional collaboration on key initiatives.',
            ],
            top_opportunities: [
                'Improve communication consistency across teams.',
                'Increase follow-through on action commitments.',
                'Strengthen accountability for operational metrics.',
                'Expand coaching practices across all leaders.',
                'Improve resource planning and capacity visibility.',
            ],
            emerging_risks: [
                'Resource constraints may impact ability to meet Q2 objectives.',
                'Inconsistent follow-through on commitments in Operations.',
                'Communication gaps between field and office teams.',
            ],
            emerging_opportunities: [
                'Leverage growing leadership bench for stretch assignments.',
                'Expand successful pilot programs to additional teams.',
                'Improve system utilization to drive efficiency.',
            ],
        },
    };

    window.initAssessmentStep = function () {
        renderAssessment(DUMMY_ASSESSMENT);
    };
})();
JS;
}
