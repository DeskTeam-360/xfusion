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
    alignment_snapshot: { color: '#16a34a', icon: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Arrow-on-Target-Icon.svg', title: 'Alignment Snapshot\u2122' },
    development_snapshot: { color: '#7c3aed', icon: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Person-Growth-Icon.svg', title: 'Development Snapshot\u2122' },
    commitment_review: { color: '#ea580c', icon: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Clipboard-Checkmark-Icon.svg', title: 'Commitment Review\u2122' },
    behavioral_trends: { color: '#0891b2', icon: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Trending-Line-Chart-Icon.svg', title: 'Behavioral Trends\u2122' },
    suggested_discussion_areas: { color: '#1e2a52', icon: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Chat-Bubbles-Icon.svg', title: 'Suggested Discussion Areas\u2122' },
    emerging_opportunities: { color: '#ca8a04', icon: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Chat-Bubbles-Icon.svg', title: 'Emerging Opportunities\u2122' },
    potential_barriers: { color: '#dc2626', icon: 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/Warning-Triangle-Icon-1.svg', title: 'Potential Barriers\u2122' },
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
    var meta = xfwBriefSectionMeta[sectionKey] || { color: '#6b7280', icon: '', title: sectionKey };
    var normalized = xfwBriefNormalizeSection(sectionData);
    var items = normalized.items.length
        ? normalized.items
        : ['No summary available for this section yet.'];
    var iconHtml = meta.icon
        ? '<img src="' + meta.icon + '" alt="" width="50" height="50">'
        : '';

    return '<div class="xfw-insight-card" data-brief-section="' + sectionKey + '">' +
        '<div class="icon">' + iconHtml + '</div>' +
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
        '<div id="xfw-brief-modal-body" class="xfw-brief-modal-body"></div>' +
        '<div style="margin-top:1rem;text-align:right">' +
        '<button type="button" class="xfw-btn xfw-btn-outline" data-close-brief-modal="1">Close</button>' +
        '</div></div>';
    root.appendChild(modal);
};

var xfwBriefIsoInText = function (text) {
    if (typeof xfwFormatEvidenceDateTime === 'function') {
        return String(text || '').replace(/\d{4}-\d{2}-\d{2}T[\d:.+-Z]+/g, function (iso) {
            return xfwFormatEvidenceDateTime(iso);
        });
    }
    return String(text || '');
};

var xfwBriefStatusBadge = function (status) {
    if (typeof xfwStatusBadgeForEvidence === 'function') {
        return xfwStatusBadgeForEvidence(status);
    }
    return 'amber';
};

var xfwBriefFormatStatus = function (status) {
    if (typeof xfwFormatEvidenceStatus === 'function') {
        return xfwFormatEvidenceStatus(status);
    }
    return String(status || '');
};

var xfwBriefParseCommitmentLine = function (line) {
    var raw = String(line || '').trim();
    if (!raw) {
        return null;
    }
    var lines = raw.split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
    var first = lines[0].replace(/^[-•]\s*/, '');
    var metaLine = lines.slice(1).join(' ').trim();

    if (metaLine) {
        return { title: first, meta: metaLine };
    }

    var bracket = first.match(/^(.+?)\s*\[([^,\]]+),\s*([^\]]+)\]\s*$/);
    if (bracket) {
        return {
            title: bracket[1].trim(),
            status: bracket[2].trim(),
            priority: bracket[3].replace(/\s*priority\s*$/i, '').trim(),
        };
    }

    return { title: first, meta: '' };
};

var xfwBriefGroupCommitmentBlocks = function (lines) {
    var blocks = [];
    var current = [];
    lines.forEach(function (line) {
        var trimmed = String(line || '').trim();
        if (trimmed === '') {
            if (current.length) {
                blocks.push(current.join('\n'));
                current = [];
            }
            return;
        }
        if (/^[-•]/.test(trimmed) && current.length) {
            blocks.push(current.join('\n'));
            current = [line];
            return;
        }
        current.push(line);
    });
    if (current.length) {
        blocks.push(current.join('\n'));
    }
    return blocks;
};

var xfwBriefRenderCommitmentCards = function (lines) {
    var cards = [];
    xfwBriefGroupCommitmentBlocks(lines).forEach(function (block) {
        var parsed = xfwBriefParseCommitmentLine(block);
        if (!parsed || !parsed.title) {
            return;
        }
        if (parsed.meta && !parsed.status) {
            cards.push(
                '<div class="xfw-evidence-commitment">' +
                '<div class="xfw-evidence-commitment-title">' + xfwEvidenceEsc(parsed.title) + '</div>' +
                '<div class="xfw-evidence-commitment-meta">' + xfwEvidenceEsc(xfwBriefIsoInText(parsed.meta)) + '</div>' +
                '</div>'
            );
            return;
        }
        cards.push(
            '<div class="xfw-evidence-commitment">' +
            '<div class="xfw-evidence-commitment-title">' + xfwEvidenceEsc(parsed.title) + '</div>' +
            '<div class="xfw-evidence-commitment-meta">' +
            '<span class="xfw-badge ' + xfwBriefStatusBadge(parsed.status) + '">' + xfwEvidenceEsc(xfwBriefFormatStatus(parsed.status)) + '</span>' +
            (parsed.priority ? '<span class="xfw-muted"> · ' + xfwEvidenceEsc(parsed.priority.charAt(0).toUpperCase() + parsed.priority.slice(1)) + ' priority</span>' : '') +
            '</div></div>'
        );
    });
    if (!cards.length) {
        return '';
    }
    return '<div class="xfw-evidence-commitments">' + cards.join('') + '</div>';
};

var xfwBriefRenderMeetingsTable = function (lines) {
    var rows = [];
    lines.forEach(function (line) {
        var trimmed = String(line || '').trim();
        if (!trimmed) {
            return;
        }
        var content = trimmed.replace(/^[-•]\s*/, '');
        var withMatch = content.match(/^(.+?)\s+with\s+(.+?)(?:\s*[·(]\s*([^)]+)\)?)?\s*$/i);
        if (withMatch) {
            rows.push({
                date: xfwBriefIsoInText(withMatch[1].trim()),
                with: withMatch[2].trim(),
                status: (withMatch[3] || '').trim(),
            });
        }
    });
    if (!rows.length) {
        return '';
    }
    return '<div style="overflow-x:auto"><table class="xfw-table"><thead><tr><th>Date</th><th>With</th><th>Status</th></tr></thead><tbody>' +
        rows.map(function (row) {
            return '<tr><td>' + xfwEvidenceEsc(row.date) + '</td><td>' + xfwEvidenceEsc(row.with) + '</td><td>' +
                (row.status
                    ? '<span class="xfw-badge ' + xfwBriefStatusBadge(row.status) + '">' + xfwEvidenceEsc(xfwBriefFormatStatus(row.status)) + '</span>'
                    : '—') +
                '</td></tr>';
        }).join('') + '</tbody></table></div>';
};

var xfwFormatBriefDetailsHtml = function (details) {
    var text = String(details || '').trim();
    if (!text) {
        return '<p class="xfw-muted">No additional detail is available for this section yet.</p>';
    }

    var lines = text.split('\n');
    var html = '';
    var i = 0;

    while (i < lines.length) {
        var line = lines[i];
        var trimmed = line.trim();

        if (/^Commitments on record:/i.test(trimmed)) {
            html += '<h4 class="xfw-brief-details-heading">Commitments</h4>';
            i++;
            while (i < lines.length && lines[i].trim() === '') {
                i++;
            }
            var commitLines = [];
            while (i < lines.length) {
                var currentLine = lines[i];
                if (/^Previous 1-on-1 meetings/i.test(currentLine.trim())) {
                    break;
                }
                commitLines.push(currentLine);
                i++;
            }
            html += xfwBriefRenderCommitmentCards(commitLines);
            continue;
        }

        if (/^Previous 1-on-1 meetings/i.test(trimmed)) {
            html += '<h4 class="xfw-brief-details-heading">' + xfwEvidenceEsc(trimmed.replace(/:$/, '')) + '</h4>';
            i++;
            var meetingLines = [];
            while (i < lines.length && lines[i].trim() !== '') {
                meetingLines.push(lines[i]);
                i++;
            }
            html += xfwBriefRenderMeetingsTable(meetingLines);
            continue;
        }

        if (/^[-•]\s/.test(trimmed) && /\[[^,\]]+,.+\]/i.test(trimmed)) {
            var legacyCommits = [];
            while (i < lines.length && /^[-•]\s/.test(lines[i].trim()) && lines[i].trim() !== '') {
                legacyCommits.push(lines[i]);
                i++;
            }
            html += '<h4 class="xfw-brief-details-heading">Commitments</h4>' + xfwBriefRenderCommitmentCards(legacyCommits);
            continue;
        }

        var para = [line];
        i++;
        while (i < lines.length && lines[i].trim() !== '' &&
            !/^Commitments on record:/i.test(lines[i]) &&
            !/^Previous 1-on-1 meetings/i.test(lines[i]) &&
            !( /^[-•]\s/.test(lines[i].trim()) && /\[[^,\]]+,.+\]/i.test(lines[i].trim()) )) {
            para.push(lines[i]);
            i++;
        }
        var block = para.join('\n').trim();
        if (block) {
            html += '<div class="xfw-brief-details-para xfw-evidence-text">' +
                xfwEvidenceEsc(xfwBriefIsoInText(block)).replace(/\n/g, '<br>') + '</div>';
        }
    }

    return html || ('<div class="xfw-brief-details-para xfw-evidence-text">' +
        xfwEvidenceEsc(xfwBriefIsoInText(text)).replace(/\n/g, '<br>') + '</div>');
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
    root.querySelector('#xfw-brief-modal-body').innerHTML = xfwFormatBriefDetailsHtml(details);
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
    host.innerHTML = '<p class="xfw-muted">Loading AI Meeting Brief\u2026</p>';
    loadWizardBrief(true).then(function (brief) {
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

var xfwFetchBriefBundlePreview = function (conversationId) {
    var url = window.XFW_WIZARD.ajaxUrl + '?action=xfoo_wizard_preview_brief_bundle&nonce=' +
        encodeURIComponent(window.XFW_WIZARD.nonce) + '&conversation_id=' + conversationId;
    return fetch(url, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json && json.success && json.data) {
                console.log('[XFW Step 1] evidence_context → LLM (brief bundle)', json.data);
                return json.data;
            }
            return null;
        })
        .catch(function () {
            return null;
        });
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

    return xfwFetchBriefBundlePreview(cid).then(function () {
        var body = new URLSearchParams({
            action: 'xfoo_wizard_generate_brief',
            nonce: window.XFW_WIZARD.nonce,
            conversation_id: String(cid),
        });

        return fetch(window.XFW_WIZARD.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body,
        }).then(function (res) { return res.json(); });
    }).then(function (json) {
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
            if (json.data.evidence_context) {
                console.log('[XFW Step 1] evidence_context (sent with generate)', json.data.evidence_context);
            }
            console.log('[XFW Step 1] AI Meeting Brief response', json.data.brief, json.data.meta || {});
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
