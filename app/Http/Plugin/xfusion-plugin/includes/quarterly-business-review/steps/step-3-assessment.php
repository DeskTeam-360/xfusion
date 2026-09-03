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
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    var AGREEMENT_OPTIONS = [
        ['strongly_agree', 'Strongly Agree'],
        ['agree', 'Agree'],
        ['neutral', 'Neutral'],
        ['disagree', 'Disagree'],
        ['strongly_disagree', 'Strongly Disagree'],
    ];

    var CAPABILITY_LABELS = {
        alignment: 'Alignment Assessment', accountability: 'Accountability Assessment',
        communication: 'Communication Assessment', leadership: 'Leadership Assessment',
        execution: 'Execution Assessment',
    };

    function donut(score, max, label, color) {
        var v = (score === null || score === undefined) ? 0 : score;
        var s = Math.max(0, Math.min(100, Math.round((v / max) * 100)));
        return '<div class="xqbr-donut-wrap">' +
            '<div class="xqbr-donut-chart">' +
            '<svg class="xqbr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xqbr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xqbr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xqbr-donut-center"><div class="xqbr-donut-score">' + (score === null || score === undefined ? '—' : score) + '<span>/' + max + '</span></div></div>' +
            '</div>' +
            '<div class="xqbr-donut-label">' + esc(label) + '</div>' +
            '</div>';
    }

    function toneForLabel(label) {
        if (label === 'Strength') return 'green';
        if (label === 'Developing') return 'amber';
        if (label === 'Opportunity') return 'red';
        return 'gray';
    }

    function capabilityBar(label, score, tone) {
        var color = { green: '#16a34a', amber: '#ca8a04', red: '#dc2626', gray: '#9ca3af' }[tone] || '#9ca3af';
        var scoreLabel = (score === null || score === undefined) ? '—' : (score + '/100');
        return '<div class="xqbr-align-row xqbr-progress-row">' +
            '<span class="xqbr-align-label">' + esc(label) + '</span>' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + (score || 0) + '%;background:' + color + '"></div></div>' +
            '<strong class="xqbr-progress-pct">' + scoreLabel + '</strong>' +
            '</div>';
    }

    function list(items, emptyMsg) {
        if (!items || !items.length) {
            return '<p class="xqbr-muted">' + esc(emptyMsg) + '</p>';
        }
        return '<ul class="xqbr-check-list">' + items.map(function (i) {
            return '<li><span class="xqbr-check">&#10003;</span>' + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    var body = null;

    function agreementOptionsHtml(canEdit, selected) {
        return AGREEMENT_OPTIONS.map(function (opt) {
            return '<label style="display:flex;align-items:center;gap:.35rem;font-size:14px">' +
                '<input type="radio" name="xqbr-agreement" value="' + opt[0] + '" ' +
                (opt[0] === selected ? 'checked' : '') + (canEdit ? '' : ' disabled') + '> ' + esc(opt[1]) + '</label>';
        }).join('');
    }

    function render(data, canEdit) {
        var a = (data && data.assessment) || {};
        var overall = a.overall_readiness || {};
        var confidence = a.confidence_level || {};
        var caps = a.cor_capability_assessment || [];

        body.innerHTML =
            '<div class="xqbr-card">' +
            '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">' +
            '<h3 style="margin-top:0">AI Organizational Assessment Summary</h3>' +
            (canEdit ? '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-regenerate-assessment-btn">Regenerate</button>' : '') +
            '</div>' +
            '<div class="xqbr-ai-split" style="display:grid;grid-template-columns:repeat(3,auto);gap:1.5rem;justify-content:start;align-items:center">' +
            donut(overall.score, 100, 'Overall Readiness Score — ' + (overall.label || 'No data'), '#ca8a04') +
            '<div><div class="xqbr-metric-label">Readiness Trend</div><div class="xqbr-metric-value" style="font-size:1.1rem;color:#16a34a">' +
            (overall.trend === 'up' ? '&#8599; Improving' : (overall.trend === 'down' ? '&#8600; Declining' : '&#8594; Steady')) + '</div>' +
            '<p class="xqbr-muted" style="max-width:180px">Based on this quarter’s evidence relative to the previous quarter.</p></div>' +
            donut(confidence.percent, 100, 'Confidence Level', '#5f9a3f') +
            '</div>' +
            (confidence.label ? '<p class="xqbr-muted" style="margin-top:.5rem">' + esc(confidence.label) + '</p>' : '') +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">AI Assessment by COR Capability</h3>' +
            '<div class="xqbr-align-list">' +
            caps.map(function (c) {
                return capabilityBar(CAPABILITY_LABELS[c.capability] || c.capability, c.score, toneForLabel(c.label));
            }).join('') +
            '</div></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Top Strengths Identified by AI</h4>' +
            list(a.top_strengths, 'No strengths identified yet.') + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Top Opportunities Identified by AI</h4>' +
            list(a.top_opportunities, 'No opportunities identified yet.') + '</div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0;background:#fff5f5"><h4>&#9888; Emerging Risks</h4>' +
            list(a.emerging_risks, 'No emerging risks identified.') + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0;background:#f0fdf4"><h4>&#8599; Emerging Opportunities</h4>' +
            list(a.emerging_opportunities, 'No emerging opportunities identified.') + '</div>' +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">Leadership Agreement</h3>' +
            '<p class="xqbr-muted">To what extent do you agree with this AI assessment?</p>' +
            '<div class="xqbr-row" id="xqbr-agreement-options">' +
            agreementOptionsHtml(canEdit, data.agreement_rating) +
            '</div>' +
            '<h4 style="margin-top:1rem">Leadership Context</h4>' +
            '<p class="xqbr-muted" style="margin-top:-.4rem">What organizational context should be considered in addition to the evidence presented?</p>' +
            '<textarea class="xqbr-input" id="xqbr-leadership-context" rows="3" maxlength="2000" placeholder="Share additional context, insights, or factors the AI may not be aware of..." ' + (canEdit ? '' : 'disabled') + '>' + esc(data.leadership_context || '') + '</textarea>' +
            (canEdit ? '<p class="xqbr-muted" style="margin-top:.3rem">Use the <b>Save Draft</b> button below to save your agreement rating and context.</p>' : '') +
            '</div>';

        var regenBtn = document.getElementById('xqbr-regenerate-assessment-btn');
        if (regenBtn) {
            regenBtn.addEventListener('click', function () {
                if (regenBtn.dataset.busy === '1') return;
                regenBtn.dataset.busy = '1';
                regenBtn.disabled = true;
                regenBtn.textContent = 'Regenerating…';
                window.xqbrGenerateAssessment().then(function (res) {
                    regenBtn.disabled = false;
                    regenBtn.dataset.busy = '';
                    regenBtn.textContent = 'Regenerate';
                    if (res && res.success) {
                        render(res.data, canEdit);
                    }
                }).catch(function () {
                    regenBtn.disabled = false;
                    regenBtn.dataset.busy = '';
                    regenBtn.textContent = 'Regenerate';
                });
            });
        }
    }

    function renderEmpty(canEdit) {
        body.innerHTML = '<div class="xqbr-card"><h3 style="margin-top:0">AI Organizational Assessment Summary</h3>' +
            '<p class="xqbr-muted">No AI assessment has been generated for this quarter yet.</p>' +
            (canEdit ? '<button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-generate-assessment-btn">Generate AI Assessment</button>' : '') +
            '<p class="xqbr-muted" id="xqbr-assessment-status" style="margin-top:.6rem"></p>' +
            '</div>';

        var btn = document.getElementById('xqbr-generate-assessment-btn');
        var statusEl = document.getElementById('xqbr-assessment-status');
        if (btn) {
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.textContent = 'Generating…';
                if (statusEl) statusEl.textContent = 'Analyzing evidence — this may take a few seconds.';
                window.xqbrGenerateAssessment().then(function (res) {
                    if (!res || !res.success) {
                        btn.disabled = false;
                        btn.dataset.busy = '';
                        btn.textContent = 'Generate AI Assessment';
                        if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate assessment.';
                        return;
                    }
                    render(res.data, canEdit);
                }).catch(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate AI Assessment';
                    if (statusEl) statusEl.textContent = 'Failed to generate assessment — network error.';
                });
            });
        }
    }

    window.initAssessmentStep = function () {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        body = document.getElementById('xqbr-assessment-body');
        if (!body) return;

        window.xqbrLoadAssessment().then(function (data) {
            if (data && data.assessment) {
                render(data, canEdit);
            } else {
                renderEmpty(canEdit);
            }
        });
    };

    window.xqbrSaveAssessmentContext = function () {
        var contextEl = document.getElementById('xqbr-leadership-context');
        var ratingEl = document.querySelector('input[name="xqbr-agreement"]:checked');
        return window.xqbrSaveLeadershipContext(contextEl ? contextEl.value : '', ratingEl ? ratingEl.value : '');
    };
})();
JS;
}
