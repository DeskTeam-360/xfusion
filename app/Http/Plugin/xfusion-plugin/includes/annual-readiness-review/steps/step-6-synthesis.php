<?php
/**
 * Step 6 — AI Strategic Renewal Synthesis™.
 *
 * Real: loads/generates via Laravel (ArrAiService::generateSynthesis() ->
 * Xfusion-llm POST /api/v1/arr/strategic-renewal-synthesis). Synthesizes
 * the evidence snapshot, the Step 3 AI assessment, the Step 4 executive
 * reflection, and the Step 5 recommendations into 8 narrative summary
 * sections — pure narrative synthesis, no numeric scores computed or
 * generated at this step. System prompt editable in wp-admin under
 * LLM Prompts -> ARR Synthesis System.
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
        '<div id="xarr-synthesis-body"><p class="xarr-muted">Loading…</p></div>';
}
JS;
}

function xfarr_wizard_synthesis_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    var SECTIONS = [
        ['&#128218;', 'Annual Organizational Learning Summary™', 'annual_organizational_learning_summary'],
        ['&#128200;', 'Readiness Progress Summary™', 'readiness_progress_summary'],
        ['&#129504;', 'Behavioral Intelligence Summary™', 'behavioral_intelligence_summary'],
        ['&#128101;', 'Leadership Intelligence Summary™', 'leadership_intelligence_summary'],
        ['&#127919;', 'Strategic Intelligence Summary™', 'strategic_intelligence_summary'],
        ['&#128260;', 'Strategic Renewal Summary™', 'strategic_renewal_summary'],
    ];

    function renderSections(synthesis) {
        return SECTIONS.map(function (s) {
            return '<div class="xarr-synth-row">' +
                '<div class="xarr-synth-icon">' + s[0] + '</div>' +
                '<div class="xarr-synth-body"><h4>' + esc(s[1]) + '</h4><p>' + esc(synthesis[s[2]] || 'Not enough evidence yet.') + '</p></div>' +
                '</div>';
        }).join('');
    }

    function focusList(items) {
        if (!items || !items.length) {
            return '<p class="xarr-muted">Not enough evidence yet.</p>';
        }
        return '<ul class="xarr-check-list">' + items.map(function (i) {
            return '<li><span class="xarr-check">&#10003;</span>' + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    function renderSynthesis(synthesis) {
        return '<div class="xarr-card"><div class="xarr-synth-list">' + renderSections(synthesis) + '</div></div>' +

            '<div class="xarr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4 style="margin-top:0">&#128681; Recommended Future Focus™</h4>' +
            '<p class="xarr-muted" style="margin-top:-.2rem">AI-identified focus areas that will drive the greatest impact in the next planning year.</p>' +
            focusList(synthesis.recommended_future_focus) +
            '</div>' +
            '<div class="xarr-card" style="margin-bottom:0"><h4 style="margin-top:0">&#11088; Executive Summary™</h4>' +
            '<p class="xarr-muted">' + esc(synthesis.executive_summary || 'Not enough evidence yet.') + '</p>' +
            '</div></div>' +

            '<div class="xarr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;margin-top:1rem">&#9989; <span><b>AI Strategic Renewal Synthesis Complete.</b> This becomes your official organizational learning record.</span></div>';
    }

    function renderEmptyState(canEdit) {
        return '<div class="xarr-card">' +
            '<h4 style="margin-top:0">No synthesis generated yet</h4>' +
            '<p class="xarr-muted">Generate the AI Strategic Renewal Synthesis™ from this year\'s evidence, assessment, executive reflection, and recommendations.</p>' +
            (canEdit
                ? '<button type="button" class="xarr-btn xarr-btn-accent" id="xarr-generate-synthesis-btn">Generate AI Strategic Renewal Synthesis</button>'
                : '<p class="xarr-muted">Only this organization\'s leaders can generate this synthesis.</p>') +
            '<p class="xarr-muted" id="xarr-synthesis-status" style="margin-top:.6rem"></p>' +
            '</div>';
    }

    function bindGenerateButton(body) {
        var btn = document.getElementById('xarr-generate-synthesis-btn');
        if (!btn || btn.dataset.wired) return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', function () {
            if (btn.dataset.busy === '1' || typeof window.xfarrGenerateSynthesis !== 'function') return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Generating…';
            var statusEl = document.getElementById('xarr-synthesis-status');
            if (statusEl) statusEl.textContent = 'Synthesizing your year. This may take a few seconds.';
            window.xfarrGenerateSynthesis().then(function (res) {
                if (!res || !res.success) {
                    btn.dataset.busy = '';
                    btn.disabled = false;
                    btn.textContent = 'Generate AI Strategic Renewal Synthesis';
                    if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate synthesis.';
                    return;
                }
                body.innerHTML = renderSynthesis((res.data && res.data.synthesis) || {});
            }).catch(function () {
                btn.dataset.busy = '';
                btn.disabled = false;
                btn.textContent = 'Generate AI Strategic Renewal Synthesis';
                if (statusEl) statusEl.textContent = 'Failed to generate synthesis — network error.';
            });
        });
    }

    window.initSynthesisStep = function () {
        var body = document.getElementById('xarr-synthesis-body');
        if (!body) return;

        if (typeof window.xfarrLoadSynthesis !== 'function') {
            body.innerHTML = '<p class="xarr-muted">Synthesis service unavailable.</p>';
            return;
        }

        window.xfarrLoadSynthesis().then(function (data) {
            var canEdit = !!(data && data.can_edit);
            if (!data || !data.has_synthesis || !data.synthesis) {
                body.innerHTML = renderEmptyState(canEdit);
                bindGenerateButton(body);
                return;
            }
            body.innerHTML = renderSynthesis(data.synthesis);
        });
    };
})();
JS;
}
