<?php
/**
 * Step 3 — Organizational Readiness™ (repeatable readiness priority cards).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_readiness_js(): string
{
    return <<<'JS'
readiness: function () {
    return '<h2 class="xar-section-title">Step 3. Organizational Readiness™</h2>' +
        '<p class="xar-section-desc">Define the capabilities your organization must strengthen to achieve the future state. Each readiness priority connects to a COR Capability™ and Behavioral Drivers™.</p>' +
        '<div class="xar-add-row">' +
        '<a href="#" class="xar-add-link" id="xar-add-readiness">+ Add Priority</a>' +
        '</div>' +
        '<div class="xar-prio-list" id="xar-readiness-list"></div>';
}
JS;
}

function xfarp_wizard_readiness_init_js(): string
{
    return <<<'JS'
(function () {
    var COR_CAPABILITIES = [
        { value: 'alignment', label: 'Alignment' },
        { value: 'accountability', label: 'Accountability' },
        { value: 'communication', label: 'Communication' },
        { value: 'leadership', label: 'Leadership' },
        { value: 'execution', label: 'Execution' },
    ];
    var DRIVERS = [
        { value: 'get_real', label: 'Get Real™' },
        { value: 'fill_buckets', label: 'Fill Buckets™' },
        { value: 'be_intentional', label: 'Be Intentional™' },
        { value: 'foster_grit', label: 'Foster Grit™' },
        { value: 'drive_growth', label: 'Drive Growth™' },
    ];
    var LEVELS = [
        { value: 'high', label: 'High' },
        { value: 'medium', label: 'Medium' },
        { value: 'low', label: 'Low' },
    ];
    // Executive Owner options come from this ARP's company group roster
    // (Laravel /api/v1/arps/{arp}/group-members) - every member of the
    // group, not just leaders, and not a hardcoded name list. Multi-select:
    // a readiness priority can have more than one Executive Owner.
    var OWNERS = ((window.XFARP_WIZARD && window.XFARP_WIZARD.groupMembers) || []).map(function (m) {
        return { value: String(m.id), label: m.name || ('User #' + m.id) };
    });

    function ensureCache() {
        if (!window.xarReadinessCache) {
            window.xarReadinessCache = [];
        }
        return window.xarReadinessCache;
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

    /** A checkbox-list multi-select field, e.g. Executive Owner(s). */
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
            cor_capability: 'leadership',
            primary_driver: 'be_intentional',
            priority_level: 'high',
            description: '',
            business_rationale: '',
            secondary_driver: 'drive_growth',
            executive_owner_user_ids: [],
            expected_impact: '',
        };
    }

    function cardHtml(item, index) {
        return '<div class="xar-prio-card" data-index="' + index + '">' +
            '<div class="xar-prio-rail">' +
            '<span class="xar-drag" aria-hidden="true">⋮⋮</span>' +
            '<span class="xar-prio-num">' + (index + 1) + '</span>' +
            '</div>' +
            '<div class="xar-prio-body">' +
            '<a href="#" class="xar-icon-btn xar-prio-delete" data-index="' + index + '" aria-label="Delete priority" role="button">' +
            '<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/trash-icon.svg" alt="" width="18" height="18">' +
            '</a>' +
            '<div class="xar-prio-grid xar-prio-grid-4">' +
            field('Priority Name', true, '<input type="text" class="xar-input" data-key="name" value="' + escAttr(item.name) + '" placeholder="Enter priority name...">') +
            field('COR Capability™', true, '<select class="xar-input" data-key="cor_capability">' + opts(COR_CAPABILITIES, item.cor_capability) + '</select>') +
            field('Primary Behavioral Driver™', true, '<select class="xar-input" data-key="primary_driver">' + opts(DRIVERS, item.primary_driver) + '</select>') +
            field('Priority Level', true, '<select class="xar-input" data-key="priority_level">' + opts(LEVELS, item.priority_level) + '</select>') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-4">' +
            field('Description', false, '<textarea class="xar-input" rows="3" data-key="description" placeholder="Describe this readiness priority...">' + escHtml(item.description) + '</textarea>') +
            field('Business Rationale', false, '<textarea class="xar-input" rows="3" data-key="business_rationale" placeholder="Why does this matter?...">' + escHtml(item.business_rationale) + '</textarea>') +
            field('Secondary Behavioral Driver™', false, '<select class="xar-input" data-key="secondary_driver">' + opts(DRIVERS, item.secondary_driver) + '</select>') +
            multiCheckboxField('Executive Owner(s)', true, 'executive_owner_user_ids', OWNERS, item.executive_owner_user_ids, 'No group members found') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-1">' +
            field('Expected Organizational Impact', false, '<textarea class="xar-input" rows="2" data-key="expected_impact" placeholder="What organizational impact do you expect?...">' + escHtml(item.expected_impact) + '</textarea>') +
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
        window.xarReadinessCache = next;
        return next;
    }

    function showLoading() {
        var list = document.getElementById('xar-readiness-list');
        if (!list) {
            return;
        }
        list.innerHTML = '<div class="xar-spinner-row"><span class="xar-spinner"></span> Loading readiness priorities…</div>';
    }

    function renderList() {
        var list = document.getElementById('xar-readiness-list');
        if (!list) {
            return;
        }
        var data = ensureCache();
        if (!data.length) {
            list.innerHTML = '<p class="xar-muted">No readiness priorities yet. Click "+ Add Priority" to create one.</p>';
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
                window.xarReadinessCache.splice(idx, 1);
                renderList();
            });
        });
    }

    window.initReadinessStep = function () {
        var addBtn = document.getElementById('xar-add-readiness');
        if (addBtn) {
            addBtn.onclick = function (e) {
                e.preventDefault();
                var list = document.getElementById('xar-readiness-list');
                if (list) {
                    collectFromDom(list);
                }
                ensureCache().push(emptyItem());
                renderList();
            };
        }

        // Already loaded once this session — render from cache immediately,
        // no need to show a loading state again.
        if (window.xarReadinessLoaded) {
            renderList();
            return;
        }

        showLoading();
        if (typeof window.xarLoadReadinessDraft === 'function') {
            window.xarLoadReadinessDraft().then(function (items) {
                window.xarReadinessCache = items || [];
                window.xarReadinessLoaded = true;
                renderList();
            });
        } else {
            window.xarReadinessCache = [];
            window.xarReadinessLoaded = true;
            renderList();
        }
    };
})();
JS;
}
