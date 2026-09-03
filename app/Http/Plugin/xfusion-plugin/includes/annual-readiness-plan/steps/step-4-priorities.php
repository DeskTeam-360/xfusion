<?php
/**
 * Step 4 — Strategic Priorities™ (repeatable initiative cards).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_priorities_js(): string
{
    return <<<'JS'
priorities: function () {
    return '<h2 class="xar-section-title">Step 4. Strategic Priorities™</h2>' +
        '<p class="xar-section-desc">Translate readiness priorities into executable strategic priorities. Assign ownership, target dates, success measures, and related groups.</p>' +
        '<div class="xar-add-row">' +
        '<a href="#" class="xar-add-link" id="xar-add-strategic">+ Add Strategic Priority</a>' +
        '</div>' +
        '<div class="xar-prio-list" id="xar-strategic-list"></div>';
}
JS;
}

function xfarp_wizard_strategic_init_js(): string
{
    return <<<'JS'
(function () {
    // Executive Owner options come from this ARP's company group roster
    // (Laravel /api/v1/arps/{arp}/group-members) - every member of the
    // group, not just leaders, and not a hardcoded name list. Multi-select:
    // a strategic priority can have more than one Executive Owner.
    var OWNERS = ((window.XFARP_WIZARD && window.XFARP_WIZARD.groupMembers) || []).map(function (m) {
        return { value: String(m.id), label: m.name || ('User #' + m.id) };
    });
    // Related Group(s) options are the real groups in this ARP's company
    // (Laravel /api/v1/arps/{arp}/groups) - replaces the old hardcoded
    // pseudo-scope list (all_leaders, operations, ...) that never actually
    // referenced a real wp_company_groups row.
    var GROUPS = ((window.XFARP_WIZARD && window.XFARP_WIZARD.companyGroups) || []).map(function (g) {
        return { value: String(g.id), label: g.name || ('Group #' + g.id) };
    });
    var ORG_KPIS = [
        { value: 'leadership_effectiveness', label: 'Leadership Effectiveness Index' },
        { value: 'employee_engagement', label: 'Employee Engagement Score' },
        { value: 'customer_nps', label: 'Customer NPS' },
        { value: 'on_time_delivery', label: 'On-Time Delivery Rate' },
        { value: 'revenue_growth', label: 'Revenue Growth' },
    ];
    var READINESS_INDICATORS = [
        { value: 'leadership_bench', label: 'Leadership Bench Strength' },
        { value: 'priority_clarity', label: 'Priority Clarity Score' },
        { value: 'commitment_completion', label: 'Commitment Completion Rate' },
        { value: 'cross_team_alignment', label: 'Cross-Team Alignment Index' },
        { value: 'execution_velocity', label: 'Execution Velocity' },
    ];

    function ensureCache() {
        if (!window.xarStrategicCache) {
            window.xarStrategicCache = [];
        }
        return window.xarStrategicCache;
    }

    function readinessOptions(selected) {
        var names = (window.xarReadinessCache || []).map(function (r) { return r.name; }).filter(Boolean);
        if (!names.length) {
            return '<option value="">No readiness priorities yet — add one in Step 3</option>';
        }
        var html = names.map(function (n) {
            return '<option value="' + escAttr(n) + '"' + (n === selected ? ' selected' : '') + '>' + escHtml(n) + '</option>';
        }).join('');
        if (selected && names.indexOf(selected) === -1) {
            html = '<option value="' + escAttr(selected) + '" selected>' + escHtml(selected) + '</option>' + html;
        }
        return html;
    }

    function opts(list, selected) {
        return list.map(function (o) {
            return '<option value="' + o.value + '"' + (o.value === selected ? ' selected' : '') + '>' + o.label + '</option>';
        }).join('');
    }

    function escAttr(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function field(label, required, control) {
        return '<div class="xar-form-field">' +
            '<label>' + label + (required ? ' <span class="xar-req">*</span>' : '') + '</label>' +
            control +
            '</div>';
    }

    /** A checkbox-list multi-select field, e.g. Executive Owner(s) / Related Group(s). */
    function multiCheckboxField(label, required, key, options, selected, emptyLabel) {
        var selectedSet = {};
        (Array.isArray(selected) ? selected : []).forEach(function (v) { selectedSet[String(v)] = true; });
        var body = options.length
            ? options.map(function (o) {
                var checked = selectedSet[String(o.value)] ? ' checked' : '';
                return '<label class="xar-multiselect-opt"><input type="checkbox" value="' + escAttr(o.value) + '"' + checked + '> ' + escHtml(o.label) + '</label>';
            }).join('')
            : '<div class="xar-multiselect-empty">' + escHtml(emptyLabel || 'No options available') + '</div>';

        return field(label, required, '<div class="xar-multiselect" data-key-array="' + key + '">' + body + '</div>');
    }

    function emptyItem() {
        var readiness = window.xarReadinessCache || [];
        return {
            title: '',
            related_readiness: readiness[0] ? readiness[0].name : '',
            executive_owner_user_ids: [],
            target_date: '',
            description: '',
            success_measures: '',
            org_kpi: 'leadership_effectiveness',
            readiness_indicator: 'leadership_bench',
            related_groups: [],
        };
    }

    function cardHtml(item, index) {
        return '<div class="xar-prio-card" data-index="' + index + '">' +
            '<div class="xar-prio-rail">' +
            '<span class="xar-drag" aria-hidden="true">⋮⋮</span>' +
            '<span class="xar-prio-num">' + (index + 1) + '</span>' +
            '</div>' +
            '<div class="xar-prio-body">' +
            '<a href="#" class="xar-icon-btn xar-prio-delete" data-index="' + index + '" aria-label="Delete strategic priority" role="button">' +
            '<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/trash-icon.svg" alt="" width="18" height="18">' +
            '</a>' +
            '<div class="xar-prio-grid xar-prio-grid-4">' +
            field('Title', true, '<input type="text" class="xar-input" data-key="title" value="' + escAttr(item.title) + '" placeholder="Enter strategic priority title...">') +
            field('Related Readiness Priority', true, '<select class="xar-input" data-key="related_readiness">' + readinessOptions(item.related_readiness) + '</select>') +
            field('Target Completion Date', true, '<input type="date" class="xar-input" data-key="target_date" value="' + escAttr(item.target_date) + '">') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-1">' +
            multiCheckboxField('Executive Owner(s)', true, 'executive_owner_user_ids', OWNERS, item.executive_owner_user_ids, 'No group members found') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-4">' +
            field('Description', false, '<textarea class="xar-input" rows="3" data-key="description" placeholder="Describe this strategic priority...">' + escHtml(item.description) + '</textarea>') +
            field('Success Measures', true, '<textarea class="xar-input" rows="3" data-key="success_measures" placeholder="How will success be measured?...">' + escHtml(item.success_measures) + '</textarea>') +
            field('Related Organizational KPI(s)', false, '<select class="xar-input" data-key="org_kpi">' + opts(ORG_KPIS, item.org_kpi) + '</select>') +
            field('Related Readiness Indicator(s)', false, '<select class="xar-input" data-key="readiness_indicator">' + opts(READINESS_INDICATORS, item.readiness_indicator) + '</select>') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-1">' +
            multiCheckboxField('Related Group(s)', false, 'related_groups', GROUPS, item.related_groups, 'No groups found in this company') +
            '</div>' +
            '</div></div>';
    }

    function collectFromDom(list) {
        var cards = list.querySelectorAll('.xar-prio-card');
        var next = [];
        cards.forEach(function (card) {
            var item = emptyItem();
            card.querySelectorAll('[data-key]').forEach(function (el) {
                item[el.getAttribute('data-key')] = el.value;
            });
            card.querySelectorAll('[data-key-array]').forEach(function (group) {
                var key = group.getAttribute('data-key-array');
                var checked = group.querySelectorAll('input[type="checkbox"]:checked');
                item[key] = Array.prototype.map.call(checked, function (cb) { return cb.value; });
            });
            next.push(item);
        });
        window.xarStrategicCache = next;
        return next;
    }

    function showLoading() {
        var list = document.getElementById('xar-strategic-list');
        if (!list) {
            return;
        }
        list.innerHTML = '<div class="xar-spinner-row"><span class="xar-spinner"></span> Loading strategic priorities…</div>';
    }

    function renderList() {
        var list = document.getElementById('xar-strategic-list');
        if (!list) {
            return;
        }
        var data = ensureCache();
        if (!data.length) {
            list.innerHTML = '<p class="xar-muted">No strategic priorities yet. Click "+ Add Strategic Priority" to create one.</p>';
            return;
        }
        list.innerHTML = data.map(cardHtml).join('');
        bindList(list);
    }

    function bindList(list) {
        list.querySelectorAll('[data-key]').forEach(function (el) {
            el.addEventListener('change', function () {
                collectFromDom(list);
            });
            el.addEventListener('input', function () {
                collectFromDom(list);
            });
        });
        list.querySelectorAll('[data-key-array] input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                collectFromDom(list);
            });
        });
        list.querySelectorAll('.xar-prio-delete').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var idx = parseInt(link.getAttribute('data-index'), 10);
                collectFromDom(list);
                window.xarStrategicCache.splice(idx, 1);
                renderList();
            });
        });
    }

    window.initStrategicStep = function () {
        var addBtn = document.getElementById('xar-add-strategic');
        if (addBtn) {
            addBtn.onclick = function (e) {
                e.preventDefault();
                var list = document.getElementById('xar-strategic-list');
                if (list) {
                    collectFromDom(list);
                }
                ensureCache().push(emptyItem());
                renderList();
            };
        }

        // Already loaded once this session — render from cache immediately,
        // no need to show a loading state again.
        if (window.xarStrategicLoaded) {
            renderList();
            return;
        }

        showLoading();
        if (typeof window.xarLoadStrategicDraft === 'function') {
            window.xarLoadStrategicDraft().then(function (items) {
                window.xarStrategicCache = items || [];
                window.xarStrategicLoaded = true;
                renderList();
            });
        } else {
            window.xarStrategicCache = [];
            window.xarStrategicLoaded = true;
            renderList();
        }
    };
})();
JS;
}
