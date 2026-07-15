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

    $result = xfoo_wizard_fusion_api_request('POST', "/conversations/{$conversationId}/generate-synthesis", [], [
        'force_refresh' => true,
        'debug' => true,
    ]);

    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json_error(['message' => $result['error'] ?? ($body['message'] ?? 'Failed to generate AI Meeting Synthesis.')], 200);
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
window.xfwSynthesisCache = { loaded: false, data: null, conversationId: 0 };

var xfwResetSynthesisCache = function () {
    window.xfwSynthesisCache = { loaded: false, data: null, conversationId: 0 };
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

        console.log('[XFW Step 5] generate synthesis request', { conversation_id: cid });

        var body = new URLSearchParams({
            action: 'xfoo_wizard_generate_synthesis',
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
        if (json.data.debug_payload) {
            console.log('[XFW Step 5] synthesis payload → LLM (from server)', json.data.debug_payload);
        }

        if (statusEl) {
            statusEl.textContent = '\u2713 AI Meeting Synthesis generated. Continue to Step 6 to review.';
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
