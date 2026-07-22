<?php
/**
 * Step 4 — Development Conversation™.
 *
 * UI-only prototype: static dummy content matching the IRR mockups
 * (focus area icons, conversation guide, tips, notes textarea, and a
 * two-party digital-signature agreement block). No Laravel calls are made
 * from this step for now — notes/signatures are local-only.
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

        '<div class="xirr-card"><h4 style="margin-top:0">Conversation Notes</h4>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">Capture key takeaways from your discussion.</p>' +
        '<textarea class="xirr-input" id="xirr-conversation-notes" rows="4" placeholder="Add notes about key insights, agreements, and next steps..."></textarea>' +
        '<p class="xirr-muted" style="margin:.4rem 0 0;font-size:13px">Notes are private to you and your leader.</p>' +
        '</div>' +

        '<div class="xirr-card"><h4 style="margin-top:0">Conversation Agreement</h4>' +
        '<p class="xirr-muted" style="margin-top:-.3rem">We acknowledge this conversation took place on:</p>' +
        '<div class="xirr-row" style="margin-bottom:1rem"><input type="date" class="xirr-input" id="xirr-agreement-date" style="max-width:12rem"></div>' +
        '<div class="xirr-signature-row">' +
        '<div class="xirr-signature-box"><div class="name">Alex Johnson</div><div class="role">Employee</div>' +
        '<button type="button" class="xirr-btn xirr-btn-outline xirr-btn-sm" id="xirr-sign-employee">&#9999;&#65039; Sign</button>' +
        '<span class="xirr-signed-badge" id="xirr-signed-employee" style="display:none">&#10003; Signed</span></div>' +
        '<div class="xirr-signature-box"><div class="name">James Scott</div><div class="role">Leader</div>' +
        '<button type="button" class="xirr-btn xirr-btn-outline xirr-btn-sm" id="xirr-sign-leader">&#9999;&#65039; Sign</button>' +
        '<span class="xirr-signed-badge" id="xirr-signed-leader" style="display:none">&#10003; Signed</span></div>' +
        '</div>' +
        '<p class="xirr-muted" style="margin:.6rem 0 0;font-size:13px">Digital signatures confirm the conversation occurred. This does not indicate agreement with all content.</p>' +
        '</div>' +

        '<p class="xirr-muted" style="margin-top:.3rem">Use the <b>Save Draft</b> button below to save your conversation notes and agreement.</p>';
}
JS;
}

function xfirr_wizard_conversation_init_js(): string
{
    return <<<'JS'
(function () {
    function wireSign(btnId, badgeId) {
        var btn = document.getElementById(btnId);
        var badge = document.getElementById(badgeId);
        if (!btn || !badge) return;
        btn.addEventListener('click', function () {
            btn.style.display = 'none';
            badge.style.display = 'inline-flex';
        });
    }

    window.initConversationStep = function () {
        wireSign('xirr-sign-employee', 'xirr-signed-employee');
        wireSign('xirr-sign-leader', 'xirr-signed-leader');
    };
})();
JS;
}
