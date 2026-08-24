<?php
/**
 * Step 6 — AI Organizational Synthesis™.
 *
 * UI-only prototype: static dummy content matching the QBR mockups. No
 * Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_synthesis_js(): string
{
    return <<<'JS'
synthesis: function () {
    return '<h2 class="xqbr-section-title">Step 6. AI Organizational Synthesis™</h2>' +
        '<p class="xqbr-section-desc">FUSION AI synthesizes all inputs to produce your official organizational readiness synthesis. This synthesis becomes the official quarterly record and informs leadership decisions.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>This synthesis is AI-generated and read-only. It combines evidence, assessment, leadership context, and commitments to provide an executive-level organizational summary.</span></div>' +
        '<div id="xqbr-synthesis-body"></div>';
}
JS;
}

function xfqbr_wizard_synthesis_init_js(): string
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

    function list(items, bulletIcon) {
        return '<ul class="xqbr-check-list">' + items.map(function (i) {
            var bullet = bulletIcon
                ? '<img class="xqbr-list-bullet" src="' + bulletIcon + '" alt="" width="20" height="20">'
                : '<span class="xqbr-check">&#10003;</span>';
            return '<li>' + bullet + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    function numbered(items) {
        return '<ol class="xqbr-numbered">' + items.map(function (i, idx) {
            return '<li><span class="xqbr-numbered-badge" aria-hidden="true">' + (idx + 1) + '</span><span>' + esc(i) + '</span></li>';
        }).join('') + '</ol>';
    }

    function attentionCard(icon, title, desc) {
        return '<div class="xqbr-activate-card">' + icon + '<h4>' + esc(title) + '</h4><p>' + esc(desc) + '</p></div>';
    }

    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/';

    window.initSynthesisStep = function () {
        var body = document.getElementById('xqbr-synthesis-body');
        if (!body) return;

        body.innerHTML =
            '<div class="xqbr-card"><h3 style="margin-top:0">Executive Summary</h3>' +
            '<p>Northwind Energy Co-op demonstrated improving readiness this quarter with a Readiness Score of 72/100. Strong progress was made in Leadership and Alignment, while Execution remains an area of focus. Five commitments have been established to drive continued improvement in Q3.</p>' +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">Organizational Readiness Summary</h3>' +
            '<div class="xqbr-ai-split" style="display:grid;grid-template-columns:auto 1fr;gap:1.5rem;align-items:center">' +
            donut(72, 100, 'Improving (↑8 vs last quarter)', '#5f9a3f') +
            '<div>' +
            '<p class="xqbr-muted">Readiness is improving with consistent progress across Alignment, Leadership, and Accountability. Execution and Communication require targeted attention to close gaps and sustain momentum.</p>' +
            '<div class="xqbr-stat-list">' +
            '<div class="xqbr-stat-row">Readiness Trend <strong style="color:#16a34a">Improving ↑8</strong></div>' +
            '<div class="xqbr-stat-row">Confidence Level <strong>82% High</strong></div>' +
            '<div class="xqbr-stat-row">Data Completeness <strong>91% High</strong></div>' +
            '</div></div></div></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'About-this-step6.svg" alt="Organizational Strengths icon"><span>Organizational Strengths</span></h4>' + list([
                'Strong leadership bench and emerging leaders.',
                'Consistent improvement in project delivery efficiency.',
                'High engagement in development activities.',
                'Clear strategic direction and aligned priorities.',
                'Effective cross-functional collaboration on key initiatives.',
            ]) + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'ORGANIZATIONAL-OPPORTUNITIES.svg" alt="Organizational Opportunities icon"><span>Organizational Opportunities</span></h4>' + list([
                'Improve communication consistency across teams.',
                'Increase follow-through on action commitments.',
                'Strengthen accountability for operational metrics.',
                'Expand coaching practices across all leaders.',
                'Improve resource planning and capacity visibility.',
            ], iconBase + 'Purple-Star.svg') + '</div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'KEY-RISKS.svg" alt="Key Risks icon"><span>Key Risks</span></h4>' + list([
                'Resource constraints may impact ability to meet Q3 objectives.',
                'Inconsistent follow-through on commitments in Operations.',
                'Communication gaps between field and office teams.',
                'Dependence on critical individuals in key roles.',
                'External market factors may impact operational margins.',
            ], iconBase + 'Red-Star.svg') + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'QUARTERLY-FOCUS.svg" alt="Quarterly Focus icon"><span>Quarterly Focus</span></h4>' + numbered([
                'Improve project delivery and operational efficiency.',
                'Strengthen member engagement and communication.',
                'Enhance safety performance and compliance.',
                'Develop internal leadership capabilities.',
                'Optimize cost management and resource utilization.',
            ]) + '</div>' +
            '</div>' +

            '<div class="xqbr-card"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'COMMITMENT-SUMMARY.svg" alt="Commitment Summary icon"><span>Commitment Summary</span></h4>' +
            '<p class="xqbr-muted" style="margin-top:-.3rem">Five organizational commitments have been established for the upcoming quarter.</p>' +
            '<div class="xqbr-stat-list">' +
            '<div class="xqbr-stat-row"><span class="xqbr-dot green"></span> In Progress <strong>2</strong></div>' +
            '<div class="xqbr-stat-row"><span class="xqbr-dot amber"></span> Not Started <strong>2</strong></div>' +
            '<div class="xqbr-stat-row"><span class="xqbr-dot red"></span> High Priority <strong>2</strong></div>' +
            '<div class="xqbr-stat-row">Total Commitments <strong>5</strong></div>' +
            '</div></div>' +

            '<div class="xqbr-card"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'RECOMMENDED-AREAS-OF-ATTENTION.svg" alt="Recommended Areas of Attention icon"><span>Recommended Areas of Attention</span></h4>' +
            '<p class="xqbr-muted" style="margin-top:-.3rem">Focus on these areas to improve readiness and reduce risk.</p>' +
            '<div class="xqbr-activate-grid">' +
            attentionCard('<img src="' + iconBase + 'Execution_1.svg" alt="Execution icon">', 'Execution', 'Execution score is below target. Focus on follow-through, resource alignment, and timely completion of key initiatives.') +
            attentionCard('<img src="' + iconBase + 'Communication_1.svg" alt="Communication icon">', 'Communication', 'Strengthen cross-team communication and ensure consistent information flow across field and office.') +
            attentionCard('<img src="' + iconBase + 'Accountability_1.svg" alt="Accountability icon">', 'Accountability', 'Reinforce accountability for metrics, commitments, and outcomes at all levels of the organization.') +
            '</div></div>';
    };
})();
JS;
}
