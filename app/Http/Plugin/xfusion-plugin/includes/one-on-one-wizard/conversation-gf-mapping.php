<?php
/**
 * Gravity Forms mapping — Step 4 Alignment Conversation™ notes.
 *
 * One GF form covers all conversation notes (per-section + general).
 * Fill in form_id and field_id after the form is created in WordPress admin.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * GF mapping for conversation guide notes.
 *
 * @return array{
 *     form_id: int,
 *     hidden: array{conversation_id: int, author_user_id: int},
 *     fields: array<string, array{field_id: int, type: string, label: string, section: string, max_length?: int}>
 * }
 */
function xfoo_conversation_gf_mapping(): array
{
    return [
        'form_id' => 514, // TODO: Form ID — Conversation Notes
        'hidden' => [
            'conversation_id' => 9, // TODO: Hidden field ID — conversation_id
            'author_user_id' => 10,  // TODO: Hidden field ID — author_user_id
        ],
        'fields' => [
            'priorities' => [
                'field_id' => 1, // TODO: Paragraph Text — 1. Current Priorities
                'type' => 'textarea',
                'label' => '1. Current Priorities',
                'section' => 'priorities',
                'max_length' => 1000,
            ],
            'progress' => [
                'field_id' => 3, // TODO: Paragraph Text — 2. Progress
                'type' => 'textarea',
                'label' => '2. Progress',
                'section' => 'progress',
                'max_length' => 1000,
            ],
            'barriers' => [
                'field_id' => 4, // TODO: Paragraph Text — 3. Barriers
                'type' => 'textarea',
                'label' => '3. Barriers',
                'section' => 'barriers',
                'max_length' => 1000,
            ],
            'development' => [
                'field_id' => 5, // TODO: Paragraph Text — 4. Development
                'type' => 'textarea',
                'label' => '4. Development',
                'section' => 'development',
                'max_length' => 1000,
            ],
            'support' => [
                'field_id' => 6, // TODO: Paragraph Text — 5. Support
                'type' => 'textarea',
                'label' => '5. Support',
                'section' => 'support',
                'max_length' => 1000,
            ],
            'future_opportunities' => [
                'field_id' => 7, // TODO: Paragraph Text — 6. Future Opportunities
                'type' => 'textarea',
                'label' => '6. Future Opportunities',
                'section' => 'future_opportunities',
                'max_length' => 1000,
            ],
            'general' => [
                'field_id' => 8, // TODO: Paragraph Text — Conversation Notes (general)
                'type' => 'textarea',
                'label' => 'Conversation Notes',
                'section' => 'general',
                'max_length' => 2000,
            ],
        ],
    ];
}

/**
 * Whether form_id, hidden field_ids, and all note field_ids are filled in.
 */
function xfoo_conversation_gf_is_configured(): bool
{
    $config = xfoo_conversation_gf_mapping();

    if ((int) ($config['form_id'] ?? 0) < 1) {
        return false;
    }

    foreach ($config['hidden'] ?? [] as $fieldId) {
        if ((int) $fieldId < 1) {
            return false;
        }
    }

    foreach ($config['fields'] ?? [] as $field) {
        if ((int) ($field['field_id'] ?? 0) < 1) {
            return false;
        }
    }

    return true;
}

/**
 * Map UI slug values to GF entry input: [ gf_field_id => value, ... ].
 *
 * @param  array<string, mixed>  $values
 * @return array<int|string, mixed>
 */
function xfoo_conversation_gf_build_entry_input(array $values): array
{
    $config = xfoo_conversation_gf_mapping();
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
function xfoo_conversation_gf_entry_to_values(array $entry): array
{
    $config = xfoo_conversation_gf_mapping();
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
 * Field slugs + labels for read-only evidence display.
 *
 * @return array<string, string>
 */
function xfoo_conversation_gf_field_labels(): array
{
    $out = [];

    foreach (xfoo_conversation_gf_mapping()['fields'] ?? [] as $slug => $field) {
        $out[$slug] = (string) ($field['label'] ?? $slug);
    }

    return $out;
}
