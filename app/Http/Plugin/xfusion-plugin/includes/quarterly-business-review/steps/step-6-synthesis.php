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
        '<div id="xqbr-synthesis-body"><div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading AI Organizational Synthesis…</div></div>';
}
JS;
}

function xfqbr_wizard_synthesis_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/08/';

    var CAPABILITY_ICONS = {
        alignment: iconBase + 'Alignment_1.svg',
        accountability: iconBase + 'Accountability_1.svg',
        communication: iconBase + 'Communication_1.svg',
        leadership: iconBase + 'Leadership_1.svg',
        execution: iconBase + 'Execution_1.svg',
    };

    function donut(score, max, label, color) {
        var v = (score === null || score === undefined) ? 0 : score;
        var s = Math.max(0, Math.min(100, Math.round((v / max) * 100)));
        return '<div class="xqbr-donut-wrap">' +
            '<div class="xqbr-donut-chart">' +
            '<svg class="xqbr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xqbr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xqbr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xqbr-donut-center"><div class="xqbr-donut-score">' + (score === null || score === undefined ? '—' : score) + '<span>/' + max + '</span></div></div>' +
            '</div>' +
            '<div class="xqbr-donut-label">' + esc(label) + '</div>' +
            '</div>';
    }

    function list(items, bulletIcon, emptyMsg) {
        if (!items || !items.length) {
            return '<p class="xqbr-muted">' + esc(emptyMsg) + '</p>';
        }
        return '<ul class="xqbr-check-list">' + items.map(function (i) {
            var bullet = bulletIcon
                ? '<img class="xqbr-list-bullet" src="' + bulletIcon + '" alt="" width="20" height="20">'
                : '<span class="xqbr-check">&#10003;</span>';
            return '<li>' + bullet + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    function numbered(items, emptyMsg) {
        if (!items || !items.length) {
            return '<p class="xqbr-muted">' + esc(emptyMsg) + '</p>';
        }
        return '<ol class="xqbr-numbered">' + items.map(function (i, idx) {
            return '<li><span class="xqbr-numbered-badge" aria-hidden="true">' + (idx + 1) + '</span><span>' + esc(i) + '</span></li>';
        }).join('') + '</ol>';
    }

    function attentionCard(icon, title, desc) {
        return '<div class="xqbr-activate-card">' + icon + '<h4>' + esc(title) + '</h4><p>' + esc(desc) + '</p></div>';
    }

    var body = null;

    function render(data, canEdit) {
        window.xqbrSynthesisCache = { has_synthesis: true };
        var readiness = data.organizational_readiness_summary || {};
        var confidence = data.confidence_level || {};
        var completeness = data.data_completeness || {};
        var commitmentSummary = data.commitment_summary || {};
        var attention = data.recommended_areas_of_attention || [];

        var trendLabel = readiness.trend === 'up' ? '&#8599; Improving'
            : (readiness.trend === 'down' ? '&#8600; Declining' : '&#8594; Steady');
        var readinessColor = readiness.score === null || readiness.score === undefined ? '#9ca3af'
            : (readiness.score >= 70 ? '#16a34a' : (readiness.score >= 50 ? '#ca8a04' : '#dc2626'));

        body.innerHTML =
            '<div class="xqbr-card">' +
            '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">' +
            '<h3 style="margin-top:0">Executive Summary</h3>' +
            (canEdit ? '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-regenerate-synthesis-btn">Regenerate</button>' : '') +
            '</div>' +
            '<p>' + esc(data.executive_summary || 'No executive summary is available yet.') + '</p>' +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">Organizational Readiness Summary</h3>' +
            '<div class="xqbr-ai-split" style="display:grid;grid-template-columns:auto 1fr;gap:1.5rem;align-items:center">' +
            donut(readiness.score, 100, trendLabel.replace(/&#\d+;\s*/, ''), readinessColor) +
            '<div>' +
            '<p class="xqbr-muted">' + esc(readiness.narrative || 'No readiness narrative is available yet.') + '</p>' +
            '<div class="xqbr-stat-list">' +
            '<div class="xqbr-stat-row">Readiness Trend <strong style="color:' + readinessColor + '">' + trendLabel + '</strong></div>' +
            '<div class="xqbr-stat-row">Confidence Level <strong>' + (confidence.percent != null ? confidence.percent + '%' : '—') + '</strong></div>' +
            '<div class="xqbr-stat-row">Data Completeness <strong>' + (completeness.percent != null ? completeness.percent + '%' : '—') + '</strong></div>' +
            '</div>' +
            (confidence.label ? '<p class="xqbr-muted" style="margin-top:.5rem;font-size:13px">' + esc(confidence.label) + '</p>' : '') +
            (completeness.label ? '<p class="xqbr-muted" style="font-size:13px">' + esc(completeness.label) + '</p>' : '') +
            '</div></div></div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'About-this-step6.svg" alt="Organizational Strengths icon"><span>Organizational Strengths</span></h4>' +
            list(data.organizational_strengths, null, 'No organizational strengths identified yet.') + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'ORGANIZATIONAL-OPPORTUNITIES.svg" alt="Organizational Opportunities icon"><span>Organizational Opportunities</span></h4>' +
            list(data.organizational_opportunities, iconBase + 'Purple-Star.svg', 'No organizational opportunities identified yet.') + '</div>' +
            '</div>' +

            '<div class="xqbr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'KEY-RISKS.svg" alt="Key Risks icon"><span>Key Risks</span></h4>' +
            list(data.key_risks, iconBase + 'Red-Star.svg', 'No key risks identified yet.') + '</div>' +
            '<div class="xqbr-card" style="margin-bottom:0"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'QUARTERLY-FOCUS.svg" alt="Quarterly Focus icon"><span>Quarterly Focus</span></h4>' +
            numbered(data.quarterly_focus, 'No quarterly focus areas identified yet.') + '</div>' +
            '</div>' +

            '<div class="xqbr-card"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'COMMITMENT-SUMMARY.svg" alt="Commitment Summary icon"><span>Commitment Summary</span></h4>' +
            '<p class="xqbr-muted" style="margin-top:-.3rem">' + (commitmentSummary.total || 0) + ' organizational commitment(s) have been established for the upcoming quarter.</p>' +
            '<div class="xqbr-stat-list">' +
            '<div class="xqbr-stat-row"><span class="xqbr-dot green"></span> In Progress <strong>' + (commitmentSummary.in_progress || 0) + '</strong></div>' +
            '<div class="xqbr-stat-row"><span class="xqbr-dot amber"></span> Not Started <strong>' + (commitmentSummary.not_started || 0) + '</strong></div>' +
            '<div class="xqbr-stat-row"><span class="xqbr-dot red"></span> High Priority <strong>' + (commitmentSummary.high_priority || 0) + '</strong></div>' +
            '<div class="xqbr-stat-row">Total Commitments <strong>' + (commitmentSummary.total || 0) + '</strong></div>' +
            '</div></div>' +

            '<div class="xqbr-card"><h4 class="xqbr-heading-with-icon"><img src="' + iconBase + 'RECOMMENDED-AREAS-OF-ATTENTION.svg" alt="Recommended Areas of Attention icon"><span>Recommended Areas of Attention</span></h4>' +
            '<p class="xqbr-muted" style="margin-top:-.3rem">Focus on these areas to improve readiness and reduce risk.</p>' +
            (attention.length
                ? ('<div class="xqbr-activate-grid">' + attention.map(function (a) {
                    var icon = a.capability && CAPABILITY_ICONS[a.capability]
                        ? '<img src="' + CAPABILITY_ICONS[a.capability] + '" alt="' + esc(a.title) + ' icon">'
                        : '<span class="xqbr-check" style="font-size:1.5rem">&#9888;</span>';
                    return attentionCard(icon, a.title, a.description);
                }).join('') + '</div>')
                : '<p class="xqbr-muted">No specific areas of attention identified yet.</p>') +
            '</div>';

        var regenBtn = document.getElementById('xqbr-regenerate-synthesis-btn');
        if (regenBtn) {
            regenBtn.addEventListener('click', function () {
                if (regenBtn.dataset.busy === '1') return;
                regenBtn.dataset.busy = '1';
                regenBtn.disabled = true;
                regenBtn.textContent = 'Regenerating…';
                window.xqbrGenerateSynthesis().then(function (res) {
                    regenBtn.disabled = false;
                    regenBtn.dataset.busy = '';
                    regenBtn.textContent = 'Regenerate';
                    if (res && res.success) {
                        render(res.data, canEdit);
                    } else if (res) {
                        window.alert(res.message || 'Failed to regenerate synthesis.');
                    }
                }).catch(function () {
                    regenBtn.disabled = false;
                    regenBtn.dataset.busy = '';
                    regenBtn.textContent = 'Regenerate';
                });
            });
        }
    }

    function renderEmpty(canEdit) {
        window.xqbrSynthesisCache = { has_synthesis: false };
        body.innerHTML = '<div class="xqbr-card"><h3 style="margin-top:0">AI Organizational Synthesis™</h3>' +
            '<p class="xqbr-muted">No AI Organizational Synthesis has been generated for this quarter yet.</p>' +
            (canEdit ? '<button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-generate-synthesis-btn">Generate AI Organizational Synthesis</button>' : '') +
            '<p class="xqbr-muted" id="xqbr-synthesis-status" style="margin-top:.6rem"></p>' +
            '</div>';

        var btn = document.getElementById('xqbr-generate-synthesis-btn');
        var statusEl = document.getElementById('xqbr-synthesis-status');
        if (btn) {
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.textContent = 'Generating…';
                if (statusEl) statusEl.textContent = 'Synthesizing evidence, assessment, and commitments — this may take a few seconds.';
                window.xqbrGenerateSynthesis().then(function (res) {
                    if (!res || !res.success) {
                        btn.disabled = false;
                        btn.dataset.busy = '';
                        btn.textContent = 'Generate AI Organizational Synthesis';
                        if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate synthesis.';
                        return;
                    }
                    render(res.data, canEdit);
                }).catch(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate AI Organizational Synthesis';
                    if (statusEl) statusEl.textContent = 'Failed to generate synthesis — network error.';
                });
            });
        }
    }

    window.initSynthesisStep = function () {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        body = document.getElementById('xqbr-synthesis-body');
        if (!body) return;

        window.xqbrLoadSynthesis().then(function (data) {
            if (data) {
                render(data, canEdit);
            } else {
                renderEmpty(canEdit);
            }
        });
    };
})();
JS;
}
