<?php
/**
 * Step 5 — Generate AI Meeting Synthesis™ (post-meeting record).
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

var xfwSynthesisCard = function (iconSrc, title, bodyHtml) {
    var iconHtml = iconSrc
        ? '<div class="icon"><img src="' + iconSrc + '" alt="" width="50" height="50"></div>'
        : '';
    return '<div class="xfw-insight-card">' + iconHtml + '<h3>' + title + '</h3>' + bodyHtml + '</div>';
};

var xfwRenderSynthesisPanel = function (synthesis) {
    if (!synthesis || typeof synthesis !== 'object') {
        return '<div class="xfw-banner warn">ℹ️ <span>No AI Meeting Synthesis has been generated yet. Return to Step 5 and click <b>Generate AI Meeting Synthesis™</b>.</span></div>';
    }

    var meeting = xfwSynthesisNormalizeSection(synthesis.meeting_summary);
    var meetingItems = meeting.items.length
        ? meeting.items
        : ['No meeting summary available yet.'];
    var meetingHtml = '<ul>' + meetingItems.map(function (i) {
        return '<li>' + xfwEvidenceEsc(i) + '</li>';
    }).join('') + '</ul>';

    var alignment = synthesis.alignment_summary && typeof synthesis.alignment_summary === 'object'
        ? synthesis.alignment_summary
        : {};
    var alignScore = alignment.score != null && !isNaN(parseFloat(alignment.score))
        ? parseFloat(alignment.score)
        : null;
    var alignLabel = alignment.label ? String(alignment.label) : '';
    var alignPct = alignScore != null ? Math.min(100, Math.max(0, (alignScore / 5) * 100)) : 0;
    var alignmentHtml =
        '<p class="xfw-muted">How aligned both participants are on priorities, goals, and expectations.</p>' +
        (alignScore != null
            ? '<div style="font-size:1.6rem;font-weight:800;color:var(--green)">' + alignScore +
                '<span style="font-size:1rem;color:var(--muted);font-weight:400"> / 5</span></div>'
            : '') +
        (alignLabel ? '<div class="xfw-muted">' + xfwEvidenceEsc(alignLabel) + '</div>' : '') +
        (alignScore != null
            ? '<div class="xfw-progress-track" style="margin:.5rem 0"><div class="xfw-progress-fill" style="width:' + alignPct + '%"></div></div>'
            : '');

    var development = xfwSynthesisNormalizeSection(synthesis.development_summary);
    var devItems = development.items.length ? development.items : ['No development summary yet.'];
    var developmentHtml = '<ul>' + devItems.map(function (i) {
        return '<li>' + xfwEvidenceEsc(i) + '</li>';
    }).join('') + '</ul>';

    var commitment = synthesis.commitment_summary && typeof synthesis.commitment_summary === 'object'
        ? synthesis.commitment_summary
        : {};
    var commitmentNorm = xfwSynthesisNormalizeSection(commitment);
    var commitmentHtml = '';
    if (commitment.employee_count != null || commitment.leader_count != null || commitment.open_count != null) {
        commitmentHtml += '<ul>';
        if (commitment.employee_count != null) {
            commitmentHtml += '<li>Employee Commitments: <b>' + xfwEvidenceEsc(String(commitment.employee_count)) + ' active</b></li>';
        }
        if (commitment.leader_count != null) {
            commitmentHtml += '<li>Leader Commitments: <b>' + xfwEvidenceEsc(String(commitment.leader_count)) + ' active</b></li>';
        }
        if (commitment.open_count != null) {
            commitmentHtml += '<li>Open Commitments: <b>' + xfwEvidenceEsc(String(commitment.open_count)) + ' total</b></li>';
        }
        commitmentHtml += '</ul>';
    } else if (commitmentNorm.items.length) {
        commitmentHtml = '<ul>' + commitmentNorm.items.map(function (i) {
            return '<li>' + xfwEvidenceEsc(i) + '</li>';
        }).join('') + '</ul>';
    } else {
        commitmentHtml = '<ul><li>No commitment summary yet.</li></ul>';
    }

    var risks = xfwSynthesisNormalizeSection(synthesis.emerging_risks);
    var riskItems = risks.items.length ? risks.items : ['No emerging risks identified.'];
    var risksHtml = '<ul>' + riskItems.map(function (i) {
        return '<li>' + xfwEvidenceEsc(i) + '</li>';
    }).join('') + '</ul>';

    var opportunities = xfwSynthesisNormalizeSection(synthesis.emerging_opportunities);
    var oppItems = opportunities.items.length ? opportunities.items : ['No emerging opportunities identified.'];
    var opportunitiesHtml = '<ul>' + oppItems.map(function (i) {
        return '<li>' + xfwEvidenceEsc(i) + '</li>';
    }).join('') + '</ul>';

    var coaching = xfwSynthesisNormalizeSection(synthesis.suggested_coaching_topics);
    var coachingItems = coaching.items.length ? coaching.items : [];
    var coachingHtml = coachingItems.length
        ? '<div class="xfw-row">' + coachingItems.map(function (t) {
            return '<span class="xfw-badge" style="background:#eef2ff;color:#4338ca">' + xfwEvidenceEsc(t) + '</span>';
        }).join('') + '</div>'
        : '<p class="xfw-muted">No coaching topics suggested yet.</p>';

    var followUp = xfwSynthesisNormalizeSection(synthesis.recommended_follow_up);
    var followItems = followUp.items.length ? followUp.items : ['No follow-up recommendations yet.'];
    var followHtml = '<div class="xfw-followup">' + followItems.map(function (item) {
        return '<div class="xfw-followup-item">' + xfwEvidenceEsc(item) + '</div>';
    }).join('') + '</div>';

    return '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwSynthesisCard(xfwSynthesisIconBase + 'Clipboard-Checkmark-Blue-Icon.svg', 'Meeting Summary\u2122', meetingHtml) +
        xfwSynthesisCard(xfwSynthesisIconBase + 'Arrow-in-Target-Icon-1.svg', 'Alignment Summary\u2122', alignmentHtml) +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwSynthesisCard(xfwSynthesisIconBase + 'Arrow-in-Target-Icon-1.svg', 'Development Summary\u2122', developmentHtml) +
        xfwSynthesisCard(xfwSynthesisIconBase + 'Clipboard-Checkmark-Icon-1.svg', 'Commitment Summary\u2122', commitmentHtml) +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        xfwSynthesisCard(xfwSynthesisIconBase + 'Warning-Triangle-Icon-2.svg', 'Emerging Risks\u2122', risksHtml) +
        xfwSynthesisCard(xfwSynthesisIconBase + 'Trending-Up-Arrow-Icon-Green-1.svg', 'Emerging Opportunities\u2122', opportunitiesHtml) +
        '</div>' +
        '<div class="xfw-card" style="margin-bottom:1rem">' +
        '<div class="xfw-commit-title"><img src="' + xfwSynthesisIconBase + 'Orange-Light-Bulb-Icon.svg" alt="" width="50" height="50"><h3>Suggested Coaching Topics\u2122</h3></div>' +
        coachingHtml +
        '</div>' +
        '<div class="xfw-card">' +
        '<div class="xfw-commit-title"><img src="' + xfwSynthesisIconBase + 'Calendar-Icon-Teal.svg" alt="" width="50" height="50"><h3>Recommended Follow-up\u2122</h3></div>' +
        followHtml +
        '</div>';
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
    });
};

var initSynthesisStep = function () {
    xfwRenderSynthesisStep();
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

            console.log('[XFW Step 5] generate synthesis request', {
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

        console.log('[XFW Step 5] AI Meeting Synthesis response', json.data.synthesis, json.data.meta || {});
        if (json.data.meta && json.data.meta.llm_fallback) {
            console.warn('[XFW Step 5] LLM unavailable, used context-composer fallback', json.data.meta.llm_error || '');
        }
        if (json.data.debug_payload) {
            console.log('[XFW Step 5] synthesis payload → LLM (from server)', json.data.debug_payload);
        }

        if (statusEl) {
            var fallbackNote = (json.data.meta && json.data.meta.llm_fallback)
                ? ' (offline composer — LLM unavailable)'
                : '';
            statusEl.textContent = '\u2713 AI Meeting Synthesis generated' + fallbackNote + '. Continue to Step 6 to review.';
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
