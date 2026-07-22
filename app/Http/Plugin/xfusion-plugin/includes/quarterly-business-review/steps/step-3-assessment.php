<?php
/**
 * Step 3 — AI Organizational Assessment™.
 *
 * UI-only prototype: static dummy content matching the QBR mockups. No
 * Laravel calls are made from this step for now.
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
        '<div id="xqbr-assessment-body"></div>';
}
JS;
}

function xfqbr_wizard_assessment_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function donut(score, max, label, color) {
        var s = Math.max(0, Math.min(100, Math.round((score / max) * 100)));
        return '<div class="xqbr-donut-wrap">' +
            '<div class="xqbr-donut-chart">' +
            '<svg class="xqbr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xqbr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xqbr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xqbr-donut-center"><div class="xqbr-donut-score">' + score + '<span>/' + max + '</span></div></div>' +
            '</div>' +
            '<div class="xqbr-donut-label">' + esc(label) + '</div>' +
            '</div>';
    }

    function capabilityBar(label, score, tone) {
        var color = { green: '#16a34a', amber: '#ca8a04', red: '#dc2626' }[tone];
        return '<div class="xqbr-align-row xqbr-progress-row">' +
            '<span class="xqbr-align-label">' + esc(label) + '</span>' +
            '<div class="xqbr-progress-track"><div class="xqbr-progress-fill" style="width:' + score + '%;background:' + color + '"></div></div>' +
            '<strong class="xqbr-progress-pct">' + score + '/100</strong>' +
            '</div>';
    }

    function list(items) {
        return '<ul class="xqbr-check-list">' + items.map(function (i) {
            return '<li><span class="xqbr-check">&#10003;</span>' + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    window.initAssessmentStep = function () {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var body = document.getElementById('xqbr-assessment-body');
        if (!body) return;

        body.innerHTML =
            '<div class="xqbr-card"><h3 style="margin-top:0">AI Organizational Assessment Summary</h3>' +
            '<div class="xqbr-ai-split" style="display:grid;grid-template-columns:repeat(3,auto);gap:1.5rem;justify-content:start;align-items:center">' +
            donut(68, 100, 'Overall Readiness Score — Moderate Strength (↑8 vs last quarter)', '#ca8a04') +
            '<div><div class="xqbr-metric-label">Readiness Trend</div><div class="xqbr-metric-value" style="font-size:1.1rem;color:#16a34a">&#8599; Improving</div>' +
            '<p class="xqbr-muted" style="max-width:180px">Consistent upward trend over last 3 quarters.</p></div>' +
            donut(82, 100, 'Confidence Level — High Confidence', '#5f9a3f') +
            '</div></div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">AI Assessment by COR Capability</h3>' +
            '<div class="xqbr-align-list">' +
            capabilityBar('Alignment Assessment', 72, 'green') +
            capabilityBar('Accountability Assessment', 64, 'amber') +
            capabilityBar('Communication Assessment', 61, 'amber') +
            capabilityBar('Leadership Assessment', 69, 'green') +
            capabilityBar('Execution Assessment', 57, 'red') +
            '</div></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Top Strengths Identified by AI</h4>' + list([
                'Strong leadership bench and emerging leaders.',
                'Consistent improvement in project delivery efficiency.',
                'High engagement in development activities.',
                'Clear strategic direction and aligned priorities.',
                'Effective cross-functional collaboration on key initiatives.',
            ]) + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Top Opportunities Identified by AI</h4>' + list([
                'Improve communication consistency across teams.',
                'Increase follow-through on action commitments.',
                'Strengthen accountability for operational metrics.',
                'Expand coaching practices across all leaders.',
                'Improve resource planning and capacity visibility.',
            ]) + '</div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0;background:#fff5f5"><h4>&#9888; Emerging Risks</h4>' + list([
                'Resource constraints may impact ability to meet Q2 objectives.',
                'Inconsistent follow-through on commitments in Operations.',
                'Communication gaps between field and office teams.',
            ]) + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0;background:#f0fdf4"><h4>&#8599; Emerging Opportunities</h4>' + list([
                'Leverage growing leadership bench for stretch assignments.',
                'Expand successful pilot programs to additional teams.',
                'Improve system utilization to drive efficiency.',
            ]) + '</div>' +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">Leadership Agreement</h3>' +
            '<p class="xqbr-muted">To what extent do you agree with this AI assessment?</p>' +
            '<div class="xqbr-row" id="xqbr-agreement-options">' +
            ['Strongly Agree', 'Agree', 'Neutral', 'Disagree', 'Strongly Disagree'].map(function (label, i) {
                return '<label style="display:flex;align-items:center;gap:.35rem;font-size:14px">' +
                    '<input type="radio" name="xqbr-agreement" value="' + label + '" ' + (i === 0 ? 'checked' : '') + (canEdit ? '' : ' disabled') + '> ' + label + '</label>';
            }).join('') +
            '</div>' +
            '<h4 style="margin-top:1rem">Leadership Context</h4>' +
            '<p class="xqbr-muted" style="margin-top:-.4rem">What organizational context should be considered in addition to the evidence presented?</p>' +
            '<textarea class="xqbr-input" id="xqbr-leadership-context" rows="3" maxlength="2000" placeholder="Share additional context, insights, or factors the AI may not be aware of..." ' + (canEdit ? '' : 'disabled') + '></textarea>' +
            '<p class="xqbr-muted" style="margin-top:.3rem">Use the <b>Save Draft</b> button below to save your agreement rating and context.</p>' +
            '</div>';
    };
})();
JS;
}
