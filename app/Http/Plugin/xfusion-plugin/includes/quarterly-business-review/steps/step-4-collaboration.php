<?php
/**
 * Step 4 — Leadership Collaboration™.
 *
 * Data loads when the step is first opened; persist only via Save Draft.
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
        '</div>' +

        '<div class="xqbr-card"><div class="xqbr-row" style="justify-content:space-between">' +
        '<h3 style="margin:0">Key Decisions & Takeaways</h3>' +
        '<a href="javascript:void(0)" class="xqbr-add-link" id="xqbr-add-decision">+ Add Decision</a>' +
        '</div>' +
        '<p class="xqbr-muted" style="margin-top:-.4rem">Capture the key decisions and takeaways agreed upon by the leadership team.</p>' +
        '<div id="xqbr-decisions-list"></div>' +
        '</div>' +
        '<p class="xqbr-muted" id="xqbr-collaboration-status" style="margin-top:.5rem"></p>';
}
JS;
}

function xfqbr_wizard_collaboration_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escAttr(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

    var decisionsCache = [];
    var collaborationEdited = false;
    var loadState = { qbrId: null, token: 0, fetched: false, loading: false };

    function markEdited() {
        collaborationEdited = true;
    }

    function currentQbrId() {
        return window.XFQBR_WIZARD && window.XFQBR_WIZARD.qbrId
            ? String(window.XFQBR_WIZARD.qbrId)
            : null;
    }

    function mapDecisionRow(row) {
        var targetDate = row.target_date || '';
        if (targetDate && targetDate.length >= 10) {
            targetDate = targetDate.substring(0, 10);
        }
        return {
            decision: row.decision || '',
            owner_name: row.owner_name || row.owner_display_name || '',
            impact_area: row.impact_area || '',
            next_step: row.next_step || '',
            target_date: targetDate,
        };
    }

    function decisionRow(item, index) {
        return '<div class="xqbr-prio-card" data-index="' + index + '" style="margin-bottom:.75rem;position:relative">' +
            '<div class="xqbr-prio-body" style="padding-right:2rem">' +
            '<a href="javascript:void(0)" class="xqbr-icon-btn xqbr-prio-delete" data-index="' + index + '" style="position:absolute;top:.5rem;right:.5rem">✕</a>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
            '<div class="xqbr-form-field"><label>Decision / Takeaway</label><input class="xqbr-input" data-key="decision" value="' + escAttr(item.decision) + '"></div>' +
            '<div class="xqbr-form-field"><label>Owner</label><input class="xqbr-input" data-key="owner_name" value="' + escAttr(item.owner_name) + '" placeholder="Name"></div>' +
            '<div class="xqbr-form-field"><label>Impact Area</label><input class="xqbr-input" data-key="impact_area" value="' + escAttr(item.impact_area) + '"></div>' +
            '<div class="xqbr-form-field"><label>Target Date</label><input type="date" class="xqbr-input" data-key="target_date" value="' + escAttr(item.target_date) + '"></div>' +
            '</div>' +
            '<div class="xqbr-prio-grid xqbr-prio-grid-1">' +
            '<div class="xqbr-form-field"><label>Next Step</label><textarea class="xqbr-input" rows="2" data-key="next_step">' + esc(item.next_step) + '</textarea></div>' +
            '</div></div></div>';
    }

    function showDecisionsLoading(message) {
        loadState.loading = true;
        var list = document.getElementById('xqbr-decisions-list');
        var addLink = document.getElementById('xqbr-add-decision');
        if (typeof window.xqbrRenderListLoading === 'function') {
            window.xqbrRenderListLoading(list, 2, message || 'Loading key decisions…');
        } else if (list) {
            list.innerHTML = window.xqbrSpinnerHtml ? window.xqbrSpinnerHtml(message) : '<p class="xqbr-muted">Loading…</p>';
        }
        if (addLink) addLink.style.display = 'none';
    }

    function renderDecisions() {
        var list = document.getElementById('xqbr-decisions-list');
        if (!list) return;
        if (loadState.loading) {
            return;
        }
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
            el.addEventListener('input', function () { collectDecisions(list); markEdited(); });
        });
        list.querySelectorAll('.xqbr-prio-delete').forEach(function (link) {
            link.addEventListener('click', function () {
                collectDecisions(list);
                markEdited();
                decisionsCache.splice(parseInt(link.getAttribute('data-index'), 10), 1);
                renderDecisions();
            });
        });
    }

    function bindAddLink(addLink) {
        if (!addLink || addLink.dataset.bound === '1') {
            return;
        }
        addLink.dataset.bound = '1';
        addLink.addEventListener('click', function () {
            var list = document.getElementById('xqbr-decisions-list');
            if (list && list.querySelector('.xqbr-prio-card')) {
                collectDecisions(list);
            }
            markEdited();
            decisionsCache.push({ decision: '', owner_name: '', impact_area: '', next_step: '', target_date: '' });
            renderDecisions();
        });
    }

    window.xqbrLoadCollaborationData = function () {
        if (typeof window.xqbrLoadDecisions !== 'function') {
            return Promise.resolve();
        }
        var qbrId = currentQbrId();
        if (!qbrId || loadState.fetched) {
            return Promise.resolve();
        }
        var statusEl = document.getElementById('xqbr-collaboration-status');
        if (statusEl) {
            statusEl.textContent = '';
        }
        showDecisionsLoading('Loading key decisions…');
        var token = loadState.token;
        return window.xqbrLoadDecisions().then(function (rows) {
            if (token !== loadState.token) {
                return;
            }
            loadState.fetched = true;
            loadState.loading = false;
            if (!collaborationEdited) {
                decisionsCache = (rows || []).map(mapDecisionRow);
                renderDecisions();
            } else {
                renderDecisions();
            }
            if (statusEl) {
                statusEl.textContent = 'Click Save Draft to save your changes.';
            }
        }).catch(function () {
            loadState.loading = false;
            var list = document.getElementById('xqbr-decisions-list');
            if (list) {
                list.innerHTML = window.xqbrSpinnerHtml
                    ? window.xqbrSpinnerHtml('Could not load decisions.')
                    : '<p class="xqbr-muted">Could not load decisions.</p>';
            }
            if (statusEl) {
                statusEl.textContent = 'Could not load decisions. Click Save Draft after editing to retry.';
            }
        });
    };

    window.initCollaborationStep = function () {
        var canEdit = !window.XFQBR_WIZARD || window.XFQBR_WIZARD.canEdit !== false;
        var notesArea = document.getElementById('xqbr-discussion-notes');
        var addLink = document.getElementById('xqbr-add-decision');
        var statusEl = document.getElementById('xqbr-collaboration-status');
        var qbrId = currentQbrId();

        if (qbrId !== loadState.qbrId) {
            loadState.qbrId = qbrId;
            loadState.token += 1;
            loadState.fetched = false;
            decisionsCache = [];
            collaborationEdited = false;
        }

        if (notesArea) {
            notesArea.value = (window.XFQBR_WIZARD && window.XFQBR_WIZARD.discussionNotes)
                ? window.XFQBR_WIZARD.discussionNotes
                : '';
            if (!canEdit) {
                notesArea.disabled = true;
            } else {
                notesArea.addEventListener('input', markEdited);
            }
        }
        if (!canEdit && addLink) {
            addLink.style.display = 'none';
        }

        if (!loadState.fetched) {
            showDecisionsLoading('Loading key decisions…');
        } else {
            renderDecisions();
        }
        bindAddLink(addLink);

        if (statusEl && canEdit && loadState.fetched) {
            statusEl.textContent = 'Click Save Draft to save your changes.';
        }
    };

    window.xqbrCollaborationIsDirty = function () {
        return collaborationEdited;
    };

    window.xqbrCollaborationMarkSaved = function () {
        collaborationEdited = false;
    };
})();
JS;
}
