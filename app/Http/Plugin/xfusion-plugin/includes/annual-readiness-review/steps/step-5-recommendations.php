<?php
/**
 * Step 5 — Strategic Renewal Recommendations™.
 *
 * Real: loads/saves via Laravel (wp_fusion_arr_renewal_recommendations,
 * replace-all semantics on save — same pattern as IRR/QBR/ARP
 * commitments). Starts with a single empty recommendation card by default
 * (more can be added with "+ Add Recommendation"; no hard cap). Saved by
 * the wizard's real Save Draft button via window.xarrSaveRecommendationsStep,
 * dispatched from arr-save-draft.php.
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
        '<p class="xarr-muted" id="xarr-recommendations-status" style="margin:-.4rem 0 .6rem"></p>' +

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
    var COR_CAPABILITIES = { alignment: 'Alignment', accountability: 'Accountability', communication: 'Communication', leadership: 'Leadership', execution: 'Execution' };
    var DRIVERS = { get_real: 'Get Real', be_intentional: 'Be Intentional', fill_buckets: 'Fill Buckets', foster_grit: 'Foster Grit', drive_growth: 'Drive Growth' };
    var TIMELINES = { q1: 'Q1', q2: 'Q2', q3: 'Q3', q4: 'Q4', fy: 'Full Year', multi_year: 'Multi-Year' };
    var STATUSES = { proposed: 'Proposed', accepted: 'Accepted', rejected: 'Rejected', carried_to_arp: 'Carried to ARP' };
    // Executive Owner options come from this ARR's company-wide roster
    // (Laravel /api/v1/arrs/{arr}/group-members) — not free text.
    var OWNERS = ((window.XFARR_WIZARD && window.XFARR_WIZARD.groupMembers) || []).map(function (m) {
        return String(m.id);
    });
    var OWNER_LABELS = {};
    ((window.XFARR_WIZARD && window.XFARR_WIZARD.groupMembers) || []).forEach(function (m) {
        OWNER_LABELS[String(m.id)] = m.name || ('User #' + m.id);
    });

    function blankItem() {
        return { title: '', description: '', priority: '', executive_owner_user_id: '', cor_capability: '', behavioral_driver: '', expected_organizational_impact: '', recommended_timeline: '', status: '' };
    }

    var cache = [blankItem()];
    var loaded = false;

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function selectOpts(map, val) {
        return '<option value="">Select…</option>' + Object.keys(map).map(function (k) {
            return '<option value="' + k + '"' + (String(val) === k ? ' selected' : '') + '>' + map[k] + '</option>';
        }).join('');
    }

    function ownerOpts(selected) {
        var blank = '<option value="">' + (OWNERS.length ? 'Select executive owner…' : 'No group members found') + '</option>';
        return blank + OWNERS.map(function (id) {
            return '<option value="' + id + '"' + (String(selected) === id ? ' selected' : '') + '>' + esc(OWNER_LABELS[id]) + '</option>';
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
            '<div class="xarr-form-field"><label>Recommendation *</label><input class="xarr-input" data-f="title" placeholder="Enter recommendation..." value="' + esc(c.title) + '"></div>' +
            '<div class="xarr-form-field"><label>Business Rationale *</label><input class="xarr-input" data-f="description" placeholder="Why this recommendation is important..." value="' + esc(c.description) + '"></div>' +
            '</div>' +
            '<div class="xarr-prio-grid xarr-prio-grid-4">' +
            '<div class="xarr-form-field"><label>Priority *</label><select class="xarr-input" data-f="priority">' + selectOpts({ high: 'High', medium: 'Medium', low: 'Low' }, c.priority) + '</select></div>' +
            '<div class="xarr-form-field"><label>Executive Owner *</label><select class="xarr-input" data-f="executive_owner_user_id">' + ownerOpts(c.executive_owner_user_id) + '</select></div>' +
            '<div class="xarr-form-field"><label>Related COR Capability™ *</label><select class="xarr-input" data-f="cor_capability">' + selectOpts(COR_CAPABILITIES, c.cor_capability) + '</select></div>' +
            '<div class="xarr-form-field"><label>Related Behavioral Driver™ *</label><select class="xarr-input" data-f="behavioral_driver">' + selectOpts(DRIVERS, c.behavioral_driver) + '</select></div>' +
            '</div>' +
            '<div class="xarr-prio-grid xarr-prio-grid-4">' +
            '<div class="xarr-form-field" style="grid-column:span 2"><label>Expected Organizational Impact *</label><input class="xarr-input" data-f="expected_organizational_impact" placeholder="What impact will this create?" value="' + esc(c.expected_organizational_impact) + '"></div>' +
            '<div class="xarr-form-field"><label>Recommended Timeline *</label><select class="xarr-input" data-f="recommended_timeline">' + selectOpts(TIMELINES, c.recommended_timeline) + '</select></div>' +
            '<div class="xarr-form-field"><label>Status *</label><select class="xarr-input" data-f="status">' + selectOpts(STATUSES, c.status) + '</select></div>' +
            '</div></div>';
    }

    function render() {
        var list = document.getElementById('xarr-recommendations-list');
        if (!list) return;
        list.innerHTML = cache.map(function (c, i) { return card(i, c); }).join('');

        list.querySelectorAll('[data-f]').forEach(function (el) {
            var handler = function () {
                var i = parseInt(el.closest('[data-idx]').dataset.idx, 10);
                cache[i][el.dataset.f] = el.value;
            };
            el.addEventListener('input', handler);
            el.addEventListener('change', handler);
        });
        list.querySelectorAll('[data-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (cache.length <= 1) return;
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
    }

    window.xarrSaveRecommendationsStep = function () {
        if (typeof window.xfarrSaveRecommendations !== 'function') {
            return Promise.resolve({ success: false, message: 'Recommendations service unavailable.' });
        }
        return window.xfarrSaveRecommendations(cache);
    };

    window.initRecommendationsStep = function () {
        var addBtn = document.getElementById('xarr-add-recommendation');
        var statusEl = document.getElementById('xarr-recommendations-status');
        if (window.XFARR_WIZARD && window.XFARR_WIZARD.canEdit === false && addBtn) {
            addBtn.style.display = 'none';
        }
        if (addBtn && !addBtn.dataset.wired) {
            addBtn.dataset.wired = '1';
            addBtn.addEventListener('click', function () {
                cache.push(blankItem());
                render();
            });
        }

        if (loaded || typeof window.xfarrLoadRecommendations !== 'function') {
            render();
            return;
        }

        if (statusEl) statusEl.textContent = 'Loading recommendations…';
        window.xfarrLoadRecommendations().then(function (data) {
            loaded = true;
            if (Array.isArray(data) && data.length > 0) {
                cache = data.map(function (d) {
                    return Object.assign(blankItem(), d, { executive_owner_user_id: d.executive_owner_user_id ? String(d.executive_owner_user_id) : '' });
                });
            }
            render();
            if (statusEl) statusEl.textContent = '';
        }).catch(function () {
            loaded = true;
            render();
        });
    };
})();
JS;
}
