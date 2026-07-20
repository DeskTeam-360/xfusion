<?php
/**
 * Step 6 — AI Readiness Review™ (dynamic AI synthesis + leadership context).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_ai_review_js(): string
{
    return <<<'JS'
ai_review: function () {
    return '<h2 class="xar-section-title">Step 6. AI Readiness Review™</h2>' +
        '<p class="xar-section-desc">FUSION AI has analyzed your plan to evaluate strategic alignment, identify potential gaps, and highlight key areas of focus to strengthen organizational readiness.</p>' +
        '<div class="xar-banner">' +
        '<span class="xar-banner-icon" aria-hidden="true">ℹ️</span>' +
        '<span>This is AI-generated analysis based on the information you have provided in Steps 1-5. These insights are for consideration only. Leadership adds context in the section below.</span>' +
        '</div>' +
        '<div class="xar-card xar-ai-generate-bar" id="xar-ai-generate-bar">' +
        '<div class="xar-row" style="justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem">' +
        '<div>' +
        '<p class="xar-muted" style="margin:0" id="xar-ai-generate-hint">Generate AI insights from your completed Steps 1–5.</p>' +
        '<p class="xar-muted" style="margin:.35rem 0 0;font-size:13px" id="xar-ai-generated-meta"></p>' +
        '</div>' +
        '<button type="button" class="xar-btn xar-btn-accent" id="xar-ai-generate-btn">Generate AI Insights</button>' +
        '</div>' +
        '<p class="xar-muted" id="xar-ai-generate-status" style="margin:.75rem 0 0;display:none"></p>' +
        '</div>' +
        '<div id="xar-ai-insights-root"></div>' +
        '<div class="xar-card xar-ai-block" id="xar-leadership-context-card">' +
        '<h3 class="xar-ai-heading">Leadership Context™</h3>' +
        '<div class="xar-field" data-field="leadership_context">' +
        '<div class="xar-field-head">' +
        '<p class="xar-field-desc" style="margin:0">What additional strategic context should future leadership conversations consider throughout the year?</p>' +
        '<span class="xar-field-count">0 / 2000</span>' +
        '</div>' +
        '<textarea rows="5" placeholder="Enter your leadership context here..." data-maxlen="2000" data-key="leadership_context"></textarea>' +
        '</div></div>';
}
JS;
}

function xfarp_wizard_ai_review_init_js(): string
{
    return <<<'JS'
(function () {
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';

    function esc(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function donut(score, label, color) {
        var s = Math.max(0, Math.min(100, parseInt(score, 10) || 0));
        return '<div class="xar-donut-wrap">' +
            '<div class="xar-donut-chart">' +
            '<svg class="xar-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xar-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xar-donut-value" cx="18" cy="18" r="15.9155" stroke="' + esc(color) + '" ' +
            'stroke-dasharray="' + s + ' ' + (100 - s) + '"></circle>' +
            '</svg>' +
            '<div class="xar-donut-center">' +
            '<div class="xar-donut-score">' + s + ' <span>of 100</span></div>' +
            '</div></div>' +
            '<div class="xar-donut-label">' + esc(label) + '</div>' +
            '</div>';
    }

    function checkItem(text) {
        return '<li><span class="xar-check" aria-hidden="true">&#10003;</span><span>' + esc(text) + '</span></li>';
    }

    function gapRow(area, desc, impact, priority) {
        var impactCls = impact === 'High' ? 'high' : (impact === 'Low' ? 'low' : 'medium');
        var prioCls = priority === 'High' ? 'high' : (priority === 'Low' ? 'low' : 'medium');
        return '<tr>' +
            '<td class="xar-gap-area"><strong>' + esc(area) + '</strong></td>' +
            '<td class="xar-gap-desc">' + esc(desc) + '</td>' +
            '<td class="xar-gap-impact"><span class="xar-impact ' + impactCls + '"><span class="xar-dot"></span>' + esc(impact) + '</span></td>' +
            '<td class="xar-gap-priority"><span class="xar-badge-pill ' + prioCls + '">' + esc(priority) + '</span></td>' +
            '</tr>';
    }

    function alignBar(label, pct) {
        var p = Math.max(0, Math.min(100, parseInt(pct, 10) || 0));
        return '<div class="xar-align-row xar-progress-row">' +
            '<span class="xar-align-label">' + esc(label) + '</span>' +
            '<div class="xar-progress-track"><div class="xar-progress-fill" style="width:' + p + '%"></div></div>' +
            '<strong class="xar-progress-pct">' + p + '%</strong>' +
            '</div>';
    }

    function riskCard(count, title, desc, tone, iconSvg) {
        return '<div class="xar-risk-card ' + tone + '">' +
            '<div class="xar-risk-icon" aria-hidden="true">' + iconSvg + '</div>' +
            '<div><div class="xar-risk-title"><strong>' + count + '</strong> ' + esc(title) + '</div>' +
            '<p class="xar-muted">' + esc(desc) + '</p></div></div>';
    }

    function focusItem(text) {
        return '<div class="xar-focus-item">' +
            '<img src="' + iconBase + 'Arrow-on-Target-Icon.svg" alt="" width="36" height="36">' +
            '<span>' + esc(text) + '</span></div>';
    }

    var shield = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z"/><path d="M12 8v5M12 16h.01"/></svg>';
    var triangle = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l10 18H2L12 3z"/><path d="M12 9v5M12 17h.01"/></svg>';
    var flag = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/></svg>';
    var check = '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>';

    window.xarRenderAiAssessment = function (assessment) {
        if (!assessment || typeof assessment !== 'object') {
            return '';
        }

        var sa = assessment.strategic_alignment || {};
        var ra = assessment.readiness_assessment || {};
        var pa = assessment.priority_alignment || {};
        var rs = assessment.risk_summary || {};
        var gaps = Array.isArray(assessment.gaps) ? assessment.gaps : [];
        var focus = Array.isArray(assessment.focus_areas) ? assessment.focus_areas : [];

        var strengthsHtml = (Array.isArray(sa.strengths) ? sa.strengths : [])
            .map(checkItem)
            .join('');

        var gapsHtml = gaps.map(function (g) {
            return gapRow(g.area, g.description, g.impact, g.priority);
        }).join('');

        var dimsHtml = (Array.isArray(pa.dimensions) ? pa.dimensions : [])
            .map(function (d) { return alignBar(d.label, d.percent); })
            .join('');

        var focusHtml = focus.map(focusItem).join('');

        return '<div class="xar-card xar-ai-block">' +
            '<h3 class="xar-ai-heading">6.1 Strategic Alignment Summary™</h3>' +
            '<div class="xar-ai-split">' +
            donut(sa.score, sa.label, sa.color) +
            '<div class="xar-ai-copy">' +
            '<p>' + esc(sa.summary) + '</p>' +
            (strengthsHtml ? '<ul class="xar-check-list">' + strengthsHtml + '</ul>' : '') +
            '</div></div></div>' +

            '<div class="xar-card xar-ai-block">' +
            '<h3 class="xar-ai-heading">6.2 Organizational Readiness Assessment™</h3>' +
            '<div class="xar-ai-split">' +
            donut(ra.score, ra.label, ra.color) +
            '<div class="xar-ai-copy">' +
            '<p>' + esc(ra.summary) + '</p>' +
            '<div class="xar-stat-list">' +
            '<div class="xar-stat-row"><span class="xar-dot green"></span><span>Strengths</span><strong>' + (ra.strengths_count || 0) + ' identified</strong></div>' +
            '<div class="xar-stat-row"><span class="xar-dot amber"></span><span>Areas for Development</span><strong>' + (ra.development_count || 0) + ' identified</strong></div>' +
            '<div class="xar-stat-row"><span class="xar-dot red"></span><span>Critical Gaps</span><strong>' + (ra.critical_gaps_count || 0) + ' identified</strong></div>' +
            '</div></div></div></div>' +

            (gaps.length ? (
                '<div class="xar-card xar-ai-block">' +
                '<h3 class="xar-ai-heading">6.3 Potential Gaps™</h3>' +
                '<div class="xar-table-scroll"><table class="xar-table xar-table-gaps">' +
                '<thead><tr><th>Gap Area</th><th class="xar-gap-desc-col" aria-hidden="true"></th><th>Impact</th><th>Priority</th></tr></thead><tbody>' +
                gapsHtml +
                '</tbody></table></div></div>'
            ) : '') +

            '<div class="xar-card xar-ai-block">' +
            '<h3 class="xar-ai-heading">6.4 Priority Alignment™</h3>' +
            '<div class="xar-ai-split">' +
            donut(pa.score, pa.label, pa.color) +
            '<div class="xar-ai-copy">' +
            '<p>' + esc(pa.summary) + '</p>' +
            (dimsHtml ? '<div class="xar-align-list">' + dimsHtml + '</div>' : '') +
            '</div></div></div>' +

            '<div class="xar-card xar-ai-block">' +
            '<h3 class="xar-ai-heading">6.5 Risk Summary™</h3>' +
            '<div class="xar-risk-grid">' +
            riskCard(rs.high || 0, 'High Risk', 'Require immediate leadership attention.', 'high', shield) +
            riskCard(rs.medium || 0, 'Medium Risk', 'Require proactive management.', 'medium', triangle) +
            riskCard(rs.low || 0, 'Low Risk', 'Monitor and manage as planned.', 'low', flag) +
            riskCard(rs.strengths || 0, 'Strengths', 'Positive factors supporting success.', 'strength', check) +
            '</div></div>' +

            (focus.length ? (
                '<div class="xar-card xar-ai-block">' +
                '<h3 class="xar-ai-heading">6.6 Suggested Areas of Focus™</h3>' +
                '<div class="xar-focus-list">' + focusHtml + '</div></div>'
            ) : '');
    };

    window.xarApplyAiReviewData = function (data) {
        var root = document.getElementById('xar-ai-insights-root');
        var btn = document.getElementById('xar-ai-generate-btn');
        var meta = document.getElementById('xar-ai-generated-meta');
        var hint = document.getElementById('xar-ai-generate-hint');
        var ta = document.querySelector('#xar-leadership-context-card textarea[data-key="leadership_context"]');
        var canEdit = window.XFARP_WIZARD && window.XFARP_WIZARD.canEdit;

        if (!root || !btn) {
            return;
        }

        var hasAssessment = !!(data && data.has_assessment && data.assessment);
        root.innerHTML = hasAssessment ? window.xarRenderAiAssessment(data.assessment) : '';

        btn.textContent = hasAssessment ? 'Regenerate AI Insights' : 'Generate AI Insights';
        btn.disabled = !canEdit;

        if (hint) {
            hint.textContent = hasAssessment
                ? 'Review the AI analysis below. Regenerate after updating Steps 1–5.'
                : 'Generate AI insights from your completed Steps 1–5.';
        }

        if (meta) {
            if (hasAssessment && data.generated_at) {
                var when = new Date(data.generated_at);
                meta.textContent = 'Last generated' + (data.insight_model ? ' (' + data.insight_model + ')' : '') +
                    ': ' + (isNaN(when.getTime()) ? data.generated_at : when.toLocaleString());
            } else {
                meta.textContent = '';
            }
        }

        if (ta) {
            ta.value = (data && data.leadership_context) ? data.leadership_context : '';
            ta.readOnly = !canEdit;
            window.xarLeadershipContext = ta.value;
            var counter = ta.closest('.xar-field');
            if (counter) {
                var countEl = counter.querySelector('.xar-field-count');
                if (countEl) {
                    countEl.textContent = ta.value.length + ' / 2000';
                }
            }
        }
    };

    window.initAiReviewStep = function () {
        var btn = document.getElementById('xar-ai-generate-btn');
        var statusEl = document.getElementById('xar-ai-generate-status');
        var ta = document.querySelector('#xar-leadership-context-card textarea[data-key="leadership_context"]');
        var generateBusy = false;

        window.xarApplyAiReviewData(window.xarAiReviewCache || { has_assessment: false, leadership_context: '' });

        if (typeof window.xarLoadAiReview === 'function') {
            window.xarLoadAiReview().then(function (data) {
                if (data) {
                    window.xarApplyAiReviewData(data);
                }
            });
        }

        if (ta) {
            ta.addEventListener('input', function () {
                window.xarLeadershipContext = ta.value;
            });
        }

        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            if (generateBusy || !window.XFARP_WIZARD || !window.XFARP_WIZARD.canEdit) {
                return;
            }
            if (typeof window.xarGenerateAiReview !== 'function') {
                return;
            }

            generateBusy = true;
            btn.disabled = true;
            if (statusEl) {
                statusEl.style.display = 'block';
                statusEl.textContent = 'Generating AI insights from Steps 1–5… This may take up to a minute.';
                statusEl.style.color = '';
            }

            window.xarGenerateAiReview()
                .then(function (json) {
                    if (!json || !json.success || !json.data) {
                        var msg = (json && json.message) ? json.message : 'AI generation failed.';
                        if (statusEl) {
                            statusEl.textContent = msg;
                            statusEl.style.color = '#dc2626';
                        }
                        return;
                    }
                    window.xarAiReviewCache = json.data;
                    window.xarApplyAiReviewData(json.data);
                    if (statusEl) {
                        statusEl.textContent = 'AI insights generated successfully.';
                        statusEl.style.color = '#5f9a3f';
                    }
                })
                .catch(function () {
                    if (statusEl) {
                        statusEl.textContent = 'AI generation failed — network error.';
                        statusEl.style.color = '#dc2626';
                    }
                })
                .finally(function () {
                    generateBusy = false;
                    btn.disabled = !window.XFARP_WIZARD.canEdit;
                });
        });
    };
})();
JS;
}
