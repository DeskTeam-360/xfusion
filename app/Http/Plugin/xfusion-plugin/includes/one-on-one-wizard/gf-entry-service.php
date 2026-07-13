<?php
/**
 * Gravity Forms entry helpers for the 1-on-1 wizard.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Find an active GF entry by hidden conversation_id (and optionally author).
 *
 * @return array<string, mixed>|null
 */
function xfoo_gf_find_entry_by_conversation(int $formId, int $conversationFieldId, int $conversationId, int $authorUserId = 0, int $authorFieldId = 0): ?array
{
    if (! class_exists('GFAPI') || $formId < 1 || $conversationFieldId < 1 || $conversationId < 1) {
        return null;
    }

    $search = [
        'status' => 'active',
        'field_filters' => [
            'mode' => 'all',
            [
                'key' => (string) $conversationFieldId,
                'value' => (string) $conversationId,
            ],
        ],
    ];

    $entries = GFAPI::get_entries($formId, $search);

    if (is_wp_error($entries) || $entries === []) {
        return null;
    }

    if ($authorUserId > 0) {
        $entries = array_values(array_filter($entries, static function ($entry) use ($authorUserId, $authorFieldId) {
            if ((int) ($entry['created_by'] ?? 0) === $authorUserId) {
                return true;
            }
            if ($authorFieldId > 0) {
                $hiddenAuthor = $entry[(string) $authorFieldId] ?? $entry[$authorFieldId] ?? '';

                return (int) $hiddenAuthor === $authorUserId;
            }

            return false;
        }));
        if ($entries === []) {
            return null;
        }
    }

    usort($entries, static fn ($a, $b) => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));

    return $entries[0];
}

/**
 * Normalize GF field input to string keys (GF entry format).
 *
 * @param  array<int|string, mixed>  $input
 * @return array<string, string>
 */
function xfoo_gf_normalize_field_input(array $input): array
{
    $out = [];

    foreach ($input as $fieldId => $value) {
        if (! is_numeric($fieldId)) {
            continue;
        }
        $out[(string) $fieldId] = is_scalar($value) ? (string) $value : '';
    }

    return $out;
}

/**
 * Persist individual field values via GFAPI (reliable for hidden fields in entry meta).
 *
 * @param  array<string, string>  $fields
 */
function xfoo_gf_persist_entry_fields(int $entryId, array $fields): void
{
    if ($entryId < 1 || ! class_exists('GFAPI')) {
        return;
    }

    foreach ($fields as $fieldId => $value) {
        if (! is_numeric($fieldId)) {
            continue;
        }
        GFAPI::update_entry_field($entryId, (int) $fieldId, $value);
    }
}

/**
 * Create or update a GF entry.
 *
 * @param  array<int|string, mixed>  $input
 * @return array{entry_id: int}|WP_Error
 */
function xfoo_gf_upsert_entry(int $formId, array $input, ?int $entryId = null)
{
    if (! class_exists('GFAPI')) {
        return new WP_Error('gf_unavailable', 'Gravity Forms not available.');
    }

    $userId = get_current_user_id();
    $fields = xfoo_gf_normalize_field_input($input);

    if ($entryId !== null && $entryId > 0) {
        $existing = GFAPI::get_entry($entryId);
        if (is_wp_error($existing)) {
            return $existing;
        }

        foreach ($fields as $fieldId => $value) {
            $existing[$fieldId] = $value;
        }
        $existing['form_id'] = $formId;
        $existing['id'] = $entryId;

        $result = GFAPI::update_entry($existing, $entryId);
        if (is_wp_error($result)) {
            return $result;
        }

        xfoo_gf_persist_entry_fields($entryId, $fields);

        return ['entry_id' => $entryId];
    }

    $entry = array_merge($fields, [
        'form_id' => $formId,
        'created_by' => $userId,
        'status' => 'active',
    ]);

    $newEntryId = GFAPI::add_entry($entry);
    if (is_wp_error($newEntryId)) {
        return $newEntryId;
    }

    $newEntryId = (int) $newEntryId;
    xfoo_gf_persist_entry_fields($newEntryId, $fields);

    return ['entry_id' => $newEntryId];
}

/**
 * Save preparation values for one role into its GF form.
 *
 * @param  array<string, mixed>  $values
 * @return array{role: string, entry_id: int}|WP_Error
 */
function xfoo_gf_save_preparation_role(string $role, int $conversationId, array $values)
{
    if (! xfoo_preparation_gf_is_configured()) {
        return new WP_Error('prep_not_configured', 'Preparation GF mapping is not configured yet.');
    }

    $config = xfoo_preparation_gf_role_config($role);
    if ($config === null) {
        return new WP_Error('invalid_role', 'Invalid preparation role.');
    }

    $formId = (int) $config['form_id'];
    $conversationFieldId = (int) ($config['hidden']['conversation_id'] ?? 0);
    $authorFieldId = (int) ($config['hidden']['author_user_id'] ?? 0);
    $userId = get_current_user_id();

    $payload = array_merge($values, [
        'conversation_id' => $conversationId,
        'author_user_id' => $userId,
    ]);

    $input = xfoo_preparation_gf_build_entry_input($role, $payload);
    if ($input === []) {
        return new WP_Error('empty_input', 'No preparation fields mapped.');
    }

    $existing = xfoo_gf_find_entry_by_conversation($formId, $conversationFieldId, $conversationId, $userId, $authorFieldId);
    $entryId = $existing ? (int) ($existing['id'] ?? 0) : null;

    $result = xfoo_gf_upsert_entry($formId, $input, $entryId > 0 ? $entryId : null);
    if (is_wp_error($result)) {
        return $result;
    }

    return ['role' => $role, 'entry_id' => (int) $result['entry_id']];
}

/**
 * Save Step 4 conversation notes into GF.
 *
 * @param  array<string, mixed>  $values
 * @return array{entry_id: int}|WP_Error
 */
function xfoo_gf_save_conversation_notes(int $conversationId, array $values)
{
    if (! xfoo_conversation_gf_is_configured()) {
        return new WP_Error('conversation_not_configured', 'Conversation GF mapping is not configured yet.');
    }

    $config = xfoo_conversation_gf_mapping();
    $formId = (int) $config['form_id'];
    $conversationFieldId = (int) ($config['hidden']['conversation_id'] ?? 0);
    $authorFieldId = (int) ($config['hidden']['author_user_id'] ?? 0);
    $userId = get_current_user_id();

    $payload = array_merge($values, [
        'conversation_id' => $conversationId,
        'author_user_id' => $userId,
    ]);

    $input = xfoo_conversation_gf_build_entry_input($payload);
    if ($input === []) {
        return new WP_Error('empty_input', 'No conversation note fields mapped.');
    }

    $existing = xfoo_gf_find_entry_by_conversation($formId, $conversationFieldId, $conversationId, $userId, $authorFieldId);
    $entryId = $existing ? (int) ($existing['id'] ?? 0) : null;

    $result = xfoo_gf_upsert_entry($formId, $input, $entryId > 0 ? $entryId : null);
    if (is_wp_error($result)) {
        return $result;
    }

    return ['entry_id' => (int) $result['entry_id']];
}

/**
 * All active GF entries for a conversation (no author filter).
 *
 * @return list<array<string, mixed>>
 */
function xfoo_gf_find_entries_by_conversation(int $formId, int $conversationFieldId, int $conversationId): array
{
    if (! class_exists('GFAPI') || $formId < 1 || $conversationFieldId < 1 || $conversationId < 1) {
        return [];
    }

    $entries = GFAPI::get_entries($formId, [
        'status' => 'active',
        'field_filters' => [
            'mode' => 'all',
            [
                'key' => (string) $conversationFieldId,
                'value' => (string) $conversationId,
            ],
        ],
    ]);

    if (is_wp_error($entries) || $entries === []) {
        return [];
    }

    usort($entries, static fn ($a, $b) => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));

    return $entries;
}

/**
 * Preparation values for one role on any conversation (latest entry, any author).
 *
 * @return array<string, string>
 */
function xfoo_gf_load_preparation_for_conversation(string $role, int $conversationId): array
{
    if (! xfoo_preparation_gf_is_configured()) {
        return [];
    }

    $config = xfoo_preparation_gf_role_config($role);
    if ($config === null) {
        return [];
    }

    $entries = xfoo_gf_find_entries_by_conversation(
        (int) $config['form_id'],
        (int) ($config['hidden']['conversation_id'] ?? 0),
        $conversationId
    );

    if ($entries === []) {
        return [];
    }

    return xfoo_preparation_gf_entry_to_values($role, $entries[0]);
}

/**
 * Conversation notes for any conversation (merge all entries, newest wins per field).
 *
 * @return array<string, string>
 */
function xfoo_gf_load_conversation_notes_for_conversation(int $conversationId): array
{
    if (! xfoo_conversation_gf_is_configured()) {
        return [];
    }

    $config = xfoo_conversation_gf_mapping();
    $entries = xfoo_gf_find_entries_by_conversation(
        (int) $config['form_id'],
        (int) ($config['hidden']['conversation_id'] ?? 0),
        $conversationId
    );

    if ($entries === []) {
        return [];
    }

    $merged = [];
    foreach (array_reverse($entries) as $entry) {
        foreach (xfoo_conversation_gf_entry_to_values($entry) as $slug => $value) {
            if ($value !== '') {
                $merged[$slug] = $value;
            }
        }
    }

    return $merged;
}

/**
 * Load preparation values for one role from GF (current user entry only).
 *
 * @return array<string, string>
 */
function xfoo_gf_load_preparation_role(string $role, int $conversationId): array
{
    if (! xfoo_preparation_gf_is_configured()) {
        return [];
    }

    $config = xfoo_preparation_gf_role_config($role);
    if ($config === null) {
        return [];
    }

    $formId = (int) $config['form_id'];
    $conversationFieldId = (int) ($config['hidden']['conversation_id'] ?? 0);
    $authorFieldId = (int) ($config['hidden']['author_user_id'] ?? 0);
    $userId = get_current_user_id();

    $existing = xfoo_gf_find_entry_by_conversation($formId, $conversationFieldId, $conversationId, $userId, $authorFieldId);
    if ($existing === null) {
        return [];
    }

    return xfoo_preparation_gf_entry_to_values($role, $existing);
}

/**
 * Load Step 4 conversation notes from GF (current user entry only).
 *
 * @return array<string, string>
 */
function xfoo_gf_load_conversation_notes(int $conversationId): array
{
    if (! xfoo_conversation_gf_is_configured()) {
        return [];
    }

    $config = xfoo_conversation_gf_mapping();
    $formId = (int) $config['form_id'];
    $conversationFieldId = (int) ($config['hidden']['conversation_id'] ?? 0);
    $authorFieldId = (int) ($config['hidden']['author_user_id'] ?? 0);
    $userId = get_current_user_id();

    $existing = xfoo_gf_find_entry_by_conversation($formId, $conversationFieldId, $conversationId, $userId, $authorFieldId);
    if ($existing === null) {
        return [];
    }

    return xfoo_conversation_gf_entry_to_values($existing);
}

/**
 * Load all wizard draft data for a conversation (roles the user may access).
 *
 * @return array{employee: array<string, string>, leader: array<string, string>, conversation: array<string, string>}
 */
function xfoo_wizard_load_draft_data(int $conversationId): array
{
    $out = [
        'employee' => [],
        'leader' => [],
        'conversation' => [],
    ];

    if ($conversationId < 1) {
        return $out;
    }

    foreach (xfoo_wizard_allowed_prep_roles() as $role) {
        $out[$role] = xfoo_gf_load_preparation_role($role, $conversationId);
    }

    $out['conversation'] = xfoo_gf_load_conversation_notes($conversationId);

    return $out;
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
 * TODO: replace stub with real pair/conversation lookup from Laravel API.
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
