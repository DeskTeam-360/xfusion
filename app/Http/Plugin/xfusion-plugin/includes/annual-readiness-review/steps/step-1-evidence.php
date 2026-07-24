<?php
/**
 * Step 1 — Generate Annual Evidence™.
 *
 * UI-only prototype: static dummy content matching the ARR mockups. No
 * Laravel calls are made from this step for now.
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
        '<div class="xarr-banner">&#8505;&#65039; <span>Evidence is automatically collected throughout the year and cannot be edited. No manual data entry is required.</span></div>' +

        '<div class="xarr-card"><h3 style="margin-top:0">Evidence Sources</h3>' +
        '<p class="xarr-muted" style="margin-top:-.3rem">The following sources are being compiled to build your Annual Evidence™.</p>' +
        '<div class="xarr-evidence-list" id="xarr-evidence-list" style="display:grid;grid-template-columns:repeat(3,1fr);gap:0 1rem"></div></div>' +

        '<div class="xarr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534"><span class="xarr-spinner" style="border-top-color:#16a34a"></span> <span><b>Collecting annual evidence…</b> All sources are being compiled. This may take a few moments.</span></div>' +

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
    var DUMMY_SOURCES = [
        { title: 'Annual Readiness Plan™', icon: '&#127919;' },
        { title: 'Quarterly Business Reviews™', icon: '&#128197;' },
        { title: '1-on-1 Alignment Capture™', icon: '&#128101;' },
        { title: 'Individual Readiness Reviews™', icon: '&#128101;' },
        { title: 'Individual Insights™', icon: '&#128200;' },
        { title: 'Group Readiness Trends', icon: '&#128202;' },
        { title: 'Executive Dashboard Trends', icon: '&#128187;' },
        { title: 'Activities', icon: '&#127891;' },
        { title: 'Self-Assessments', icon: '&#128203;' },
        { title: 'Reflection Themes (AI extracted only)', icon: '&#10024;' },
        { title: 'Tool Usage', icon: '&#128295;' },
        { title: 'Operational KPIs', icon: '&#128200;' },
        { title: 'Organizational KPIs', icon: '&#127970;' },
        { title: 'Historical Commitments', icon: '&#128337;' },
        { title: 'Additional Platform Intelligence', icon: '&#128218;' },
    ];

    function renderSources() {
        var list = document.getElementById('xarr-evidence-list');
        if (!list) return;
        list.innerHTML = DUMMY_SOURCES.map(function (s) {
            return '<div class="xarr-evidence-row">' +
                '<div class="xarr-evidence-icon">' + s.icon + '</div>' +
                '<div><div class="xarr-evidence-title">' + s.title + '</div></div>' +
                '<div class="xarr-evidence-status ok">&#10003; Included</div>' +
                '</div>';
        }).join('');
    }

    window.initEvidenceStep = function () {
        renderSources();
    };
})();
JS;
}
