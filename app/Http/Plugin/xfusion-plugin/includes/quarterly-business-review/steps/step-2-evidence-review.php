<?php
/**
 * Step 2 — Organizational Evidence™ (review dashboard).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_evidence_review_js(): string
{
    return <<<'JS'
evidence_review: function () {
    return '<h2 class="xqbr-section-title">Step 2. Organizational Evidence™</h2>' +
        '<p class="xqbr-section-desc">Review the objective evidence for the current review period. This data is pulled from across the platform and provides the factual foundation for leadership analysis and discussion.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>This is objective evidence only. Interpretation and assessment will be provided in Step 3 by AI.</span></div>' +
        '<div id="xqbr-evidence-review-body"><div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading organizational evidence…</div></div>';
}
JS;
}

function xfqbr_wizard_evidence_review_init_js(): string
{
    return <<<'JS'
(function () {
    function metricCard(label, value, unit, trend) {
        var trendHtml = trend ? '<div class="xqbr-metric-trend ' + trend + '">' +
            (trend === 'up' ? '&#8593;' : (trend === 'down' ? '&#8595;' : '&#8226;')) + ' vs last quarter</div>' : '';
        var valueHtml = value === null || value === undefined
            ? '<div class="xqbr-metric-value no-data">No data</div>'
            : '<div class="xqbr-metric-value">' + value + (unit ? '<span class="unit">' + unit + '</span>' : '') + '</div>';
        return '<div class="xqbr-metric-card"><div class="xqbr-metric-label">' + label + '</div>' + valueHtml + trendHtml + '</div>';
    }

    function kpiRow(kpi, index, canEdit) {
        var statusBadge = { on_track: 'green', at_risk: 'amber', off_track: 'red' }[kpi.status] || '';
        if (canEdit) {
            return '<tr data-index="' + index + '">' +
                '<td><input class="xqbr-input" data-key="name" value="' + escAttr(kpi.name) + '" placeholder="KPI name"></td>' +
                '<td><input class="xqbr-input" data-key="current_value" value="' + escAttr(kpi.current_value) + '" placeholder="Current"></td>' +
                '<td><input class="xqbr-input" data-key="target_value" value="' + escAttr(kpi.target_value) + '" placeholder="Target"></td>' +
                '<td><select class="xqbr-input" data-key="status"><option value="on_track"' + (kpi.status === 'on_track' ? ' selected' : '') + '>On track</option>' +
                '<option value="at_risk"' + (kpi.status === 'at_risk' ? ' selected' : '') + '>At risk</option>' +
                '<option value="off_track"' + (kpi.status === 'off_track' ? ' selected' : '') + '>Off track</option></select></td>' +
                '<td><a href="javascript:void(0)" class="xqbr-kpi-delete" data-index="' + index + '">Remove</a></td></tr>';
        }
        return '<tr><td>' + escHtml(kpi.name) + '</td><td>' + escHtml(kpi.current_value) + '</td><td>' + escHtml(kpi.target_value) + '</td>' +
            '<td><span class="xqbr-badge-pill ' + statusBadge + '">' + escHtml(kpi.status) + '</span></td><td></td></tr>';
    }

    function escHtml(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escAttr(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

    function render(evidence, kpis) {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var body = document.getElementById('xqbr-evidence-review-body');
        if (!body) return;

        if (!evidence) {
            body.innerHTML = '<p class="xqbr-muted">No evidence has been generated yet. Go back to Step 1 and click Generate Evidence.</p>';
            return;
        }

        var oo = evidence.one_on_one_completion || {};
        var assess = evidence.assessment_completion || {};
        var activity = evidence.activity_participation || {};
        var commit = evidence.commitment_completion || {};
        var objectives = evidence.qbr_objectives_progress || {};
        var readiness = evidence.readiness_indicators || {};

        var html = '<div class="xqbr-card"><h3 style="margin-top:0">Organizational Evidence Summary</h3>' +
            '<div class="xqbr-metric-grid">' +
            metricCard('Overall Readiness Score', evidence.overall_readiness_score, '/100', evidence.overall_readiness_trend) +
            metricCard('QBR Objectives Progress', objectives.progress, '%', null) +
            metricCard('Commitment Completion', commit.rate, '%', null) +
            metricCard('1-on-1 Completion Rate', oo.rate, '%', null) +
            metricCard('Activity Participation', activity.rate, '%', null) +
            metricCard('Assessment Completion', assess.rate, '%', null) +
            '</div></div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">KPI Summary (vs Target)</h3>' +
            (canEdit ? '<div class="xqbr-row" style="justify-content:flex-end;margin-bottom:.5rem"><a href="javascript:void(0)" class="xqbr-add-link" id="xqbr-kpi-add">+ Add KPI</a></div>' : '') +
            '<div class="xqbr-table-scroll"><table class="xqbr-table"><thead><tr><th>KPI</th><th>Current</th><th>Target</th><th>Status</th><th></th></tr></thead>' +
            '<tbody id="xqbr-kpi-tbody">' +
            (kpis.length ? kpis.map(function (k, i) { return kpiRow(k, i, canEdit); }).join('') : '<tr><td colspan="5" class="xqbr-muted">No KPIs recorded yet.</td></tr>') +
            '</tbody></table></div>' +
            (canEdit ? '<div class="xqbr-row" style="margin-top:.75rem"><button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-kpi-save">Save KPIs</button><span class="xqbr-muted" id="xqbr-kpi-save-status"></span></div>' : '') +
            '</div>' +

            '<div class="xqbr-card"><h3 style="margin-top:0">Readiness Indicators</h3>' +
            '<div class="xqbr-metric-grid">' +
            metricCard('People Readiness', readiness.people_readiness, '%', null) +
            metricCard('Process Readiness', readiness.process_readiness, '%', null) +
            metricCard('System Readiness', readiness.system_readiness, '%', null) +
            '</div></div>';

        body.innerHTML = html;

        function collectKpis() {
            var rows = [];
            document.querySelectorAll('#xqbr-kpi-tbody tr[data-index]').forEach(function (tr) {
                var row = {};
                tr.querySelectorAll('[data-key]').forEach(function (el) { row[el.getAttribute('data-key')] = el.value; });
                rows.push(row);
            });
            return rows;
        }

        var addLink = document.getElementById('xqbr-kpi-add');
        if (addLink) {
            addLink.addEventListener('click', function () {
                kpis = collectKpis();
                kpis.push({ name: '', current_value: '', target_value: '', status: 'on_track' });
                render(evidence, kpis);
            });
        }
        document.querySelectorAll('.xqbr-kpi-delete').forEach(function (link) {
            link.addEventListener('click', function () {
                kpis = collectKpis();
                kpis.splice(parseInt(link.getAttribute('data-index'), 10), 1);
                render(evidence, kpis);
            });
        });
        var saveBtn = document.getElementById('xqbr-kpi-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var statusEl = document.getElementById('xqbr-kpi-save-status');
                saveBtn.disabled = true;
                if (statusEl) statusEl.textContent = ' Saving…';
                window.xqbrSaveKpis(collectKpis()).then(function (res) {
                    saveBtn.disabled = false;
                    if (statusEl) statusEl.textContent = (res && res.success) ? ' Saved ' + res.saved_at : ' Save failed.';
                });
            });
        }
    }

    // TEMPORARY: render static dummy evidence while real aggregation is being
    // debugged — see the note in step-1-evidence.php. Save KPIs still calls
    // the real Laravel endpoint (harmless either way).
    var DUMMY_EVIDENCE = {
        overall_readiness_score: 72,
        overall_readiness_trend: 'up',
        qbr_objectives_progress: { progress: 68 },
        commitment_completion: { rate: 63 },
        one_on_one_completion: { rate: 81 },
        activity_participation: { rate: 76 },
        assessment_completion: { rate: 74 },
        readiness_indicators: { people_readiness: 71, process_readiness: 68, system_readiness: 76 },
    };
    var DUMMY_KPIS = [
        { name: 'Revenue Growth', current_value: '$4.2M', target_value: '$4.5M', status: 'at_risk' },
        { name: 'Customer Retention', current_value: '92%', target_value: '90%', status: 'on_track' },
        { name: 'Project On-Time Delivery', current_value: '88%', target_value: '90%', status: 'at_risk' },
        { name: 'Employee Engagement', current_value: '78%', target_value: '80%', status: 'at_risk' },
    ];

    window.initEvidenceReviewStep = function () {
        render(DUMMY_EVIDENCE, DUMMY_KPIS);
    };
})();
JS;
}
