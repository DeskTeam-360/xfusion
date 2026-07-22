<?php
/**
 * Step 6 — AI Organizational Synthesis™.
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
        '<div id="xqbr-synthesis-body"><div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading synthesis…</div></div>';
}
JS;
}

function xfqbr_wizard_synthesis_init_js(): string
{
    return <<<'JS'
(function () {
    function escHtml(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function list(items) {
        return '<ul class="xqbr-check-list">' + (items || []).map(function (i) {
            return '<li><span class="xqbr-check">&#10003;</span>' + escHtml(i) + '</li>';
        }).join('') + '</ul>';
    }

    function render(data, meta) {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var body = document.getElementById('xqbr-synthesis-body');
        if (!body) return;

        if (!data) {
            body.innerHTML = canEdit
                ? '<div class="xqbr-card"><button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-generate-synthesis-btn">Generate AI Synthesis</button>' +
                  '<p class="xqbr-muted" id="xqbr-synthesis-status" style="margin-top:.6rem">No synthesis has been generated yet for this quarter.</p></div>'
                : '<div class="xqbr-card"><p class="xqbr-muted">No synthesis has been generated yet for this quarter.</p></div>';
            wireGenerate();
            return;
        }

        var readiness = data.organizational_readiness_summary || {};
        var commitSummary = data.commitment_summary || {};

        body.innerHTML =
            '<div class="xqbr-card"><h3 style="margin-top:0">Executive Summary</h3><p>' + escHtml(data.executive_summary) + '</p></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Organizational Readiness Summary</h4>' +
            '<div class="xqbr-metric-value">' + (readiness.score != null ? readiness.score + '<span class="unit">/100</span>' : 'No data') + '</div>' +
            '<p class="xqbr-muted">' + escHtml(readiness.narrative) + '</p></div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Commitment Summary</h4>' +
            '<div class="xqbr-stat-list">' +
            '<div class="xqbr-stat-row">Total <strong>' + (commitSummary.total || 0) + '</strong></div>' +
            '<div class="xqbr-stat-row">High Priority <strong>' + (commitSummary.high_priority || 0) + '</strong></div>' +
            '<div class="xqbr-stat-row">In Progress <strong>' + (commitSummary.in_progress || 0) + '</strong></div>' +
            '<div class="xqbr-stat-row">Not Started <strong>' + (commitSummary.not_started || 0) + '</strong></div>' +
            '</div></div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Organizational Strengths</h4>' + list(data.organizational_strengths) + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4>Organizational Opportunities</h4>' + list(data.organizational_opportunities) + '</div>' +
            '</div>' +

            '<div class="xqbr-card"><h4>Key Risks</h4>' + list(data.key_risks) + '</div>' +
            '<div class="xqbr-card"><h4>Quarterly Focus</h4>' + list(data.quarterly_focus) + '</div>' +
            '<div class="xqbr-card"><h4>Recommended Areas of Attention</h4>' + list(data.recommended_areas_of_attention) + '</div>' +

            '<div class="xqbr-card">' +
            (canEdit ? '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-regenerate-synthesis-btn">Regenerate AI Synthesis</button>' : '') +
            '<p class="xqbr-muted" id="xqbr-synthesis-status" style="margin-top:.6rem">' +
            (meta && meta.llm_fallback ? 'Generated from available context directly — Xfusion-llm was unavailable.' : '') +
            '</p></div>';

        wireGenerate();
    }

    function wireGenerate() {
        ['xqbr-generate-synthesis-btn', 'xqbr-regenerate-synthesis-btn'].forEach(function (id) {
            var btn = document.getElementById(id);
            if (!btn) return;
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                var originalText = btn.textContent;
                btn.textContent = 'Generating… (may take up to a minute)';
                window.xqbrGenerateSynthesis().then(function (res) {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = originalText;
                    if (!res || !res.success) {
                        var statusEl = document.getElementById('xqbr-synthesis-status');
                        if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate synthesis.';
                        return;
                    }
                    render(res.data, res.meta);
                }).catch(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = originalText;
                });
            });
        });
    }

    window.initSynthesisStep = function () {
        var body = document.getElementById('xqbr-synthesis-body');
        if (body) body.innerHTML = '<div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading synthesis…</div>';
        window.xqbrLoadSynthesis().then(function (data) {
            render(data, null);
        });
    };
})();
JS;
}
