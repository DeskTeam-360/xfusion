<?php
/**
 * Step 4 — Executive Strategic Reflection™.
 *
 * UI-only prototype: static dummy content matching the ARR mockup (8
 * reflection prompts with icons, AI Insight & Guidance, Discussion Tips,
 * Conversation Notes). No Laravel calls are made from this step for now —
 * all fields are local-only.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_reflection_js(): string
{
    return <<<'JS'
reflection: function () {
    var fields = [
        ['&#128218;', 'Organizational Learning', 'What were our most important organizational learnings this year?'],
        ['&#128200;', 'Readiness Progression', 'How has our organizational readiness progressed over the past year?'],
        ['&#127919;', 'Strategic Assumptions', 'What assumptions about our strategy or environment were validated or challenged?'],
        ['&#9888;&#65039;', 'Organizational Barriers', 'What barriers continue to limit our performance and growth?'],
        ['&#11088;', 'Organizational Strengths', 'What are our greatest strengths that we should leverage more?'],
        ['&#128101;', 'Leadership Effectiveness', 'How effective was our leadership this year? What should we continue, stop, or start?'],
        ['&#128202;', 'Resource Allocation', 'Did we allocate our resources to the right priorities? What should change?'],
        ['&#128640;', 'Future Opportunities', 'What opportunities should we pursue to accelerate our future state next year?'],
    ];
    return '<h2 class="xarr-section-title">Step 4. Executive Strategic Reflection™</h2>' +
        '<p class="xarr-section-desc">This is your opportunity to reflect on the year\'s evidence, discuss key insights, and explore what they mean for our future. The AI has prepared talking points and insights to guide your conversation.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>This is the primary executive learning conversation. The AI informs. Leadership decides.</span></div>' +
        fields.map(function (f, i) {
            return '<div class="xarr-reflect-field"><div class="xarr-reflect-icon">' + f[0] + '</div>' +
                '<div class="xarr-reflect-body"><label>' + f[1] + '</label>' +
                '<p class="xarr-muted">' + f[2] + '</p>' +
                '<textarea class="xarr-input" data-field="' + i + '" rows="3"></textarea></div></div>';
        }).join('') +

        '<div class="xarr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">' +
        '<div class="xarr-card" style="margin-bottom:0"><h4 style="margin-top:0">&#10024; AI Insight &amp; Guidance</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">Based on your annual evidence and AI assessment, here are key areas to explore in your reflection.</p>' +
        '<ul class="xarr-check-list">' +
        '<li>Readiness improved in Alignment and Communication.</li>' +
        '<li>Behavioral Drivers trend strongest in Get Real and Drive Growth.</li>' +
        '<li>Leadership bench strength is developing, but consistency is needed.</li>' +
        '<li>Resource constraints remain a moderate risk to execution.</li>' +
        '<li>Opportunities exist to strengthen cross-functional collaboration and innovation.</li>' +
        '</ul><a href="javascript:void(0)" class="xarr-link">View all insights &rarr;</a></div>' +
        '<div class="xarr-card" style="margin-bottom:0"><h4 style="margin-top:0">&#128172; Discussion Tips</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">To maximize the value of your reflection conversation:</p>' +
        '<ul class="xarr-check-list">' +
        '<li>Be open and candid.</li><li>Focus on patterns, not isolated events.</li>' +
        '<li>Challenge assumptions.</li><li>Prioritize what will have the greatest impact.</li>' +
        '<li>Capture decisions and next steps.</li>' +
        '</ul></div></div>' +

        '<div class="xarr-card"><h4 style="margin-top:0">&#128221; Conversation Notes</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">Capture key insights, decisions, and reflections from your discussion.</p>' +
        '<textarea class="xarr-input" id="xarr-reflection-notes" rows="4" maxlength="4000" placeholder="Start typing your notes here..."></textarea>' +
        '<p class="xarr-muted" style="font-size:12px;margin-top:.3rem" id="xarr-notes-count">0 / 4000 characters</p>' +
        '</div>';
}
JS;
}

function xfarr_wizard_reflection_init_js(): string
{
    return <<<'JS'
(function () {
    window.initReflectionStep = function () {
        var notes = document.getElementById('xarr-reflection-notes');
        var count = document.getElementById('xarr-notes-count');
        if (notes && count) {
            notes.addEventListener('input', function () { count.textContent = notes.value.length + ' / 4000 characters'; });
        }
    };
})();
JS;
}
