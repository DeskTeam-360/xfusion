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
        statusEl.innerHTML = '<span class="xfw-badge ' + xfwStatusBadgeClass(ctx.status) + '">' + xfwEsc(ctx.status || 'scheduled') + '</span>';
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

var xfwPickerState = { pairsMap: {}, pairId: 0, userRole: '' };

var xfwRenderConversationList = function (pair, role, rows) {
    var el = root.querySelector('#xfw-gate-conversations');
    if (!el) {
        return;
    }
    var otherName = role === 'leader'
        ? (pair.employee ? pair.employee.name : 'Employee')
        : (pair.leader ? pair.leader.name : 'Leader');
    var active = (rows || []).filter(function (c) {
        return c.status === 'scheduled' || c.status === 'in_progress';
    });

    if (active.length === 1 && rows.length <= 3) {
        var only = active[0];
        xfwApplyMeetingContext({
            conversationId: only.id,
            pairId: xfwPickerState.pairId,
            userRole: role,
            employeeName: pair.employee ? pair.employee.name : '—',
            leaderName: pair.leader ? pair.leader.name : '—',
            scheduledAt: only.scheduled_at || '',
            status: only.status || 'scheduled',
            meetingLink: only.meeting_link || '',
        }, { resetStep: true });
        return;
    }

    var html = '<h3 style="margin:0 0 .5rem">Meetings with ' + xfwEsc(otherName) + '</h3>';
    if (!rows.length) {
        html += '<p class="xfw-muted">No meetings scheduled yet.</p>';
    } else {
        html += '<table class="xfw-table"><thead><tr><th>Scheduled</th><th>Status</th><th></th></tr></thead><tbody>';
        rows.forEach(function (c) {
            var fmt = xfwFormatMeetingDate(c.scheduled_at);
            var btnLabel = c.status === 'in_progress' ? 'Resume' : (c.status === 'completed' ? 'View' : 'Open');
            html += '<tr><td>' + xfwEsc(fmt.date) + ' ' + xfwEsc(fmt.time) + '</td>' +
                '<td><span class="xfw-badge ' + xfwStatusBadgeClass(c.status) + '">' + xfwEsc(c.status) + '</span></td>' +
                '<td><button type="button" class="xfw-btn xfw-btn-outline xfw-btn-sm" data-open-conversation="' + c.id + '"' +
                ' data-scheduled="' + xfwEsc(c.scheduled_at || '') + '"' +
                ' data-status="' + xfwEsc(c.status || '') + '"' +
                ' data-link="' + xfwEsc(c.meeting_link || '') + '">' + btnLabel + '</button></td></tr>';
        });
        html += '</tbody></table>';
    }

    if (role === 'leader') {
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
            xfwOoCall('xfusion_oo_schedule', {
                pair_id: xfwPickerState.pairId,
                scheduled_at: dt,
                meeting_link: link,
            }).then(function (res) {
                schedBtn.disabled = false;
                schedBtn.textContent = 'Schedule & Open';
                if (!res || !res.success || !res.data) {
                    return;
                }
                var created = res.data;
                xfwApplyMeetingContext({
                    conversationId: created.id,
                    pairId: xfwPickerState.pairId,
                    userRole: role,
                    employeeName: pair.employee ? pair.employee.name : '—',
                    leaderName: pair.leader ? pair.leader.name : '—',
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
        xfwRenderConversationList(pair, role, res.data || []);
    }).catch(function () {
        el.innerHTML = '<p class="xfw-muted">Unable to load meetings.</p>';
    });
};

window.xfwInitMeetingPicker = function () {
    var pairsEl = root.querySelector('#xfw-gate-pairs');
    var convEl = root.querySelector('#xfw-gate-conversations');
    if (!pairsEl) {
        return;
    }
    if (convEl) {
        convEl.classList.add('xfw-hidden');
        convEl.innerHTML = '';
    }
    pairsEl.innerHTML = '<p class="xfw-muted">Loading pairings…</p>';

    xfwOoCall('xfusion_oo_pairs').then(function (res) {
        if (!res || !res.success) {
            pairsEl.innerHTML = '<p class="xfw-muted">' + xfwEsc((res && res.message) || 'Unable to load pairings.') + '</p>';
            return;
        }
        var pairs = res.data || [];
        if (!pairs.length) {
            pairsEl.innerHTML = '<p class="xfw-muted">No 1-on-1 pairings found for your account.</p>';
            return;
        }
        xfwPickerState.pairsMap = {};
        pairs.forEach(function (p) { xfwPickerState.pairsMap[p.id] = p; });

        if (pairs.length === 1) {
            var p = pairs[0];
            xfwPickerState.pairId = p.id;
            xfwPickerState.userRole = p.role;
            pairsEl.innerHTML = '<p class="xfw-muted"><strong>Pairing:</strong> ' +
                xfwEsc(p.role === 'leader' ? (p.employee ? p.employee.name : '—') : (p.leader ? p.leader.name : '—')) +
                ' <span class="xfw-badge amber">' + xfwEsc(p.role) + '</span></p>';
            xfwLoadGateConversations();
            return;
        }

        var html = '<label class="xfw-label" for="xfw-gate-pair-select">Select pairing</label>' +
            '<select class="xfw-input" id="xfw-gate-pair-select">';
        pairs.forEach(function (p) {
            var other = p.role === 'leader' ? p.employee : p.leader;
            html += '<option value="' + p.id + '" data-role="' + xfwEsc(p.role) + '">' +
                xfwEsc(other ? other.name : '—') + ' (' + xfwEsc(p.role) + ')</option>';
        });
        html += '</select>';
        pairsEl.innerHTML = html;

        var sel = pairsEl.querySelector('#xfw-gate-pair-select');
        var onPairChange = function () {
            xfwPickerState.pairId = parseInt(sel.value, 10);
            xfwPickerState.userRole = sel.selectedOptions[0].getAttribute('data-role') || '';
            xfwLoadGateConversations();
        };
        sel.addEventListener('change', onPairChange);
        onPairChange();
    }).catch(function () {
        pairsEl.innerHTML = '<p class="xfw-muted">Unable to load pairings.</p>';
    });
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
