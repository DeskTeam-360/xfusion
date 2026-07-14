<?php
/**
 * Gravity Forms mapping — Step 3 Shared Preparation™.
 *
 * Fill in form_id and field_id after the GF forms are created in WordPress admin.
 * The custom UI in step-3-preparation.php uses the same `data-field` slugs as the
 * keys below; the Save Draft handler maps each slug to its GF field_id.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Full GF mapping for employee + leader preparation forms.
 *
 * @return array{
 *     employee: array{
 *         form_id: int,
 *         author_role: string,
 *         hidden: array{conversation_id: int, author_user_id: int},
 *         fields: array<string, array{field_id: int, type: string, label: string, max_length?: int}>
 *     },
 *     leader: array{
 *         form_id: int,
 *         author_role: string,
 *         hidden: array{conversation_id: int, author_user_id: int},
 *         fields: array<string, array{field_id: int, type: string, label: string, max_length?: int}>
 *     }
 * }
 */
function xfoo_preparation_gf_mapping(): array
{
    return [
        'employee' => [
            'form_id' => 512, // TODO: Form ID — Employee Preparation
            'author_role' => 'employee',
            'hidden' => [
                'conversation_id' => 10, // TODO: Hidden field ID — conversation_id
                'author_user_id' => 11,  // TODO: Hidden field ID — author_user_id
            ],
            'fields' => [
                'alignment_clarity' => [
                    'field_id' => 1, // TODO: Radio/Likert 1–5
                    'type' => 'scale',
                    'label' => 'Alignment Clarity',
                ],
                'workload_sustainability' => [
                    'field_id' => 3, // TODO: Radio/Likert 1–5
                    'type' => 'scale',
                    'label' => 'Current Workload Sustainability',
                ],
                'confidence_priorities' => [
                    'field_id' => 4, // TODO: Radio/Likert 1–5
                    'type' => 'scale',
                    'label' => 'Confidence in Current Priorities',
                ],
                'biggest_accomplishment' => [
                    'field_id' => 5, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Biggest accomplishment since last meeting',
                    'max_length' => 1000,
                ],
                'biggest_obstacle' => [
                    'field_id' => 6, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Biggest current obstacle',
                    'max_length' => 1000,
                ],
                'support_needed' => [
                    'field_id' => 7, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Support needed from your leader',
                    'max_length' => 1000,
                ],
                'development_focus' => [
                    'field_id' => 8, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Development focus',
                    'max_length' => 1000,
                ],
            ],
        ],
        'leader' => [
            'form_id' => 513    , // TODO: Form ID — Leader Preparation
            'author_role' => 'leader',
            'hidden' => [
                'conversation_id' => 1, // TODO: Hidden field ID — conversation_id
                'author_user_id' => 3,  // TODO: Hidden field ID — author_user_id
            ],
            'fields' => [
                'priority_alignment' => [
                    'field_id' => 4, // TODO: Radio/Likert 1–5
                    'type' => 'scale',
                    'label' => 'Priority Alignment',
                ],
                'observed_progress' => [
                    'field_id' => 5, // TODO: Radio/Likert 1–5
                    'type' => 'scale',
                    'label' => 'Observed Progress',
                ],
                'support_effectiveness' => [
                    'field_id' => 6, // TODO: Radio/Likert 1–5
                    'type' => 'scale',
                    'label' => 'Support Effectiveness',
                ],
                'coaching_topics' => [
                    'field_id' => 7, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Coaching topics to discuss',
                    'max_length' => 1000,
                ],
                'org_updates' => [
                    'field_id' => 8, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Organizational updates to share',
                    'max_length' => 1000,
                ],
                'discussion_priorities' => [
                    'field_id' => 9, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Top discussion priorities',
                    'max_length' => 1000,
                ],
            ],
        ],
    ];
}

/**
 * Config for one role (employee | leader).
 *
 * @return array<string, mixed>|null
 */
function xfoo_preparation_gf_role_config(string $role): ?array
{
    $mapping = xfoo_preparation_gf_mapping();
    if (! isset($mapping[$role])) {
        return null;
    }

    return $mapping[$role];
}

/**
 * Whether every form_id, hidden field_id, and question field_id is filled in.
 */
function xfoo_preparation_gf_is_configured(): bool
{
    foreach (xfoo_preparation_gf_mapping() as $roleConfig) {
        if ((int) ($roleConfig['form_id'] ?? 0) < 1) {
            return false;
        }

        foreach ($roleConfig['hidden'] ?? [] as $fieldId) {
            if ((int) $fieldId < 1) {
                return false;
            }
        }

        foreach ($roleConfig['fields'] ?? [] as $field) {
            if ((int) ($field['field_id'] ?? 0) < 1) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Map UI slug values to GF entry input: [ gf_field_id => value, ... ].
 *
 * @param  array<string, mixed>  $values  Keyed by data-field slug from custom UI.
 * @return array<int|string, mixed>
 */
function xfoo_preparation_gf_build_entry_input(string $role, array $values): array
{
    $config = xfoo_preparation_gf_role_config($role);
    if ($config === null) {
        return [];
    }

    $entry = [];

    foreach ($config['hidden'] as $hiddenKey => $fieldId) {
        if ((int) $fieldId < 1) {
            continue;
        }
        if ($hiddenKey === 'conversation_id') {
            $cid = (int) ($values['conversation_id'] ?? 0);
            if ($cid > 0) {
                $entry[(string) $fieldId] = (string) $cid;
            }
            continue;
        }
        if ($hiddenKey === 'author_user_id') {
            $authorId = (int) ($values['author_user_id'] ?? get_current_user_id());
            if ($authorId > 0) {
                $entry[(string) $fieldId] = (string) $authorId;
            }
            continue;
        }
    }

    foreach ($config['fields'] as $slug => $field) {
        $fieldId = (int) ($field['field_id'] ?? 0);
        if ($fieldId < 1 || ! array_key_exists($slug, $values)) {
            continue;
        }
        $entry[(string) $fieldId] = (string) $values[$slug];
    }

    return $entry;
}

/**
 * Map a GF entry back to UI slug values.
 *
 * @param  array<string, mixed>  $entry
 * @return array<string, string>
 */
function xfoo_preparation_gf_entry_to_values(string $role, array $entry): array
{
    $config = xfoo_preparation_gf_role_config($role);
    if ($config === null) {
        return [];
    }

    $values = [];

    foreach ($config['fields'] as $slug => $field) {
        $fieldId = (string) ($field['field_id'] ?? '');
        if ($fieldId === '' || ! isset($entry[$fieldId])) {
            continue;
        }
        $values[$slug] = (string) $entry[$fieldId];
    }

    return $values;
}

/**
 * Field slugs + labels for the wizard UI (no GF field IDs exposed to browser).
 *
 * @return array{employee: array<string, string>, leader: array<string, string>}
 */
function xfoo_preparation_gf_field_labels_for_js(): array
{
    $out = [];

    foreach (xfoo_preparation_gf_mapping() as $role => $roleConfig) {
        $out[$role] = [];
        foreach ($roleConfig['fields'] ?? [] as $slug => $field) {
            $out[$role][$slug] = (string) ($field['label'] ?? $slug);
        }
    }

    return $out;
}
