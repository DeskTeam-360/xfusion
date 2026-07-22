<?php
/**
 * Step 5 — Quarterly Commitments™ (max 5, auto carry-forward).
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
        '<p class="xqbr-muted" style="margin-top:-.4rem" id="xqbr-commitment-count">You can create up to 5 commitments for this quarter.</p>' +
        '<div id="xqbr-commitments-list"></div>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-save-commitments-btn" style="margin-top:.75rem">Save Commitments</button>' +
        '<span class="xqbr-muted" id="xqbr-commitments-save-status" style="margin-left:.5rem"></span>' +
        '</div>' +
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
    var cache = [];

    function escHtml(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
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
            '<div class="xqbr-form-field"><label>Owner (user ID)</label><input type="number" class="xqbr-input" data-key="owner_user_id" value="' + escAttr(item.owner_user_id) + '"></div>' +
            '<div class="xqbr-form-field"><label>Priority</label><select class="xqbr-input" data-key="priority">' +
            opt('high', 'High', item.priority) + opt('medium', 'Medium', item.priority) + opt('low', 'Low', item.priority) + '</select></div>' +
            '<div class="xqbr-form-field"><label>Status</label><select class="xqbr-input" data-key="status">' +
            opt('open', 'Not Started', item.status) + opt('in_progress', 'In Progress', item.status) +
            opt('done', 'Done', item.status) + opt('carried_forward', 'Carried Forward', item.status) + '</select>' +
            (statusBadge ? '<span class="xqbr-badge-pill ' + statusBadge + '" style="margin-top:.35rem;display:inline-block">' + escHtml(item.status) + '</span>' : '') +
            '</div></div>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
            '<div class="xqbr-form-field"><label>Related ARP Objective</label><input class="xqbr-input" data-key="related_arp_objective" value="' + escAttr(item.related_arp_objective) + '"></div>' +
            '<div class="xqbr-form-field"><label>Success Measure</label><input class="xqbr-input" data-key="success_measure" value="' + escAttr(item.success_measure) + '"></div>' +
            '<div class="xqbr-form-field"><label>Due Date</label><input type="date" class="xqbr-input" data-key="due_date" value="' + escAttr(item.due_date) + '"></div>' +
            '<div class="xqbr-form-field"><label>&nbsp;</label></div>' +
            '</div>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-1">' +
            '<div class="xqbr-form-field"><label>Description</label><textarea class="xqbr-input" rows="2" data-key="description">' + escHtml(item.description) + '</textarea></div>' +
            '</div>' +
            (item.carried_forward_from_id ? '<input type="hidden" data-key="carried_forward_from_id" value="' + escAttr(item.carried_forward_from_id) + '">' : '') +
            '</div></div>';
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
            el.addEventListener('input', function () { collect(list); });
            el.addEventListener('change', function () { collect(list); });
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
        var saveBtn = document.getElementById('xqbr-save-commitments-btn');

        if (!canEdit) {
            if (addLink) addLink.style.display = 'none';
            if (saveBtn) saveBtn.style.display = 'none';
        }

        if (addLink) {
            addLink.addEventListener('click', function () {
                var list = document.getElementById('xqbr-commitments-list');
                if (list) collect(list);
                if (cache.length >= MAX_COMMITMENTS) return;
                cache.push({ title: '', description: '', owner_user_id: '', priority: 'medium', related_arp_objective: '', success_measure: '', due_date: '', status: 'open' });
                renderList();
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var list = document.getElementById('xqbr-commitments-list');
                var items = list ? collect(list) : cache;
                var statusEl = document.getElementById('xqbr-commitments-save-status');
                saveBtn.disabled = true;
                if (statusEl) statusEl.textContent = 'Saving…';
                window.xqbrSaveCommitments(items).then(function (res) {
                    saveBtn.disabled = false;
                    if (statusEl) statusEl.textContent = (res && res.success) ? 'Saved ' + res.saved_at : (res && res.message) || 'Save failed.';
                });
            });
        }

        var list = document.getElementById('xqbr-commitments-list');
        if (list) list.innerHTML = '<div class="xqbr-spinner-row"><span class="xqbr-spinner"></span> Loading commitments…</div>';

        window.xqbrLoadCommitments().then(function (items) {
            cache = items || [];
            renderList();
        });
    };
})();
JS;
}
