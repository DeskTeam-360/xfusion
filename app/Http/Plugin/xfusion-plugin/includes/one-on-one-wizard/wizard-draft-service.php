<?php
/**
 * 1-on-1 wizard Steps 3–4 — Laravel-backed save/load (preparation + conversation notes).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return array{employee: array<string, string>, leader: array<string, string>, conversation: array<string, string>, your_role: ?string}
 */
function xfoo_wizard_load_draft_data(int $conversationId, string $scope = 'wizard'): array
{
    $empty = [
        'employee' => [],
        'leader' => [],
        'conversation' => [],
        'your_role' => null,
    ];

    if ($conversationId < 1) {
        return $empty;
    }

    $query = [
        'user_id' => get_current_user_id(),
        'scope' => $scope,
    ];

    if (current_user_can('manage_options')) {
        $query['wizard_admin'] = '1';
    }

    $result = xfoo_wizard_fusion_api_request('GET', "/conversations/{$conversationId}/wizard-draft", $query);

    if (! $result['ok']) {
        return $empty;
    }

    $body = is_array($result['body'] ?? null) ? $result['body'] : [];
    $data = is_array($body['data'] ?? null) ? $body['data'] : [];

    $role = isset($data['your_role']) ? sanitize_key((string) $data['your_role']) : '';

    return [
        'employee' => is_array($data['employee'] ?? null) ? $data['employee'] : [],
        'leader' => is_array($data['leader'] ?? null) ? $data['leader'] : [],
        'conversation' => is_array($data['conversation'] ?? null) ? $data['conversation'] : [],
        'your_role' => in_array($role, ['employee', 'leader'], true) ? $role : null,
        'last_step' => isset($data['last_step']) ? sanitize_key((string) $data['last_step']) : null,
        'next_conversation' => is_array($data['next_conversation'] ?? null) ? $data['next_conversation'] : null,
    ];
}

/**
 * Persist the furthest wizard step this conversation has unlocked, so
 * reopening resumes there instead of restarting at Step 1.
 *
 * @return true|WP_Error
 */
function xfoo_wizard_save_step_to_laravel(int $conversationId, string $step)
{
    $body = ['user_id' => get_current_user_id(), 'step' => $step];

    if (current_user_can('manage_options')) {
        $body['wizard_admin'] = true;
    }

    $result = xfoo_wizard_fusion_api_request(
        'POST',
        "/conversations/{$conversationId}/wizard-draft/step",
        [],
        $body
    );

    if (! $result['ok']) {
        $msg = is_array($result['body']) ? ($result['body']['message'] ?? 'Save failed.') : ($result['error'] ?? 'Save failed.');

        return new WP_Error('step_save_failed', (string) $msg);
    }

    return true;
}

/**
 * @param  array<string, mixed>  $employeeValues
 * @param  array<string, mixed>  $leaderValues
 * @return true|WP_Error
 */
function xfoo_wizard_save_preparation_to_laravel(int $conversationId, array $employeeValues, array $leaderValues)
{
    $body = ['user_id' => get_current_user_id()];

    if (current_user_can('manage_options')) {
        $body['wizard_admin'] = true;
    }

    if ($employeeValues !== []) {
        $body['employee'] = $employeeValues;
    }
    if ($leaderValues !== []) {
        $body['leader'] = $leaderValues;
    }

    if (! isset($body['employee']) && ! isset($body['leader'])) {
        return new WP_Error('empty', 'No preparation values to save.');
    }

    $result = xfoo_wizard_fusion_api_request(
        'POST',
        "/conversations/{$conversationId}/wizard-draft/preparation",
        [],
        $body
    );

    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        $msg = (string) ($body['message'] ?? $result['error'] ?? 'Save failed.');
        if (isset($body['errors']) && is_array($body['errors'])) {
            $first = reset($body['errors']);
            if (is_array($first)) {
                $first = reset($first);
            }
            if (is_string($first) && $first !== '') {
                $msg = $first;
            }
        }

        return new WP_Error('prep_save_failed', $msg);
    }

    return true;
}

/**
 * @param  array<string, mixed>  $conversationValues
 * @return true|WP_Error
 */
function xfoo_wizard_save_conversation_notes_to_laravel(int $conversationId, array $conversationValues)
{
    if ($conversationValues === []) {
        return new WP_Error('empty', 'No conversation notes to save.');
    }

    $body = [
        'user_id' => get_current_user_id(),
        'values' => $conversationValues,
    ];

    if (current_user_can('manage_options')) {
        $body['wizard_admin'] = true;
    }

    $result = xfoo_wizard_fusion_api_request(
        'POST',
        "/conversations/{$conversationId}/wizard-draft/conversation-notes",
        [],
        $body
    );

    if (! $result['ok']) {
        $msg = is_array($result['body']) ? ($result['body']['message'] ?? 'Save failed.') : ($result['error'] ?? 'Save failed.');

        return new WP_Error('notes_save_failed', (string) $msg);
    }

    return true;
}

/**
 * Keep only known Step 3 fields for a role, dropping empty values.
 *
 * The UI always serializes the other party's locked column as empty
 * textarea strings. Sending that as `employee: { biggest_accomplishment: "" }`
 * makes Laravel treat it as a write to the other side and reject the save.
 *
 * @param  array<string, mixed>  $values
 * @return array<string, string>
 */
function xfoo_wizard_sanitize_prep_values(string $role, array $values): array
{
    $config = function_exists('xfoo_preparation_gf_role_config')
        ? xfoo_preparation_gf_role_config($role)
        : null;
    $fields = (is_array($config) && is_array($config['fields'] ?? null)) ? $config['fields'] : [];
    $out = [];

    foreach ($fields as $slug => $field) {
        if (! array_key_exists($slug, $values)) {
            continue;
        }
        $raw = $values[$slug];
        if (! is_scalar($raw)) {
            continue;
        }
        $val = trim((string) $raw);
        if ($val === '' || $val === 'null') {
            continue;
        }
        if (($field['type'] ?? '') === 'scale') {
            if (! preg_match('/^[1-5]$/', $val)) {
                continue;
            }
        }
        $out[$slug] = $val;
    }

    return $out;
}

/**
 * Roles the current user may save on Step 3 for this conversation.
 *
 * @return list<string>
 */
function xfoo_wizard_allowed_prep_roles(int $conversationId = 0): array
{
    $role = xfoo_wizard_current_user_role($conversationId);

    if ($role === 'admin') {
        return ['employee', 'leader'];
    }

    if (in_array($role, ['employee', 'leader'], true)) {
        return [$role];
    }

    return [];
}

/**
 * Resolve wizard participant role for the logged-in user.
 *
 * Pairing on this conversation wins — a WP admin who is the leader must
 * save as leader, not as "admin" (which used to POST both columns and
 * made Laravel reject the empty employee payload).
 */
function xfoo_wizard_current_user_role(int $conversationId = 0): string
{
    $uid = get_current_user_id();
    if ($conversationId > 0 && $uid > 0 && function_exists('xfoo_wizard_evidence_pair_for_conversation')) {
        $pair = xfoo_wizard_evidence_pair_for_conversation($conversationId);
        if ((int) ($pair['leader_user_id'] ?? 0) === $uid) {
            return 'leader';
        }
        if ((int) ($pair['employee_user_id'] ?? 0) === $uid) {
            return 'employee';
        }
    }

    $fromRequest = isset($_REQUEST['user_role']) ? sanitize_key(wp_unslash($_REQUEST['user_role'])) : '';
    if (in_array($fromRequest, ['employee', 'leader'], true)) {
        return $fromRequest;
    }

    if (current_user_can('manage_options')) {
        return 'admin';
    }

    $fromFilter = apply_filters('xfoo_wizard_user_role', 'employee', $uid);

    return in_array($fromFilter, ['employee', 'leader', 'admin'], true) ? $fromFilter : 'employee';
}
