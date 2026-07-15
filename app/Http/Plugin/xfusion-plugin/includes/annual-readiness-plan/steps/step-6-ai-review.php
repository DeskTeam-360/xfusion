<?php
/**
 * Step 6 — AI Readiness Review™ (static AI synthesis UI + leadership context).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_ai_review_js(): string
{
    return <<<'JS'
ai_review: function () {
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';
    var ctx = window.xarLeadershipContext || '';

    function donut(score, label, color) {
        return '<div class="xar-donut-wrap">' +
            '<svg class="xar-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xar-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xar-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" ' +
            'stroke-dasharray="' + score + ' ' + (100 - score) + '"></circle>' +
            '</svg>' +
            '<div class="xar-donut-center">' +
            '<div class="xar-donut-score">' + score + ' <span>of 100</span></div>' +
            '<div class="xar-donut-label">' + label + '</div>' +
            '</div></div>';
    }

    function checkItem(text) {
        return '<li><span class="xar-check" aria-hidden="true">&#10003;</span><span>' + text + '</span></li>';
    }

    function gapRow(area, desc, impact, priority) {
        var impactCls = impact === 'High' ? 'high' : (impact === 'Low' ? 'low' : 'medium');
        var prioCls = priority === 'High' ? 'high' : (priority === 'Low' ? 'low' : 'medium');
        return '<tr>' +
            '<td><strong>' + area + '</strong><div class="xar-muted xar-gap-desc">' + desc + '</div></td>' +
            '<td><span class="xar-impact ' + impactCls + '"><span class="xar-dot"></span>' + impact + '</span></td>' +
            '<td><span class="xar-badge-pill ' + prioCls + '">' + priority + '</span></td>' +
            '</tr>';
    }

    function alignBar(label, pct) {
        return '<div class="xar-align-row">' +
            '<div class="xar-align-meta"><span>' + label + '</span><strong>' + pct + '%</strong></div>' +
            '<div class="xar-progress-track"><div class="xar-progress-fill" style="width:' + pct + '%"></div></div>' +
            '</div>';
    }

    function riskCard(count, title, desc, tone, iconSvg) {
        return '<div class="xar-risk-card ' + tone + '">' +
            '<div class="xar-risk-icon" aria-hidden="true">' + iconSvg + '</div>' +
            '<div><div class="xar-risk-title"><strong>' + count + '</strong> ' + title + '</div>' +
            '<p class="xar-muted">' + desc + '</p></div></div>';
    }

    function focusItem(text) {
        return '<div class="xar-focus-item">' +
            '<img src="' + iconBase + 'Arrow-on-Target-Icon.svg" alt="" width="36" height="36">' +
            '<span>' + text + '</span></div>';
    }

    var shield = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z"/><path d="M12 8v5M12 16h.01"/></svg>';
    var triangle = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l10 18H2L12 3z"/><path d="M12 9v5M12 17h.01"/></svg>';
    var flag = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/></svg>';
    var check = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>';

    return '<h2 class="xar-section-title">Step 6. AI Readiness Review™</h2>' +
        '<p class="xar-section-desc">FUSION AI has analyzed your Annual Readiness Plan for strategic alignment, organizational readiness, gaps, and recommended focus areas.</p>' +
        '<div class="xar-banner">' +
        '<span class="xar-banner-icon" aria-hidden="true">ℹ️</span>' +
        '<span>This analysis is AI-generated based on Steps 1–5. It is intended to inform leadership judgment, not replace it. You cannot edit the AI-generated sections below — add leadership context at the end of this step.</span>' +
        '</div>' +

        '<div class="xar-card xar-ai-block">' +
        '<h3 class="xar-ai-heading">6.1 Strategic Alignment Summary™</h3>' +
        '<div class="xar-ai-split">' +
        donut(84, 'Strong Alignment', '#5f9a3f') +
        '<div class="xar-ai-copy">' +
        '<p>Your plan demonstrates strong strategic alignment between your future state, readiness priorities, and execution initiatives. Leadership intent is clear and translated into actionable organizational focus.</p>' +
        '<ul class="xar-check-list">' +
        checkItem('Clear connection between future state and strategic priorities') +
        checkItem('Well-defined readiness capabilities linked to COR dimensions') +
        checkItem('Executive ownership assigned across critical initiatives') +
        checkItem('Learning agenda identified to guide yearly adaptation') +
        '</ul></div></div></div>' +

        '<div class="xar-card xar-ai-block">' +
        '<h3 class="xar-ai-heading">6.2 Organizational Readiness Assessment™</h3>' +
        '<div class="xar-ai-split">' +
        donut(76, 'Readiness Score', '#c4a035') +
        '<div class="xar-ai-copy">' +
        '<p>Overall readiness is solid with clear strengths and a focused set of development needs. One critical gap requires leadership attention before full execution momentum.</p>' +
        '<div class="xar-stat-list">' +
        '<div class="xar-stat-row"><span class="xar-dot green"></span><span>Strengths</span><strong>7 identified</strong></div>' +
        '<div class="xar-stat-row"><span class="xar-dot amber"></span><span>Areas for Development</span><strong>4 identified</strong></div>' +
        '<div class="xar-stat-row"><span class="xar-dot red"></span><span>Critical Gaps</span><strong>1 identified</strong></div>' +
        '</div></div></div></div>' +

        '<div class="xar-card xar-ai-block">' +
        '<h3 class="xar-ai-heading">6.3 Potential Gaps™</h3>' +
        '<div class="xar-table-scroll"><table class="xar-table">' +
        '<thead><tr><th>Gap Area</th><th>Impact</th><th>Priority</th></tr></thead><tbody>' +
        gapRow('Data Analytics Capability', 'Limited analytic depth may slow evidence-based decisions.', 'Medium', 'Medium') +
        gapRow('Change Management Capacity', 'Transformation pace may outstrip change support capacity.', 'Medium', 'Medium') +
        gapRow('Cross-Functional Collaboration', 'Handoffs between teams create friction and delay.', 'High', 'High') +
        gapRow('Innovation Culture', 'Experimentation habits are uneven across functions.', 'Medium', 'Medium') +
        '</tbody></table></div></div>' +

        '<div class="xar-card xar-ai-block">' +
        '<h3 class="xar-ai-heading">6.4 Priority Alignment™</h3>' +
        '<div class="xar-ai-split">' +
        donut(82, 'Alignment Score', '#5f9a3f') +
        '<div class="xar-ai-copy">' +
        alignBar('Future State Alignment', 88) +
        alignBar('Readiness Priority Alignment', 84) +
        alignBar('Resource Alignment', 78) +
        alignBar('Timeline Alignment', 76) +
        '</div></div></div>' +

        '<div class="xar-card xar-ai-block">' +
        '<h3 class="xar-ai-heading">6.5 Risk Summary™</h3>' +
        '<div class="xar-risk-grid">' +
        riskCard(2, 'High Risk', 'Require immediate leadership attention.', 'high', shield) +
        riskCard(4, 'Medium Risk', 'Require proactive management.', 'medium', triangle) +
        riskCard(6, 'Low Risk', 'Monitor and manage as planned.', 'low', flag) +
        riskCard(3, 'Strengths', 'Positive factors supporting success.', 'strength', check) +
        '</div></div>' +

        '<div class="xar-card xar-ai-block">' +
        '<h3 class="xar-ai-heading">6.6 Suggested Areas of Focus™</h3>' +
        '<div class="xar-focus-list">' +
        focusItem('Strengthen data analytics capabilities to support evidence-based decisions') +
        focusItem('Build change management capacity alongside strategic initiatives') +
        focusItem('Enhance cross-functional collaboration mechanisms and handoffs') +
        focusItem('Reinforce accountability rhythms for strategic priority owners') +
        focusItem('Protect learning objectives as active leadership agenda items') +
        '</div></div>' +

        '<div class="xar-card xar-ai-block">' +
        '<h3 class="xar-ai-heading">Leadership Context™</h3>' +
        '<div class="xar-field" data-field="leadership_context">' +
        '<div class="xar-field-head">' +
        '<p class="xar-field-desc" style="margin:0">What additional strategic context should future leadership conversations consider throughout the year?</p>' +
        '<span class="xar-field-count">' + ctx.length + ' / 2000</span>' +
        '</div>' +
        '<textarea rows="5" placeholder="Enter your leadership context here..." data-maxlen="2000" data-key="leadership_context">' +
        String(ctx).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') +
        '</textarea></div></div>';
}
JS;
}

function xfarp_wizard_ai_review_init_js(): string
{
    return <<<'JS'
(function () {
    window.initAiReviewStep = function () {
        var main = document.getElementById('xar-main');
        if (!main) {
            return;
        }
        var t = main.querySelector('textarea[data-key="leadership_context"]');
        if (!t) {
            return;
        }
        t.addEventListener('input', function () {
            window.xarLeadershipContext = t.value;
        });
    };
})();
JS;
}
