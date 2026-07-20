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
 * @return array{employee: array<string, string>, leader: array<string, string>, conversation: array<string, string>}
 */
function xfoo_wizard_load_draft_data(int $conversationId, string $scope = 'wizard'): array
{
    $empty = [
        'employee' => [],
        'leader' => [],
        'conversation' => [],
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

    return [
        'employee' => is_array($data['employee'] ?? null) ? $data['employee'] : [],
        'leader' => is_array($data['leader'] ?? null) ? $data['leader'] : [],
        'conversation' => is_array($data['conversation'] ?? null) ? $data['conversation'] : [],
    ];
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
        $msg = is_array($result['body']) ? ($result['body']['message'] ?? 'Save failed.') : ($result['error'] ?? 'Save failed.');

        return new WP_Error('prep_save_failed', (string) $msg);
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
 * Roles the current user may save on Step 3.
 *
 * @return list<string>
 */
function xfoo_wizard_allowed_prep_roles(): array
{
    $role = xfoo_wizard_current_user_role();

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
 */
function xfoo_wizard_current_user_role(): string
{
    if (current_user_can('manage_options')) {
        return 'admin';
    }

    $fromRequest = isset($_REQUEST['user_role']) ? sanitize_key(wp_unslash($_REQUEST['user_role'])) : '';
    if (in_array($fromRequest, ['employee', 'leader'], true)) {
        return $fromRequest;
    }

    $fromFilter = apply_filters('xfoo_wizard_user_role', 'employee', get_current_user_id());

    return in_array($fromFilter, ['employee', 'leader', 'admin'], true) ? $fromFilter : 'employee';
}
