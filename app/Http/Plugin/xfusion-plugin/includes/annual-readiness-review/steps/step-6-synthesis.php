<?php
/**
 * Step 6 — AI Strategic Renewal Synthesis™.
 *
 * UI-only prototype: static dummy content matching the ARR mockup (8
 * synthesis section rows with icon + description + View Details link). No
 * Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_synthesis_js(): string
{
    return <<<'JS'
synthesis: function () {
    return '<h2 class="xarr-section-title">Step 6. AI Strategic Renewal Synthesis™</h2>' +
        '<p class="xarr-section-desc">FUSION AI has synthesized one full year of organizational evidence, assessments, and executive insights to generate your organizational learning and strategic intelligence.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>This synthesis is AI-generated and read-only. It reflects your organization\'s collective learning and strategic intelligence to inform next year\'s future state.</span></div>' +
        '<div class="xarr-card"><div class="xarr-synth-list" id="xarr-synthesis-list"></div></div>';
}
JS;
}

function xfarr_wizard_synthesis_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    var SECTIONS = [
        ['&#128218;', 'Annual Organizational Learning Summary™', 'Key lessons learned, insights gained, and organizational intelligence from one year of evidence and experience.'],
        ['&#128200;', 'Readiness Progress Summary™', 'Overall readiness progression, trends, and movement across key readiness dimensions.'],
        ['&#129504;', 'Behavioral Intelligence Summary™', 'Behavioral driver trends, strengths, challenges, and opportunities for continued growth.'],
        ['&#128101;', 'Leadership Intelligence Summary™', 'Leadership effectiveness trends, bench strength, and leadership development insights.'],
        ['&#127919;', 'Strategic Intelligence Summary™', 'Strategic risks, opportunities, assumptions, and environmental factors impacting future strategy.'],
        ['&#128260;', 'Strategic Renewal Summary™', 'Consolidated recommendations and priorities to guide the next Annual Readiness Plan™.'],
        ['&#128681;', 'Recommended Future Focus™', 'AI-identified focus areas that will drive the greatest impact in the next planning year.'],
        ['&#11088;', 'Executive Summary™', 'A concise overview of your organization\'s annual readiness, key learnings, and strategic priorities for the year ahead.'],
    ];

    function renderSections() {
        var list = document.getElementById('xarr-synthesis-list');
        if (!list) return;
        list.innerHTML = SECTIONS.map(function (s, i) {
            var isLast = i === SECTIONS.length - 1;
            return '<div class="xarr-synth-row">' +
                '<div class="xarr-synth-icon">' + s[0] + '</div>' +
                '<div class="xarr-synth-body"><h4>' + esc(s[1]) + '</h4><p>' + esc(s[2]) + '</p></div>' +
                '<a href="javascript:void(0)" class="xarr-link" style="flex-shrink:0;white-space:nowrap">' + (isLast ? 'View Executive Summary' : 'View Details') + ' &rarr;</a>' +
                '</div>';
        }).join('');
    }

    window.initSynthesisStep = function () {
        renderSections();
    };
})();
JS;
}
