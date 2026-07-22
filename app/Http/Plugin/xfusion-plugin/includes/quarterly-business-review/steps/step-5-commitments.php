<?php
/**
 * Step 5 — Quarterly Commitments™ (max 5).
 *
 * Organizational commitments persist via Laravel (xqbrSaveCommitmentsStep).
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
        '</div>' +
        '<p class="xqbr-muted" id="xqbr-commitments-status" style="margin-top:.5rem"></p>';
}
JS;
}

function xfqbr_wizard_commitments_init_js(): string
{
    return <<<'JS'
(function () {
    var MAX_COMMITMENTS = 5;
    var cache = [];
    var userEdited = false;
    var loadState = { qbrId: null, token: 0, fetched: false, loading: false };
    var STATUS_LABELS = {
        open: 'Not Started',
        in_progress: 'In Progress',
        done: 'Done',
        carried_forward: 'Carried Forward',
    };

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escAttr(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

    function opt(value, label, selected) {
        return '<option value="' + value + '"' + (value === selected ? ' selected' : '') + '>' + label + '</option>';
    }

    function markEdited() {
        userEdited = true;
    }

    function currentQbrId() {
        return window.XFQBR_WIZARD && window.XFQBR_WIZARD.qbrId
            ? String(window.XFQBR_WIZARD.qbrId)
            : null;
    }

    function applyLoadedRows(rows) {
        cache = (rows || []).map(mapCommitmentRow);
        renderList();
    }

    function mapCommitmentRow(row) {
        var dueDate = row.due_date || '';
        if (dueDate && dueDate.length >= 10) {
            dueDate = dueDate.substring(0, 10);
        }
        return {
            id: row.id || null,
            title: row.title || '',
            description: row.description || '',
            owner_name: row.owner_name || row.owner_display_name || '',
            owner_user_id: row.owner_user_id || null,
            priority: row.priority || 'medium',
            related_arp_objective: row.related_arp_objective || '',
            success_measure: row.success_measure || '',
            due_date: dueDate,
            status: row.status || 'open',
            carried_forward_from_id: row.carried_forward_from_id || null,
        };
    }

    function commitmentCard(item, index) {
        var statusBadge = { open: '', in_progress: 'amber', done: 'green', carried_forward: 'amber' }[item.status] || '';
        var cardAttrs = 'class="xqbr-prio-card" data-index="' + index + '"';
        if (item.id) {
            cardAttrs += ' data-id="' + escAttr(String(item.id)) + '"';
        }
        if (item.carried_forward_from_id) {
            cardAttrs += ' data-carried-forward-from-id="' + escAttr(String(item.carried_forward_from_id)) + '"';
        }
        return '<div ' + cardAttrs + '>' +
            '<div class="xqbr-prio-rail"><span class="xqbr-prio-num">' + (index + 1) + '</span></div>' +
            '<div class="xqbr-prio-body">' +
            '<a href="javascript:void(0)" class="xqbr-icon-btn xqbr-prio-delete" data-index="' + index + '">✕</a>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
            '<div class="xqbr-form-field"><label>Commitment Title</label><input class="xqbr-input" data-key="title" value="' + escAttr(item.title) + '"></div>' +
            '<div class="xqbr-form-field"><label>Owner</label><input class="xqbr-input" data-key="owner_name" value="' + escAttr(item.owner_name) + '" placeholder="Name"></div>' +
            '<div class="xqbr-form-field"><label>Priority</label><select class="xqbr-input" data-key="priority">' +
            opt('high', 'High', item.priority) + opt('medium', 'Medium', item.priority) + opt('low', 'Low', item.priority) + '</select></div>' +
            '<div class="xqbr-form-field"><label>Status</label><select class="xqbr-input" data-key="status">' +
            opt('open', 'Not Started', item.status) + opt('in_progress', 'In Progress', item.status) +
            opt('done', 'Done', item.status) + opt('carried_forward', 'Carried Forward', item.status) + '</select>' +
            (statusBadge ? '<span class="xqbr-badge-pill ' + statusBadge + '" style="margin-top:.35rem;display:inline-block">' + esc(STATUS_LABELS[item.status] || item.status) + '</span>' : '') +
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

        var list = document.getElementById('xqbr-commitments-list');
        if (list && list.querySelector('.xqbr-prio-card')) {
            collect(list);
        }

        var high = cache.filter(function (c) { return c.priority === 'high'; }).length;
        var medium = cache.filter(function (c) { return c.priority === 'medium'; }).length;
        var low = cache.filter(function (c) { return c.priority === 'low'; }).length;
        var notStarted = cache.filter(function (c) { return c.status === 'open'; }).length;
        var inProgress = cache.filter(function (c) { return c.status === 'in_progress'; }).length;
        var done = cache.filter(function (c) { return c.status === 'done'; }).length;
        var carriedForward = cache.filter(function (c) { return c.status === 'carried_forward'; }).length;

        summary.innerHTML = '<div class="xqbr-stat-list">' +
            '<div class="xqbr-stat-row">Total Commitments <strong>' + cache.length + ' of ' + MAX_COMMITMENTS + '</strong></div>' +
            '<div class="xqbr-stat-row">High Priority <strong>' + high + '</strong></div>' +
            '<div class="xqbr-stat-row">Medium Priority <strong>' + medium + '</strong></div>' +
            '<div class="xqbr-stat-row">Low Priority <strong>' + low + '</strong></div>' +
            '<div class="xqbr-stat-row">Not Started <strong>' + notStarted + '</strong></div>' +
            '<div class="xqbr-stat-row">In Progress <strong>' + inProgress + '</strong></div>' +
            '<div class="xqbr-stat-row">Completed <strong>' + done + '</strong></div>' +
            '<div class="xqbr-stat-row">Carried Forward <strong>' + carriedForward + '</strong></div>' +
            '</div>';
    }

    function showListLoading(message) {
        loadState.loading = true;
        var list = document.getElementById('xqbr-commitments-list');
        var summary = document.getElementById('xqbr-commitment-summary');
        var addLink = document.getElementById('xqbr-add-commitment');
        if (typeof window.xqbrRenderListLoading === 'function') {
            window.xqbrRenderListLoading(list, 3, message || 'Loading organizational commitments…');
        } else if (list) {
            list.innerHTML = window.xqbrSpinnerHtml ? window.xqbrSpinnerHtml(message) : '<p class="xqbr-muted">Loading…</p>';
        }
        if (typeof window.xqbrRenderSummaryLoading === 'function') {
            window.xqbrRenderSummaryLoading(summary);
        }
        if (addLink) addLink.style.display = 'none';
        var countEl = document.getElementById('xqbr-commitment-count');
        if (countEl) countEl.textContent = 'Loading…';
    }

    function renderList() {
        var list = document.getElementById('xqbr-commitments-list');
        if (!list) return;
        if (loadState.loading) {
            return;
        }
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
            if (card.dataset.id) {
                item.id = parseInt(card.dataset.id, 10);
            }
            if (card.dataset.carriedForwardFromId) {
                item.carried_forward_from_id = parseInt(card.dataset.carriedForwardFromId, 10);
            }
            card.querySelectorAll('[data-key]').forEach(function (el) { item[el.getAttribute('data-key')] = el.value; });
            next.push(item);
        });
        cache = next;
        return next;
    }

    function bindEvents(list) {
        list.querySelectorAll('[data-key]').forEach(function (el) {
            el.addEventListener('input', function () { collect(list); markEdited(); renderSummary(); });
            el.addEventListener('change', function () { collect(list); markEdited(); renderList(); });
        });
        list.querySelectorAll('.xqbr-prio-delete').forEach(function (link) {
            link.addEventListener('click', function () {
                collect(list);
                markEdited();
                cache.splice(parseInt(link.getAttribute('data-index'), 10), 1);
                renderList();
            });
        });
    }

    function bindAddLink(addLink) {
        if (!addLink || addLink.dataset.bound === '1') {
            return;
        }
        addLink.dataset.bound = '1';
        addLink.addEventListener('click', function () {
            var list = document.getElementById('xqbr-commitments-list');
            if (list && list.querySelector('.xqbr-prio-card')) {
                collect(list);
            }
            if (cache.length >= MAX_COMMITMENTS) {
                return;
            }
            markEdited();
            cache.push({
                title: '',
                description: '',
                owner_name: '',
                priority: 'medium',
                related_arp_objective: '',
                success_measure: '',
                due_date: '',
                status: 'open',
            });
            renderList();
        });
    }

    function loadCommitmentsFromApi() {
        if (typeof window.xqbrLoadCommitments !== 'function') {
            return Promise.resolve();
        }
        var qbrId = currentQbrId();
        if (!qbrId || loadState.fetched) {
            return Promise.resolve();
        }
        var statusEl = document.getElementById('xqbr-commitments-status');
        if (statusEl) {
            statusEl.textContent = '';
        }
        showListLoading('Loading organizational commitments…');
        var token = loadState.token;
        return window.xqbrLoadCommitments().then(function (rows) {
            if (token !== loadState.token) {
                return;
            }
            loadState.fetched = true;
            loadState.loading = false;
            if (!userEdited) {
                applyLoadedRows(rows);
            } else {
                renderList();
            }
            if (statusEl) {
                statusEl.textContent = 'Click Save Draft to save your changes.';
            }
        }).catch(function () {
            loadState.loading = false;
            var list = document.getElementById('xqbr-commitments-list');
            if (list) {
                list.innerHTML = (window.xqbrSpinnerHtml
                    ? window.xqbrSpinnerHtml('Could not load commitments.')
                    : '<p class="xqbr-muted">Could not load commitments.</p>');
            }
            if (statusEl) {
                statusEl.textContent = 'Could not load commitments. Click Save Draft after editing to retry.';
            }
        });
    }

    window.xqbrLoadCommitmentsData = function () {
        return loadCommitmentsFromApi();
    };

    window.initCommitmentsStep = function () {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var addLink = document.getElementById('xqbr-add-commitment');
        var statusEl = document.getElementById('xqbr-commitments-status');
        var qbrId = currentQbrId();

        if (qbrId !== loadState.qbrId) {
            loadState.qbrId = qbrId;
            loadState.token += 1;
            loadState.fetched = false;
            cache = [];
            userEdited = false;
        }

        if (!canEdit && addLink) addLink.style.display = 'none';

        if (!loadState.fetched) {
            showListLoading('Loading organizational commitments…');
        } else {
            renderList();
        }
        bindAddLink(addLink);

        if (statusEl && canEdit && loadState.fetched) {
            statusEl.textContent = 'Click Save Draft to save your changes.';
        }
    };

    window.xqbrCommitmentsMarkSaved = function () {
        userEdited = false;
    };

    window.xqbrCommitmentsIsDirty = function () {
        return userEdited;
    };
})();
JS;
}
