<?php
/**
 * Step 3 — AI Annual Readiness Assessment™.
 *
 * UI-only prototype: static dummy content matching the ARR mockup
 * (organizational readiness/strategic alignment donuts, behavioral
 * intelligence list, COR capability bars, leadership readiness donut,
 * development trends, readiness progress line chart, strategic risks/
 * opportunities, emerging themes, executive agreement + strategic context).
 * No Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_assessment_js(): string
{
    return <<<'JS'
assessment: function () {
    return '<h2 class="xarr-section-title">Step 3. AI Annual Readiness Assessment™</h2>' +
        '<p class="xarr-section-desc">FUSION AI has analyzed a full year of organizational evidence to evaluate your organization\'s readiness, alignment, and strategic position.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>This assessment is AI-generated and read-only. It serves as the foundation for executive reflection in Step 4.</span></div>' +
        '<div id="xarr-assessment-body"></div>' +

        '<div class="xarr-card"><h4 style="margin-top:0">Executive Agreement</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">Please indicate your agreement with the AI Annual Readiness Assessment™.</p>' +
        '<div id="xarr-agreement-options" style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:.75rem">' +
        ['Strongly Agree','Agree','Neutral','Disagree','Strongly Disagree'].map(function (o, i) {
            return '<label class="xarr-row"><input type="radio" name="xarr-agreement" value="' + o.toLowerCase().replace(/ /g,'_') + '"> ' + o + '</label>';
        }).join('') + '</div>' +
        '<button type="button" class="xarr-btn xarr-btn-outline" id="xarr-save-agreement">Save Agreement</button>' +
        '</div>' +

        '<div class="xarr-card"><h4 style="margin-top:0">Executive Strategic Context</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">What strategic context should be considered before planning next year\'s future state?</p>' +
        '<textarea class="xarr-input" id="xarr-strategic-context" rows="3" maxlength="2000" placeholder="Enter your strategic context here..."></textarea>' +
        '<p class="xarr-muted" style="font-size:12px;margin:.3rem 0 .6rem" id="xarr-context-count">0 / 2000 characters</p>' +
        '<button type="button" class="xarr-btn xarr-btn-outline" id="xarr-save-context">Save Context</button>' +
        '</div>';
}
JS;
}

function xfarr_wizard_assessment_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function donut(score, max, color) {
        var s = Math.max(0, Math.min(100, Math.round((score / max) * 100)));
        return '<div class="xarr-donut-wrap">' +
            '<div class="xarr-donut-chart">' +
            '<svg class="xarr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xarr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xarr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xarr-donut-center"><div class="xarr-donut-score">' + score + '<span> of ' + max + '</span></div></div>' +
            '</div></div>';
    }

    function progressRow(label, value, max) {
        max = max || 5;
        var pct = Math.round((value / max) * 100);
        return '<div class="xarr-align-row xarr-progress-row">' +
            '<div class="xarr-align-label">' + esc(label) + '</div>' +
            '<div class="xarr-progress-track"><div class="xarr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="xarr-progress-pct">' + value + '</div>' +
            '</div>';
    }

    function lineChart(series, labels, yMin, yMax) {
        var w = 400, h = 130, padL = 22, padB = 16, padT = 6, padR = 6;
        var plotW = w - padL - padR, plotH = h - padT - padB;
        function x(i) { return padL + (i / (labels.length - 1)) * plotW; }
        function y(v) { return padT + plotH - ((v - yMin) / (yMax - yMin)) * plotH; }
        var lines = series.map(function (s) {
            var pts = s.values.map(function (v, i) { return x(i) + ',' + y(v); }).join(' ');
            var dash = s.dashed ? ' stroke-dasharray="4 3"' : '';
            return '<polyline points="' + pts + '" fill="none" stroke="' + s.color + '" stroke-width="2"' + dash + '/>';
        }).join('');
        var xLabels = labels.map(function (m, i) {
            return '<text x="' + x(i) + '" y="' + (h - 2) + '" font-size="8" fill="#9ca3af" text-anchor="middle">' + m + '</text>';
        }).join('');
        return '<svg viewBox="0 0 ' + w + ' ' + h + '" style="width:100%;height:auto">' + xLabels + lines + '</svg>';
    }

    function themeCard(icon, title, desc) {
        return '<div style="text-align:center"><div style="font-size:1.4rem">' + icon + '</div>' +
            '<h4 style="margin:.4rem 0 .2rem;font-size:14px">' + esc(title) + '</h4>' +
            '<p class="xarr-muted" style="font-size:13px">' + esc(desc) + '</p></div>';
    }

    window.initAssessmentStep = function () {
        var body = document.getElementById('xarr-assessment-body');
        if (!body) return;

        body.innerHTML =
            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Organizational Readiness Summary™</h4>' +
            donut(3.4, 5, '#2f6f3e') +
            '<p class="xarr-muted" style="text-align:center">Overall readiness shows moderate progress with key opportunities to strengthen execution and capability.</p>' +
            '<p class="xarr-metric-trend down" style="text-align:center">&#8595; 0.3 vs 2024</p>' +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Strategic Alignment Summary™</h4>' +
            donut(3.2, 5, '#2f6f3e') +
            '<p class="xarr-muted" style="text-align:center">Alignment to future state objectives remains moderate with consistency and cross-functional collaboration gaps.</p>' +
            '<p class="xarr-metric-trend down" style="text-align:center">&#8595; 0.2 vs 2024</p>' +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Behavioral Intelligence™</h4>' +
            [['Get Real', 3.1], ['Be Intentional', 3.2], ['Fill Buckets', 2.9], ['Foster Grit', 3.0], ['Drive Growth', 3.3]].map(function (d) {
                return '<div class="xarr-stat-row"><span class="xarr-dot green"></span>' + d[0] + '<strong>' + d[1] + '</strong></div>';
            }).join('') +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +
            '</div>' +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>COR Capability Analysis™</h4>' +
            progressRow('Alignment', 3.2) + progressRow('Accountability', 3.3) + progressRow('Communication', 2.8) +
            progressRow('Leadership', 3.1) + progressRow('Execution', 2.9) +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Leadership Readiness™</h4>' +
            donut(3.3, 6, '#2f6f3e') +
            '<p class="xarr-muted" style="text-align:center">Leadership bench strength is developing, with opportunities to accelerate capability and strategic influence.</p>' +
            '<p class="xarr-metric-trend down" style="text-align:center">&#8595; 0.2 vs 2024</p>' +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Development Trends™</h4>' +
            '<div style="font-size:2.2rem;font-weight:800;color:var(--navy)">68%</div>' +
            '<p class="xarr-muted" style="margin-top:-.3rem">Participation Rate</p>' +
            '<p class="xarr-metric-trend down">&#8595; 6% vs 2024</p>' +
            lineChart([{ values: [58, 60, 65, 68], color: '#2f6f3e' }], ['Q1','Q2','Q3','Q4'], 0, 100) +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +
            '</div>' +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Readiness Progress™</h4>' +
            lineChart([
                { values: [2.6, 3.0, 3.1, 3.4], color: '#2f6f3e' },
                { values: [2.2, 2.4, 2.5, 2.6], color: '#9ca3af', dashed: true },
            ], ['Q1 2025','Q2 2025','Q3 2025','Q4 2025'], 0, 5) +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Strategic Risks™</h4>' +
            ['Execution consistency across teams','Resource constraints in critical areas','Change fatigue impacting adoption','Skills gap in emerging capabilities','Market volatility and external pressures'].map(function (r) {
                return '<div class="xarr-stat-row" style="color:#dc2626"><span>&#9888;&#65039;</span>' + esc(r) + '</div>';
            }).join('') +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Strategic Opportunities™</h4>' +
            ['Strengthen cross-functional alignment','Leverage leadership momentum','Expand high-impact development','Optimize operational efficiency','Innovate to accelerate differentiation'].map(function (r) {
                return '<div class="xarr-stat-row" style="color:#16a34a"><span class="xarr-check" style="margin-top:0">&#10003;</span>' + esc(r) + '</div>';
            }).join('') +
            '<a href="javascript:void(0)" class="xarr-link">View details &rarr;</a></div>' +
            '</div>' +

            '<div class="xarr-card"><h4>Emerging Organizational Themes™</h4>' +
            '<div class="xarr-pattern-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">' +
            themeCard('&#128101;', 'Collaboration', 'Cross-functional collaboration is improving and driving results.') +
            themeCard('&#128737;&#65039;', 'Accountability', 'Clearer ownership is increasing execution velocity.') +
            themeCard('&#127793;', 'Development', 'Investment in people is accelerating capability growth.') +
            themeCard('&#128260;', 'Adaptability', 'Teams are adapting well to change and market demands.') +
            '</div>' +
            '<a href="javascript:void(0)" class="xarr-link" style="display:block;margin-top:.5rem">View all themes &rarr;</a></div>';

        wireAgreementForm();
    };

    function wireAgreementForm() {
        var saveAgreement = document.getElementById('xarr-save-agreement');
        if (saveAgreement) {
            saveAgreement.addEventListener('click', function () {
                saveAgreement.textContent = 'Saved ✓';
                window.setTimeout(function () { saveAgreement.textContent = 'Save Agreement'; }, 1500);
            });
        }
        var ctx = document.getElementById('xarr-strategic-context');
        var count = document.getElementById('xarr-context-count');
        if (ctx && count) {
            ctx.addEventListener('input', function () { count.textContent = ctx.value.length + ' / 2000 characters'; });
        }
        var saveContext = document.getElementById('xarr-save-context');
        if (saveContext) {
            saveContext.addEventListener('click', function () {
                saveContext.textContent = 'Saved ✓';
                window.setTimeout(function () { saveContext.textContent = 'Save Context'; }, 1500);
            });
        }
    }
})();
JS;
}
