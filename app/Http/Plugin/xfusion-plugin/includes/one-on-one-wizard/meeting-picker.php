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

/**
 * Gate markup — shared by standalone picker and legacy embedded gate.
 */
function xfoo_meeting_picker_gate_markup(): string
{
    return '<div id="xfw-meeting-gate" class="xfw-meeting-gate">' .
        '<div class="xfw-card xfw-gate-card">' .
        '<h2 class="xfw-section-title" style="margin-top:0">Select your 1-on-1 meeting</h2>' .
        '<p class="xfw-section-desc">Open an existing meeting from <strong>Your meetings</strong>, or schedule a new one if you are a leader.</p>' .
        '<div class="xfw-gate-columns">' .
        '<div class="xfw-gate-col xfw-gate-col-schedule">' .
        '<h3 class="xfw-gate-col-title">Schedule a new meeting</h3>' .
        '<p class="xfw-muted xfw-gate-col-desc">Leaders: select a company group and team member, then set date and time.</p>' .
        '<div id="xfw-gate-pairs"></div>' .
        '<div id="xfw-gate-conversations" class="xfw-hidden"></div>' .
        '</div>' .
        '<div class="xfw-gate-col xfw-gate-col-meetings">' .
        '<h3 class="xfw-gate-col-title">Your meetings</h3>' .
        '<p class="xfw-muted xfw-gate-col-desc">All scheduled and past meetings across your company groups.</p>' .
        '<div id="xfw-gate-all-meetings"></div>' .
        '</div>' .
        '</div></div></div>';
}

/**
 * Standalone meeting picker (like ARP picker) — shown when no conversation_id.
 */
function xfoo_render_meeting_picker_gate(): string
{
    $css      = xfoo_wizard_styles_css();
    $pickerJs = xfoo_wizard_meeting_picker_js();

    $wizardConfig = [
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'ooNonce'        => wp_create_nonce('xfusion_one_on_one'),
        'userId'         => get_current_user_id(),
        'conversationId' => 0,
        'pairId'         => 0,
        'userRole'       => '',
    ];

    ob_start();
    ?>
<div id="xfoo-wiz" data-conversation-id="0">

    <div class="xfw-header">
        <div class="xfw-header-inner">
            <div>
                <h1>1-ON-1 ALIGNMENT CAPTURE&trade; INTERACTIVE TOOL</h1>
                <p>Continuous Alignment Process</p>
            </div>
        </div>
    </div>

    <?php echo xfoo_meeting_picker_gate_markup(); ?>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
window.XFW_WIZARD = <?php echo wp_json_encode($wizardConfig); ?>;
<?php echo $pickerJs; ?>

var root = document.getElementById('xfoo-wiz');
if (root && typeof window.xfwInitMeetingPicker === 'function') {
    window.xfwInitMeetingPicker();
}
})();
</script>
    <?php

    return (string) ob_get_clean();
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

var xfwStatusSelectOptions = [
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

var xfwWireStatusSelect = function () {
    var statusEl = root.querySelector('#xfw-si-status');
    if (!statusEl || statusEl.dataset.wired) {
        return;
    }
    statusEl.dataset.wired = '1';
    statusEl.addEventListener('change', function (e) {
        if (!e.target || e.target.id !== 'xfw-si-status-select') {
            return;
        }
        xfwUpdateMeetingStatus(e.target.value, e.target);
    });
};

var xfwRenderSidebarStatus = function (status) {
    var statusEl = root.querySelector('#xfw-si-status');
    if (!statusEl) {
        return;
    }
    xfwWireStatusSelect();
    var key = String(status || 'scheduled').toLowerCase();
    var sel = statusEl.querySelector('#xfw-si-status-select');
    if (!sel) {
        var html = '<select class="xfw-input xfw-status-select" id="xfw-si-status-select">';
        xfwStatusSelectOptions.forEach(function (opt) {
            html += '<option value="' + opt.value + '">' + xfwEsc(opt.label) + '</option>';
        });
        html += '</select>';
        statusEl.innerHTML = html;
        sel = statusEl.querySelector('#xfw-si-status-select');
    }
    if (sel) {
        sel.value = key;
        sel.dataset.prev = key;
    }
};

var xfwUpdateMeetingStatus = function (newStatus, selEl) {
    var conversationId = parseInt(window.XFW_WIZARD.conversationId, 10);
    if (conversationId < 1 || !selEl) {
        return;
    }
    var prevStatus = selEl.dataset.prev || selEl.value;
    if (newStatus === prevStatus) {
        return;
    }
    selEl.disabled = true;
    xfwOoCall('xfusion_oo_update_status', {
        conversation_id: conversationId,
        status: newStatus,
    }).then(function (res) {
        selEl.disabled = false;
        if (!res || !res.success) {
            selEl.value = prevStatus;
            return;
        }
        var updated = (res.data && res.data.status) ? res.data.status : newStatus;
        selEl.value = updated;
        selEl.dataset.prev = updated;
        var stored = xfwLoadStoredContext();
        if (stored && parseInt(stored.conversationId, 10) === conversationId) {
            stored.status = updated;
            xfwSaveStoredContext(stored);
        }
    }).catch(function () {
        selEl.disabled = false;
        selEl.value = prevStatus;
    });
};

var xfwMeetingTiming = function (meeting) {
    var status = String(meeting.status || 'scheduled').toLowerCase();
    if (status === 'completed') {
        return { key: 'completed', label: 'Completed', badge: 'green' };
    }
    if (status === 'cancelled') {
        return { key: 'cancelled', label: 'Cancelled', badge: 'gray' };
    }
    if (status === 'in_progress') {
        return { key: 'in_progress', label: 'In session', badge: 'blue' };
    }

    var scheduled = meeting.scheduled_at ? new Date(meeting.scheduled_at) : null;
    if (!scheduled || isNaN(scheduled.getTime())) {
        return { key: 'scheduled', label: 'Scheduled', badge: 'amber' };
    }

    var now = new Date();
    if (scheduled.getTime() < now.getTime()) {
        return { key: 'overdue', label: 'Overdue', badge: 'red' };
    }

    var startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var endOfToday = new Date(startOfToday.getTime() + 86400000 - 1);
    if (scheduled >= startOfToday && scheduled <= endOfToday) {
        return { key: 'today', label: 'Today', badge: 'amber' };
    }

    var inSevenDays = new Date(now.getTime() + 7 * 86400000);
    if (scheduled <= inSevenDays) {
        return { key: 'upcoming', label: 'Upcoming', badge: 'blue' };
    }

    return { key: 'later', label: 'Later', badge: 'gray' };
};

var xfwMeetingTimingSort = function (a, b) {
    var order = { overdue: 0, in_progress: 1, today: 2, upcoming: 3, scheduled: 4, later: 5, completed: 6, cancelled: 7 };
    var ta = xfwMeetingTiming(a);
    var tb = xfwMeetingTiming(b);
    var oa = order[ta.key] !== undefined ? order[ta.key] : 99;
    var ob = order[tb.key] !== undefined ? order[tb.key] : 99;
    if (oa !== ob) {
        return oa - ob;
    }
    var da = a.scheduled_at ? new Date(a.scheduled_at).getTime() : 0;
    var db = b.scheduled_at ? new Date(b.scheduled_at).getTime() : 0;
    if (ta.key === 'completed' || ta.key === 'cancelled') {
        return db - da;
    }
    return da - db;
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
        xfwRenderSidebarStatus(ctx.status);
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

var xfwHasWizardWorkspace = function () {
    return !!(root && root.querySelector('#xfw-wizard-workspace'));
};

var xfwNavigateToConversation = function (conversationId) {
    if (!conversationId) {
        return;
    }
    try {
        var url = new URL(window.location.href);
        url.searchParams.set('conversation_id', String(conversationId));
        window.location.href = url.toString();
    } catch (e) {
        window.location.href = window.location.pathname + '?conversation_id=' + encodeURIComponent(String(conversationId));
    }
};

window.xfooBackToMeetingPicker = function () {
    try {
        var url = new URL(window.location.href);
        url.searchParams.delete('conversation_id');
        window.location.href = url.toString();
    } catch (e) {
        window.location.href = window.location.pathname;
    }
};

var xfwApplyMeetingContext = function (ctx, options) {
    options = options || {};
    if (!ctx || !ctx.conversationId) {
        return;
    }
    if (!xfwHasWizardWorkspace()) {
        xfwNavigateToConversation(ctx.conversationId);
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
    if (prevId !== ctx.conversationId && typeof xfwResetSynthesisCache === 'function') {
        xfwResetSynthesisCache();
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
    if (typeof window.xfooBackToMeetingPicker === 'function') {
        window.xfooBackToMeetingPicker();
        return;
    }
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
    meetingsTimingFilter: 'all',
    meetingsRoleFilter: 'all',
    meetingsPage: 1,
    meetingsPerPage: 10,
};

var xfwMeetingsFilterOptions = [
    { value: 'all', label: 'All timings' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'in_progress', label: 'In session' },
    { value: 'today', label: 'Today' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'later', label: 'Later' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

var xfwMeetingsRoleOptions = [
    { value: 'all', label: 'All roles' },
    { value: 'leader', label: 'Leader' },
    { value: 'employee', label: 'Employee' },
];

var xfwFilterMeetingsList = function (meetings) {
    var timingFilter = xfwPickerState.meetingsTimingFilter || 'all';
    var roleFilter = xfwPickerState.meetingsRoleFilter || 'all';

    return meetings.filter(function (m) {
        if (roleFilter !== 'all' && String(m.user_role || '').toLowerCase() !== roleFilter) {
            return false;
        }
        if (timingFilter === 'all') {
            return true;
        }
        return xfwMeetingTiming(m).key === timingFilter;
    });
};

var xfwWireMeetingsListControls = function () {
    var host = root.querySelector('.xfw-gate-col-meetings');
    if (!host || host.dataset.meetingsWired) {
        return;
    }
    host.dataset.meetingsWired = '1';

    host.addEventListener('change', function (e) {
        if (!e.target) {
            return;
        }
        if (e.target.id === 'xfw-meetings-timing-filter') {
            xfwPickerState.meetingsTimingFilter = e.target.value || 'all';
            xfwPickerState.meetingsPage = 1;
            xfwRenderAllMeetings();
            return;
        }
        if (e.target.id === 'xfw-meetings-role-filter') {
            xfwPickerState.meetingsRoleFilter = e.target.value || 'all';
            xfwPickerState.meetingsPage = 1;
            xfwRenderAllMeetings();
        }
    });

    host.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-meetings-page]') : null;
        if (!btn || btn.disabled) {
            return;
        }
        var page = parseInt(btn.getAttribute('data-meetings-page'), 10);
        if (!isNaN(page) && page > 0) {
            xfwPickerState.meetingsPage = page;
            xfwRenderAllMeetings();
        }
    });
};

var xfwRenderAllMeetings = function () {
    var el = root.querySelector('#xfw-gate-all-meetings');
    if (!el) {
        return;
    }

    var meetings = xfwPickerState.meetings || [];
    var html = '';

    if (!meetings.length) {
        html += '<p class="xfw-muted">No meetings yet. Leaders can schedule a new 1-on-1 using the form on the left.</p>';
        el.innerHTML = html;
        return;
    }

    var filtered = xfwFilterMeetingsList(meetings);
    var sorted = filtered.slice().sort(xfwMeetingTimingSort);
    var perPage = xfwPickerState.meetingsPerPage || 10;
    var total = sorted.length;
    var totalPages = Math.max(1, Math.ceil(total / perPage));
    var page = Math.min(Math.max(1, xfwPickerState.meetingsPage || 1), totalPages);
    xfwPickerState.meetingsPage = page;
    var startIdx = (page - 1) * perPage;
    var pageRows = sorted.slice(startIdx, startIdx + perPage);

    html += '<div class="xfw-meetings-toolbar">' +
        '<div class="xfw-meetings-filters">' +
        '<div class="xfw-meetings-filter-field">' +
        '<label class="xfw-label" for="xfw-meetings-timing-filter">Timing</label>' +
        '<select class="xfw-input" id="xfw-meetings-timing-filter">';
    xfwMeetingsFilterOptions.forEach(function (opt) {
        html += '<option value="' + opt.value + '"' + (xfwPickerState.meetingsTimingFilter === opt.value ? ' selected' : '') + '>' +
            xfwEsc(opt.label) + '</option>';
    });
    html += '</select></div>' +
        '<div class="xfw-meetings-filter-field">' +
        '<label class="xfw-label" for="xfw-meetings-role-filter">Your role</label>' +
        '<select class="xfw-input" id="xfw-meetings-role-filter">';
    xfwMeetingsRoleOptions.forEach(function (opt) {
        html += '<option value="' + opt.value + '"' + (xfwPickerState.meetingsRoleFilter === opt.value ? ' selected' : '') + '>' +
            xfwEsc(opt.label) + '</option>';
    });
    html += '</select></div></div></div>';

    if (!total) {
        html += '<p class="xfw-muted">No meetings match the selected filters.</p>';
        el.innerHTML = html;
        return;
    }

    html += '<div class="xfw-meetings-table-wrap"><table class="xfw-table"><thead><tr>' +
        '<th>Timing</th><th>Group</th><th>With</th><th>Your role</th><th>Scheduled</th><th></th>' +
        '</tr></thead><tbody>';

    pageRows.forEach(function (m) {
        var fmt = xfwFormatMeetingDate(m.scheduled_at);
        var timing = xfwMeetingTiming(m);
        var btnLabel = m.status === 'in_progress' ? 'Resume' : (m.status === 'completed' ? 'View' : 'Open');
        html += '<tr><td><span class="xfw-badge ' + timing.badge + '">' + xfwEsc(timing.label) + '</span></td>' +
            '<td>' + xfwEsc(m.group ? m.group.title : '—') + '</td>' +
            '<td>' + xfwEsc(m.counterpart_name || '—') + '</td>' +
            '<td><span class="xfw-badge amber">' + xfwEsc(m.user_role || '') + '</span></td>' +
            '<td>' + xfwEsc(fmt.date) + ' ' + xfwEsc(fmt.time) + '</td>' +
            '<td><button type="button" class="xfw-badge green xfw-meeting-open-btn" data-open-meeting-id="' + m.id + '">' + btnLabel + '</button></td></tr>';
    });

    html += '</tbody></table></div>';

    var rangeStart = startIdx + 1;
    var rangeEnd = startIdx + pageRows.length;
    html += '<div class="xfw-meetings-pagination">' +
        '<span class="xfw-muted">Showing ' + rangeStart + '–' + rangeEnd + ' of ' + total + '</span>' +
        '<div class="xfw-meetings-pagination-actions">' +
        '<button type="button" class="xfw-meetings-page-btn" data-meetings-page="' + (page - 1) + '"' +
        (page <= 1 ? ' disabled' : '') + '>Previous</button>' +
        '<span class="xfw-muted">Page ' + page + ' of ' + totalPages + '</span>' +
        '<button type="button" class="xfw-meetings-page-btn" data-meetings-page="' + (page + 1) + '"' +
        (page >= totalPages ? ' disabled' : '') + '>Next</button>' +
        '</div></div>';

    el.innerHTML = html;

    el.querySelectorAll('[data-open-meeting-id]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var meetingId = parseInt(btn.getAttribute('data-open-meeting-id'), 10);
            if (isNaN(meetingId)) {
                return;
            }
            var row = meetings.find(function (m) { return parseInt(m.id, 10) === meetingId; });
            if (row) {
                xfwOpenMeetingRow(row);
            }
        });
    });
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

    if (convEl) {
        convEl.classList.add('xfw-hidden');
        convEl.innerHTML = '';
    }
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

        xfwRenderSchedulePanel(pairCtx, 'leader');
    });
};

var xfwRenderGroupPicker = function (groups) {
    var pairsEl = root.querySelector('#xfw-gate-pairs');
    if (!pairsEl) {
        return;
    }

    if (!groups.length) {
        pairsEl.innerHTML = '<p class="xfw-muted">No company groups found. Ask your admin to add you to a Company Group.</p>';
        return;
    }

    var html = '<label class="xfw-label" for="xfw-gate-group-select">Company group</label>' +
        '<select class="xfw-input" id="xfw-gate-group-select">' +
        '<option value="">— Select company group —</option>';

    groups.forEach(function (g) {
        html += '<option value="' + g.id + '">' + xfwEsc(xfwGroupLabel(g)) + '</option>';
    });
    html += '</select><div id="xfw-gate-member-wrap" style="margin-top:.75rem"></div>';

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

var xfwRenderSchedulePanel = function (pair, role) {
    var el = root.querySelector('#xfw-gate-conversations');
    if (!el) {
        return;
    }

    if (role !== 'leader') {
        el.classList.add('xfw-hidden');
        el.innerHTML = '';
        return;
    }

    var employeeName = pair.employee ? pair.employee.name : 'team member';
    var html = '<div class="xfw-gate-schedule">' +
        '<h4 style="margin:0 0 .35rem">Schedule new meeting</h4>' +
        '<p class="xfw-muted" style="margin:0 0 .75rem;font-size:.85rem">With ' + xfwEsc(employeeName) + '. Open existing meetings from <strong>Your meetings</strong> on the right.</p>' +
        '<input type="datetime-local" class="xfw-input" id="xfw-gate-new-date" style="margin-bottom:.5rem">' +
        '<input type="url" class="xfw-input" id="xfw-gate-new-link" placeholder="Meeting link (optional)" style="margin-bottom:.5rem">' +
        '<button type="button" class="xfw-btn xfw-btn-accent" id="xfw-gate-schedule-btn" style="width:100%">Schedule &amp; Open</button>' +
        '</div>';

    el.innerHTML = html;
    el.classList.remove('xfw-hidden');

    var schedBtn = el.querySelector('#xfw-gate-schedule-btn');
    if (!schedBtn) {
        return;
    }

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
        xfwPickerState.meetingsPage = 1;

        xfwWireMeetingsListControls();
        xfwRenderAllMeetings();
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
    if (id < 1) {
        return null;
    }
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
};

var xfwInitMeetingGate = function () {
    xfwWireStatusSelect();
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
    if (!xfwHasWizardWorkspace()) {
        window.xfwInitMeetingPicker();
        return;
    }
    xfwShowMeetingGate();
};

/* xfwInitMeetingGate() is called from load-draft.php after all scripts are ready */
JS;
}
