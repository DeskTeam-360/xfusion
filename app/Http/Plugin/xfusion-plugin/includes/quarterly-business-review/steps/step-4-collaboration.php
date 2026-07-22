<?php
/**
 * Step 4 — Leadership Collaboration™.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_step_collaboration_js(): string
{
    return <<<'JS'
collaboration: function () {
    var prompts = [
        ['🎯', 'Review the Evidence', 'What stands out most from the organizational evidence?'],
        ['✨', 'AI Assessment Insights', 'What strengths and opportunities should we focus on?'],
        ['🎯', 'Priority Alignment', 'Are our priorities aligned with our highest impact opportunities?'],
        ['⚠️', 'Barriers & Challenges', 'What barriers are limiting our progress toward our objectives?'],
        ['🤝', 'Resource Needs', 'What resources or support do we need to accelerate our progress?'],
        ['📉', 'Readiness Gaps', 'Where are our largest readiness gaps?'],
        ['📈', 'Opportunities', 'What opportunities can drive the greatest impact this quarter?'],
        ['🚩', 'Next Quarter Focus', 'What should be our top focus areas for the upcoming quarter?'],
    ];
    var guideHtml = prompts.map(function (p) {
        return '<div class="xqbr-guide-card"><h4>' + p[0] + ' ' + p[1] + '</h4><p>' + p[2] + '</p></div>';
    }).join('');

    return '<h2 class="xqbr-section-title">Step 4. Leadership Collaboration™</h2>' +
        '<p class="xqbr-section-desc">Review the objective evidence and AI assessment. Discuss key themes, priorities, barriers, resource needs and opportunities. Capture your leadership context and decisions.</p>' +
        '<div class="xqbr-banner">ℹ️ <span>This is your collaborative discussion space. Use the evidence and assessment as input to guide your conversation and capture leadership context.</span></div>' +
        '<div class="xqbr-card"><h3 style="margin-top:0">Discussion Guide</h3>' +
        '<p class="xqbr-muted" style="margin-top:-.5rem">Use these prompts to guide your leadership conversation.</p>' +
        '<div class="xqbr-guide-grid">' + guideHtml + '</div></div>' +

        '<div class="xqbr-card"><div class="xqbr-row" style="justify-content:space-between">' +
        '<h3 style="margin:0">Leadership Discussion Notes</h3></div>' +
        '<p class="xqbr-muted" style="margin-top:-.4rem">Capture key discussion points, decisions, and insights from your leadership conversation.</p>' +
        '<textarea class="xqbr-input" id="xqbr-discussion-notes" rows="6" maxlength="20000" placeholder="Start typing your discussion notes here..."></textarea>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-save-notes-btn">Save Notes</button>' +
        '<span class="xqbr-muted" id="xqbr-notes-save-status" style="margin-left:.5rem"></span>' +
        '</div>' +

        '<div class="xqbr-card"><div class="xqbr-row" style="justify-content:space-between">' +
        '<h3 style="margin:0">Key Decisions & Takeaways</h3>' +
        '<a href="javascript:void(0)" class="xqbr-add-link" id="xqbr-add-decision">+ Add Decision</a>' +
        '</div>' +
        '<p class="xqbr-muted" style="margin-top:-.4rem">Capture the key decisions and takeaways agreed upon by the leadership team.</p>' +
        '<div id="xqbr-decisions-list"></div>' +
        '<button type="button" class="xqbr-btn xqbr-btn-outline xqbr-btn-sm" id="xqbr-save-decisions-btn" style="margin-top:.75rem">Save Decisions</button>' +
        '<span class="xqbr-muted" id="xqbr-decisions-save-status" style="margin-left:.5rem"></span>' +
        '</div>';
}
JS;
}

function xfqbr_wizard_collaboration_init_js(): string
{
    return <<<'JS'
(function () {
    function escHtml(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escAttr(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

    var decisionsCache = [];

    function decisionRow(item, index) {
        return '<div class="xqbr-prio-card" data-index="' + index + '" style="margin-bottom:.75rem">' +
            '<div class="xqbr-prio-body" style="padding-right:2rem">' +
            '<a href="javascript:void(0)" class="xqbr-icon-btn xqbr-prio-delete" data-index="' + index + '" style="position:absolute;top:.5rem;right:.5rem">✕</a>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
            '<div class="xqbr-form-field"><label>Decision / Takeaway</label><input class="xqbr-input" data-key="decision" value="' + escAttr(item.decision) + '"></div>' +
            '<div class="xqbr-form-field"><label>Owner (user ID)</label><input type="number" class="xqbr-input" data-key="owner_user_id" value="' + escAttr(item.owner_user_id) + '"></div>' +
            '<div class="xqbr-form-field"><label>Impact Area</label><input class="xqbr-input" data-key="impact_area" value="' + escAttr(item.impact_area) + '"></div>' +
            '<div class="xqbr-form-field"><label>Target Date</label><input type="date" class="xqbr-input" data-key="target_date" value="' + escAttr(item.target_date) + '"></div>' +
            '</div>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-1">' +
            '<div class="xqbr-form-field"><label>Next Step</label><textarea class="xqbr-input" rows="2" data-key="next_step">' + escHtml(item.next_step) + '</textarea></div>' +
            '</div></div></div>';
    }

    function renderDecisions() {
        var list = document.getElementById('xqbr-decisions-list');
        if (!list) return;
        if (!decisionsCache.length) {
            list.innerHTML = '<p class="xqbr-muted">No decisions or takeaways added yet. Use the + Add Decision button to capture key outcomes from your discussion.</p>';
            return;
        }
        list.innerHTML = decisionsCache.map(decisionRow).join('');
        bindDecisionEvents(list);
    }

    function collectDecisions(list) {
        var next = [];
        list.querySelectorAll('.xqbr-prio-card').forEach(function (card) {
            var item = {};
            card.querySelectorAll('[data-key]').forEach(function (el) { item[el.getAttribute('data-key')] = el.value; });
            next.push(item);
        });
        decisionsCache = next;
        return next;
    }

    function bindDecisionEvents(list) {
        list.querySelectorAll('[data-key]').forEach(function (el) {
            el.addEventListener('input', function () { collectDecisions(list); });
        });
        list.querySelectorAll('.xqbr-prio-delete').forEach(function (link) {
            link.addEventListener('click', function () {
                collectDecisions(list);
                decisionsCache.splice(parseInt(link.getAttribute('data-index'), 10), 1);
                renderDecisions();
            });
        });
    }

    window.initCollaborationStep = function () {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var notesArea = document.getElementById('xqbr-discussion-notes');
        var saveNotesBtn = document.getElementById('xqbr-save-notes-btn');
        var saveDecisionsBtn = document.getElementById('xqbr-save-decisions-btn');
        var addLink = document.getElementById('xqbr-add-decision');

        if (!canEdit) {
            if (notesArea) notesArea.disabled = true;
            if (saveNotesBtn) saveNotesBtn.style.display = 'none';
            if (saveDecisionsBtn) saveDecisionsBtn.style.display = 'none';
            if (addLink) addLink.style.display = 'none';
        }

        if (window.XFQBR_WIZARD && window.XFQBR_WIZARD.discussionNotes && notesArea) {
            notesArea.value = window.XFQBR_WIZARD.discussionNotes;
        }

        if (saveNotesBtn) {
            saveNotesBtn.addEventListener('click', function () {
                var statusEl = document.getElementById('xqbr-notes-save-status');
                saveNotesBtn.disabled = true;
                if (statusEl) statusEl.textContent = 'Saving…';
                window.xqbrSaveDiscussionNotes(notesArea.value).then(function (res) {
                    saveNotesBtn.disabled = false;
                    if (statusEl) statusEl.textContent = (res && res.success) ? 'Saved ' + res.saved_at : 'Save failed.';
                });
            });
        }

        if (addLink) {
            addLink.addEventListener('click', function () {
                var list = document.getElementById('xqbr-decisions-list');
                if (list) collectDecisions(list);
                decisionsCache.push({ decision: '', owner_user_id: '', impact_area: '', next_step: '', target_date: '' });
                renderDecisions();
            });
        }

        if (saveDecisionsBtn) {
            saveDecisionsBtn.addEventListener('click', function () {
                var list = document.getElementById('xqbr-decisions-list');
                var items = list ? collectDecisions(list) : decisionsCache;
                var statusEl = document.getElementById('xqbr-decisions-save-status');
                saveDecisionsBtn.disabled = true;
                if (statusEl) statusEl.textContent = 'Saving…';
                window.xqbrSaveDecisions(items).then(function (res) {
                    saveDecisionsBtn.disabled = false;
                    if (statusEl) statusEl.textContent = (res && res.success) ? 'Saved ' + res.saved_at : 'Save failed.';
                });
            });
        }

        window.xqbrLoadDecisions().then(function (items) {
            decisionsCache = items || [];
            renderDecisions();
        });
    };
})();
JS;
}
