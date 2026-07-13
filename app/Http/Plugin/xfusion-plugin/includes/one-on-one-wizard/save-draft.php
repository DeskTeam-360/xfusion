<?php
/**
 * Save Draft skeleton — collect custom UI values and write to Gravity Forms.
 *
 * Wired to #xfw-save-draft and #xfw-save-draft-2. Saves whichever step data
 * is present in the current wizard view (preparation + conversation notes).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfoo_wizard_save_draft', 'xfoo_wizard_ajax_save_draft');

function xfoo_wizard_ajax_save_draft(): void
{
    check_ajax_referer('xfoo_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $conversationId = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    if ($conversationId < 1) {
        wp_send_json_error(['message' => 'conversation_id is required.'], 422);
    }

    $step = isset($_POST['step']) ? sanitize_key(wp_unslash($_POST['step'])) : '';
    $saved = [];
    $skipped = [];
    $errors = [];

    if (isset($_POST['user_role'])) {
        $_REQUEST['user_role'] = sanitize_key(wp_unslash($_POST['user_role']));
    }

    $employeeValues = xfoo_wizard_decode_json_post('employee');
    $leaderValues = xfoo_wizard_decode_json_post('leader');
    $conversationValues = xfoo_wizard_decode_json_post('conversation');
    $employeeCommitments = xfoo_wizard_decode_json_post('employee_commitments');
    $leaderCommitments = xfoo_wizard_decode_json_post('leader_commitments');

    if ($step === 'preparation') {
        if (! class_exists('GFAPI')) {
            wp_send_json_error(['message' => 'Gravity Forms not available.'], 503);
        }
        xfoo_wizard_save_prep_roles($conversationId, $employeeValues, $leaderValues, $saved, $skipped, $errors);
    }

    if ($step === 'conversation') {
        if (! class_exists('GFAPI')) {
            wp_send_json_error(['message' => 'Gravity Forms not available.'], 503);
        }
        xfoo_wizard_save_conversation_step($conversationId, $conversationValues, $saved, $skipped, $errors);
    }

    if ($step === 'commitments') {
        xfoo_wizard_save_commitments_step($conversationId, $employeeCommitments, $leaderCommitments, $saved, $skipped, $errors);
    }

    if ($errors !== []) {
        wp_send_json_error([
            'message' => 'Some drafts could not be saved.',
            'errors' => $errors,
            'saved' => $saved,
            'skipped' => $skipped,
        ], 200);
    }

    wp_send_json_success([
        'message' => $saved === [] ? 'Nothing to save for this step.' : 'Draft saved.',
        'saved' => $saved,
        'skipped' => $skipped,
        'saved_at' => current_time('g:i A'),
    ]);
}

/**
 * @param  array<string, mixed>  $employeeValues
 * @param  array<string, mixed>  $leaderValues
 * @param  list<array<string, mixed>>  $saved
 * @param  list<string>  $skipped
 * @param  list<array{scope: string, message: string}>  $errors
 */
function xfoo_wizard_save_prep_roles(
    int $conversationId,
    array $employeeValues,
    array $leaderValues,
    array &$saved,
    array &$skipped,
    array &$errors
): void {
    if (! xfoo_preparation_gf_is_configured()) {
        $skipped[] = 'preparation:not_configured';

        return;
    }

    $allowed = xfoo_wizard_allowed_prep_roles();

    foreach (['employee' => $employeeValues, 'leader' => $leaderValues] as $role => $values) {
        if ($values === []) {
            continue;
        }

        if (! in_array($role, $allowed, true)) {
            $skipped[] = 'preparation:' . $role . ':forbidden';
            continue;
        }

        $result = xfoo_gf_save_preparation_role($role, $conversationId, $values);
        if (is_wp_error($result)) {
            $errors[] = ['scope' => 'preparation:' . $role, 'message' => $result->get_error_message()];
            continue;
        }

        $saved[] = array_merge(['scope' => 'preparation:' . $role], $result);

        // TODO: sync JSON content to wp_fusion_one_on_one_preparations via Laravel API.
        // xfoo_wizard_sync_preparation_to_fusion($conversationId, $role, $values);
    }
}

/**
 * @param  array<string, mixed>  $conversationValues
 * @param  list<array<string, mixed>>  $saved
 * @param  list<string>  $skipped
 * @param  list<array{scope: string, message: string}>  $errors
 */
function xfoo_wizard_save_conversation_step(
    int $conversationId,
    array $conversationValues,
    array &$saved,
    array &$skipped,
    array &$errors
): void {
    if ($conversationValues === []) {
        return;
    }

    if (! xfoo_conversation_gf_is_configured()) {
        $skipped[] = 'conversation:not_configured';

        return;
    }

    $result = xfoo_gf_save_conversation_notes($conversationId, $conversationValues);
    if (is_wp_error($result)) {
        $errors[] = ['scope' => 'conversation', 'message' => $result->get_error_message()];

        return;
    }

    $saved[] = array_merge(['scope' => 'conversation'], $result);

    // TODO: sync per-section notes to wp_fusion_one_on_one_notes via Laravel API.
    // xfoo_wizard_sync_conversation_notes_to_fusion($conversationId, $conversationValues);
}

/**
 * @param  list<array<string, mixed>>  $employeeCommitments
 * @param  list<array<string, mixed>>  $leaderCommitments
 * @param  list<array<string, mixed>>  $saved
 * @param  list<string>  $skipped
 * @param  list<array{scope: string, message: string}>  $errors
 */
function xfoo_wizard_save_commitments_step(
    int $conversationId,
    array $employeeCommitments,
    array $leaderCommitments,
    array &$saved,
    array &$skipped,
    array &$errors
): void {
    if ($employeeCommitments === [] && $leaderCommitments === []) {
        $skipped[] = 'commitments:empty';

        return;
    }

    $result = xfoo_wizard_save_commitments_batch($conversationId, $employeeCommitments, $leaderCommitments);

    foreach ($result['saved'] as $item) {
        $saved[] = $item;
    }

    foreach ($result['errors'] as $item) {
        $errors[] = $item;
    }
}

/**
 * @return array<string, mixed>|list<array<string, mixed>>
 */
function xfoo_wizard_decode_json_post(string $key): array
{
    if (! isset($_POST[$key])) {
        return [];
    }

    $raw = wp_unslash($_POST[$key]);
    if (is_array($raw)) {
        return $raw;
    }

    $decoded = json_decode((string) $raw, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * JS: collect UI values + POST to admin-ajax save-draft endpoint.
 */
function xfoo_wizard_save_draft_js(): string
{
    return <<<'JS'
var saveDraftBusy = false;

var collectRolePrepValues = function (role) {
    var col = root.querySelector('.xfw-prep-col.' + role);
    if (!col) {
        return {};
    }
    var out = {};
    col.querySelectorAll('[data-field][data-type="scale"]').forEach(function (el) {
        var selected = el.querySelector('.xfw-scale-btn.selected');
        if (selected) {
            out[el.dataset.field] = selected.dataset.value;
        }
    });
    col.querySelectorAll('[data-field][data-type="textarea"]').forEach(function (el) {
        var textarea = el.querySelector('textarea');
        if (textarea) {
            out[el.dataset.field] = textarea.value;
        }
    });
    return out;
};

var collectConversationNotes = function () {
    var out = {};
    root.querySelectorAll('[data-field][data-type="textarea"]').forEach(function (el) {
        if (!el.closest('.xfw-prep-col')) {
            var textarea = el.querySelector('textarea');
            if (textarea) {
                out[el.dataset.field] = textarea.value;
            }
        }
    });
    return out;
};

var updateAutosaveLabel = function (text, isError) {
    var el = root.querySelector('.xfw-autosave');
    if (!el) {
        return;
    }
    el.textContent = text;
    el.style.color = isError ? '#dc2626' : '#16a34a';
};

var saveDraft = function () {
    if (saveDraftBusy || !window.XFW_WIZARD) {
        return;
    }

    var conversationId = typeof xfwGetActiveConversationId === 'function'
        ? xfwGetActiveConversationId()
        : parseInt(window.XFW_WIZARD.conversationId, 10);
    if (!conversationId) {
        updateAutosaveLabel('⚠ Set conversation_id on shortcode to save draft', true);
        return;
    }

    var stepKey = STEPS[current] ? STEPS[current].key : '';
    var payload = new URLSearchParams();
    payload.set('action', 'xfoo_wizard_save_draft');
    payload.set('nonce', window.XFW_WIZARD.nonce);
    payload.set('conversation_id', String(conversationId));
    payload.set('step', stepKey);
    if (window.XFW_WIZARD.userRole) {
        payload.set('user_role', window.XFW_WIZARD.userRole);
    }

    if (stepKey === 'preparation') {
        payload.set('employee', JSON.stringify(collectRolePrepValues('employee')));
        payload.set('leader', JSON.stringify(collectRolePrepValues('leader')));
    }

    if (stepKey === 'conversation') {
        payload.set('conversation', JSON.stringify(collectConversationNotes()));
    }

    if (stepKey === 'commitments') {
        payload.set('employee_commitments', JSON.stringify(collectCommitmentRows('employee')));
        payload.set('leader_commitments', JSON.stringify(collectCommitmentRows('leader')));
    }

    saveDraftBusy = true;
    updateAutosaveLabel('Saving draft...', false);

    fetch(window.XFW_WIZARD.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success) {
                var msg = (json && json.data && json.data.message) ? json.data.message : 'Save failed.';
                updateAutosaveLabel('⚠ ' + msg, true);
                return;
            }
            var savedAt = (json.data && json.data.saved_at) ? json.data.saved_at : '';
            updateAutosaveLabel('✓ Draft saved' + (savedAt ? ' ' + savedAt : ''), false);
            if (window.xfwDraftCache && window.xfwDraftCache.data) {
                if (stepKey === 'preparation') {
                    window.xfwDraftCache.data.employee = collectRolePrepValues('employee');
                    window.xfwDraftCache.data.leader = collectRolePrepValues('leader');
                }
                if (stepKey === 'conversation') {
                    window.xfwDraftCache.data.conversation = collectConversationNotes();
                }
            }
            if (stepKey === 'commitments' && typeof loadCommitments === 'function') {
                loadCommitments(true);
            }
        })
        .catch(function () {
            updateAutosaveLabel('⚠ Save failed — network error', true);
        })
        .finally(function () {
            saveDraftBusy = false;
        });
};

['#xfw-save-draft', '#xfw-save-draft-2'].forEach(function (sel) {
    var btn = root.querySelector(sel);
    if (btn) {
        btn.addEventListener('click', saveDraft);
    }
});
JS;
}
