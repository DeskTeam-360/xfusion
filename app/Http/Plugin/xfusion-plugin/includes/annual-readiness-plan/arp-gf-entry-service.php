<?php
/**
 * Gravity Forms entry helpers for the ARP wizard (steps 1, 2, 5).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

$xfarpGfService = dirname(__DIR__) . '/one-on-one-wizard/gf-entry-service.php';
if (is_readable($xfarpGfService)) {
    require_once $xfarpGfService;
}

/**
 * Find the latest active GF entry for an ARP step by company + plan year.
 *
 * @return array<string, mixed>|null
 */
function xfarp_gf_find_entry_by_context(string $step, int $companyId, int $planYear, int $authorUserId = 0): ?array
{
    if (! class_exists('GFAPI')) {
        return null;
    }

    $config = xfarp_gf_step_config($step);
    if ($config === null || $companyId < 1 || $planYear < 1) {
        return null;
    }

    $formId = (int) ($config['form_id'] ?? 0);
    $companyFieldId = (int) ($config['hidden']['company_id'] ?? 0);
    $yearFieldId = (int) ($config['hidden']['plan_year'] ?? 0);
    $authorFieldId = (int) ($config['hidden']['author_user_id'] ?? 0);

    if ($formId < 1 || $companyFieldId < 1 || $yearFieldId < 1) {
        return null;
    }

    $search = [
        'status' => 'active',
        'field_filters' => [
            'mode' => 'all',
            [
                'key' => (string) $companyFieldId,
                'value' => (string) $companyId,
            ],
            [
                'key' => (string) $yearFieldId,
                'value' => (string) $planYear,
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
 * Save one GF-backed ARP step.
 *
 * @param  array<string, mixed>  $values
 * @return array{step: string, entry_id: int}|WP_Error
 */
function xfarp_gf_save_step(string $step, array $values, array $context)
{
    if (! xfarp_gf_step_is_configured($step)) {
        return new WP_Error('arp_gf_not_configured', 'ARP GF mapping for step "' . $step . '" is not configured yet.');
    }

    if (! function_exists('xfoo_gf_upsert_entry')) {
        return new WP_Error('gf_helpers_missing', 'GF entry helpers are not loaded.');
    }

    $config = xfarp_gf_step_config($step);
    if ($config === null) {
        return new WP_Error('invalid_step', 'Invalid ARP step.');
    }

    $companyId = (int) ($context['company_id'] ?? 0);
    $planYear = (int) ($context['plan_year'] ?? 0);
    if ($companyId < 1 || $planYear < 1) {
        return new WP_Error('missing_context', 'company_id and plan_year are required.');
    }

    $formId = (int) $config['form_id'];
    $authorUserId = (int) ($context['author_user_id'] ?? get_current_user_id());
    $payload = array_merge($values, $context);
    $input = xfarp_gf_build_entry_input($step, $payload, $context);

    if ($input === []) {
        return new WP_Error('empty_input', 'No ARP fields mapped for this step.');
    }

    $existing = xfarp_gf_find_entry_by_context($step, $companyId, $planYear, $authorUserId);
    $entryId = $existing ? (int) ($existing['id'] ?? 0) : null;

    $result = xfoo_gf_upsert_entry($formId, $input, $entryId > 0 ? $entryId : null);
    if (is_wp_error($result)) {
        return $result;
    }

    return ['step' => $step, 'entry_id' => (int) $result['entry_id']];
}

/**
 * Load saved values for one GF-backed ARP step.
 *
 * @return array<string, string>
 */
function xfarp_gf_load_step(string $step, array $context): array
{
    if (! xfarp_gf_step_is_configured($step)) {
        return [];
    }

    $companyId = (int) ($context['company_id'] ?? 0);
    $planYear = (int) ($context['plan_year'] ?? 0);
    $authorUserId = (int) ($context['author_user_id'] ?? get_current_user_id());

    if ($companyId < 1 || $planYear < 1) {
        return [];
    }

    $existing = xfarp_gf_find_entry_by_context($step, $companyId, $planYear, $authorUserId);
    if ($existing === null) {
        return [];
    }

    return xfarp_gf_entry_to_values($step, $existing);
}

/**
 * Load all GF-backed ARP steps for the wizard.
 *
 * @return array{foundation: array<string, string>, future_state: array<string, string>, learning: array<string, string>}
 */
function xfarp_gf_load_all_steps(array $context): array
{
    $out = [
        'foundation' => [],
        'future_state' => [],
        'learning' => [],
    ];

    foreach (xfarp_gf_step_keys() as $step) {
        $out[$step] = xfarp_gf_load_step($step, $context);
    }

    return $out;
}
