<?php
/**
 * Step 4 — Key Performance Indicators™ (repeatable KPI cards).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_kpis_js(): string
{
    return <<<'JS'
kpis: function () {
    return '<h2 class="xar-section-title">Step 4. Key Performance Indicators™ (KPIs)</h2>' +
        '<p class="xar-section-desc">Define the key performance indicators that will measure progress toward your readiness priorities and strategic priorities. Each KPI can be linked to multiple Readiness Priorities and will cascade to be selected within Strategic Priorities.</p>' +
        '<div class="xar-add-row">' +
        '<a href="#" class="xar-add-link" id="xar-add-kpi">+ Add KPI</a>' +
        '</div>' +
        '<div class="xar-prio-list" id="xar-kpi-list"></div>';
}
JS;
}

function xfarp_wizard_kpis_init_js(): string
{
    return <<<'JS'
(function () {
    var KPI_TYPES = [
        { value: 'leading', label: 'Leading Indicator' },
        { value: 'trailing', label: 'Trailing Indicator' },
    ];
    var FREQUENCIES = [
        { value: 'daily', label: 'Daily' },
        { value: 'weekly', label: 'Weekly' },
        { value: 'monthly', label: 'Monthly' },
        { value: 'quarterly', label: 'Quarterly' },
        { value: 'annually', label: 'Annually' },
    ];
    // Owner options come from this ARP's company group roster, same source
    // as Step 3's Executive Owner(s).
    var OWNERS = ((window.XFARP_WIZARD && window.XFARP_WIZARD.groupMembers) || []).map(function (m) {
        return { value: String(m.id), label: m.name || ('User #' + m.id) };
    });

    function ensureCache() {
        if (!window.xarKpiCache) {
            window.xarKpiCache = [];
        }
        return window.xarKpiCache;
    }

    function readinessOptions() {
        return (window.xarReadinessCache || []).map(function (r) {
            return { value: String(r.id), label: r.name || ('Priority #' + r.id) };
        }).filter(function (o) { return o.value && o.value !== 'undefined'; });
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
        return {
            name: '',
            type: 'leading',
            description: '',
            why_it_matters: '',
            current_baseline: '',
            target_value: '',
            target_date: '',
            measurement_frequency: 'quarterly',
            data_source: '',
            owner_user_id: '',
            readiness_priority_ids: [],
            notes: '',
        };
    }

    function cardHtml(item, index) {
        return '<div class="xar-prio-card" data-index="' + index + '">' +
            '<div class="xar-prio-rail">' +
            '<span class="xar-drag" aria-hidden="true">⋮⋮</span>' +
            '<span class="xar-prio-num">' + (index + 1) + '</span>' +
            '</div>' +
            '<div class="xar-prio-body">' +
            '<a href="#" class="xar-icon-btn xar-prio-delete" data-index="' + index + '" aria-label="Delete KPI" role="button">' +
            '<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/trash-icon.svg" alt="" width="18" height="18">' +
            '</a>' +
            '<div class="xar-prio-grid xar-prio-grid-2">' +
            field('KPI Name', true, '<input type="text" class="xar-input" data-key="name" value="' + escAttr(item.name) + '" placeholder="Enter KPI name...">') +
            field('KPI Type', true, '<select class="xar-input" data-key="type">' + opts(KPI_TYPES, item.type) + '</select>') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-2">' +
            field('Description', true, '<textarea class="xar-input" rows="3" data-key="description" placeholder="Describe what this KPI measures...">' + escHtml(String(item.description || '').trim()) + '</textarea>') +
            field('Why It Matters', true, '<textarea class="xar-input" rows="3" data-key="why_it_matters" placeholder="Why does this KPI matter?...">' + escHtml(String(item.why_it_matters || '').trim()) + '</textarea>') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-3">' +
            field('Current Baseline', true, '<input type="text" class="xar-input" data-key="current_baseline" value="' + escAttr(item.current_baseline) + '">') +
            field('Target Value', true, '<input type="text" class="xar-input" data-key="target_value" value="' + escAttr(item.target_value) + '">') +
            field('Target Date', true, '<input type="date" class="xar-input" data-key="target_date" value="' + escAttr(item.target_date) + '">') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-3">' +
            field('Measurement Frequency', true, '<select class="xar-input" data-key="measurement_frequency">' + opts(FREQUENCIES, item.measurement_frequency) + '</select>') +
            field('Data Source', true, '<input type="text" class="xar-input" data-key="data_source" value="' + escAttr(item.data_source) + '" placeholder="e.g. CultureAmp, Asana, Finance System">') +
            field('Owner', true, '<select class="xar-input" data-key="owner_user_id">' + opts([{ value: '', label: '— Select owner —' }].concat(OWNERS), item.owner_user_id) + '</select>') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-2">' +
            multiCheckboxField('Link to Readiness Priorities', true, 'readiness_priority_ids', readinessOptions(), item.readiness_priority_ids, 'Add Readiness Priorities in Step 3 first') +
            field('Notes / Additional Context', false, '<textarea class="xar-input" rows="3" data-key="notes" placeholder="Any additional context...">' + escHtml(String(item.notes || '').trim()) + '</textarea>') +
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
        window.xarKpiCache = next;
        return next;
    }

    function showLoading() {
        var list = document.getElementById('xar-kpi-list');
        if (!list) {
            return;
        }
        list.innerHTML = '<div class="xar-spinner-row"><span class="xar-spinner"></span> Loading KPIs…</div>';
    }

    function renderList() {
        var list = document.getElementById('xar-kpi-list');
        if (!list) {
            return;
        }
        var data = ensureCache();
        if (!data.length) {
            list.innerHTML = '<p class="xar-muted">No KPIs yet. Click "+ Add KPI" to create one.</p>';
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
                window.xarKpiCache.splice(idx, 1);
                renderList();
            });
        });
    }

    window.initKpiStep = function () {
        var addBtn = document.getElementById('xar-add-kpi');
        if (addBtn) {
            addBtn.onclick = function (e) {
                e.preventDefault();
                var list = document.getElementById('xar-kpi-list');
                if (list) {
                    collectFromDom(list);
                }
                ensureCache().push(emptyItem());
                renderList();
            };
        }

        if (window.xarKpiLoaded) {
            renderList();
            return;
        }

        showLoading();

        // Readiness Priorities must be loaded first so the "Link to
        // Readiness Priorities" checkboxes have real options.
        var readinessPromise = (window.xarReadinessLoaded && window.xarReadinessCache)
            ? Promise.resolve(window.xarReadinessCache)
            : (typeof window.xarLoadReadinessDraft === 'function' ? window.xarLoadReadinessDraft() : Promise.resolve([]));

        readinessPromise.then(function (readinessItems) {
            if (!window.xarReadinessLoaded) {
                window.xarReadinessCache = readinessItems || [];
                window.xarReadinessLoaded = true;
            }

            if (typeof window.xarLoadKpiDraft === 'function') {
                return window.xarLoadKpiDraft();
            }
            return [];
        }).then(function (items) {
            window.xarKpiCache = items || [];
            window.xarKpiLoaded = true;
            renderList();
        });
    };
})();
JS;
}
