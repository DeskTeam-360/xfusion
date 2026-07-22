<?php
/**
 * Step 5 — Quarterly Commitments™ (max 5).
 *
 * UI-only prototype: static dummy content, local-only state (no Laravel
 * calls) while the visual design is being finalized.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_commitments_js(): string
{
    return <<<'JS'
commitments: function () {
    return '<h2 class="xqbr-section-title">Step 5. Quarterly Commitments™</h2>' +
        '<p class="xqbr-section-desc">Create up to five organizational commitments for the upcoming quarter. These commitments drive focus and accountability and will be reviewed in the next Quarterly Business Review™.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>Incomplete commitments will automatically carry forward to the next quarterly review until completed or intentionally closed.</span></div>' +
        '<div class="xqbr-card"><div class="xqbr-row" style="justify-content:space-between">' +
        '<h3 style="margin:0">Organizational Commitments</h3>' +
        '<a href="javascript:void(0)" class="xqbr-add-link" id="xqbr-add-commitment">+ Add Commitment</a>' +
        '</div>' +
        '<p class="xqbr-muted" style="margin-top:-.4rem" id="xqbr-commitment-count"></p>' +
        '<div id="xqbr-commitments-list"></div>' +
        '</div>' +
        '<div class="xqbr-card"><h4>Commitment Summary</h4><div id="xqbr-commitment-summary"></div></div>' +
        '<div class="xqbr-card" style="background:#fbfaf5">' +
        '<h4>Commitment Tip</h4>' +
        '<p class="xqbr-muted">Make your commitments specific, measurable, and aligned to your Annual Readiness Plan™ objectives. This ensures accountability and progress tracking throughout the quarter.</p>' +
        '</div>';
}
JS;
}

function xfqbr_wizard_commitments_init_js(): string
{
    return <<<'JS'
(function () {
    var MAX_COMMITMENTS = 5;
    var cache = [
        { title: 'Improve Project Delivery Efficiency', description: 'Streamline project planning and execution processes to reduce cycle time and improve on-time delivery.', owner_user_id: 'Sarah Johnson', priority: 'high', related_arp_objective: 'Operational Excellence Objective 2.1', success_measure: 'Increase on-time project delivery from 88% to 95%', due_date: '2025-06-30', status: 'in_progress' },
        { title: 'Strengthen Member Engagement', description: 'Increase member participation and satisfaction through enhanced communication and outreach.', owner_user_id: 'David Miller', priority: 'high', related_arp_objective: 'Member Engagement Objective 1.2', success_measure: 'Increase member engagement score from 72 to 80', due_date: '2025-06-30', status: 'in_progress' },
        { title: 'Enhance Safety Performance', description: 'Reduce incident rates through proactive safety training and site inspections.', owner_user_id: 'Lisa Chen', priority: 'medium', related_arp_objective: 'Safety & Compliance Objective 3.1', success_measure: 'Reduce recordable incident rate from 1.2 to 0.8', due_date: '2025-06-30', status: 'open' },
        { title: 'Develop Leadership Bench Strength', description: 'Build internal leadership capabilities through mentoring and development programs.', owner_user_id: 'James Scott', priority: 'medium', related_arp_objective: 'People Development Objective 4.1', success_measure: 'Identify and develop 3 internal leaders', due_date: '2025-06-30', status: 'open' },
        { title: 'Optimize Cost Management', description: 'Implement cost control initiatives to improve operational margins.', owner_user_id: 'Mark Thompson', priority: 'medium', related_arp_objective: 'Financial Stewardship Objective 5.1', success_measure: 'Reduce operating expenses by 5%', due_date: '2025-06-30', status: 'open' },
    ];

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escAttr(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

    function opt(value, label, selected) {
        return '<option value="' + value + '"' + (value === selected ? ' selected' : '') + '>' + label + '</option>';
    }

    function commitmentCard(item, index) {
        var statusBadge = { open: '', in_progress: 'amber', done: 'green', carried_forward: 'amber' }[item.status] || '';
        return '<div class="xqbr-prio-card" data-index="' + index + '">' +
            '<div class="xqbr-prio-rail"><span class="xqbr-prio-num">' + (index + 1) + '</span></div>' +
            '<div class="xqbr-prio-body">' +
            '<a href="javascript:void(0)" class="xqbr-icon-btn xqbr-prio-delete" data-index="' + index + '">✕</a>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
            '<div class="xqbr-form-field"><label>Commitment Title</label><input class="xqbr-input" data-key="title" value="' + escAttr(item.title) + '"></div>' +
            '<div class="xqbr-form-field"><label>Owner</label><input class="xqbr-input" data-key="owner_user_id" value="' + escAttr(item.owner_user_id) + '" placeholder="Name"></div>' +
            '<div class="xqbr-form-field"><label>Priority</label><select class="xqbr-input" data-key="priority">' +
            opt('high', 'High', item.priority) + opt('medium', 'Medium', item.priority) + opt('low', 'Low', item.priority) + '</select></div>' +
            '<div class="xqbr-form-field"><label>Status</label><select class="xqbr-input" data-key="status">' +
            opt('open', 'Not Started', item.status) + opt('in_progress', 'In Progress', item.status) +
            opt('done', 'Done', item.status) + opt('carried_forward', 'Carried Forward', item.status) + '</select>' +
            (statusBadge ? '<span class="xqbr-badge-pill ' + statusBadge + '" style="margin-top:.35rem;display:inline-block">' + esc(item.status) + '</span>' : '') +
            '</div></div>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
            '<div class="xqbr-form-field"><label>Related ARP Objective</label><input class="xqbr-input" data-key="related_arp_objective" value="' + escAttr(item.related_arp_objective) + '"></div>' +
            '<div class="xqbr-form-field"><label>Success Measure</label><input class="xqbr-input" data-key="success_measure" value="' + escAttr(item.success_measure) + '"></div>' +
            '<div class="xqbr-form-field"><label>Due Date</label><input type="date" class="xqbr-input" data-key="due_date" value="' + escAttr(item.due_date) + '"></div>' +
            '<div class="xqbr-form-field"><label>&nbsp;</label></div>' +
            '</div>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-1">' +
            '<div class="xqbr-form-field"><label>Description</label><textarea class="xqbr-input" rows="2" data-key="description">' + esc(item.description) + '</textarea></div>' +
            '</div></div></div>';
    }

    function renderSummary() {
        var summary = document.getElementById('xqbr-commitment-summary');
        if (!summary) return;
        var high = cache.filter(function (c) { return c.priority === 'high'; }).length;
        var medium = cache.filter(function (c) { return c.priority === 'medium'; }).length;
        var notStarted = cache.filter(function (c) { return c.status === 'open'; }).length;
        var inProgress = cache.filter(function (c) { return c.status === 'in_progress'; }).length;
        var done = cache.filter(function (c) { return c.status === 'done'; }).length;
        summary.innerHTML = '<div class="xqbr-stat-list">' +
            '<div class="xqbr-stat-row">Total Commitments <strong>' + cache.length + ' of ' + MAX_COMMITMENTS + '</strong></div>' +
            '<div class="xqbr-stat-row">High Priority <strong>' + high + '</strong></div>' +
            '<div class="xqbr-stat-row">Medium Priority <strong>' + medium + '</strong></div>' +
            '<div class="xqbr-stat-row">Not Started <strong>' + notStarted + '</strong></div>' +
            '<div class="xqbr-stat-row">In Progress <strong>' + inProgress + '</strong></div>' +
            '<div class="xqbr-stat-row">Completed <strong>' + done + '</strong></div>' +
            '</div>';
    }

    function renderList() {
        var list = document.getElementById('xqbr-commitments-list');
        if (!list) return;
        if (!cache.length) {
            list.innerHTML = '<p class="xqbr-muted">No commitments yet. Click "+ Add Commitment" to create one.</p>';
        } else {
            list.innerHTML = cache.map(commitmentCard).join('');
            bindEvents(list);
        }
        var countEl = document.getElementById('xqbr-commitment-count');
        if (countEl) countEl.textContent = cache.length + ' of ' + MAX_COMMITMENTS + ' commitments used.';
        var addLink = document.getElementById('xqbr-add-commitment');
        if (addLink) addLink.style.display = cache.length >= MAX_COMMITMENTS ? 'none' : '';
        renderSummary();
    }

    function collect(list) {
        var next = [];
        list.querySelectorAll('.xqbr-prio-card').forEach(function (card) {
            var item = {};
            card.querySelectorAll('[data-key]').forEach(function (el) { item[el.getAttribute('data-key')] = el.value; });
            next.push(item);
        });
        cache = next;
        return next;
    }

    function bindEvents(list) {
        list.querySelectorAll('[data-key]').forEach(function (el) {
            el.addEventListener('input', function () { collect(list); renderSummary(); });
            el.addEventListener('change', function () { collect(list); renderSummary(); });
        });
        list.querySelectorAll('.xqbr-prio-delete').forEach(function (link) {
            link.addEventListener('click', function () {
                collect(list);
                cache.splice(parseInt(link.getAttribute('data-index'), 10), 1);
                renderList();
            });
        });
    }

    window.initCommitmentsStep = function () {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var addLink = document.getElementById('xqbr-add-commitment');

        if (!canEdit && addLink) addLink.style.display = 'none';

        if (addLink) {
            addLink.addEventListener('click', function () {
                var list = document.getElementById('xqbr-commitments-list');
                if (list) collect(list);
                if (cache.length >= MAX_COMMITMENTS) return;
                cache.push({ title: '', description: '', owner_user_id: '', priority: 'medium', related_arp_objective: '', success_measure: '', due_date: '', status: 'open' });
                renderList();
            });
        }

        renderList();
    };
})();
JS;
}
