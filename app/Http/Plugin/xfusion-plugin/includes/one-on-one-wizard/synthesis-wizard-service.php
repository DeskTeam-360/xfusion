<?php
/**
 * Step 6 — Generate AI Meeting Synthesis™ (post-meeting record).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfoo_wizard_generate_synthesis', 'xfoo_wizard_ajax_generate_synthesis');

function xfoo_wizard_ajax_generate_synthesis(): void
{
    check_ajax_referer('xfoo_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $conversationId = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    if ($conversationId < 1) {
        wp_send_json_error(['message' => 'conversation_id is required.'], 422);
    }

    $payload = [
        'force_refresh' => true,
        'debug' => true,
    ];

    $preparations = xfoo_wizard_decode_json_post('preparations');
    if ($preparations !== []) {
        $payload['preparations'] = $preparations;
    }

    $notes = xfoo_wizard_decode_json_post('notes');
    if ($notes !== []) {
        $payload['notes'] = $notes;
    }

    $commitments = xfoo_wizard_decode_json_post('commitments');
    if ($commitments !== []) {
        $payload['commitments'] = $commitments;
    }

    $result = xfoo_wizard_fusion_api_request('POST', "/conversations/{$conversationId}/generate-synthesis", [], $payload);

    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json_error(['message' => $body['message'] ?? $result['error'] ?? 'Failed to generate AI Meeting Synthesis.'], 200);
    }

    $body = is_array($result['body']) ? $result['body'] : [];
    wp_send_json_success([
        'synthesis' => $body['data'] ?? null,
        'meta' => $body['meta'] ?? [],
        'debug_payload' => $body['debug_payload'] ?? null,
    ]);
}

function xfoo_wizard_synthesis_js(): string
{
    return <<<'JS'
window.xfwSynthesisCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };

var xfwResetSynthesisCache = function () {
    window.xfwSynthesisCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };
};

var xfwSynthesisIconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';

var xfwSynthesisFollowupIcons = [
    xfwSynthesisIconBase + 'Checkmark-Circle-Green-Icon_SVG.svg',
    xfwSynthesisIconBase + 'Two-People-Dark-Blue-Icon_SVG.svg',
    xfwSynthesisIconBase + 'Document-File-Icon_SVG.svg',
];

var xfwSynthesisSectionMeta = {
    meeting_summary: {
        icon: xfwSynthesisIconBase + 'Clipboard-Checkmark-Blue-Icon.svg',
        title: 'Meeting Summary\u2122',
        description: 'Overall recap of the conversation, key topics discussed, and main takeaways.',
    },
    alignment_summary: {
        icon: xfwSynthesisIconBase + 'Arrow-in-Target-Icon-1.svg',
        title: 'Alignment Summary\u2122',
        description: 'How aligned both participants are on priorities, goals, and expectations.',
    },
    development_summary: {
        icon: xfwSynthesisIconBase + 'Person-Growth-Icon.svg',
        title: 'Development Summary\u2122',
        description: 'Key growth areas, strengths, and development opportunities identified.',
    },
    commitment_summary: {
        icon: xfwSynthesisIconBase + 'Clipboard-Checkmark-Icon-1.svg',
        title: 'Commitment Summary\u2122',
        description: 'Overview of commitments created and their status.',
    },
    emerging_risks: {
        icon: xfwSynthesisIconBase + 'Warning-Triangle-Icon-2.svg',
        title: 'Emerging Risks\u2122',
        description: 'Potential issues or risks that may impact success if not addressed.',
    },
    emerging_opportunities: {
        icon: xfwSynthesisIconBase + 'Trending-Up-Arrow-Icon-Green-1.svg',
        title: 'Emerging Opportunities\u2122',
        description: 'Opportunities identified to drive growth and improve outcomes.',
    },
    suggested_coaching_topics: {
        icon: xfwSynthesisIconBase + 'Orange-Light-Bulb-Icon.svg',
        title: 'Suggested Coaching Topics\u2122',
        description: 'Recommended topics for future coaching conversations.',
    },
    recommended_follow_up: {
        icon: xfwSynthesisIconBase + 'Calendar-Icon-Teal.svg',
        title: 'Recommended Follow-up\u2122',
        description: 'Next steps to maintain momentum and ensure accountability.',
    },
};

var xfwSynthesisNormalizeSection = function (raw) {
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

var xfwSynthesisCardHead = function (sectionKey) {
    var meta = xfwSynthesisSectionMeta[sectionKey] || { icon: '', title: sectionKey, description: '' };
    var iconHtml = meta.icon
        ? '<div class="icon"><img src="' + meta.icon + '" alt="" width="50" height="50"></div>'
        : '';
    var descHtml = meta.description
        ? '<p class="xfw-insight-desc">' + meta.description + '</p>'
        : '';
    return '<div class="xfw-insight-head">' +
        iconHtml +
        '<div class="xfw-insight-head-text"><h3>' + meta.title + '</h3>' + descHtml + '</div>' +
        '</div>';
};

var xfwSynthesisCard = function (sectionKey, bodyHtml) {
    return '<div class="xfw-insight-card" data-synthesis-section="' + sectionKey + '">' +
        xfwSynthesisCardHead(sectionKey) +
        '<div class="xfw-insight-body">' + bodyHtml + '</div>' +
        '<a href="#" class="xfw-link xfw-synthesis-details-link" data-synthesis-section="' + sectionKey + '">View Full Summary &rarr;</a>' +
        '</div>';
};

var xfwSynthesisWideCard = function (sectionKey, bodyHtml) {
    return '<div class="xfw-card xfw-synthesis-wide-card" data-synthesis-section="' + sectionKey + '">' +
        xfwSynthesisCardHead(sectionKey) +
        '<div class="xfw-insight-body">' + bodyHtml + '</div>' +
        '</div>';
};

var xfwSynthesisAlignmentBody = function (alignment) {
    var normalized = xfwSynthesisNormalizeSection(alignment);
    var alignScore = alignment && alignment.score != null && !isNaN(parseFloat(alignment.score))
        ? parseFloat(alignment.score)
        : null;
    var alignLabel = alignment && alignment.label ? String(alignment.label) : '';
    var alignPct = alignScore != null ? Math.min(100, Math.max(0, (alignScore / 5) * 100)) : 0;
    var html = '';

    if (alignScore != null) {
        var scoreText = (Math.round(alignScore * 10) / 10).toFixed(alignScore % 1 === 0 ? 0 : 1);
        html += '<div class="xfw-alignment-rating-label xfw-muted">Alignment Rating</div>';
        html += '<div style="font-size:1.6rem;font-weight:800;color:var(--green)">' + scoreText +
            '<span style="font-size:1rem;color:var(--muted);font-weight:400"> / 5</span></div>';
    }

    if (alignLabel) {
        html += '<div class="xfw-muted">' + xfwEvidenceEsc(alignLabel) + '</div>';
    }

    if (alignScore != null) {
        html += '<div class="xfw-progress-track" style="margin:.5rem 0"><div class="xfw-progress-fill" style="width:' + alignPct + '%"></div></div>';
    } else if (normalized.items.length) {
        html += '<ul>' + normalized.items.map(function (i) {
            return '<li>' + xfwEvidenceEsc(i) + '</li>';
        }).join('') + '</ul>';
    }

    return html;
};

var xfwSynthesisCommitmentBody = function (commitment) {
    var normalized = xfwSynthesisNormalizeSection(commitment);
    var employeeCount = commitment && commitment.employee_count != null ? parseInt(commitment.employee_count, 10) : null;
    var leaderCount = commitment && commitment.leader_count != null ? parseInt(commitment.leader_count, 10) : null;
    var openCount = commitment && commitment.open_count != null ? parseInt(commitment.open_count, 10) : null;

    if (employeeCount != null || leaderCount != null || openCount != null) {
        var html = '<ul>';
        if (employeeCount != null) {
            html += '<li>Employee Commitments: <b>' + employeeCount + ' active</b></li>';
        }
        if (leaderCount != null) {
            html += '<li>Leader Commitments: <b>' + leaderCount + ' active</b></li>';
        }
        if (openCount != null) {
            html += '<li>Open Commitments: <b>' + openCount + ' total</b></li>';
        }
        html += '</ul>';
        return html;
    }

    if (normalized.items.length) {
        return '<ul>' + normalized.items.map(function (i) {
            return '<li>' + xfwEvidenceEsc(i) + '</li>';
        }).join('') + '</ul>';
    }

    return '<ul><li>Employee Commitments: <b>0 active</b></li><li>Leader Commitments: <b>0 active</b></li><li>Open Commitments: <b>0 total</b></li></ul>';
};

var xfwSynthesisListBody = function (raw, emptyMessage) {
    var normalized = xfwSynthesisNormalizeSection(raw);
    var items = normalized.items.length ? normalized.items : [emptyMessage];
    return '<ul>' + items.map(function (i) {
        return '<li>' + xfwEvidenceEsc(i) + '</li>';
    }).join('') + '</ul>';
};

var xfwSynthesisFollowupBody = function (raw) {
    var normalized = xfwSynthesisNormalizeSection(raw);
    var items = normalized.items.length ? normalized.items : ['No follow-up recommendations yet.'];
    return '<div class="xfw-followup">' + items.map(function (item, index) {
        var icon = xfwSynthesisFollowupIcons[index % xfwSynthesisFollowupIcons.length];
        return '<div class="xfw-followup-item"><img src="' + icon + '" alt="" width="50" height="50"> ' +
            xfwEvidenceEsc(item) + '</div>';
    }).join('') + '</div>';
};

var xfwRenderSynthesisPanel = function (synthesis) {
    if (!synthesis || typeof synthesis !== 'object') {
        return '<div class="xfw-banner warn">ℹ️ <span>No AI Meeting Synthesis has been generated yet. Click <b>Generate AI Meeting Synthesis™</b> above.</span></div>';
    }

    var coaching = xfwSynthesisNormalizeSection(synthesis.suggested_coaching_topics);
    var coachingItems = coaching.items.length ? coaching.items : [];
    var coachingHtml = coachingItems.length
        ? '<div class="xfw-row">' + coachingItems.map(function (t) {
            return '<span class="xfw-badge" style="background:#eef2ff;color:#4338ca">' + xfwEvidenceEsc(t) + '</span>';
        }).join('') + '</div>'
        : '<p class="xfw-muted">No coaching topics suggested yet.</p>';

    return '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwSynthesisCard('meeting_summary', xfwSynthesisListBody(synthesis.meeting_summary, 'No meeting summary available yet.')) +
        xfwSynthesisCard('alignment_summary', xfwSynthesisAlignmentBody(synthesis.alignment_summary || {})) +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwSynthesisCard('development_summary', xfwSynthesisListBody(synthesis.development_summary, 'No development summary yet.')) +
        xfwSynthesisCard('commitment_summary', xfwSynthesisCommitmentBody(synthesis.commitment_summary || {})) +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwSynthesisCard('emerging_risks', xfwSynthesisListBody(synthesis.emerging_risks, 'No emerging risks identified.')) +
        xfwSynthesisCard('emerging_opportunities', xfwSynthesisListBody(synthesis.emerging_opportunities, 'No emerging opportunities identified.')) +
        '</div>' +
        xfwSynthesisWideCard('suggested_coaching_topics', coachingHtml) +
        xfwSynthesisWideCard('recommended_follow_up', xfwSynthesisFollowupBody(synthesis.recommended_follow_up));
};

var xfwEnsureSynthesisModal = function () {
    if (root.querySelector('#xfw-synthesis-modal')) {
        return;
    }
    var modal = document.createElement('div');
    modal.id = 'xfw-synthesis-modal';
    modal.className = 'xfw-modal xfw-hidden';
    modal.innerHTML =
        '<div class="xfw-modal-backdrop" data-close-synthesis-modal="1"></div>' +
        '<div class="xfw-modal-card xfw-card">' +
        '<h3 id="xfw-synthesis-modal-title" style="margin-top:0"></h3>' +
        '<div id="xfw-synthesis-modal-body" class="xfw-brief-modal-body"></div>' +
        '<div style="margin-top:1rem;text-align:right">' +
        '<button type="button" class="xfw-btn xfw-btn-outline" data-close-synthesis-modal="1">Close</button>' +
        '</div></div>';
    root.appendChild(modal);
};

var xfwSynthesisDetailsText = function (sectionKey, sectionData) {
    if (!sectionData || typeof sectionData !== 'object') {
        return 'No additional detail is available for this section yet.';
    }
    var normalized = xfwSynthesisNormalizeSection(sectionData);
    var parts = [];

    if (sectionKey === 'alignment_summary') {
        if (sectionData.score != null && !isNaN(parseFloat(sectionData.score))) {
            parts.push('Alignment score: ' + parseFloat(sectionData.score) + ' / 5');
        }
        if (sectionData.label) {
            parts.push('Label: ' + String(sectionData.label));
        }
    }

    if (sectionKey === 'commitment_summary') {
        if (normalized.details) {
            return normalized.details;
        }
        if (sectionData.employee_count != null) {
            parts.push('Employee Commitments: ' + sectionData.employee_count + ' active');
        }
        if (sectionData.leader_count != null) {
            parts.push('Leader Commitments: ' + sectionData.leader_count + ' active');
        }
        if (sectionData.open_count != null) {
            parts.push('Open Commitments: ' + sectionData.open_count + ' total');
        }
        return parts.join('\n\n') || 'No additional detail is available for this section yet.';
    }

    if (normalized.items.length) {
        parts.push(normalized.items.join('\n\n'));
    }

    if (normalized.details) {
        parts.push(normalized.details);
    }

    return parts.join('\n\n') || 'No additional detail is available for this section yet.';
};

var xfwOpenSynthesisDetails = function (sectionKey) {
    var synthesis = window.xfwSynthesisCache.data;
    if (!synthesis) {
        return;
    }
    var meta = xfwSynthesisSectionMeta[sectionKey] || { title: 'Details' };
    var details = xfwSynthesisDetailsText(sectionKey, synthesis[sectionKey]);

    xfwEnsureSynthesisModal();
    var modal = root.querySelector('#xfw-synthesis-modal');
    root.querySelector('#xfw-synthesis-modal-title').textContent = meta.title;
    var bodyHost = root.querySelector('#xfw-synthesis-modal-body');
    if (typeof xfwFormatBriefDetailsHtml === 'function') {
        bodyHost.innerHTML = xfwFormatBriefDetailsHtml(details);
    } else {
        bodyHost.innerHTML = '<div class="xfw-brief-details-para xfw-evidence-text">' +
            xfwEvidenceEsc(details).replace(/\n/g, '<br>') + '</div>';
    }
    modal.classList.remove('xfw-hidden');
};

var xfwCloseSynthesisModal = function () {
    var modal = root.querySelector('#xfw-synthesis-modal');
    if (modal) {
        modal.classList.add('xfw-hidden');
    }
};

var xfwBindSynthesisDetailsLinks = function () {
    var main = root.querySelector('#xfw-main');
    if (!main) {
        return;
    }
    main.querySelectorAll('.xfw-synthesis-details-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            xfwOpenSynthesisDetails(link.getAttribute('data-synthesis-section') || '');
        });
    });
};

var loadWizardSynthesis = function (force) {
    if (!window.XFW_WIZARD) {
        return Promise.resolve(null);
    }
    var cid = typeof xfwGetActiveConversationId === 'function' ? xfwGetActiveConversationId() : 0;
    if (!cid) {
        return Promise.resolve(null);
    }
    if (!force && window.xfwSynthesisCache.loaded && window.xfwSynthesisCache.conversationId === cid) {
        return Promise.resolve(window.xfwSynthesisCache.data);
    }
    if (window.xfwSynthesisCache.loading && window.xfwSynthesisCache.conversationId === cid && window.xfwSynthesisCache._promise) {
        return window.xfwSynthesisCache._promise;
    }

    window.xfwSynthesisCache.loading = true;
    window.xfwSynthesisCache.conversationId = cid;

    var body = new URLSearchParams({
        action: 'xfusion_oo_synthesis',
        nonce: window.XFW_WIZARD.ooNonce,
        conversation_id: String(cid),
    });

    window.xfwSynthesisCache._promise = fetch(window.XFW_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: body,
    })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json && json.success && json.data) {
                window.xfwSynthesisCache.data = json.data;
            } else {
                window.xfwSynthesisCache.data = null;
            }
            window.xfwSynthesisCache.loaded = true;
            return window.xfwSynthesisCache.data;
        })
        .catch(function () {
            window.xfwSynthesisCache.data = null;
            window.xfwSynthesisCache.loaded = true;
            return null;
        })
        .finally(function () {
            window.xfwSynthesisCache.loading = false;
        });

    return window.xfwSynthesisCache._promise;
};

var xfwRenderSynthesisStep = function () {
    var host = root.querySelector('#xfw-synthesis-content');
    if (!host) {
        return;
    }
    host.innerHTML = '<p class="xfw-muted">Loading AI Meeting Synthesis\u2026</p>';
    loadWizardSynthesis(true).then(function (synthesis) {
        host.innerHTML = xfwRenderSynthesisPanel(synthesis);
        xfwBindSynthesisDetailsLinks();
    });
};

var initSynthesisStep = function () {
    xfwEnsureSynthesisModal();
    xfwRenderSynthesisStep();
    if (typeof initGenerateSynthesisButton === 'function') {
        initGenerateSynthesisButton();
    }

    if (!root.dataset.synthesisModalBound) {
        root.dataset.synthesisModalBound = '1';
        root.addEventListener('click', function (e) {
            if (e.target && e.target.getAttribute && e.target.getAttribute('data-close-synthesis-modal') === '1') {
                xfwCloseSynthesisModal();
            }
        });
    }
};

var xfwCollectSynthesisNotes = function () {
    if (typeof collectConversationNotes === 'function') {
        var values = collectConversationNotes();
        return Object.keys(values).filter(function (key) {
            return String(values[key] || '').trim() !== '';
        }).map(function (key) {
            return { section: key, note: String(values[key]) };
        });
    }
    var draft = window.xfwDraftCache && window.xfwDraftCache.data ? window.xfwDraftCache.data : {};
    var conversation = draft.conversation || {};
    return Object.keys(conversation).filter(function (key) {
        return String(conversation[key] || '').trim() !== '';
    }).map(function (key) {
        return { section: key, note: String(conversation[key]) };
    });
};

var xfwCollectSynthesisPreparations = function () {
    var out = {};
    if (typeof collectRolePrepValues === 'function') {
        out.employee = collectRolePrepValues('employee') || {};
        out.leader = collectRolePrepValues('leader') || {};
    } else if (window.xfwDraftCache && window.xfwDraftCache.data) {
        out.employee = window.xfwDraftCache.data.employee || {};
        out.leader = window.xfwDraftCache.data.leader || {};
    }
    return out;
};

var xfwCollectSynthesisCommitments = function () {
    if (typeof collectCommitmentRows !== 'function') {
        return [];
    }
    var rows = collectCommitmentRows('employee').concat(collectCommitmentRows('leader'));
    return rows.filter(function (row) {
        return String(row.title || '').trim() !== '';
    }).map(function (row) {
        return {
            title: String(row.title || ''),
            description: String(row.description || ''),
            owner_role: String(row.owner_role || 'shared'),
            status: String(row.status || 'open'),
            id: row.id ? parseInt(row.id, 10) : 0,
        };
    });
};

var xfwSaveCommitmentsBeforeSynthesis = function () {
    var conversationId = typeof xfwGetActiveConversationId === 'function'
        ? xfwGetActiveConversationId()
        : parseInt(window.XFW_WIZARD.conversationId, 10);
    if (!conversationId || typeof collectCommitmentRows !== 'function') {
        return Promise.resolve();
    }

    var payload = new URLSearchParams();
    payload.set('action', 'xfoo_wizard_save_draft');
    payload.set('nonce', window.XFW_WIZARD.nonce);
    payload.set('conversation_id', String(conversationId));
    payload.set('step', 'commitments');
    payload.set('employee_commitments', JSON.stringify(collectCommitmentRows('employee')));
    payload.set('leader_commitments', JSON.stringify(collectCommitmentRows('leader')));

    console.log('[XFW Step 5] commitments → save draft', {
        employee: collectCommitmentRows('employee'),
        leader: collectCommitmentRows('leader'),
    });

    return fetch(window.XFW_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); }).then(function (json) {
        if (!json || !json.success) {
            throw new Error((json && json.data && json.data.message) ? json.data.message : 'Unable to save commitments.');
        }
    });
};

var generateWizardSynthesis = function () {
    var cid = typeof xfwGetActiveConversationId === 'function' ? xfwGetActiveConversationId() : 0;
    var statusEl = root.querySelector('#xfw-generate-synthesis-status');
    var btn = root.querySelector('#xfw-generate-synthesis');
    if (!cid || !btn) {
        return Promise.resolve(null);
    }

    btn.disabled = true;
    if (statusEl) {
        statusEl.textContent = 'Saving commitments\u2026';
        statusEl.style.color = '';
    }

    return xfwSaveCommitmentsBeforeSynthesis().then(function () {
        if (statusEl) {
            statusEl.textContent = 'Generating AI Meeting Synthesis\u2026';
        }

        var draftPromise = typeof loadWizardDraft === 'function'
            ? loadWizardDraft(false)
            : Promise.resolve(window.xfwDraftCache ? window.xfwDraftCache.data : null);

        return draftPromise.then(function () {
            var synthesisPayload = {
                preparations: xfwCollectSynthesisPreparations(),
                notes: xfwCollectSynthesisNotes(),
                commitments: xfwCollectSynthesisCommitments(),
            };

            console.log('[XFW Step 6] generate synthesis request', {
                conversation_id: cid,
                payload: synthesisPayload,
            });

            var body = new URLSearchParams({
                action: 'xfoo_wizard_generate_synthesis',
                nonce: window.XFW_WIZARD.nonce,
                conversation_id: String(cid),
                preparations: JSON.stringify(synthesisPayload.preparations),
                notes: JSON.stringify(synthesisPayload.notes),
                commitments: JSON.stringify(synthesisPayload.commitments),
            });

            return fetch(window.XFW_WIZARD.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: body,
            }).then(function (res) { return res.json(); });
        });
    }).then(function (json) {
        btn.disabled = false;
        if (!json || !json.success || !json.data || !json.data.synthesis) {
            var errMsg = 'Unable to generate the synthesis. Please try again.';
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

        window.xfwSynthesisCache.data = json.data.synthesis;
        window.xfwSynthesisCache.loaded = true;
        window.xfwSynthesisCache.conversationId = cid;

        console.log('[XFW Step 6] AI Meeting Synthesis response', json.data.synthesis, json.data.meta || {});
        if (json.data.meta && json.data.meta.llm_fallback) {
            console.warn('[XFW Step 6] LLM unavailable, used context-composer fallback', json.data.meta.llm_error || '');
        }
        if (json.data.debug_payload) {
            console.log('[XFW Step 6] synthesis payload → LLM (from server)', json.data.debug_payload);
        }

        if (typeof STEPS !== 'undefined' && STEPS[current] && STEPS[current].key === 'synthesis') {
            xfwRenderSynthesisStep();
        }

        if (statusEl) {
            var fallbackNote = (json.data.meta && json.data.meta.llm_fallback)
                ? ' (offline composer — LLM unavailable)'
                : '';
            statusEl.textContent = '\u2713 AI Meeting Synthesis generated' + fallbackNote + '. Review below.';
            statusEl.style.color = '#16a34a';
        }
        return json.data.synthesis;
    }).catch(function (err) {
        btn.disabled = false;
        if (statusEl) {
            statusEl.textContent = (err && err.message) ? err.message : 'Unable to generate the synthesis. Please try again.';
            statusEl.style.color = '#dc2626';
        }
        return null;
    });
};

var initGenerateSynthesisButton = function () {
    var btn = root.querySelector('#xfw-generate-synthesis');
    if (!btn || btn.dataset.bound === '1') {
        return;
    }
    btn.dataset.bound = '1';
    btn.addEventListener('click', function () {
        generateWizardSynthesis();
    });
};

window.xfwResetSynthesisCache = xfwResetSynthesisCache;
JS;
}
