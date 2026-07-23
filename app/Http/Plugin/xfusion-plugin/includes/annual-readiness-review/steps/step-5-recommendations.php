<?php
/**
 * Step 5 — Strategic Renewal Recommendations™.
 *
 * UI-only prototype: static dummy content matching the ARR mockup. Starts
 * with 3 empty recommendation cards (matching the mockup) plus an Add
 * Recommendation button. No Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_recommendations_js(): string
{
    return <<<'JS'
recommendations: function () {
    return '<h2 class="xarr-section-title">Step 5. Strategic Renewal Recommendations™</h2>' +
        '<p class="xarr-section-desc">Based on your reflection and the AI assessment, define strategic recommendations to inform next year\'s Annual Readiness Plan™. These will populate the ARP as draft planning considerations.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>Add, edit, and prioritize recommendations below. Leadership will determine which become strategic priorities in the next ARP.</span></div>' +

        '<div class="xarr-row" style="justify-content:space-between;margin-bottom:.75rem">' +
        '<span class="xarr-muted" style="font-weight:800;text-transform:uppercase;font-size:14px;color:var(--navy)">Recommendations</span>' +
        '<button type="button" class="xarr-btn xarr-btn-outline" id="xarr-add-recommendation">+ Add Recommendation</button>' +
        '</div>' +
        '<div id="xarr-recommendations-list"></div>' +

        '<p class="xarr-muted" style="margin-top:.5rem">Recommendations added here will automatically populate the next Annual Readiness Plan™ as draft planning considerations.</p>';
}
JS;
}

function xfarr_wizard_recommendations_init_js(): string
{
    return <<<'JS'
(function () {
    var COR_CAPABILITIES = ['Alignment', 'Accountability', 'Communication', 'Leadership', 'Execution'];
    var DRIVERS = ['Get Real', 'Be Intentional', 'Fill Buckets', 'Foster Grit', 'Drive Growth'];
    var TIMELINES = ['Q1', 'Q2', 'Q3', 'Q4', 'Full Year', 'Multi-Year'];
    var STATUSES = ['Draft', 'Proposed', 'Accepted', 'Rejected'];
    var cache = [
        { recommendation: '', rationale: '', priority: '', owner: '', capability: '', driver: '', impact: '', timeline: '', status: '' },
        { recommendation: '', rationale: '', priority: '', owner: '', capability: '', driver: '', impact: '', timeline: '', status: '' },
        { recommendation: '', rationale: '', priority: '', owner: '', capability: '', driver: '', impact: '', timeline: '', status: '' },
    ];

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function selectOpts(opts, val) {
        return '<option value="">Select…</option>' + opts.map(function (o) {
            return '<option' + (val === o ? ' selected' : '') + '>' + o + '</option>';
        }).join('');
    }

    function card(i, c) {
        return '<div class="xarr-card" data-idx="' + i + '">' +
            '<div class="xarr-row" style="justify-content:space-between;margin-bottom:.5rem">' +
            '<strong style="color:var(--green);text-transform:uppercase;font-size:13px">Recommendation ' + (i + 1) + '</strong>' +
            '<div class="xarr-row" style="gap:.4rem">' +
            '<button type="button" class="xarr-icon-btn" data-dup="' + i + '" title="Duplicate">&#10697;</button>' +
            '<button type="button" class="xarr-icon-btn xarr-prio-delete" data-remove="' + i + '" title="Remove">&#10005;</button>' +
            '</div></div>' +
            '<div class="xarr-prio-grid xarr-prio-grid-4" style="grid-template-columns:1fr 1fr">' +
            '<div class="xarr-form-field"><label>Recommendation *</label><input class="xarr-input" data-f="recommendation" placeholder="Enter recommendation..." value="' + esc(c.recommendation) + '"></div>' +
            '<div class="xarr-form-field"><label>Business Rationale *</label><input class="xarr-input" data-f="rationale" placeholder="Why this recommendation is important..." value="' + esc(c.rationale) + '"></div>' +
            '</div>' +
            '<div class="xarr-prio-grid xarr-prio-grid-4">' +
            '<div class="xarr-form-field"><label>Priority *</label><select class="xarr-input" data-f="priority">' + selectOpts(['High','Medium','Low'], c.priority) + '</select></div>' +
            '<div class="xarr-form-field"><label>Executive Owner *</label><input class="xarr-input" data-f="owner" placeholder="Select executive owner" value="' + esc(c.owner) + '"></div>' +
            '<div class="xarr-form-field"><label>Related COR Capability™ *</label><select class="xarr-input" data-f="capability">' + selectOpts(COR_CAPABILITIES, c.capability) + '</select></div>' +
            '<div class="xarr-form-field"><label>Related Behavioral Driver™ *</label><select class="xarr-input" data-f="driver">' + selectOpts(DRIVERS, c.driver) + '</select></div>' +
            '</div>' +
            '<div class="xarr-prio-grid xarr-prio-grid-4">' +
            '<div class="xarr-form-field" style="grid-column:span 2"><label>Expected Organizational Impact *</label><input class="xarr-input" data-f="impact" placeholder="What impact will this create?" value="' + esc(c.impact) + '"></div>' +
            '<div class="xarr-form-field"><label>Recommended Timeline *</label><select class="xarr-input" data-f="timeline">' + selectOpts(TIMELINES, c.timeline) + '</select></div>' +
            '<div class="xarr-form-field"><label>Status *</label><select class="xarr-input" data-f="status">' + selectOpts(STATUSES, c.status) + '</select></div>' +
            '</div></div>';
    }

    function render() {
        var list = document.getElementById('xarr-recommendations-list');
        var addBtn = document.getElementById('xarr-add-recommendation');
        if (!list) return;
        list.innerHTML = cache.map(function (c, i) { return card(i, c); }).join('');

        list.querySelectorAll('[data-f]').forEach(function (el) {
            el.addEventListener('input', function () {
                var i = parseInt(el.closest('[data-idx]').dataset.idx, 10);
                cache[i][el.dataset.f] = el.value;
            });
            el.addEventListener('change', function () {
                var i = parseInt(el.closest('[data-idx]').dataset.idx, 10);
                cache[i][el.dataset.f] = el.value;
            });
        });
        list.querySelectorAll('[data-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                cache.splice(parseInt(btn.dataset.remove, 10), 1);
                render();
            });
        });
        list.querySelectorAll('[data-dup]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var i = parseInt(btn.dataset.dup, 10);
                cache.splice(i + 1, 0, Object.assign({}, cache[i]));
                render();
            });
        });

        if (addBtn) addBtn.disabled = false;
    }

    window.initRecommendationsStep = function () {
        var addBtn = document.getElementById('xarr-add-recommendation');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                cache.push({ recommendation: '', rationale: '', priority: '', owner: '', capability: '', driver: '', impact: '', timeline: '', status: '' });
                render();
            });
        }
        render();
    };
})();
JS;
}
