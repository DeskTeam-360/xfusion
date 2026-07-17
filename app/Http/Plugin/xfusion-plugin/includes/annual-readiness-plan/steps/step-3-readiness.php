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
    var OWNERS = [
        { value: 'james_scott', label: 'James Scott', initials: 'JS' },
        { value: 'maria_chen', label: 'Maria Chen', initials: 'MC' },
        { value: 'alex_rivera', label: 'Alex Rivera', initials: 'AR' },
    ];

    function seedReadiness() {
        return [
            {
                name: 'Strengthen Leadership Capability',
                cor_capability: 'leadership',
                primary_driver: 'be_intentional',
                priority_level: 'high',
                description: 'Build leadership bench and develop leaders at all levels.',
                business_rationale: 'Sustainable growth requires strong leadership capacity across the organization.',
                secondary_driver: 'drive_growth',
                executive_owner: 'james_scott',
                expected_impact: 'Stronger decision making, higher engagement, and a deeper leadership bench for future growth.',
            },
            {
                name: 'Improve Cross-Functional Alignment',
                cor_capability: 'alignment',
                primary_driver: 'get_real',
                priority_level: 'high',
                description: 'Create shared priorities and clearer handoffs across teams.',
                business_rationale: 'Misalignment between functions slows execution and creates rework.',
                secondary_driver: 'be_intentional',
                executive_owner: 'maria_chen',
                expected_impact: 'Faster delivery cycles, fewer conflicting priorities, and clearer ownership.',
            },
            {
                name: 'Strengthen Accountability Practices',
                cor_capability: 'accountability',
                primary_driver: 'foster_grit',
                priority_level: 'medium',
                description: 'Establish consistent follow-through on commitments and outcomes.',
                business_rationale: 'Accountability gaps undermine trust and delay strategic progress.',
                secondary_driver: 'be_intentional',
                executive_owner: 'alex_rivera',
                expected_impact: 'Higher completion rates on priorities and clearer ownership of outcomes.',
            },
            {
                name: 'Elevate Communication Clarity',
                cor_capability: 'communication',
                primary_driver: 'fill_buckets',
                priority_level: 'medium',
                description: 'Improve upward, downward, and cross-team communication rituals.',
                business_rationale: 'Incomplete information flow creates friction and slow decisions.',
                secondary_driver: 'get_real',
                executive_owner: 'james_scott',
                expected_impact: 'Better information flow, reduced ambiguity, and stronger team trust.',
            },
            {
                name: 'Accelerate Execution Discipline',
                cor_capability: 'execution',
                primary_driver: 'drive_growth',
                priority_level: 'high',
                description: 'Translate strategy into measurable weekly and quarterly execution.',
                business_rationale: 'Strategy without disciplined execution fails to create organizational readiness.',
                secondary_driver: 'foster_grit',
                executive_owner: 'maria_chen',
                expected_impact: 'More predictable delivery and stronger linkage between plans and results.',
            },
        ];
    }

    function ensureCache() {
        if (!window.xarReadinessCache) {
            window.xarReadinessCache = seedReadiness();
        }
        return window.xarReadinessCache;
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

    function emptyItem() {
        return {
            name: '',
            cor_capability: 'leadership',
            primary_driver: 'be_intentional',
            priority_level: 'high',
            description: '',
            business_rationale: '',
            secondary_driver: 'drive_growth',
            executive_owner: 'james_scott',
            expected_impact: '',
        };
    }

    function field(label, required, control) {
        return '<div class="xar-form-field">' +
            '<label>' + label + (required ? ' <span class="xar-req">*</span>' : '') + '</label>' +
            control +
            '</div>';
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
            field('Executive Owner', true,
                '<div class="xar-owner-field">' +
                '<span class="xar-avatar" data-owner-avatar>' + ownerInitials(item.executive_owner) + '</span>' +
                '<select class="xar-input" data-key="executive_owner">' + ownerOpts(item.executive_owner) + '</select>' +
                '</div>') +
            '</div>' +
            '<div class="xar-prio-grid xar-prio-grid-1">' +
            field('Expected Organizational Impact', false, '<textarea class="xar-input" rows="2" data-key="expected_impact" placeholder="What organizational impact do you expect?...">' + escHtml(item.expected_impact) + '</textarea>') +
            '</div>' +
            '</div></div>';
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
        window.xarReadinessCache = next;
        return next;
    }

    function renderList() {
        var list = document.getElementById('xar-readiness-list');
        if (!list) {
            return;
        }
        var data = ensureCache();
        list.innerHTML = data.map(cardHtml).join('');
        bindList(list);
    }

    function bindList(list) {
        list.querySelectorAll('[data-key]').forEach(function (el) {
            el.addEventListener('change', function () {
                collectFromDom(list);
                if (el.getAttribute('data-key') === 'executive_owner') {
                    var avatar = el.closest('.xar-owner-field');
                    if (avatar) {
                        var badge = avatar.querySelector('[data-owner-avatar]');
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
                window.xarReadinessCache.splice(idx, 1);
                renderList();
            });
        });
    }

    window.initReadinessStep = function () {
        ensureCache();
        renderList();
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

        // Replace the dummy seed with real saved data from Laravel, if any
        // exists for this ARP. Falls back silently to the seed on failure.
        if (typeof window.xarLoadReadinessDraft === 'function') {
            window.xarLoadReadinessDraft().then(function (items) {
                if (items && items.length) {
                    window.xarReadinessCache = items;
                    renderList();
                }
            });
        }
    };
})();
JS;
}
