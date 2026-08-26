<?php
/**
 * Step 3 — AI Annual Readiness Assessment™.
 *
 * Real: loads/generates via Laravel (ArrAiService::generateAssessment() ->
 * Xfusion-llm POST /api/v1/arr/annual-assessment). Organizational Readiness /
 * Strategic Alignment / Behavioral Intelligence / COR Capability Analysis /
 * Leadership Readiness / Development Trends scores are computed by
 * ArrEvidenceService::computeReadinessIndicators() (real scoring data,
 * never by the LLM — see CLAUDE.md's "AI never computes scores" rule).
 * Strategic Risks/Opportunities/Emerging Themes and the narrative summaries
 * are the AI's interpretation. Readiness Progress™ (a quarterly trend line)
 * has no tracked history anywhere in FUSION yet and is shown as an honest
 * "Not available yet" note, same pattern as Step 2.
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

        '<div class="xarr-card" id="xarr-assessment-generate-card">' +
        '<button type="button" class="xarr-btn xarr-btn-accent" id="xarr-generate-assessment-btn">Generate AI Assessment</button>' +
        '<p class="xarr-muted" id="xarr-assessment-status" style="margin-top:.6rem"></p>' +
        '</div>' +

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

    function notAvailable(note) {
        return '<div class="xarr-banner" style="background:#f8f7f4;border-color:#e7e4dc">' +
            '<span class="xarr-evidence-status soon" style="font-style:italic">Not available yet.</span> ' +
            '<span class="xarr-muted" style="font-size:13px">' + esc(note) + '</span></div>';
    }

    function donut(score, max, color) {
        if (score === null || score === undefined) return notAvailable('No scoring data found yet.');
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

    function trendLine(current, prior, label) {
        if (current === null || current === undefined || prior === null || prior === undefined) {
            return '<p class="xarr-muted" style="text-align:center;font-size:12px">No prior-year data to compare</p>';
        }
        var delta = Math.round((current - prior) * 100) / 100;
        var up = delta >= 0;
        return '<p class="xarr-metric-trend ' + (up ? 'up' : 'down') + '" style="text-align:center">' +
            (up ? '&#8593; ' : '&#8595; ') + Math.abs(delta) + ' ' + esc(label) + '</p>';
    }

    function progressRow(label, value) {
        if (value === null || value === undefined) {
            return '<div class="xarr-align-row xarr-progress-row"><div class="xarr-align-label">' + esc(label) + '</div>' +
                '<div class="xarr-muted" style="font-size:12px">No data yet</div></div>';
        }
        var pct = Math.round((value / 5) * 100);
        return '<div class="xarr-align-row xarr-progress-row">' +
            '<div class="xarr-align-label">' + esc(label) + '</div>' +
            '<div class="xarr-progress-track"><div class="xarr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="xarr-progress-pct">' + value + '</div>' +
            '</div>';
    }

    function themeCard(icon, title, desc) {
        return '<div style="text-align:center"><div style="font-size:1.4rem">' + icon + '</div>' +
            '<h4 style="margin:.4rem 0 .2rem;font-size:14px">' + esc(title) + '</h4>' +
            '<p class="xarr-muted" style="font-size:13px">' + esc(desc) + '</p></div>';
    }

    var THEME_ICONS = ['&#128101;', '&#127793;', '&#128260;', '&#127942;', '&#128202;', '&#10024;'];

    function render(body, data) {
        var ri = (data.assessment && data.assessment.readiness_indicators) || {};
        var a = data.assessment || {};
        var period = { year: window.XFARR_WIZARD && window.XFARR_WIZARD.year };

        var orgReadiness = ri.organizational_readiness || {};
        var stratAlign = ri.strategic_alignment || {};
        var leadership = ri.leadership_readiness || {};
        var devTrends = ri.development_trends || {};
        var behavioral = ri.behavioral_intelligence || [];
        var corAnalysis = ri.cor_capability_analysis || [];

        body.innerHTML =
            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Organizational Readiness Summary™</h4>' +
            donut(orgReadiness.score, ri.scale_max || 5, '#2f6f3e') +
            '<p class="xarr-muted" style="text-align:center">' + esc(a.organizational_readiness_narrative || '') + '</p>' +
            trendLine(orgReadiness.score, orgReadiness.prior, 'vs last year') +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Strategic Alignment Summary™</h4>' +
            donut(stratAlign.score, ri.scale_max || 5, '#2f6f3e') +
            '<p class="xarr-muted" style="text-align:center">' + esc(a.strategic_alignment_narrative || '') + '</p>' +
            trendLine(stratAlign.score, stratAlign.prior, 'vs last year') +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Behavioral Intelligence™</h4>' +
            (behavioral.length ? behavioral.map(function (d) {
                return '<div class="xarr-stat-row"><span class="xarr-dot green"></span>' + esc(d.label) + '<strong>' + (d.score !== null ? d.score : '—') + '</strong></div>';
            }).join('') : '<p class="xarr-muted">No scoring data found yet.</p>') +
            '</div>' +
            '</div>' +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>COR Capability Analysis™</h4>' +
            (corAnalysis.length ? corAnalysis.map(function (d) { return progressRow(d.label, d.score); }).join('') : '<p class="xarr-muted">No scoring data found yet.</p>') +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Leadership Readiness™</h4>' +
            donut(leadership.score, ri.scale_max || 5, '#2f6f3e') +
            '<p class="xarr-muted" style="text-align:center">' + esc(a.leadership_readiness_narrative || '') + '</p>' +
            trendLine(leadership.score, leadership.prior, 'vs last year') +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Development Trends™</h4>' +
            (devTrends.participation_rate !== null && devTrends.participation_rate !== undefined ?
                '<div style="font-size:2.2rem;font-weight:800;color:var(--navy)">' + devTrends.participation_rate + '%</div>' +
                '<p class="xarr-muted" style="margin-top:-.3rem">Participation Rate</p>' +
                trendLine(devTrends.participation_rate, devTrends.prior_rate, 'pt vs last year') +
                '<p class="xarr-muted" style="font-size:12px">' + esc(a.development_trends_narrative || '') + '</p>'
                : '<p class="xarr-muted">No activity/tool participation data found yet.</p>') +
            '</div>' +
            '</div>' +

            '<div class="xarr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            '<div class="xarr-card" style="margin-bottom:0"><h4>Readiness Progress™</h4>' +
            notAvailable('No quarterly-interval readiness history is tracked anywhere in FUSION yet; only a single current value per year is available.') +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Strategic Risks™</h4>' +
            ((a.strategic_risks || []).length ? a.strategic_risks.map(function (r) {
                return '<div class="xarr-stat-row" style="color:#dc2626"><span>&#9888;&#65039;</span>' + esc(r) + '</div>';
            }).join('') : '<p class="xarr-muted">Generate the AI assessment to see strategic risks.</p>') +
            '</div>' +

            '<div class="xarr-card" style="margin-bottom:0"><h4>Strategic Opportunities™</h4>' +
            ((a.strategic_opportunities || []).length ? a.strategic_opportunities.map(function (r) {
                return '<div class="xarr-stat-row" style="color:#16a34a"><span class="xarr-check" style="margin-top:0">&#10003;</span>' + esc(r) + '</div>';
            }).join('') : '<p class="xarr-muted">Generate the AI assessment to see strategic opportunities.</p>') +
            '</div>' +
            '</div>' +

            '<div class="xarr-card"><h4>Emerging Organizational Themes™</h4>' +
            ((a.emerging_themes || []).length ?
                '<div class="xarr-pattern-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">' +
                a.emerging_themes.map(function (t, i) { return themeCard(THEME_ICONS[i % THEME_ICONS.length], t.title, t.description); }).join('') +
                '</div>'
                : '<p class="xarr-muted">Generate the AI assessment to see emerging themes.</p>') +
            '</div>';

        var agreementInputs = document.querySelectorAll('input[name="xarr-agreement"]');
        agreementInputs.forEach(function (el) {
            el.checked = (a.agreement_rating === el.value) || (data.agreement_rating === el.value);
        });
        var ctx = document.getElementById('xarr-strategic-context');
        var count = document.getElementById('xarr-context-count');
        if (ctx) {
            ctx.value = data.executive_context || '';
            if (count) count.textContent = ctx.value.length + ' / 2000 characters';
        }
    }

    window.initAssessmentStep = function () {
        var body = document.getElementById('xarr-assessment-body');
        var btn = document.getElementById('xarr-generate-assessment-btn');
        var statusEl = document.getElementById('xarr-assessment-status');
        if (!body) return;

        if (window.XFARR_WIZARD && window.XFARR_WIZARD.canEdit === false && btn) {
            btn.style.display = 'none';
        }

        function load() {
            if (typeof window.xfarrLoadAssessment !== 'function') {
                body.innerHTML = '<p class="xarr-muted">Assessment service unavailable.</p>';
                return;
            }
            if (statusEl) statusEl.textContent = 'Loading assessment…';
            window.xfarrLoadAssessment().then(function (data) {
                if (!data) {
                    body.innerHTML = '<p class="xarr-muted">No assessment generated yet. Click Generate AI Assessment above.</p>';
                    if (statusEl) statusEl.textContent = '';
                    return;
                }
                render(body, data);
                if (statusEl) statusEl.textContent = 'Assessment generated ' + (data.created_at ? new Date(data.created_at).toLocaleString() : '') + (data.insight_model ? ' · ' + data.insight_model : '');
            });
        }

        load();

        if (btn && !btn.dataset.wired) {
            btn.dataset.wired = '1';
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1' || typeof window.xfarrGenerateAssessment !== 'function') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.textContent = 'Generating…';
                if (statusEl) statusEl.textContent = 'Analyzing a full year of organizational evidence. This may take up to a minute.';
                window.xfarrGenerateAssessment().then(function (res) {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate AI Assessment';
                    if (!res || !res.success) {
                        if (statusEl) statusEl.textContent = (res && res.message) ? res.message : 'Failed to generate assessment.';
                        return;
                    }
                    render(body, res.data);
                    if (statusEl) statusEl.textContent = '✓ Assessment generated.';
                }).catch(function () {
                    btn.disabled = false;
                    btn.dataset.busy = '';
                    btn.textContent = 'Generate AI Assessment';
                    if (statusEl) statusEl.textContent = 'Failed to generate assessment.';
                });
            });
        }

        wireAgreementForm();
    };

    function wireAgreementForm() {
        var saveAgreement = document.getElementById('xarr-save-agreement');
        if (saveAgreement && !saveAgreement.dataset.wired) {
            saveAgreement.dataset.wired = '1';
            saveAgreement.addEventListener('click', function () {
                var checked = document.querySelector('input[name="xarr-agreement"]:checked');
                if (!checked || typeof window.xfarrSaveAssessmentAgreement !== 'function') return;
                saveAgreement.disabled = true;
                window.xfarrSaveAssessmentAgreement(checked.value).then(function (res) {
                    saveAgreement.disabled = false;
                    saveAgreement.textContent = (res && res.success) ? 'Saved ✓' : 'Failed — try again';
                    window.setTimeout(function () { saveAgreement.textContent = 'Save Agreement'; }, 1500);
                }).catch(function () {
                    saveAgreement.disabled = false;
                    saveAgreement.textContent = 'Failed — try again';
                    window.setTimeout(function () { saveAgreement.textContent = 'Save Agreement'; }, 1500);
                });
            });
        }
        var ctx = document.getElementById('xarr-strategic-context');
        var count = document.getElementById('xarr-context-count');
        if (ctx && count && !ctx.dataset.wired) {
            ctx.dataset.wired = '1';
            ctx.addEventListener('input', function () { count.textContent = ctx.value.length + ' / 2000 characters'; });
        }
        var saveContext = document.getElementById('xarr-save-context');
        if (saveContext && !saveContext.dataset.wired) {
            saveContext.dataset.wired = '1';
            saveContext.addEventListener('click', function () {
                if (typeof window.xfarrSaveAssessmentContext !== 'function') return;
                saveContext.disabled = true;
                window.xfarrSaveAssessmentContext(ctx ? ctx.value : '').then(function (res) {
                    saveContext.disabled = false;
                    saveContext.textContent = (res && res.success) ? 'Saved ✓' : 'Failed — try again';
                    window.setTimeout(function () { saveContext.textContent = 'Save Context'; }, 1500);
                }).catch(function () {
                    saveContext.disabled = false;
                    saveContext.textContent = 'Failed — try again';
                    window.setTimeout(function () { saveContext.textContent = 'Save Context'; }, 1500);
                });
            });
        }
    }
})();
JS;
}
