<?php
/**
 * Unified COR insights — one API request, one DB row (scoring_group_id = 0).
 * Per-category [send_evaluation] flow is unchanged.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_COR_UNIFIED_GROUP_ID = 0;

const XFUSION_COR_UNIFIED_GROUP_TITLE = 'COR Unified Insights';

/**
 * Map detail weight to primary / secondary / tertiary tier.
 * Matches CSV scale: 1.00 = primary, 0.50 = secondary, 0.25 = tertiary.
 */
function xfusion_cor_unified_weight_tier(float $weight): ?string
{
    if ($weight <= 0) {
        return null;
    }

    $rounded = round($weight, 2);

    if (abs($rounded - 1.0) < 0.01) {
        return 'primary';
    }

    if (abs($rounded - 0.5) < 0.01) {
        return 'secondary';
    }

    if (abs($rounded - 0.25) < 0.01) {
        return 'tertiary';
    }

    return null;
}

/**
 * Category slug for API payload keys (e.g. get_real).
 */
function xfusion_cor_unified_category_key(string $title): string
{
    $slug = sanitize_title($title);

    return str_replace('-', '_', $slug);
}

/**
 * @return array{primary: list<array{question: string, answer: string}>, secondary: list<array{question: string, answer: string}>, tertiary: list<array{question: string, answer: string}>}
 */
function xfusion_cor_unified_empty_tiers(): array
{
    return [
        'primary' => [],
        'secondary' => [],
        'tertiary' => [],
    ];
}

/**
 * Collect Q&A for one performance category, bucketed by weight tier.
 *
 * @return array{primary: list<array{question: string, answer: string}>, secondary: list<array{question: string, answer: string}>, tertiary: list<array{question: string, answer: string}>, latest_entry_ts: int}
 */
function xfusion_cor_unified_collect_category(int $groupId, int $userId, ?array $period = null): array
{
    $tiers = xfusion_cor_unified_empty_tiers();
    $latestEntryTs = 0;
    $seen = [];

    if ($groupId < 1 || $userId < 1 || ! function_exists('xfusion_csg_group_details_for_scoring')) {
        return array_merge($tiers, ['latest_entry_ts' => 0]);
    }

    global $wpdb;
    $entryTable = $wpdb->prefix . 'gf_entry';
    $details = xfusion_csg_group_details_for_scoring($groupId);

    foreach ($details as $detail) {
        $formId = (int) ($detail['form_id'] ?? 0);
        $fieldId = (int) ($detail['field_id'] ?? 0);
        $weight = (float) ($detail['weight'] ?? 0);
        $tier = xfusion_cor_unified_weight_tier($weight);

        if ($tier === null || $formId < 1 || $fieldId < 1) {
            continue;
        }

        $dedupeKey = $formId . ':' . $fieldId;
        if (isset($seen[$dedupeKey])) {
            continue;
        }
        $seen[$dedupeKey] = true;

        if (! function_exists('xfusion_csg_latest_entry_id') || ! function_exists('xfusion_csg_entry_field_value')) {
            continue;
        }

        $entryId = xfusion_csg_latest_entry_id($formId, $userId, $period);
        if ($entryId > 0) {
            $entryDate = $wpdb->get_var(
                $wpdb->prepare("SELECT date_created FROM {$entryTable} WHERE id = %d", $entryId)
            );
            if (is_string($entryDate) && $entryDate !== '') {
                $ts = strtotime($entryDate);
                if ($ts !== false && $ts > $latestEntryTs) {
                    $latestEntryTs = $ts;
                }
            }
        }

        $raw = $entryId > 0 ? xfusion_csg_entry_field_value($entryId, $formId, $fieldId) : null;
        $answer = $raw !== null ? trim((string) $raw) : '';
        if ($answer === '' || $answer === '-') {
            continue;
        }

        $label = function_exists('xfusion_send_eval_gf_field_label')
            ? xfusion_send_eval_gf_field_label($formId, $fieldId)
            : sprintf(__('Field %d', 'xfusion'), $fieldId);

        $tiers[$tier][] = [
            'question' => $label,
            'answer' => $answer,
        ];
    }

    return array_merge($tiers, ['latest_entry_ts' => $latestEntryTs]);
}

/**
 * @return array<string, float|null>
 */
function xfusion_cor_unified_collect_capabilities(int $userId): array
{
    $keys = ['alignment', 'accountability', 'communication', 'leadership', 'execution'];
    $out = array_fill_keys($keys, null);

    if (! function_exists('xfusion_cor_dimensions_data')) {
        return $out;
    }

    foreach (xfusion_cor_dimensions_data($userId) as $item) {
        $key = (string) ($item['key'] ?? '');
        if ($key !== '' && array_key_exists($key, $out)) {
            $avg = $item['average'] ?? null;
            $out[$key] = $avg !== null ? round((float) $avg, 2) : null;
        }
    }

    return $out;
}

/**
 * Build unified request body per AI.md.
 *
 * @return array<string, mixed>|null
 */
function xfusion_cor_unified_collect_payload(int $userId): ?array
{
    if ($userId < 1 || ! function_exists('xfusion_cor_readiness_categories')) {
        return null;
    }

    $period = function_exists('xfusion_insight_date_filter_score_period')
        ? xfusion_insight_date_filter_score_period()
        : null;

    $performance = [];
    $latestEntryTs = 0;
    $hasAnswers = false;

    foreach (xfusion_cor_readiness_categories() as $title) {
        $groupId = function_exists('xfusion_send_eval_group_id_from_category')
            ? xfusion_send_eval_group_id_from_category($title)
            : 0;

        $key = xfusion_cor_unified_category_key($title);
        $collected = $groupId > 0
            ? xfusion_cor_unified_collect_category($groupId, $userId, $period)
            : array_merge(xfusion_cor_unified_empty_tiers(), ['latest_entry_ts' => 0]);

        $ts = (int) ($collected['latest_entry_ts'] ?? 0);
        if ($ts > $latestEntryTs) {
            $latestEntryTs = $ts;
        }

        unset($collected['latest_entry_ts']);

        foreach (['primary', 'secondary', 'tertiary'] as $tier) {
            if (! empty($collected[$tier])) {
                $hasAnswers = true;
            }
        }

        $performance[$key] = $collected;
    }

    if (! $hasAnswers) {
        return null;
    }

    $createdAt = $latestEntryTs > 0
        ? gmdate('Y-m-d\TH:i:s\Z', $latestEntryTs)
        : gmdate('Y-m-d\TH:i:s\Z');

    return array_merge([
        'user_id' => $userId,
        'created_at' => $createdAt,
        'company_information' => 0,
        'cor_organization_capabilities' => xfusion_cor_unified_collect_capabilities($userId),
        'performance' => $performance,
    ], function_exists('xfusion_llm_insight_generation_config') ? xfusion_llm_insight_generation_config() : []);
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message: string, data?: array<string, mixed>}
 */
function xfusion_cor_unified_call_api(array $body): array
{
    if (! function_exists('xfusion_llm_api_url')) {
        return ['ok' => false, 'message' => 'XFusion LLM helpers not loaded.'];
    }

    $skip = function_exists('xfusion_llm_config_skip_reason') ? xfusion_llm_config_skip_reason() : '';
    if ($skip !== '') {
        return ['ok' => false, 'message' => $skip];
    }

    $url = xfusion_llm_api_url() . '/api/v1/evaluation/evaluate-unified';
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    $key = xfusion_llm_api_key();
    if ($key !== '') {
        $headers['Authorization'] = 'Bearer ' . $key;
    }

    $response = wp_remote_post($url, [
        'timeout' => 180,
        'headers' => $headers,
        'body' => wp_json_encode($body),
    ]);

    if (is_wp_error($response)) {
        return ['ok' => false, 'message' => $response->get_error_message()];
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw = wp_remote_retrieve_body($response);
    $decoded = json_decode($raw, true);

    if ($code < 200 || $code >= 300) {
        $detail = is_array($decoded) && isset($decoded['detail'])
            ? (is_string($decoded['detail']) ? $decoded['detail'] : wp_json_encode($decoded['detail']))
            : $raw;

        return ['ok' => false, 'message' => sprintf('API error (%d): %s', $code, (string) $detail)];
    }

    return [
        'ok' => true,
        'message' => __('Unified insights generated successfully.', 'xfusion'),
        'data' => is_array($decoded) ? $decoded : [],
    ];
}

/**
 * @return array{
 *   on_cooldown: bool,
 *   seconds_remaining: int,
 *   available_at: string,
 *   available_at_ts: int,
 *   last_evaluated_at: string
 * }
 */
function xfusion_cor_unified_cooldown_status(int $userId): array
{
    $empty = [
        'on_cooldown' => false,
        'seconds_remaining' => 0,
        'available_at' => '',
        'available_at_ts' => 0,
        'last_evaluated_at' => '',
    ];

    if ($userId < 1) {
        return $empty;
    }

    if (function_exists('xfusion_llm_insight_cooldown_enabled') && ! xfusion_llm_insight_cooldown_enabled()) {
        return $empty;
    }

    $latest = function_exists('xfusion_result_evaluation_latest_unified_published')
        ? xfusion_result_evaluation_latest_unified_published($userId)
        : null;

    if ($latest === null) {
        return $empty;
    }

    $evaluatedAt = trim((string) ($latest['evaluated_at'] ?? ''));
    if ($evaluatedAt === '') {
        return $empty;
    }

    $lastTs = strtotime($evaluatedAt . ' UTC');
    if ($lastTs === false) {
        return $empty;
    }

    $availableTs = $lastTs + XFUSION_SEND_EVAL_COOLDOWN_SECONDS;
    $remaining = $availableTs - time();

    return [
        'on_cooldown' => $remaining > 0,
        'seconds_remaining' => $remaining > 0 ? $remaining : 0,
        'available_at' => gmdate('Y-m-d H:i:s', $availableTs),
        'available_at_ts' => $availableTs,
        'last_evaluated_at' => $evaluatedAt,
    ];
}

/**
 * Run unified COR insights (single API call + single DB row).
 *
 * @return array{
 *   ok: bool,
 *   skipped: bool,
 *   message: string,
 *   result_id: int,
 *   cooldown?: array<string, mixed>,
 *   evaluation?: array<string, mixed>
 * }
 */
function xfusion_cor_unified_process(int $userId, bool $enforceCooldown = true): array
{
    $base = [
        'ok' => false,
        'skipped' => false,
        'message' => '',
        'result_id' => 0,
    ];

    if ($userId < 1) {
        $base['skipped'] = true;
        $base['message'] = __('Invalid user.', 'xfusion');

        return $base;
    }

    if ($enforceCooldown && (! function_exists('xfusion_llm_insight_cooldown_enabled') || xfusion_llm_insight_cooldown_enabled())) {
        $cooldown = xfusion_cor_unified_cooldown_status($userId);
        if ($cooldown['on_cooldown']) {
            $base['skipped'] = true;
            $base['message'] = sprintf(
                /* translators: %s: remaining time */
                __('Generate Insights is available again in %s.', 'xfusion'),
                xfusion_send_eval_format_cooldown_remaining((int) $cooldown['seconds_remaining'])
            );
            $base['cooldown'] = $cooldown;

            return $base;
        }
    }

    $payload = xfusion_cor_unified_collect_payload($userId);
    if ($payload === null) {
        $base['skipped'] = true;
        $base['message'] = __('No answered questions found for COR categories.', 'xfusion');

        return $base;
    }

    $result = xfusion_cor_unified_call_api($payload);
    if (! $result['ok']) {
        $base['message'] = $result['message'];

        return $base;
    }

    $apiData = $result['data'] ?? [];
    $savedId = function_exists('xfusion_result_evaluation_insert_unified')
        ? xfusion_result_evaluation_insert_unified(
            $userId,
            $apiData,
            $payload,
            function_exists('xfusion_llm_insight_default_status') ? xfusion_llm_insight_default_status() : null
        )
        : 0;

    if ($savedId < 1) {
        $tableHint = function_exists('xfusion_result_evaluation_table_name')
            ? xfusion_result_evaluation_table_name()
            : 'wp_xfusion_result_evaluations';

        $base['message'] = sprintf(
            /* translators: %s: database table name */
            __('Insights received but failed to save to table %s.', 'xfusion'),
            $tableHint
        );
        $base['evaluation'] = is_array($apiData['evaluation'] ?? null) ? $apiData['evaluation'] : $apiData;

        return $base;
    }

    $base['ok'] = true;
    $base['message'] = $result['message'];
    $base['result_id'] = $savedId;

    if (function_exists('xfusion_llm_insight_default_status')) {
        $defaultStatus = xfusion_llm_insight_default_status();
        if ($defaultStatus === (defined('XFUSION_RESULT_EVAL_STATUS_DRAFT') ? XFUSION_RESULT_EVAL_STATUS_DRAFT : 'draft')) {
            $base['message'] .= ' ' . __('Saved as draft. Publish in Result Evaluations admin to show on the dashboard.', 'xfusion');
            $base['is_draft'] = true;
        } elseif ($defaultStatus === (defined('XFUSION_RESULT_EVAL_STATUS_SANDBOX') ? XFUSION_RESULT_EVAL_STATUS_SANDBOX : 'sandbox')) {
            $base['message'] .= ' ' . __('Saved in sandbox mode. Visible on the dashboard; you can generate again without cooldown.', 'xfusion');
            $base['is_sandbox'] = true;
        }
    }

    return $base;
}

/**
 * Latest unified evaluation payload for a user (respects insight date filter).
 *
 * @return array<string, mixed>|null
 */
function xfusion_cor_unified_evaluation_for_user(int $userId): ?array
{
    if ($userId < 1) {
        return null;
    }

    $latest = function_exists('xfusion_insight_date_filter_resolve_unified')
        ? xfusion_insight_date_filter_resolve_unified($userId)
        : (function_exists('xfusion_result_evaluation_latest_unified')
            ? xfusion_result_evaluation_latest_unified($userId)
            : null);

    if ($latest === null) {
        return null;
    }

    $evaluation = is_array($latest['evaluation'] ?? null) ? $latest['evaluation'] : [];

    return $evaluation !== [] ? $evaluation : null;
}

/**
 * COR organization capabilities narrative from latest unified insight.
 */
function xfusion_cor_unified_organization_capabilities_for_user(int $userId): string
{
    $evaluation = xfusion_cor_unified_evaluation_for_user($userId);
    if ($evaluation === null) {
        return '';
    }

    return trim((string) ($evaluation['cor_organization_capabilities'] ?? ''));
}

/**
 * Key observation text from latest unified insight for dashboard display.
 */
function xfusion_cor_unified_key_observation_for_user(int $userId): string
{
    $evaluation = xfusion_cor_unified_evaluation_for_user($userId);
    if ($evaluation === null) {
        return '';
    }

    return trim((string) ($evaluation['key_observation'] ?? ''));
}

/**
 * Recommended focus area from latest unified insight.
 */
function xfusion_cor_unified_recommended_focus_area_for_user(int $userId): string
{
    $evaluation = xfusion_cor_unified_evaluation_for_user($userId);
    if ($evaluation === null) {
        return '';
    }

    return trim((string) ($evaluation['recommended_focus_area'] ?? ''));
}

/**
 * Per-category strength/opportunity from unified performance block.
 *
 * @return array{strength: string, opportunity: string}
 */
function xfusion_cor_unified_performance_feedback_for_category(int $userId, string $categoryTitle): array
{
    $empty = ['strength' => '', 'opportunity' => ''];

    $latest = function_exists('xfusion_insight_date_filter_resolve_unified')
        ? xfusion_insight_date_filter_resolve_unified($userId)
        : (function_exists('xfusion_result_evaluation_latest_unified')
            ? xfusion_result_evaluation_latest_unified($userId)
            : null);

    if ($latest === null) {
        return $empty;
    }

    $evaluation = is_array($latest['evaluation'] ?? null) ? $latest['evaluation'] : [];
    $performance = is_array($evaluation['performance'] ?? null) ? $evaluation['performance'] : [];
    $key = xfusion_cor_unified_category_key($categoryTitle);
    $block = is_array($performance[$key] ?? null) ? $performance[$key] : [];

    $strength = trim((string) ($block['strength'] ?? ''));
    $opportunity = trim((string) ($block['opportunity'] ?? $block['weakness'] ?? ''));
    if (function_exists('xfusion_result_evaluation_strip_feedback_prefix')) {
        $strength = xfusion_result_evaluation_strip_feedback_prefix($strength);
        $opportunity = xfusion_result_evaluation_strip_feedback_prefix($opportunity);
    }

    return [
        'strength' => $strength,
        'opportunity' => $opportunity,
    ];
}
