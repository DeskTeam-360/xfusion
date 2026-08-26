<?php
/**
 * Step 6 — AI Development Synthesis™.
 *
 * Real: loads/generates via Laravel (IrrAiService::generateSynthesis() ->
 * POST /api/v1/360/development-synthesis). Readiness score and behavioral
 * growth average are always computed by IrrEvidenceService — the AI only
 * supplies qualitative content. System prompt editable in wp-admin under
 * LLM Prompts -> IRR Synthesis System.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_synthesis_js(): string
{
    return <<<'JS'
synthesis: function () {
    return '<h2 class="xirr-section-title">Step 6. AI Development Synthesis™</h2>' +
        '<p class="xirr-section-desc">FUSION has synthesized your year of evidence, conversations, and commitments into your Annual Development Synthesis™.<br>This is your official annual developmental record.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>This synthesis cannot be edited. It serves as a trusted foundation for future growth and strategic alignment.</span></div>' +
        '<div id="xirr-synthesis-body"><p class="xirr-muted">Loading…</p></div>';
}
JS;
}

function xfirr_wizard_synthesis_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function summaryCard(icon, title, body, cta) {
        return '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">' + icon + ' ' + esc(title) + '</h4>' +
            '<p class="xirr-muted">' + body + '</p>' +
            (cta || '') + '</div>';
    }

    function checkList(items, emptyText) {
        if (!items || !items.length) {
            return '<p class="xirr-muted">' + esc(emptyText || 'None recorded yet.') + '</p>';
        }
        return '<ul class="xirr-check-list">' + items.map(function (i) {
            return '<li><span class="xirr-check">&#10003;</span>' + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    function gauge(value, max, label, color) {
        var hasValue = value != null;
        var frac = hasValue ? Math.max(0, Math.min(100, Math.round((value / max) * 100))) : 0;
        return '<div class="xirr-donut-wrap">' +
            '<div class="xirr-donut-chart">' +
            '<svg class="xirr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xirr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            (hasValue ? '<circle class="xirr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + frac + ' ' + (100 - frac) + '"></circle>' : '') +
            '</svg>' +
            '<div class="xirr-donut-center"><div class="xirr-donut-score">' + (hasValue ? value : '—') + '<span>/' + max + '</span></div></div>' +
            '</div><div class="xirr-donut-label">' + esc(label) + '</div></div>';
    }

    function roadmap(items) {
        if (!items || !items.length) {
            return '<p class="xirr-muted">No roadmap available yet.</p>';
        }
        return '<div class="xirr-roadmap-list">' + items.map(function (it) {
            return '<div class="xirr-roadmap-item">' +
                '<div class="xirr-roadmap-rail"><div class="xirr-roadmap-dot"></div><div class="xirr-roadmap-line"></div></div>' +
                '<div><div class="xirr-roadmap-period">' + esc(it.period || '') + '</div><div class="xirr-roadmap-text">' + esc(it.text || '') + '</div></div>' +
                '</div>';
        }).join('') + '</div>';
    }

    function renderSynthesis(synthesis) {
        var ri = synthesis.readiness_indicators || {};
        var growth = synthesis.behavioral_growth || {};
        var strength = synthesis.strength_summary || {};
        var opportunities = synthesis.opportunity_summary || [];
        var coaching = synthesis.executive_coaching_summary || {};
        var scaleMax = ri.scale_max || 5;

        return '<div class="xirr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            summaryCard('&#128203;', 'Annual Development Summary™', esc(synthesis.annual_development_summary || 'No summary available yet.'), '') +
            summaryCard('&#128200;', 'Behavioral Growth Summary™', esc(synthesis.behavioral_growth_summary || 'No summary available yet.') +
                (growth.average_score != null
                    ? '<br><br><strong style="font-size:1.3rem;color:var(--navy)">' + growth.average_score.toFixed(2) + '</strong> Average Score' +
                      (growth.trend_note ? ' <span style="color:#16a34a">' + esc(growth.trend_note) + '</span>' : '')
                    : ''), '') +
            summaryCard('&#10024;', 'Strength Summary™', esc(strength.title || ''), checkList(strength.items, 'No strengths recorded yet.')) +
            '</div>' +

            '<div class="xirr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">&#128221; Opportunity Summary™</h4>' +
            (opportunities.length
                ? opportunities.map(function (o) { return '<p class="xirr-muted"><b>' + esc(o.title || '') + '</b> — ' + esc(o.description || '') + '</p>'; }).join('')
                : '<p class="xirr-muted">No opportunities recorded yet.</p>') +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0;text-align:center"><h4 style="margin-top:0;text-align:left">Readiness Summary™</h4>' +
            gauge(ri.overall_score, scaleMax, ri.overall_label || 'No data', '#2f6f3e') +
            '<p class="xirr-muted" style="text-align:left;margin-top:.75rem">' + esc(ri.trend_note ? 'Compared to last year: ' + ri.trend_note + '.' : 'No prior-year comparison available yet.') + '</p>' +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">Development Roadmap™</h4>' +
            roadmap(synthesis.development_roadmap) + '</div>' +
            '</div>' +

            '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1.2fr 1fr;gap:1rem">' +
            '<div class="xirr-card"><h4 style="margin-top:0">Recommended Focus Areas™</h4>' +
            '<p class="xirr-muted" style="margin-top:-.2rem">Focusing on these areas will drive the greatest impact on your growth and organizational contribution over the next 12 months.</p>' +
            checkList(synthesis.recommended_focus_areas, 'No focus areas recorded yet.') +
            '</div>' +
            '<div class="xirr-card"><h4 style="margin-top:0">Executive Coaching Summary™</h4>' +
            '<p class="xirr-muted">' + esc(coaching.summary || 'No coaching summary available yet.') + '</p>' +
            '<p class="xirr-muted" style="margin-bottom:.2rem">Coaching Engagement</p>' +
            '<p style="font-weight:700;color:#16a34a;margin:0 0 .6rem">' + esc(coaching.engagement_level || '—') + '</p>' +
            '<p class="xirr-muted" style="margin-bottom:.2rem">Recommendation</p>' +
            '<p style="margin:0 0 .6rem">' + esc(coaching.recommendation || '—') + '</p>' +
            '</div>' +
            '</div>' +

            '<div class="xirr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;margin-top:1rem">&#9989; <span><b>AI Development Synthesis Complete.</b> Your annual developmental synthesis is ready to publish.</span></div>';
    }

    function renderEmptyState(canEdit) {
        return '<div class="xirr-card">' +
            '<h4 style="margin-top:0">No synthesis generated yet</h4>' +
            '<p class="xirr-muted">Generate the AI Development Synthesis™ from this year’s evidence, assessment, conversation, and commitments.</p>' +
            (canEdit
                ? '<button type="button" class="xirr-btn xirr-btn-accent" id="xirr-generate-synthesis-btn">Generate AI Development Synthesis</button>'
                : '<p class="xirr-muted">Only the review’s manager can generate this synthesis.</p>') +
            '<p class="xirr-muted" id="xirr-synthesis-status" style="margin-top:.6rem"></p>' +
            '</div>';
    }

    function bindGenerateButton(body) {
        var btn = document.getElementById('xirr-generate-synthesis-btn');
        if (!btn || btn.dataset.wired) return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', function () {
            if (btn.dataset.busy === '1' || typeof window.xfirrGenerateSynthesis !== 'function') return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Generating…';
            var statusEl = document.getElementById('xirr-synthesis-status');
            if (statusEl) statusEl.textContent = 'Synthesizing your year. This may take a few seconds.';
            window.xfirrGenerateSynthesis().then(function (res) {
                if (!res || !res.success) {
                    btn.dataset.busy = '';
                    btn.disabled = false;
                    btn.textContent = 'Generate AI Development Synthesis';
                    if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate synthesis.';
                    return;
                }
                body.innerHTML = renderSynthesis(res.data.synthesis || {});
            }).catch(function () {
                btn.dataset.busy = '';
                btn.disabled = false;
                btn.textContent = 'Generate AI Development Synthesis';
                if (statusEl) statusEl.textContent = 'Failed to generate synthesis — network error.';
            });
        });
    }

    window.initSynthesisStep = function () {
        var body = document.getElementById('xirr-synthesis-body');
        if (!body) return;

        if (typeof window.xfirrLoadSynthesis !== 'function') {
            body.innerHTML = '<p class="xirr-muted">Synthesis service unavailable.</p>';
            return;
        }

        window.xfirrLoadSynthesis().then(function (data) {
            var canEdit = !!(window.XFIRR_WIZARD && window.XFIRR_WIZARD.canEdit);
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
