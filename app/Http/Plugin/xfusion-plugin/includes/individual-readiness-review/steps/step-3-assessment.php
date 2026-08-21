<?php
/**
 * Step 3 — AI Development Assessment™.
 *
 * Real: loads/generates via Laravel (IrrAiService -> POST
 * /api/v1/360/development-assessment). Readiness Indicator scores are always
 * computed by IrrEvidenceService::computeReadinessIndicators() — the AI only
 * supplies qualitative content (strengths, opportunities, patterns,
 * contributions, key takeaway). System prompt is editable in wp-admin under
 * LLM Prompts -> IRR Assessment System.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_assessment_js(): string
{
    return <<<'JS'
assessment: function () {
    return '<h2 class="xirr-section-title">Step 3. AI Development Assessment™</h2>' +
        '<p class="xirr-section-desc">FUSION AI analyzes a full year of evidence to identify patterns, strengths, opportunities, and readiness indicators.<br>Review the AI assessment below before adding your reflections in the next step.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>This assessment is AI-generated and based on your evidence from across the platform. It cannot be edited.</span></div>' +
        '<div id="xirr-assessment-body"><p class="xirr-muted">Loading…</p></div>';
}
JS;
}

function xfirr_wizard_assessment_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    var ROW_ICONS = ['&#9989;', '&#128101;', '&#9999;&#65039;', '&#10024;', '&#128203;'];

    function point(fraction) {
        var theta = (-90 + fraction * 180) * Math.PI / 180;
        return { x: 110 + 75 * Math.sin(theta), y: 110 - 75 * Math.cos(theta) };
    }
    function arcPath(f1, f2) {
        var p1 = point(f1), p2 = point(f2);
        return 'M ' + p1.x + ' ' + p1.y + ' A 75 75 0 0 1 ' + p2.x + ' ' + p2.y;
    }
    function rpmGauge(value, max, zoneLabel, zoneColor, trendNote) {
        var hasValue = value != null;
        var frac = hasValue ? Math.max(0, Math.min(1, value / max)) : 0;
        var needleDeg = -90 + frac * 180;
        return '<div style="text-align:center">' +
            '<svg viewBox="0 0 220 130" style="width:230px;max-width:100%">' +
            '<path d="' + arcPath(0, 0.4) + '" stroke="#dc2626" stroke-width="14" fill="none"/>' +
            '<path d="' + arcPath(0.4, 0.65) + '" stroke="#f59e0b" stroke-width="14" fill="none"/>' +
            '<path d="' + arcPath(0.65, 1) + '" stroke="#16a34a" stroke-width="14" fill="none"/>' +
            (hasValue ? '<line x1="110" y1="110" x2="110" y2="45" stroke="#1e2a52" stroke-width="4" transform="rotate(' + needleDeg + ' 110 110)"/>' : '') +
            '<circle cx="110" cy="110" r="7" fill="#1e2a52"/>' +
            '</svg>' +
            '<div style="font-size:2rem;font-weight:800;color:var(--navy);margin-top:-1rem">' + (hasValue ? value.toFixed(1) : '—') + '<span style="font-size:1rem;font-weight:500;color:var(--muted)"> of ' + max.toFixed(1) + '</span></div>' +
            '<div style="font-weight:600;color:' + zoneColor + '">' + esc(zoneLabel) + (trendNote ? ' (' + esc(trendNote) + ')' : '') + '</div>' +
            '</div>';
    }

    function strengthRow(icon, title, desc, tagLabel, tagValue) {
        return '<div class="xirr-evidence-row" style="align-items:flex-start">' +
            '<div class="xirr-evidence-icon">' + icon + '</div>' +
            '<div><div class="xirr-evidence-title">' + esc(title) + '</div><div class="xirr-evidence-desc">' + esc(desc) + '</div></div>' +
            '<div class="xirr-evidence-status" style="text-align:right"><div class="xirr-muted" style="font-size:12px">' + esc(tagLabel) + '</div><div style="font-weight:700;color:var(--navy)">' + esc(tagValue) + '</div></div>' +
            '</div>';
    }

    function strengthRows(items, defaultLabel) {
        if (!items || !items.length) {
            return '<p class="xirr-muted">No items identified yet.</p>';
        }
        return items.map(function (item, i) {
            return strengthRow(
                ROW_ICONS[i % ROW_ICONS.length],
                item.title || '',
                item.description || '',
                item.tag_label || defaultLabel,
                item.tag_value || '—'
            );
        }).join('');
    }

    function progressRow(label, value, max) {
        max = max || 5;
        var hasValue = value != null;
        var pct = hasValue ? Math.round((value / max) * 100) : 0;
        return '<div class="xirr-align-row xirr-progress-row">' +
            '<div class="xirr-align-label">' + esc(label) + '</div>' +
            '<div class="xirr-progress-track"><div class="xirr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="xirr-progress-pct">' + (hasValue ? value : '—') + '</div>' +
            '</div>';
    }

    function bulletList(items, emptyText) {
        if (!items || !items.length) {
            return '<p class="xirr-muted">' + esc(emptyText) + '</p>';
        }
        return '<ul class="xirr-check-list">' + items.map(function (i) {
            return '<li>' + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    function renderAssessment(assessment) {
        var ri = (assessment && assessment.readiness_indicators) || {};
        var scaleMax = ri.scale_max || 5;
        var pattern = (assessment && assessment.behavioral_pattern_summary) || {};

        return '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Behavioral Strengths™</h4>' +
            strengthRows(assessment.behavioral_strengths, 'Evidence') +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Development Opportunities™</h4>' +
            strengthRows(assessment.development_opportunities, 'Impact') +
            '</div></div>' +

            '<div class="xirr-card"><h4>Behavioral Pattern Summary™</h4>' +
            '<p class="xirr-muted" style="margin-top:-.2rem">' + esc(pattern.summary || 'No pattern summary available yet.') + '</p>' +
            '<div class="xirr-pattern-grid">' +
            '<div class="xirr-pattern-item"><div class="label">Primary Pattern</div><span class="xirr-pattern-chip">' + esc(pattern.primary_pattern || '—') + '</span></div>' +
            '<div class="xirr-pattern-item"><div class="label">Secondary Pattern</div><span class="xirr-pattern-chip">' + esc(pattern.secondary_pattern || '—') + '</span></div>' +
            '<div class="xirr-pattern-item"><div class="label">Energy Pattern</div><span class="xirr-pattern-chip">' + esc(pattern.energy_pattern || '—') + '</span></div>' +
            '<div class="xirr-pattern-item"><div class="label">Growth Edge</div><span class="xirr-pattern-chip" style="background:#fef3c7;color:#92400e">' + esc(pattern.growth_edge || '—') + '</span></div>' +
            '</div></div>' +

            '<div class="xirr-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Readiness Indicators™</h4>' +
            rpmGauge(ri.overall_score, scaleMax, ri.overall_label || 'No data', '#ca8a04', ri.trend_note) +
            progressRow('Self-Awareness', ri.self_awareness, scaleMax) + progressRow('Learning Agility', ri.learning_agility, scaleMax) + progressRow('Accountability', ri.accountability, scaleMax) +
            progressRow('Adaptability', ri.adaptability, scaleMax) + progressRow('Leadership Impact', ri.leadership_impact, scaleMax) + progressRow('Future Readiness', ri.future_readiness, scaleMax) +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Leadership Contributions™</h4>' +
            bulletList(assessment.leadership_contributions, 'No leadership evidence recorded yet.') +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Organizational Contribution™</h4>' +
            bulletList(assessment.organizational_contribution, 'No organizational evidence recorded yet.') +
            '</div>' +
            '</div>' +

            '<div class="xirr-card"><h4>Key Takeaway</h4>' +
            '<p class="xirr-muted">' + esc(assessment.key_takeaway || 'No key takeaway available yet.') + '</p>' +
            '</div>';
    }

    function renderEmptyState(canEdit) {
        return '<div class="xirr-card">' +
            '<h4 style="margin-top:0">No assessment generated yet</h4>' +
            '<p class="xirr-muted">Generate the AI Development Assessment™ from Step 1’s evidence.</p>' +
            (canEdit
                ? '<button type="button" class="xirr-btn xirr-btn-accent" id="xirr-generate-assessment-btn">Generate AI Development Assessment</button>'
                : '<p class="xirr-muted">Only the review’s manager can generate this assessment.</p>') +
            '<p class="xirr-muted" id="xirr-assessment-status" style="margin-top:.6rem"></p>' +
            '</div>';
    }

    function bindGenerateButton(body) {
        var btn = document.getElementById('xirr-generate-assessment-btn');
        if (!btn || btn.dataset.wired) return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', function () {
            if (btn.dataset.busy === '1' || typeof window.xfirrGenerateAssessment !== 'function') return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Generating…';
            var statusEl = document.getElementById('xirr-assessment-status');
            if (statusEl) statusEl.textContent = 'Analyzing your evidence. This may take a few seconds.';
            window.xfirrGenerateAssessment().then(function (res) {
                if (!res || !res.success) {
                    btn.dataset.busy = '';
                    btn.disabled = false;
                    btn.textContent = 'Generate AI Development Assessment';
                    if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate assessment.';
                    return;
                }
                body.innerHTML = renderAssessment(res.data.assessment || {});
            }).catch(function () {
                btn.dataset.busy = '';
                btn.disabled = false;
                btn.textContent = 'Generate AI Development Assessment';
                if (statusEl) statusEl.textContent = 'Failed to generate assessment — network error.';
            });
        });
    }

    window.initAssessmentStep = function () {
        var body = document.getElementById('xirr-assessment-body');
        if (!body) return;

        if (typeof window.xfirrLoadAssessment !== 'function') {
            body.innerHTML = '<p class="xirr-muted">Assessment service unavailable.</p>';
            return;
        }

        window.xfirrLoadAssessment().then(function (data) {
            var canEdit = !!(window.XFIRR_WIZARD && window.XFIRR_WIZARD.canEdit);
            if (!data || !data.has_assessment || !data.assessment) {
                body.innerHTML = renderEmptyState(canEdit);
                bindGenerateButton(body);
                return;
            }
            body.innerHTML = renderAssessment(data.assessment);
        });
    };
})();
JS;
}
