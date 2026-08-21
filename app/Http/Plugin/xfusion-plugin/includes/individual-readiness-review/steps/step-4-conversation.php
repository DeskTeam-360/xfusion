<?php
/**
 * Step 4 — Development Conversation™.
 *
 * Conversation Notes + Conversation Agreement (date + dual digital
 * signatures) are real: loaded/saved via Laravel
 * (wp_fusion_360_conversation_agreements). Employee/leader names come from
 * the review itself. Each party can only sign their own side — enforced
 * both client-side (button hidden for the other role) and server-side
 * (403 if the signing user isn't actually that party).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_conversation_js(): string
{
    return <<<'JS'
conversation: function () {
    return '<h2 class="xirr-section-title">Step 4. Development Conversation™</h2>' +
        '<p class="xirr-section-desc">This is a collaborative coaching conversation between you and your leader.<br>Discuss the evidence, patterns, and insights to deepen understanding and plan your future growth.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>Focus on learning, alignment, and development. This is not a performance evaluation.</span></div>' +

        '<div class="xirr-card"><h4 style="margin-top:0">Conversation Focus Areas</h4>' +
        '<div class="xirr-guide-grid" style="grid-template-columns:repeat(6,minmax(0,1fr))">' +
        [['&#128200;','Review Evidence'],['&#128202;','Discuss Patterns'],['&#10024;','Explore Strengths'],
         ['&#128161;','Identify Growth Opportunities'],['&#128101;','Align on Support Needs'],['&#127919;','Plan for Future Success']].map(function (f) {
            return '<div style="text-align:center"><div style="font-size:1.6rem">' + f[0] + '</div><div style="font-size:13px;font-weight:600;color:var(--navy);margin-top:.4rem">' + f[1] + '</div></div>';
        }).join('') + '</div></div>' +

        '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1.4fr 1fr;gap:1rem;margin-bottom:1rem">' +
        '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">Conversation Guide</h4>' +
        '<ol style="margin:0;padding-left:1.2rem">' +
        '<li style="margin-bottom:.6rem"><strong>Review Key Insights</strong><br><span class="xirr-muted">Start with the AI assessment summary and key themes.</span></li>' +
        '<li style="margin-bottom:.6rem"><strong>Explore Strengths</strong><br><span class="xirr-muted">Discuss what went well and what drove your success.</span></li>' +
        '<li style="margin-bottom:.6rem"><strong>Discuss Opportunities</strong><br><span class="xirr-muted">Talk through growth areas and potential blind spots.</span></li>' +
        '<li style="margin-bottom:.6rem"><strong>Assess Alignment</strong><br><span class="xirr-muted">Review alignment with team, organizational, and ARP priorities.</span></li>' +
        '<li style="margin-bottom:.6rem"><strong>Identify Support Needs</strong><br><span class="xirr-muted">Determine resources, coaching, or tools that will help.</span></li>' +
        '<li><strong>Look Ahead</strong><br><span class="xirr-muted">Discuss future goals and the path forward.</span></li>' +
        '</ol></div>' +
        '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">Conversation Tips</h4>' +
        '<ul class="xirr-check-list">' +
        '<li>Listen actively and ask open-ended questions.</li>' +
        '<li>Use evidence to support observations.</li>' +
        '<li>Focus on growth, not gaps.</li>' +
        '<li>Create a safe space for honesty and reflection.</li>' +
        '</ul>' +
        '<h4 style="margin-bottom:.3rem">Duration Guideline</h4>' +
        '<p style="font-weight:700;color:var(--navy);margin:0">30 – 45 minutes</p>' +
        '<p class="xirr-muted" style="margin:.2rem 0 0">Recommended time for a meaningful conversation.</p>' +
        '</div></div>' +

        '<div id="xirr-conversation-body"><p class="xirr-muted">Loading…</p></div>';
}
JS;
}

function xfirr_wizard_conversation_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function signatureBox(role, name, signedAt, signedName, canSign) {
        var signed = !!signedAt;
        return '<div class="xirr-signature-box">' +
            '<div class="name">' + esc(name) + '</div><div class="role">' + (role === 'employee' ? 'Employee' : 'Leader') + '</div>' +
            (signed
                ? '<span class="xirr-signed-badge" id="xirr-signed-' + role + '">&#10003; Signed' + (signedName ? ' by ' + esc(signedName) : '') + '</span>'
                : (canSign
                    ? '<button type="button" class="xirr-btn xirr-btn-outline xirr-btn-sm" id="xirr-sign-' + role + '">&#9999;&#65039; Sign</button>'
                    : '<span class="xirr-muted" style="font-size:13px">Awaiting signature</span>')) +
            '</div>';
    }

    function renderBody(data) {
        var yourRole = data.your_role;
        var employeeName = (window.XFIRR_WIZARD && window.XFIRR_WIZARD.employeeName) || 'Employee';
        var leaderName = (window.XFIRR_WIZARD && window.XFIRR_WIZARD.managerName) || 'Leader';
        var canEditNotes = !!(window.XFIRR_WIZARD && window.XFIRR_WIZARD.canEdit) || !!yourRole;

        return '<div class="xirr-card"><h4 style="margin-top:0">Conversation Notes</h4>' +
            '<p class="xirr-muted" style="margin-top:-.3rem">Capture key takeaways from your discussion.</p>' +
            '<textarea class="xirr-input" id="xirr-conversation-notes" rows="4" placeholder="Add notes about key insights, agreements, and next steps..."' +
            (canEditNotes ? '' : ' disabled') + '>' + esc(data.conversation_notes || '') + '</textarea>' +
            '<p class="xirr-muted" style="margin:.4rem 0 0;font-size:13px">Notes are private to you and your leader.</p>' +
            '<p class="xirr-muted" id="xirr-conversation-notes-status" style="margin:.3rem 0 0;font-size:13px"></p>' +
            '</div>' +

            '<div class="xirr-card"><h4 style="margin-top:0">Conversation Agreement</h4>' +
            '<p class="xirr-muted" style="margin-top:-.3rem">We acknowledge this conversation took place on:</p>' +
            '<div class="xirr-row" style="margin-bottom:1rem"><input type="date" class="xirr-input" id="xirr-agreement-date" style="max-width:12rem" value="' + esc(data.conversation_date || '') + '"' +
            (canEditNotes ? '' : ' disabled') + '></div>' +
            '<div class="xirr-signature-row">' +
            signatureBox('employee', employeeName, data.employee_signed_at, data.employee_signature_name, yourRole === 'employee') +
            signatureBox('leader', leaderName, data.leader_signed_at, data.leader_signature_name, yourRole === 'leader') +
            '</div>' +
            '<p class="xirr-muted" style="margin:.6rem 0 0;font-size:13px">Digital signatures confirm the conversation occurred. This does not indicate agreement with all content.</p>' +
            '<p class="xirr-muted" id="xirr-agreement-status" style="margin:.4rem 0 0;font-size:13px"></p>' +
            '</div>' +

            '<p class="xirr-muted" style="margin-top:.3rem">Use the <b>Save Draft</b> button below to save your conversation notes and agreement date.</p>';
    }

    function bindInteractions(data) {
        var notesEl = document.getElementById('xirr-conversation-notes');
        var notesStatus = document.getElementById('xirr-conversation-notes-status');
        var agreementStatus = document.getElementById('xirr-agreement-status');

        ['employee', 'leader'].forEach(function (role) {
            var btn = document.getElementById('xirr-sign-' + role);
            if (!btn || btn.dataset.wired) return;
            btn.dataset.wired = '1';
            btn.addEventListener('click', function () {
                if (btn.dataset.busy === '1' || typeof window.xfirrSaveConversation !== 'function') return;
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.textContent = 'Signing…';
                window.xfirrSaveConversation({ sign_role: role }).then(function (json) {
                    if (!json || !json.success) {
                        btn.dataset.busy = '';
                        btn.disabled = false;
                        btn.textContent = '✏️ Sign';
                        if (agreementStatus) agreementStatus.textContent = (json && json.message) ? json.message : 'Failed to sign.';
                        return;
                    }
                    var body = document.getElementById('xirr-conversation-body');
                    if (body) {
                        body.innerHTML = renderBody(json.data);
                        bindInteractions(json.data);
                    }
                }).catch(function () {
                    btn.dataset.busy = '';
                    btn.disabled = false;
                    btn.textContent = '✏️ Sign';
                    if (agreementStatus) agreementStatus.textContent = 'Failed to sign — network error.';
                });
            });
        });

        if (notesEl && !notesEl.disabled) {
            var saveTimer = null;
            notesEl.addEventListener('blur', function () {
                if (typeof window.xfirrSaveConversation !== 'function') return;
                if (notesStatus) notesStatus.textContent = 'Saving…';
                window.xfirrSaveConversation({ conversation_notes: notesEl.value }).then(function (json) {
                    if (notesStatus) {
                        notesStatus.textContent = (json && json.success) ? '✓ Notes saved' : ((json && json.message) || 'Failed to save notes.');
                    }
                }).catch(function () {
                    if (notesStatus) notesStatus.textContent = 'Failed to save notes — network error.';
                });
            });
        }

        var dateEl = document.getElementById('xirr-agreement-date');
        if (dateEl && !dateEl.disabled) {
            dateEl.addEventListener('change', function () {
                if (typeof window.xfirrSaveConversation !== 'function' || !dateEl.value) return;
                window.xfirrSaveConversation({ conversation_date: dateEl.value });
            });
        }
    }

    window.initConversationStep = function () {
        var host = document.getElementById('xirr-conversation-body');
        if (!host) return;
        if (typeof window.xfirrLoadConversation !== 'function') {
            host.innerHTML = '<p class="xirr-muted">Conversation service unavailable.</p>';
            return;
        }
        window.xfirrLoadConversation().then(function (data) {
            data = data || {};
            host.innerHTML = renderBody(data);
            bindInteractions(data);
        });
    };

    window.xirrSaveConversationStep = function () {
        var notesEl = document.getElementById('xirr-conversation-notes');
        if (!notesEl || typeof window.xfirrSaveConversation !== 'function') {
            return Promise.resolve({ success: false });
        }
        return window.xfirrSaveConversation({ conversation_notes: notesEl.value });
    };
})();
JS;
}
