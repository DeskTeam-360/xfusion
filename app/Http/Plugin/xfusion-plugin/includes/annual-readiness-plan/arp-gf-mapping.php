<?php
/**
 * Gravity Forms mapping — ARP Steps 1, 2, and 5.
 *
 * Create three GF forms in WordPress admin (one per step), then fill in
 * form_id and field_id below. Custom wizard UI uses the same `data-key` slugs
 * as the keys under `fields`.
 *
 * Recommended hidden fields on every form (Hidden type):
 *   - company_id      — wp_companies.id
 *   - plan_year       — e.g. 2026
 *   - arp_id          — wp_fusion_arps.id (0 until Laravel creates the row)
 *   - author_user_id  — wp_users.ID (executive editing the plan)
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * GF mapping keyed by wizard step slug.
 *
 * @return array<string, array{
 *     form_id: int,
 *     hidden: array{company_id: int, plan_year: int, arp_id: int, author_user_id: int},
 *     fields: array<string, array{field_id: int, type: string, label: string, max_length?: int, required?: bool}>
 * }>
 */
function xfarp_gf_mapping(): array
{
    return [
        'foundation' => [
            'form_id' => 515, // TODO: Form ID — ARP Step 1 Organizational Foundation
            'hidden' => [
                'company_id' => 8,     // TODO: Hidden field ID — company_id
                'plan_year' => 9,      // TODO: Hidden field ID — plan_year
                'arp_id' => 10,         // TODO: Hidden field ID — arp_id
                'author_user_id' => 11, // TODO: Hidden field ID — author_user_id
            ],
            'fields' => [
                'mission' => [
                    'field_id' => 1, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Mission',
                    'max_length' => 500,
                    'required' => true,
                ],
                'vision' => [
                    'field_id' => 3, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Vision',
                    'max_length' => 500,
                    'required' => true,
                ],
                'core_values' => [
                    'field_id' => 4, // TODO: Paragraph Text (optional)
                    'type' => 'textarea',
                    'label' => 'Core Values',
                    'max_length' => 1000,
                    'required' => false,
                ],
                'organizational_description' => [
                    'field_id' => 5, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Organizational Description',
                    'max_length' => 1000,
                    'required' => true,
                ],
                'business_environment' => [
                    'field_id' => 6, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Business Environment',
                    'max_length' => 1000,
                    'required' => true,
                ],
                'executive_narrative' => [
                    'field_id' => 7, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Executive Narrative',
                    'max_length' => 2000,
                    'required' => true,
                ],
            ],
        ],
        'future_state' => [
            'form_id' => 516, // TODO: Form ID — ARP Step 2 Future State
            'hidden' => [
                'company_id' => 8,     // TODO: Hidden field ID — company_id
                'plan_year' => 9,      // TODO: Hidden field ID — plan_year
                'arp_id' => 10,         // TODO: Hidden field ID — arp_id
                'author_user_id' => 11, // TODO: Hidden field ID — author_user_id
            ],
            'fields' => [
                'future_state_narrative' => [
                    'field_id' => 1, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Future State Narrative',
                    'max_length' => 2000,
                    'required' => true,
                ],
                'future_characteristics' => [
                    'field_id' => 3, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Future Organizational Characteristics',
                    'max_length' => 1000,
                    'required' => false,
                ],
                'desired_culture' => [
                    'field_id' => 4, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Desired Organizational Culture',
                    'max_length' => 1000,
                    'required' => false,
                ],
                'desired_customer_experience' => [
                    'field_id' => 5, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Desired Customer Experience',
                    'max_length' => 1000,
                    'required' => false,
                ],
                'desired_employee_experience' => [
                    'field_id' => 6, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Desired Employee Experience',
                    'max_length' => 1000,
                    'required' => false,
                ],
                'desired_leadership_environment' => [
                    'field_id' => 7, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Desired Leadership Environment',
                    'max_length' => 1000,
                    'required' => false,
                ],
            ],
        ],
        'learning' => [
            'form_id' => 0, // TODO: Form ID — ARP Step 5 Organizational Learning
            'hidden' => [
                'company_id' => 8,     // TODO: Hidden field ID — company_id
                'plan_year' => 9,      // TODO: Hidden field ID — plan_year
                'arp_id' => 10,         // TODO: Hidden field ID — arp_id
                'author_user_id' => 11, // TODO: Hidden field ID — author_user_id
            ],
            'fields' => [
                'assumptions' => [
                    'field_id' => 1, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Key Organizational Assumptions',
                    'max_length' => 2000,
                    'required' => false,
                ],
                'risks' => [
                    'field_id' => 3, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Potential Risks',
                    'max_length' => 2000,
                    'required' => false,
                ],
                'opportunities' => [
                    'field_id' => 4, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Potential Opportunities',
                    'max_length' => 2000,
                    'required' => false,
                ],
                'learning_objectives' => [
                    'field_id' => 5, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Learning Objectives',
                    'max_length' => 2000,
                    'required' => false,
                ],
                'leadership_questions' => [
                    'field_id' => 6, // TODO: Paragraph Text
                    'type' => 'textarea',
                    'label' => 'Questions Leadership Intends to Answer',
                    'max_length' => 2000,
                    'required' => false,
                ],
            ],
        ],
    ];
}

/**
 * Config for one ARP GF step (foundation | future_state | learning).
 *
 * @return array<string, mixed>|null
 */
function xfarp_gf_step_config(string $step): ?array
{
    $mapping = xfarp_gf_mapping();

    return $mapping[$step] ?? null;
}

/**
 * Steps that persist through Gravity Forms.
 *
 * @return list<string>
 */
function xfarp_gf_step_keys(): array
{
    return ['foundation', 'future_state', 'learning'];
}

/**
 * Whether form_id, hidden field_ids, and all field_ids are filled in for a step.
 */
function xfarp_gf_step_is_configured(string $step): bool
{
    $config = xfarp_gf_step_config($step);
    if ($config === null) {
        return false;
    }

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
 * Whether every GF-backed ARP step is configured.
 */
function xfarp_gf_is_configured(): bool
{
    foreach (xfarp_gf_step_keys() as $step) {
        if (! xfarp_gf_step_is_configured($step)) {
            return false;
        }
    }

    return true;
}

/**
 * Resolve company + plan year context for the current wizard session.
 *
 * @return array{company_id: int, plan_year: int, arp_id: int, author_user_id: int}
 */
function xfarp_wizard_session_context(int $planYear = 0, int $arpId = 0): array
{
    $userId = get_current_user_id();
    $companyId = 0;

    if (function_exists('xfusion_wp_user_linked_company_id')) {
        $companyId = xfusion_wp_user_linked_company_id($userId);
    }

    if ($planYear < 1) {
        $planYear = (int) wp_date('Y');
    }

    return [
        'company_id' => max(0, $companyId),
        'plan_year' => $planYear,
        'arp_id' => max(0, $arpId),
        'author_user_id' => max(0, $userId),
    ];
}

/**
 * Map UI slug values to GF entry input: [ gf_field_id => value, ... ].
 *
 * @param  array<string, mixed>  $values  Keyed by data-key slug from custom UI.
 * @return array<int|string, mixed>
 */
function xfarp_gf_build_entry_input(string $step, array $values, array $context): array
{
    $config = xfarp_gf_step_config($step);
    if ($config === null) {
        return [];
    }

    $entry = [];

    foreach ($config['hidden'] as $hiddenKey => $fieldId) {
        if ((int) $fieldId < 1) {
            continue;
        }
        if (isset($context[$hiddenKey])) {
            $entry[(string) $fieldId] = (string) $context[$hiddenKey];
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
function xfarp_gf_entry_to_values(string $step, array $entry): array
{
    $config = xfarp_gf_step_config($step);
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
