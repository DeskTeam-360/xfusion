<?php
/**
 * Step 0 — Meeting Picker gate (pair → conversation) before the 6-step wizard.
 *
 * Reuses existing xfusion_oo_* AJAX handlers from one-on-one-shortcode.php.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_meeting_picker_js(): string
{
    return <<<'JS'
var XFW_CTX_KEY = 'xfoo_wizard_ctx';

var xfwEsc = function (s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
};

var xfwOoCall = function (action, data) {
    var body = new URLSearchParams(Object.assign({ action: action, nonce: window.XFW_WIZARD.ooNonce }, data || {}));
    return fetch(window.XFW_WIZARD.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (r) { return r.json(); });
};

var xfwFormatMeetingDate = function (iso) {
    if (!iso) {
        return { date: '—', time: '—' };
    }
    var d = new Date(iso);
    if (isNaN(d.getTime())) {
        return { date: String(iso), time: '—' };
    }
    return {
        date: d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }),
        time: d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }),
    };
};

var xfwStatusBadgeClass = function (status) {
    if (status === 'completed') {
        return 'green';
    }
    if (status === 'in_progress') {
        return 'blue';
    }
    return 'amber';
};

var xfwFormatStatusLabel = function (status) {
    var key = String(status || 'scheduled').toLowerCase();
    var labels = {
        scheduled: 'Scheduled',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    if (labels[key]) {
        return labels[key];
    }
    return key.replace(/_/g, ' ').replace(/\b\w/g, function (ch) { return ch.toUpperCase(); });
};

var xfwReadUrlConversationId = function () {
    try {
        var params = new URLSearchParams(window.location.search);
        return parseInt(params.get('conversation_id') || '0', 10) || 0;
    } catch (e) {
        return 0;
    }
};

var xfwGetActiveConversationId = function () {
    var fromWizard = window.XFW_WIZARD ? parseInt(window.XFW_WIZARD.conversationId, 10) : 0;
    if (fromWizard > 0) {
        return fromWizard;
    }
    if (root && root.dataset.conversationId) {
        var fromRoot = parseInt(root.dataset.conversationId, 10);
        if (fromRoot > 0) {
            return fromRoot;
        }
    }
    return xfwReadUrlConversationId();
};

var xfwLoadStoredContext = function () {
    try {
        var raw = localStorage.getItem(XFW_CTX_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
};

var xfwSaveStoredContext = function (ctx) {
    try {
        localStorage.setItem(XFW_CTX_KEY, JSON.stringify(ctx));
    } catch (e) {}
};

var xfwUpdateSidebarMeeting = function (ctx) {
    var set = function (id, text) {
        var el = root.querySelector(id);
        if (el) {
            el.textContent = text || '—';
        }
    };
    var fmt = xfwFormatMeetingDate(ctx.scheduledAt);
    set('#xfw-si-employee', ctx.employeeName);
    set('#xfw-si-leader', ctx.leaderName);
    set('#xfw-si-date', fmt.date);
    set('#xfw-si-time', fmt.time);
    var statusEl = root.querySelector('#xfw-si-status');
    if (statusEl) {
        statusEl.innerHTML = '<span class="xfw-badge ' + xfwStatusBadgeClass(ctx.status) + '">' + xfwEsc(xfwFormatStatusLabel(ctx.status)) + '</span>';
    }
    var linkRow = root.querySelector('#xfw-si-link-row');
    var linkEl = root.querySelector('#xfw-si-link');
    if (linkRow && linkEl) {
        if (ctx.meetingLink) {
            linkRow.style.display = '';
            linkEl.innerHTML = '<a href="' + xfwEsc(ctx.meetingLink) + '" target="_blank" rel="noopener" class="xfw-link">Join meeting</a>';
        } else {
            linkRow.style.display = 'none';
            linkEl.innerHTML = '';
        }
    }
    var roleEl = root.querySelector('#xfw-si-role');
    if (roleEl) {
        roleEl.textContent = ctx.userRole ? (ctx.userRole.charAt(0).toUpperCase() + ctx.userRole.slice(1)) : '—';
    }
    if (typeof window.xfwRenderSidebar === 'function' && typeof STEPS !== 'undefined' && STEPS[current] && STEPS[current].key === 'synthesis') {
        window.xfwRenderSidebar();
    }
};

var xfwShowWizardWorkspace = function (show) {
    var gate = root.querySelector('#xfw-meeting-gate');
    var ws = root.querySelector('#xfw-wizard-workspace');
    if (gate) {
        gate.classList.toggle('xfw-hidden', !!show);
    }
    if (ws) {
        ws.classList.toggle('xfw-hidden', !show);
    }
    root.querySelectorAll('.xfw-wizard-only').forEach(function (el) {
        el.classList.toggle('xfw-hidden', !show);
    });
};

var xfwApplyMeetingContext = function (ctx, options) {
    options = options || {};
    if (!ctx || !ctx.conversationId) {
        return;
    }
    var prevId = parseInt(window.XFW_WIZARD.conversationId, 10) || 0;
    if (prevId !== ctx.conversationId && typeof xfwResetDraftCache === 'function') {
        xfwResetDraftCache();
    }
    if (prevId !== ctx.conversationId && typeof xfwResetCommitmentsCache === 'function') {
        xfwResetCommitmentsCache();
    }
    if (prevId !== ctx.conversationId && typeof xfwResetEvidenceCache === 'function') {
        xfwResetEvidenceCache();
    }
    if (prevId !== ctx.conversationId && typeof xfwResetBriefCache === 'function') {
        xfwResetBriefCache();
    }
    window.XFW_WIZARD.conversationId = ctx.conversationId;
    window.XFW_WIZARD.userRole = ctx.userRole || '';
    window.XFW_WIZARD.pairId = ctx.pairId || 0;
    root.dataset.conversationId = String(ctx.conversationId);
    if (ctx.userRole) {
        root.dataset.userRole = ctx.userRole;
    }
    xfwSaveStoredContext(ctx);
    xfwUpdateSidebarMeeting(ctx);
    xfwShowWizardWorkspace(true);
    try {
        var url = new URL(window.location.href);
        url.searchParams.set('conversation_id', String(ctx.conversationId));
        window.history.replaceState({}, '', url.toString());
    } catch (e) {}
    if (typeof window.xfwBootWizard === 'function') {
        window.xfwBootWizard(!!options.resetStep);
    }
    if (typeof loadWizardDraft === 'function') {
        loadWizardDraft(true).then(function () {
            if (typeof xfwOnDraftLoaded === 'function') {
                xfwOnDraftLoaded();
            }
        });
    }
    if (typeof preloadCommitments === 'function') {
        preloadCommitments(true);
    }
    var autosave = root.querySelector('.xfw-autosave');
    if (autosave) {
        autosave.textContent = '✓ Ready to save';
        autosave.style.color = '#16a34a';
    }
};

var xfwShowMeetingGate = function () {
    xfwShowWizardWorkspace(false);
    if (typeof window.xfwInitMeetingPicker === 'function') {
        window.xfwInitMeetingPicker();
    }
};

var xfwPickerState = {
    dashboard: null,
    groups: [],
    meetings: [],
    selectedGroupId: 0,
    selectedGroup: null,
    pairsMap: {},
    pairId: 0,
    userRole: '',
    pendingEmployeeId: 0,
    leaderInfo: null,
};

var xfwMeetingContextFromRow = function (row) {
    return {
        conversationId: row.id,
        pairId: row.pair_id,
        userRole: row.user_role,
        employeeName: row.employee ? row.employee.name : '—',
        leaderName: row.leader ? row.leader.name : '—',
        scheduledAt: row.scheduled_at || '',
        status: row.status || 'scheduled',
        meetingLink: row.meeting_link || '',
        groupTitle: row.group ? row.group.title : '',
    };
};

var xfwOpenMeetingRow = function (row) {
    if (!row || !row.id) {
        return;
    }
    xfwApplyMeetingContext(xfwMeetingContextFromRow(row), { resetStep: true });
};

var xfwRenderAllMeetings = function (meetings) {
    var el = root.querySelector('#xfw-gate-all-meetings');
    if (!el) {
        return;
    }

    var html = '<div class="xfw-gate-section">' +
        '<h3 style="margin:0 0 .5rem">Your meetings</h3>';

    if (!meetings.length) {
        html += '<p class="xfw-muted">No meetings yet. Choose a company group below to schedule your first 1-on-1.</p></div>';
        el.innerHTML = html;
        return;
    }

    html += '<p class="xfw-muted" style="margin:0 0 .75rem">All scheduled and past meetings across your company groups.</p>' +
        '<div style="overflow-x:auto"><table class="xfw-table"><thead><tr>' +
        '<th>Group</th><th>With</th><th>Your role</th><th>Scheduled</th><th>Status</th><th></th>' +
        '</tr></thead><tbody>';

    meetings.forEach(function (m, idx) {
        var fmt = xfwFormatMeetingDate(m.scheduled_at);
        var btnLabel = m.status === 'in_progress' ? 'Resume' : (m.status === 'completed' ? 'View' : 'Open');
        html += '<tr><td>' + xfwEsc(m.group ? m.group.title : '—') + '</td>' +
            '<td>' + xfwEsc(m.counterpart_name || '—') + '</td>' +
            '<td><span class="xfw-badge amber">' + xfwEsc(m.user_role || '') + '</span></td>' +
            '<td>' + xfwEsc(fmt.date) + ' ' + xfwEsc(fmt.time) + '</td>' +
            '<td><span class="xfw-badge ' + xfwStatusBadgeClass(m.status) + '">' + xfwEsc(xfwFormatStatusLabel(m.status)) + '</span></td>' +
            '<td><button type="button" class="xfw-btn xfw-btn-outline xfw-btn-sm" data-open-meeting-idx="' + idx + '">' + btnLabel + '</button></td></tr>';
    });

    html += '</tbody></table></div></div>';
    el.innerHTML = html;

    el.querySelectorAll('[data-open-meeting-idx]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idx = parseInt(btn.getAttribute('data-open-meeting-idx'), 10);
            if (!isNaN(idx) && meetings[idx]) {
                xfwOpenMeetingRow(meetings[idx]);
            }
        });
    });
};

var xfwGroupLabel = function (group) {
    var roleLabel = group.role === 'leader' ? 'Leader' : 'Member';
    var company = group.company ? ' · ' + group.company : '';
    return group.title + company + ' (' + roleLabel + ')';
};

var xfwSelectGroup = function (group) {
    var convEl = root.querySelector('#xfw-gate-conversations');
    xfwPickerState.selectedGroup = group || null;
    xfwPickerState.selectedGroupId = group ? group.id : 0;
    xfwPickerState.userRole = group ? group.role : '';
    xfwPickerState.pairId = 0;
    xfwPickerState.pendingEmployeeId = 0;
    xfwPickerState.pairsMap = {};

    if (!group) {
        if (convEl) {
            convEl.classList.add('xfw-hidden');
            convEl.innerHTML = '';
        }
        return;
    }

    if (group.role === 'leader') {
        xfwPickerState.leaderInfo = group.leader || xfwPickerState.dashboard.user || null;
        xfwRenderLeaderGroupContext(group);
        return;
    }

    xfwPickerState.leaderInfo = group.leader || null;
    xfwPickerState.pairId = group.pair_id || 0;
    if (group.pair_id && group.leader) {
        xfwPickerState.pairsMap[group.pair_id] = {
            leader: group.leader,
            employee: xfwPickerState.dashboard.user || { name: '—' },
        };
    }

    var groupMeetings = (xfwPickerState.meetings || []).filter(function (m) {
        return m.group && parseInt(m.group.id, 10) === parseInt(group.id, 10);
    });

    if (groupMeetings.length) {
        xfwRenderGroupMeetings(group, groupMeetings);
    } else if (group.pair_id) {
        xfwLoadGateConversations();
    } else if (convEl) {
        convEl.innerHTML = '<p class="xfw-muted">No meetings with your leader in this group yet. Your leader can schedule one.</p>';
        convEl.classList.remove('xfw-hidden');
    }
};

var xfwRenderGroupMeetings = function (group, rows) {
    var el = root.querySelector('#xfw-gate-conversations');
    if (!el) {
        return;
    }

    var pair = {
        leader: group.leader || { name: '—' },
        employee: xfwPickerState.dashboard.user || { name: '—' },
    };
    var role = group.role || 'employee';

    if (group.role === 'leader' && xfwPickerState.pendingEmployeeId) {
        var member = (group.members || []).find(function (m) {
            return m.employee && m.employee.id === xfwPickerState.pendingEmployeeId;
        });
        if (member) {
            pair.employee = member.employee;
        }
    }

    xfwRenderConversationList(pair, role, rows.map(function (m) {
        return {
            id: m.id,
            scheduled_at: m.scheduled_at,
            meeting_link: m.meeting_link,
            status: m.status,
        };
    }), { skipSchedule: group.role !== 'leader' });
};

var xfwRenderLeaderGroupContext = function (group) {
    var convEl = root.querySelector('#xfw-gate-conversations');
    var members = group.members || [];

    if (!members.length) {
        if (convEl) {
            convEl.innerHTML = '<p class="xfw-muted">No members in this group yet.</p>';
            convEl.classList.remove('xfw-hidden');
        }
        return;
    }

    var html = '<div class="xfw-gate-section" style="margin:0;padding:0;border:none">' +
        '<label class="xfw-label" for="xfw-gate-member-select">Team member in this group</label>' +
        '<select class="xfw-input" id="xfw-gate-member-select">' +
        '<option value="">— Select team member —</option>';
    members.forEach(function (m) {
        html += '<option value="' + m.employee.id + '">' + xfwEsc(m.employee.name) + '</option>';
    });
    html += '</select></div>';

    var pairsEl = root.querySelector('#xfw-gate-member-wrap');
    if (pairsEl) {
        pairsEl.innerHTML = html;
    }

    var sel = root.querySelector('#xfw-gate-member-select');
    if (!sel) {
        return;
    }

    sel.addEventListener('change', function () {
        var id = parseInt(sel.value, 10);
        if (!id) {
            if (convEl) {
                convEl.classList.add('xfw-hidden');
                convEl.innerHTML = '';
            }
            return;
        }

        var member = members.find(function (m) { return m.employee.id === id; });
        if (!member) {
            return;
        }

        xfwPickerState.pendingEmployeeId = member.employee.id;
        xfwPickerState.pairId = member.pair_id || 0;
        var pairCtx = {
            employee: member.employee,
            leader: group.leader || xfwPickerState.dashboard.user || { name: '—' },
        };
        if (member.pair_id) {
            xfwPickerState.pairsMap[member.pair_id] = pairCtx;
        }

        var memberMeetings = (xfwPickerState.meetings || []).filter(function (m) {
            return m.employee && m.employee.id === id &&
                m.group && parseInt(m.group.id, 10) === parseInt(group.id, 10);
        });

        if (memberMeetings.length) {
            xfwRenderGroupMeetings(group, memberMeetings);
        } else if (member.pair_id) {
            xfwLoadGateConversations();
        } else {
            xfwRenderConversationList(pairCtx, 'leader', [], { forceSchedule: true });
        }
    });
};

var xfwRenderGroupPicker = function (groups) {
    var pairsEl = root.querySelector('#xfw-gate-pairs');
    if (!pairsEl) {
        return;
    }

    if (!groups.length) {
        pairsEl.innerHTML = '<div class="xfw-gate-section"><p class="xfw-muted">No company groups found. Ask your admin to add you to a Company Group.</p></div>';
        return;
    }

    var html = '<div class="xfw-gate-section">' +
        '<h3 style="margin:0 0 .5rem">Schedule a new meeting</h3>' +
        '<p class="xfw-muted" style="margin:0 0 .75rem">Select the company group context, then pick a team member (if you are the leader).</p>' +
        '<label class="xfw-label" for="xfw-gate-group-select">Company group</label>' +
        '<select class="xfw-input" id="xfw-gate-group-select">' +
        '<option value="">— Select company group —</option>';

    groups.forEach(function (g) {
        html += '<option value="' + g.id + '">' + xfwEsc(xfwGroupLabel(g)) + '</option>';
    });
    html += '</select><div id="xfw-gate-member-wrap" style="margin-top:.75rem"></div></div>';

    pairsEl.innerHTML = html;

    var sel = pairsEl.querySelector('#xfw-gate-group-select');
    sel.addEventListener('change', function () {
        var id = parseInt(sel.value, 10);
        if (!id) {
            xfwSelectGroup(null);
            return;
        }
        var group = groups.find(function (g) { return g.id === id; });
        if (group) {
            xfwSelectGroup(group);
        }
    });

    if (groups.length === 1) {
        sel.value = String(groups[0].id);
        xfwSelectGroup(groups[0]);
    }
};

var xfwScheduleMeeting = function (dt, link) {
    if (xfwPickerState.pairId > 0) {
        return xfwOoCall('xfusion_oo_schedule', {
            pair_id: xfwPickerState.pairId,
            scheduled_at: dt,
            meeting_link: link,
        });
    }
    if (xfwPickerState.pendingEmployeeId > 0) {
        var payload = {
            employee_user_id: xfwPickerState.pendingEmployeeId,
            scheduled_at: dt,
            meeting_link: link,
        };
        if (xfwPickerState.selectedGroupId > 0) {
            payload.group_id = xfwPickerState.selectedGroupId;
        }
        return xfwOoCall('xfusion_oo_schedule_for_employee', payload);
    }
    return Promise.resolve(null);
};

var xfwRenderConversationList = function (pair, role, rows, options) {
    options = options || {};
    var el = root.querySelector('#xfw-gate-conversations');
    if (!el) {
        return;
    }
    var otherName = role === 'leader'
        ? (pair.employee ? pair.employee.name : 'Employee')
        : (pair.leader ? pair.leader.name : 'Leader');

    var html = '<h3 style="margin:0 0 .5rem">Meetings with ' + xfwEsc(otherName) + '</h3>';
    if (!rows.length) {
        html += '<p class="xfw-muted">No meetings in this context yet — schedule one below.</p>';
    } else {
        html += '<table class="xfw-table"><thead><tr><th>Scheduled</th><th>Status</th><th></th></tr></thead><tbody>';
        rows.forEach(function (c) {
            var fmt = xfwFormatMeetingDate(c.scheduled_at);
            var btnLabel = c.status === 'in_progress' ? 'Resume' : (c.status === 'completed' ? 'View' : 'Open');
            html += '<tr><td>' + xfwEsc(fmt.date) + ' ' + xfwEsc(fmt.time) + '</td>' +
                '<td><span class="xfw-badge ' + xfwStatusBadgeClass(c.status) + '">' + xfwEsc(xfwFormatStatusLabel(c.status)) + '</span></td>' +
                '<td><button type="button" class="xfw-btn xfw-btn-outline xfw-btn-sm" data-open-conversation="' + c.id + '"' +
                ' data-scheduled="' + xfwEsc(c.scheduled_at || '') + '"' +
                ' data-status="' + xfwEsc(c.status || '') + '"' +
                ' data-link="' + xfwEsc(c.meeting_link || '') + '">' + btnLabel + '</button></td></tr>';
        });
        html += '</tbody></table>';
    }

    if (role === 'leader' && (options.forceSchedule || !options.skipSchedule)) {
        html += '<div class="xfw-gate-schedule" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">' +
            '<h4 style="margin:0 0 .5rem">Schedule new meeting</h4>' +
            '<input type="datetime-local" class="xfw-input" id="xfw-gate-new-date" style="margin-bottom:.5rem">' +
            '<input type="url" class="xfw-input" id="xfw-gate-new-link" placeholder="Meeting link (optional)" style="margin-bottom:.5rem">' +
            '<button type="button" class="xfw-btn xfw-btn-accent" id="xfw-gate-schedule-btn" style="width:100%">Schedule &amp; Open</button>' +
            '</div>';
    }

    el.innerHTML = html;
    el.classList.remove('xfw-hidden');

    el.querySelectorAll('[data-open-conversation]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            xfwApplyMeetingContext({
                conversationId: parseInt(btn.getAttribute('data-open-conversation'), 10),
                pairId: xfwPickerState.pairId,
                userRole: role,
                employeeName: pair.employee ? pair.employee.name : '—',
                leaderName: pair.leader ? pair.leader.name : '—',
                scheduledAt: btn.getAttribute('data-scheduled') || '',
                status: btn.getAttribute('data-status') || 'scheduled',
                meetingLink: btn.getAttribute('data-link') || '',
            }, { resetStep: true });
        });
    });

    var schedBtn = el.querySelector('#xfw-gate-schedule-btn');
    if (schedBtn) {
        schedBtn.addEventListener('click', function () {
            var dt = (el.querySelector('#xfw-gate-new-date') || {}).value;
            var link = (el.querySelector('#xfw-gate-new-link') || {}).value || '';
            if (!dt) {
                return;
            }
            schedBtn.disabled = true;
            schedBtn.textContent = 'Scheduling…';
            xfwScheduleMeeting(dt, link).then(function (res) {
                schedBtn.disabled = false;
                schedBtn.textContent = 'Schedule & Open';
                if (!res || !res.success || !res.data) {
                    return;
                }
                var created = res.data;
                if (created.pair_id) {
                    xfwPickerState.pairId = created.pair_id;
                }
                xfwApplyMeetingContext({
                    conversationId: created.id,
                    pairId: created.pair_id || xfwPickerState.pairId,
                    userRole: role,
                    employeeName: pair.employee ? pair.employee.name : (created.employee ? created.employee.name : '—'),
                    leaderName: pair.leader ? pair.leader.name : (created.leader ? created.leader.name : '—'),
                    scheduledAt: created.scheduled_at || dt,
                    status: created.status || 'scheduled',
                    meetingLink: created.meeting_link || link,
                }, { resetStep: true });
            }).catch(function () {
                schedBtn.disabled = false;
                schedBtn.textContent = 'Schedule & Open';
            });
        });
    }
};

var xfwLoadGateConversations = function () {
    var pairId = xfwPickerState.pairId;
    var pair = xfwPickerState.pairsMap[pairId];
    var role = xfwPickerState.userRole;
    var el = root.querySelector('#xfw-gate-conversations');
    if (!pair || !el) {
        return;
    }
    el.innerHTML = '<p class="xfw-muted">Loading meetings…</p>';
    el.classList.remove('xfw-hidden');
    xfwOoCall('xfusion_oo_conversations', { pair_id: pairId }).then(function (res) {
        if (!res || !res.success) {
            el.innerHTML = '<p class="xfw-muted">' + xfwEsc((res && res.message) || 'Unable to load meetings.') + '</p>';
            return;
        }
        xfwRenderConversationList(pair, role, res.data || [], { forceSchedule: role === 'leader' });
    }).catch(function () {
        el.innerHTML = '<p class="xfw-muted">Unable to load meetings.</p>';
    });
};

window.xfwInitMeetingPicker = function () {
    var allEl = root.querySelector('#xfw-gate-all-meetings');
    var pairsEl = root.querySelector('#xfw-gate-pairs');
    var convEl = root.querySelector('#xfw-gate-conversations');
    if (!pairsEl) {
        return;
    }
    if (allEl) {
        allEl.innerHTML = '<p class="xfw-muted">Loading meetings…</p>';
    }
    if (convEl) {
        convEl.classList.add('xfw-hidden');
        convEl.innerHTML = '';
    }
    pairsEl.innerHTML = '<p class="xfw-muted">Loading groups…</p>';

    xfwOoCall('xfusion_oo_meeting_dashboard').then(function (res) {
        if (!res || !res.success || !res.data) {
            var msg = xfwEsc((res && res.message) || 'Unable to load meetings.');
            if (allEl) {
                allEl.innerHTML = '<p class="xfw-muted">' + msg + '</p>';
            }
            pairsEl.innerHTML = '';
            return;
        }

        xfwPickerState.dashboard = res.data;
        xfwPickerState.groups = res.data.groups || [];
        xfwPickerState.meetings = res.data.meetings || [];

        xfwRenderAllMeetings(xfwPickerState.meetings);
        xfwRenderGroupPicker(xfwPickerState.groups);
    }).catch(function () {
        if (allEl) {
            allEl.innerHTML = '<p class="xfw-muted">Unable to load meetings.</p>';
        }
        pairsEl.innerHTML = '';
    });
};

var xfwInitMeetingPickerEmployeeFlow = function () {
    window.xfwInitMeetingPicker();
};

var xfwEnrichConversationContext = function (ctx) {
    return xfwOoCall('xfusion_oo_pairs').then(function (res) {
        if (!res || !res.success) {
            return ctx;
        }
        var pairs = res.data || [];
        if (!pairs.length) {
            return ctx;
        }
        var promises = pairs.map(function (pair) {
            return xfwOoCall('xfusion_oo_conversations', { pair_id: pair.id }).then(function (cres) {
                if (!cres || !cres.success) {
                    return null;
                }
                var match = (cres.data || []).find(function (c) {
                    return parseInt(c.id, 10) === parseInt(ctx.conversationId, 10);
                });
                if (!match) {
                    return null;
                }
                return {
                    conversationId: ctx.conversationId,
                    pairId: pair.id,
                    userRole: pair.role || ctx.userRole || '',
                    employeeName: pair.employee ? pair.employee.name : '—',
                    leaderName: pair.leader ? pair.leader.name : '—',
                    scheduledAt: match.scheduled_at || '',
                    status: match.status || 'scheduled',
                    meetingLink: match.meeting_link || '',
                };
            });
        });
        return Promise.all(promises).then(function (results) {
            for (var i = 0; i < results.length; i++) {
                if (results[i]) {
                    return results[i];
                }
            }
            return ctx;
        });
    }).catch(function () {
        return ctx;
    });
};

var xfwResolveInitialContext = function () {
    var id = parseInt(window.XFW_WIZARD.conversationId, 10) || xfwReadUrlConversationId() || 0;
    if (id > 0) {
        var stored = xfwLoadStoredContext();
        if (stored && parseInt(stored.conversationId, 10) === id) {
            return stored;
        }
        return {
            conversationId: id,
            pairId: window.XFW_WIZARD.pairId || 0,
            userRole: window.XFW_WIZARD.userRole || '',
            employeeName: '—',
            leaderName: '—',
            scheduledAt: '',
            status: 'scheduled',
            meetingLink: '',
        };
    }
    var stored = xfwLoadStoredContext();
    if (stored && stored.conversationId) {
        return stored;
    }
    return null;
};

var xfwInitMeetingGate = function () {
    var changeBtn = root.querySelector('#xfw-change-meeting');
    if (changeBtn && !changeBtn.dataset.wired) {
        changeBtn.dataset.wired = '1';
        changeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            xfwShowMeetingGate();
        });
    }
    var ctx = xfwResolveInitialContext();
    if (ctx && ctx.conversationId) {
        var needsEnrich = !ctx.scheduledAt || ctx.employeeName === '—' || !ctx.userRole;
        if (needsEnrich) {
            xfwEnrichConversationContext(ctx).then(function (enriched) {
                xfwApplyMeetingContext(enriched, { resetStep: false });
            });
            return;
        }
        xfwApplyMeetingContext(ctx, { resetStep: false });
        return;
    }
    xfwShowWizardWorkspace(false);
    window.xfwInitMeetingPicker();
};

/* xfwInitMeetingGate() is called from load-draft.php after all scripts are ready */
JS;
}
