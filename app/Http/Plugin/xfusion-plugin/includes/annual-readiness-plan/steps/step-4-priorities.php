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
    var OWNERS = [
        { value: 'james_scott', label: 'James Scott', initials: 'JS' },
        { value: 'maria_chen', label: 'Maria Chen', initials: 'MC' },
        { value: 'alex_rivera', label: 'Alex Rivera', initials: 'AR' },
    ];
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
    var GROUPS = [
        { value: 'all_leaders', label: 'All Leaders' },
        { value: 'operations', label: 'Operations' },
        { value: 'all_employees', label: 'All Employees' },
        { value: 'sales', label: 'Sales' },
        { value: 'product', label: 'Product' },
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

    function ownerOpts(selected) {
        return OWNERS.map(function (o) {
            return '<option value="' + o.value + '"' + (o.value === selected ? ' selected' : '') + '>' + o.label + '</option>';
        }).join('');
    }

    function ownerInitials(value) {
        var found = OWNERS.filter(function (o) { return o.value === value; })[0];
        return found ? found.initials : '—';
    }

    function groupOptions(selected) {
        if (Array.isArray(selected)) {
            selected = selected[0] || '';
        }
        return opts(GROUPS, selected || 'all_leaders');
    }

    function emptyItem() {
        var readiness = window.xarReadinessCache || [];
        return {
            title: '',
            related_readiness: readiness[0] ? readiness[0].name : '',
            executive_owner: 'james_scott',
            target_date: '',
            description: '',
            success_measures: '',
            org_kpi: 'leadership_effectiveness',
            readiness_indicator: 'leadership_bench',
            related_groups: 'all_leaders',
        };
    }

    function field(label, required, control) {
        return '<div class="xar-form-field">' +
            '<label>' + label + (required ? ' <span class="xar-req">*</span>' : '') + '</label>' +
            control +
            '</div>';
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
            field('Executive Owner', true,
                '<div class="xar-owner-field">' +
                '<span class="xar-avatar" data-owner-avatar>' + ownerInitials(item.executive_owner) + '</span>' +
                '<select class="xar-input" data-key="executive_owner">' + ownerOpts(item.executive_owner) + '</select>' +
                '</div>') +
            field('Target Completion Date', true, '<input type="date" class="xar-input" data-key="target_date" value="' + escAttr(item.target_date) + '">') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-4">' +
            field('Description', false, '<textarea class="xar-input" rows="3" data-key="description" placeholder="Describe this strategic priority...">' + escHtml(item.description) + '</textarea>') +
            field('Success Measures', true, '<textarea class="xar-input" rows="3" data-key="success_measures" placeholder="How will success be measured?...">' + escHtml(item.success_measures) + '</textarea>') +
            field('Related Organizational KPI(s)', false, '<select class="xar-input" data-key="org_kpi">' + opts(ORG_KPIS, item.org_kpi) + '</select>') +
            field('Related Readiness Indicator(s)', false, '<select class="xar-input" data-key="readiness_indicator">' + opts(READINESS_INDICATORS, item.readiness_indicator) + '</select>') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-4">' +
            field('Related Group(s)', false, '<select class="xar-input" data-key="related_groups">' + groupOptions(item.related_groups) + '</select>') +
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
                if (el.getAttribute('data-key') === 'executive_owner') {
                    var wrap = el.closest('.xar-owner-field');
                    if (wrap) {
                        var badge = wrap.querySelector('[data-owner-avatar]');
                        if (badge) {
                            badge.textContent = ownerInitials(el.value);
                        }
                    }
                }
            });
            el.addEventListener('input', function () {
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
