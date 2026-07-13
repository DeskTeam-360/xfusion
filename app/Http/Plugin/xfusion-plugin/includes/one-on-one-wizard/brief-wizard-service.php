<?php
/**
 * Step 2 — AI Meeting Brief™ dynamic render + View Details modal.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * JS: load/render brief, View Details modal, generate from Step 1.
 */
function xfoo_wizard_brief_js(): string
{
    return <<<'JS'
window.xfwBriefCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };

var xfwResetBriefCache = function () {
    window.xfwBriefCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };
};

var xfwBriefSectionMeta = {
    alignment_snapshot: { color: '#16a34a', icon: '\u{1F3AF}', title: 'Alignment Snapshot\u2122' },
    development_snapshot: { color: '#7c3aed', icon: '\u{1F464}', title: 'Development Snapshot\u2122' },
    commitment_review: { color: '#ea580c', icon: '\u{1F4CB}', title: 'Commitment Review\u2122' },
    behavioral_trends: { color: '#0891b2', icon: '\u{1F4C8}', title: 'Behavioral Trends\u2122' },
    suggested_discussion_areas: { color: '#1e2a52', icon: '\u{1F4AC}', title: 'Suggested Discussion Areas\u2122' },
    emerging_opportunities: { color: '#ca8a04', icon: '\u{1F4A1}', title: 'Emerging Opportunities\u2122' },
    potential_barriers: { color: '#dc2626', icon: '\u26A0\uFE0F', title: 'Potential Barriers\u2122' },
};

var xfwBriefNormalizeSection = function (raw) {
    if (!raw) {
        return { items: [], details: '' };
    }
    if (Array.isArray(raw)) {
        return { items: raw, details: raw.join('\n\n') };
    }
    return {
        items: Array.isArray(raw.items) ? raw.items : [],
        details: String(raw.details || ''),
    };
};

var xfwBriefCard = function (sectionKey, sectionData) {
    var meta = xfwBriefSectionMeta[sectionKey] || { color: '#6b7280', icon: '\u{1F4C4}', title: sectionKey };
    var normalized = xfwBriefNormalizeSection(sectionData);
    var items = normalized.items.length
        ? normalized.items
        : ['No summary available for this section yet.'];

    return '<div class="xfw-insight-card" data-brief-section="' + sectionKey + '">' +
        '<div class="icon" style="background:' + meta.color + '22;color:' + meta.color + '">' + meta.icon + '</div>' +
        '<h3>' + meta.title + '</h3>' +
        '<ul>' + items.map(function (i) {
            return '<li>' + xfwEvidenceEsc(i) + '</li>';
        }).join('') + '</ul>' +
        '<a href="#" class="xfw-link xfw-brief-details-link" data-brief-section="' + sectionKey + '">View Details &rarr;</a>' +
        '</div>';
};

var xfwRenderBriefPanel = function (brief) {
    if (!brief || typeof brief !== 'object') {
        return '<div class="xfw-banner warn">ℹ️ <span>No AI Meeting Brief has been generated yet. Return to Step 1 and click <b>Generate AI Meeting Brief™</b>.</span></div>';
    }

    var discussion = xfwBriefNormalizeSection(brief.suggested_discussion_areas);
    var discussionItems = discussion.items.length
        ? discussion.items
        : ['Use Step 1 evidence to identify the most important discussion themes for this meeting.'];

    return '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwBriefCard('alignment_snapshot', brief.alignment_snapshot) +
        xfwBriefCard('development_snapshot', brief.development_snapshot) +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwBriefCard('commitment_review', brief.commitment_review) +
        xfwBriefCard('behavioral_trends', brief.behavioral_trends) +
        '</div>' +
        '<div class="xfw-card" style="margin-bottom:1rem" data-brief-section="suggested_discussion_areas">' +
        '<h3>Suggested Discussion Areas\u2122</h3>' +
        '<ol class="xfw-numbered">' + discussionItems.map(function (d, i) {
            return '<li><span class="n">' + (i + 1) + '</span>' + xfwEvidenceEsc(d) + '</li>';
        }).join('') + '</ol>' +
        '<a href="#" class="xfw-link xfw-brief-details-link" data-brief-section="suggested_discussion_areas">View Details &rarr;</a>' +
        '</div>' +
        '<div class="xfw-grid-2">' +
        xfwBriefCard('emerging_opportunities', brief.emerging_opportunities) +
        xfwBriefCard('potential_barriers', brief.potential_barriers) +
        '</div>';
};

var xfwEnsureBriefModal = function () {
    if (root.querySelector('#xfw-brief-modal')) {
        return;
    }
    var modal = document.createElement('div');
    modal.id = 'xfw-brief-modal';
    modal.className = 'xfw-modal xfw-hidden';
    modal.innerHTML =
        '<div class="xfw-modal-backdrop" data-close-brief-modal="1"></div>' +
        '<div class="xfw-modal-card xfw-card">' +
        '<h3 id="xfw-brief-modal-title" style="margin-top:0"></h3>' +
        '<div id="xfw-brief-modal-body" class="xfw-evidence-text" style="white-space:pre-wrap"></div>' +
        '<div style="margin-top:1rem;text-align:right">' +
        '<button type="button" class="xfw-btn xfw-btn-outline" data-close-brief-modal="1">Close</button>' +
        '</div></div>';
    root.appendChild(modal);
};

var xfwOpenBriefDetails = function (sectionKey) {
    var brief = window.xfwBriefCache.data;
    if (!brief) {
        return;
    }
    var meta = xfwBriefSectionMeta[sectionKey] || { title: 'Details' };
    var normalized = xfwBriefNormalizeSection(brief[sectionKey]);
    var details = normalized.details || normalized.items.join('\n\n') || 'No additional detail is available for this section yet.';

    xfwEnsureBriefModal();
    var modal = root.querySelector('#xfw-brief-modal');
    root.querySelector('#xfw-brief-modal-title').textContent = meta.title;
    root.querySelector('#xfw-brief-modal-body').textContent = details;
    modal.classList.remove('xfw-hidden');
};

var xfwCloseBriefModal = function () {
    var modal = root.querySelector('#xfw-brief-modal');
    if (modal) {
        modal.classList.add('xfw-hidden');
    }
};

var xfwBindBriefDetailsLinks = function () {
    var main = root.querySelector('#xfw-main');
    if (!main) {
        return;
    }
    main.querySelectorAll('.xfw-brief-details-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            xfwOpenBriefDetails(link.getAttribute('data-brief-section') || '');
        });
    });
};

var loadWizardBrief = function (force) {
    if (!window.XFW_WIZARD) {
        return Promise.resolve(null);
    }
    var cid = typeof xfwGetActiveConversationId === 'function' ? xfwGetActiveConversationId() : 0;
    if (!cid) {
        return Promise.resolve(null);
    }
    if (!force && window.xfwBriefCache.loaded && window.xfwBriefCache.conversationId === cid) {
        return Promise.resolve(window.xfwBriefCache.data);
    }
    if (window.xfwBriefCache.loading && window.xfwBriefCache.conversationId === cid && window.xfwBriefCache._promise) {
        return window.xfwBriefCache._promise;
    }

    window.xfwBriefCache.loading = true;
    window.xfwBriefCache.conversationId = cid;

    var body = new URLSearchParams({
        action: 'xfusion_oo_brief',
        nonce: window.XFW_WIZARD.ooNonce,
        conversation_id: String(cid),
    });

    window.xfwBriefCache._promise = fetch(window.XFW_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: body,
    })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json && json.success && json.data) {
                window.xfwBriefCache.data = json.data;
            } else {
                window.xfwBriefCache.data = null;
            }
            window.xfwBriefCache.loaded = true;
            return window.xfwBriefCache.data;
        })
        .catch(function () {
            window.xfwBriefCache.data = null;
            window.xfwBriefCache.loaded = true;
            return null;
        })
        .finally(function () {
            window.xfwBriefCache.loading = false;
        });

    return window.xfwBriefCache._promise;
};

var xfwRenderBriefStep = function () {
    var host = root.querySelector('#xfw-brief-content');
    if (!host) {
        return;
    }
    if (window.xfwBriefCache.data) {
        host.innerHTML = xfwRenderBriefPanel(window.xfwBriefCache.data);
        xfwBindBriefDetailsLinks();
        return;
    }
    host.innerHTML = '<p class="xfw-muted">Loading AI Meeting Brief\u2026</p>';
    loadWizardBrief(false).then(function (brief) {
        host.innerHTML = xfwRenderBriefPanel(brief);
        xfwBindBriefDetailsLinks();
    });
};

var initBriefStep = function () {
    xfwEnsureBriefModal();
    xfwRenderBriefStep();

    if (!root.dataset.briefModalBound) {
        root.dataset.briefModalBound = '1';
        root.addEventListener('click', function (e) {
            if (e.target && e.target.getAttribute && e.target.getAttribute('data-close-brief-modal') === '1') {
                xfwCloseBriefModal();
            }
        });
    }
};

var generateWizardBrief = function () {
    var cid = typeof xfwGetActiveConversationId === 'function' ? xfwGetActiveConversationId() : 0;
    var statusEl = root.querySelector('#xfw-generate-brief-status');
    var btn = root.querySelector('#xfw-generate-brief');
    if (!cid || !btn) {
        return Promise.resolve(null);
    }

    btn.disabled = true;
    if (statusEl) {
        statusEl.textContent = 'Generating AI Meeting Brief\u2026';
        statusEl.style.color = '';
    }

    var body = new URLSearchParams({
        action: 'xfoo_wizard_generate_brief',
        nonce: window.XFW_WIZARD.nonce,
        conversation_id: String(cid),
    });

    return fetch(window.XFW_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: body,
    })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            btn.disabled = false;
            if (!json || !json.success || !json.data || !json.data.brief) {
                var errMsg = 'Unable to generate the brief. Please try again.';
                if (json && json.data && json.data.message) {
                    errMsg = json.data.message;
                } else if (json && json.message) {
                    errMsg = json.message;
                }
                if (statusEl) {
                    statusEl.textContent = errMsg;
                    statusEl.style.color = '#dc2626';
                }
                return null;
            }
            window.xfwBriefCache.data = json.data.brief;
            window.xfwBriefCache.loaded = true;
            window.xfwBriefCache.conversationId = cid;
            if (statusEl) {
                statusEl.textContent = '\u2713 AI Meeting Brief generated. Continue to Step 2 to review.';
                statusEl.style.color = '#16a34a';
            }
            return json.data.brief;
        })
        .catch(function () {
            btn.disabled = false;
            if (statusEl) {
                statusEl.textContent = 'Unable to generate the brief. Please try again.';
                statusEl.style.color = '#dc2626';
            }
            return null;
        });
};

var initGenerateBriefButton = function () {
    var btn = root.querySelector('#xfw-generate-brief');
    if (!btn || btn.dataset.bound === '1') {
        return;
    }
    btn.dataset.bound = '1';
    btn.addEventListener('click', function () {
        generateWizardBrief();
    });
};

window.xfwResetBriefCache = xfwResetBriefCache;
JS;
}
