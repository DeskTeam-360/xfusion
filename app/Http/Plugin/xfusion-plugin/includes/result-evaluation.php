<?php
/**
 * AI evaluation results — stored in `{prefix}xfusion_result_evaluations`.
 *
 * Data is not stored in wp_posts so sandbox/production content sync does not mix evaluation records.
 * Table is created automatically via dbDelta; Laravel migration: database/migrations/2026_06_03_000000_...
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_RESULT_EVAL_DB_VERSION = '1.3';

/** Unified COR insights row uses scoring_group_id = 0 (one row per generate-all). */
const XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID = 0;

const XFUSION_RESULT_EVAL_UNIFIED_TITLE = 'COR Unified Insights';

function xfusion_result_evaluation_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'xfusion_result_evaluations';
}

function xfusion_result_evaluation_maybe_create_table(): void
{
    if (get_option('xfusion_result_evaluations_db_version') === XFUSION_RESULT_EVAL_DB_VERSION) {
        return;
    }

    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = xfusion_result_evaluation_table_name();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL,
        evaluated_at datetime NOT NULL,
        company_information bigint(20) unsigned NOT NULL DEFAULT 0,
        scoring_group_id bigint(20) unsigned NOT NULL,
        scoring_group_title varchar(255) NOT NULL DEFAULT '',
        score tinyint(3) unsigned NOT NULL DEFAULT 0,
        evaluation_input longtext NOT NULL,
        evaluation longtext NOT NULL,
        prompt_tokens int(10) unsigned NOT NULL DEFAULT 0,
        completion_tokens int(10) unsigned NOT NULL DEFAULT 0,
        tokens_used int(10) unsigned NOT NULL DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'published',
        insight_model varchar(64) NOT NULL DEFAULT '',
        prompt_version_id varchar(64) NOT NULL DEFAULT '',
        prompt_version_label varchar(255) NOT NULL DEFAULT '',
        cost_usd decimal(12,6) NOT NULL DEFAULT 0,
        inserted_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_group (user_id, scoring_group_id),
        KEY evaluated_at (evaluated_at)
    ) {$charset};";

    dbDelta($sql);
    update_option('xfusion_result_evaluations_db_version', XFUSION_RESULT_EVAL_DB_VERSION);
}

add_action('plugins_loaded', 'xfusion_result_evaluation_maybe_create_table', 5);

function xfusion_result_evaluation_status_published(): string
{
    return defined('XFUSION_RESULT_EVAL_STATUS_PUBLISHED')
        ? XFUSION_RESULT_EVAL_STATUS_PUBLISHED
        : 'published';
}

function xfusion_result_evaluation_status_draft(): string
{
    return defined('XFUSION_RESULT_EVAL_STATUS_DRAFT')
        ? XFUSION_RESULT_EVAL_STATUS_DRAFT
        : 'draft';
}

function xfusion_result_evaluation_status_sandbox(): string
{
    return defined('XFUSION_RESULT_EVAL_STATUS_SANDBOX')
        ? XFUSION_RESULT_EVAL_STATUS_SANDBOX
        : 'sandbox';
}

/**
 * Statuses shown on the user dashboard (published + sandbox).
 *
 * @return list<string>
 */
function xfusion_result_evaluation_user_visible_statuses(): array
{
    return [
        xfusion_result_evaluation_status_published(),
        xfusion_result_evaluation_status_sandbox(),
    ];
}

function xfusion_result_evaluation_status_is_user_visible(string $status): bool
{
    return in_array(
        xfusion_result_evaluation_normalize_status($status),
        xfusion_result_evaluation_user_visible_statuses(),
        true
    );
}

function xfusion_result_evaluation_status_triggers_cooldown(string $status): bool
{
    return xfusion_result_evaluation_normalize_status($status) === xfusion_result_evaluation_status_published();
}

function xfusion_result_evaluation_status_label(string $status): string
{
    $status = xfusion_result_evaluation_normalize_status($status);

    if ($status === xfusion_result_evaluation_status_draft()) {
        return __('Draft', 'xfusion');
    }
    if ($status === xfusion_result_evaluation_status_sandbox()) {
        return __('Sandbox', 'xfusion');
    }

    return __('Published', 'xfusion');
}

function xfusion_result_evaluation_normalize_status(string $status): string
{
    $status = sanitize_key($status);

    if ($status === xfusion_result_evaluation_status_draft()) {
        return xfusion_result_evaluation_status_draft();
    }
    if ($status === xfusion_result_evaluation_status_sandbox()) {
        return xfusion_result_evaluation_status_sandbox();
    }

    return xfusion_result_evaluation_status_published();
}

function xfusion_result_evaluation_parse_datetime(string $iso): string
{
    $ts = strtotime($iso);

    return $ts !== false ? gmdate('Y-m-d H:i:s', $ts) : gmdate('Y-m-d H:i:s');
}

/**
 * Display name for titles: first_name from user meta, then WP user fallbacks.
 */
function xfusion_result_evaluation_user_first_name(int $userId): string
{
    if ($userId < 1) {
        return '';
    }

    $firstName = trim((string) get_user_meta($userId, 'first_name', true));
    if ($firstName !== '') {
        return $firstName;
    }

    $user = get_userdata($userId);
    if ($user instanceof \WP_User) {
        $displayParts = preg_split('/\s+/', trim($user->display_name), 2);
        $fromDisplay = is_array($displayParts) && isset($displayParts[0]) ? trim((string) $displayParts[0]) : '';
        if ($fromDisplay !== '') {
            return $fromDisplay;
        }

        if (! empty($user->user_nicename)) {
            return (string) $user->user_nicename;
        }

        if (! empty($user->user_login)) {
            return (string) $user->user_login;
        }
    }

    return sprintf(__('User %d', 'xfusion'), $userId);
}

function xfusion_result_evaluation_read_token_int(array $source, string ...$keys): int
{
    foreach ($keys as $key) {
        if (! array_key_exists($key, $source) || $source[$key] === null || $source[$key] === '') {
            continue;
        }

        return max(0, (int) $source[$key]);
    }

    return 0;
}

/**
 * @param array<string, mixed> $apiData
 * @return array{tokens_used: int, prompt_tokens: int, completion_tokens: int}
 */
function xfusion_result_evaluation_parse_token_usage(array $apiData): array
{
    $usage = [];
    foreach (['token_usage', 'usage', 'tokenUsage'] as $key) {
        if (isset($apiData[$key]) && is_array($apiData[$key])) {
            $usage = $apiData[$key];
            break;
        }
    }

    $prompt = xfusion_result_evaluation_read_token_int($usage, 'prompt_tokens', 'input_tokens');
    if ($prompt === 0) {
        $prompt = xfusion_result_evaluation_read_token_int($apiData, 'prompt_tokens', 'input_tokens');
    }

    $completion = xfusion_result_evaluation_read_token_int($usage, 'completion_tokens', 'output_tokens');
    if ($completion === 0) {
        $completion = xfusion_result_evaluation_read_token_int($apiData, 'completion_tokens', 'output_tokens');
    }

    $total = xfusion_result_evaluation_read_token_int($usage, 'total_tokens', 'tokens_used');
    if ($total === 0) {
        $total = xfusion_result_evaluation_read_token_int($apiData, 'total_tokens', 'tokens_used');
    }
    if ($total === 0 && ($prompt > 0 || $completion > 0)) {
        $total = $prompt + $completion;
    }

    return [
        'prompt_tokens' => $prompt,
        'completion_tokens' => $completion,
        'tokens_used' => $total,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function xfusion_result_evaluation_decode_json(string $json): ?array
{
    $json = trim($json);
    if ($json === '') {
        return null;
    }

    $attempts = [$json, stripslashes($json), wp_unslash($json)];

    foreach ($attempts as $candidate) {
        if (! is_string($candidate) || $candidate === '') {
            continue;
        }

        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (is_string($decoded)) {
            $nested = json_decode($decoded, true);
            if (is_array($nested)) {
                return $nested;
            }
        }
    }

    return null;
}

/**
 * Strip legacy heading prefixes from AI feedback body text.
 */
function xfusion_result_evaluation_strip_feedback_prefix(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $prefixes = [
        "WHAT YOU'RE DOING WELL:",
        "WHAT YOU'RE DOING WELL —",
        "WHAT YOU'RE DOING WELL -",
        "GREATEST STRENGTH:",
        "GREATEST STRENGTH —",
        "GREATEST STRENGTH -",
        "WHAT'S HOLDING YOU BACK:",
        "WHAT'S HOLDING YOU BACK —",
        "WHAT'S HOLDING YOU BACK -",
        "GREATEST OPPORTUNITY:",
        "GREATEST OPPORTUNITY —",
        "GREATEST OPPORTUNITY -",
    ];

    foreach ($prefixes as $prefix) {
        if (stripos($text, $prefix) === 0) {
            return trim(substr($text, strlen($prefix)));
        }
    }

    return $text;
}

/**
 * Normalize unified evaluation JSON before save (strip headings from performance blocks).
 *
 * @param array<string, mixed> $eval
 * @return array<string, mixed>
 */
function xfusion_result_evaluation_normalize_unified_eval(array $eval): array
{
    if (! isset($eval['performance']) || ! is_array($eval['performance'])) {
        return $eval;
    }

    foreach ($eval['performance'] as $key => $block) {
        if (! is_array($block)) {
            continue;
        }
        if (isset($block['weakness']) && ! isset($block['opportunity'])) {
            $block['opportunity'] = $block['weakness'];
            unset($block['weakness']);
        }
        if (isset($block['strength'])) {
            $block['strength'] = xfusion_result_evaluation_strip_feedback_prefix((string) $block['strength']);
        }
        if (isset($block['opportunity'])) {
            $block['opportunity'] = xfusion_result_evaluation_strip_feedback_prefix((string) $block['opportunity']);
        }
        $eval['performance'][$key] = $block;
    }

    return $eval;
}

/**
 * Normalize per-category evaluation JSON before save.
 *
 * @param array<string, mixed> $eval
 * @return array<string, mixed>
 */
function xfusion_result_evaluation_normalize_category_eval(array $eval): array
{
    if (isset($eval['strengths'])) {
        $eval['strengths'] = xfusion_result_evaluation_strip_feedback_prefix((string) $eval['strengths']);
    }
    if (isset($eval['improvements'])) {
        $eval['improvements'] = xfusion_result_evaluation_strip_feedback_prefix((string) $eval['improvements']);
    }

    return $eval;
}

/**
 * Normalize evaluation payload to strengths / improvements / evaluator_notes.
 *
 * @param array<string, mixed>|null $evaluation
 * @return array{strengths: string, improvements: string, evaluator_notes: string}
 */
function xfusion_result_evaluation_extract_feedback(?array $evaluation): array
{
    $empty = [
        'strengths' => '',
        'improvements' => '',
        'evaluator_notes' => '',
    ];

    if ($evaluation === null || $evaluation === []) {
        return $empty;
    }

    if (isset($evaluation['evaluation']) && is_array($evaluation['evaluation'])) {
        $evaluation = $evaluation['evaluation'];
    }

    return [
        'strengths' => xfusion_result_evaluation_strip_feedback_prefix((string) ($evaluation['strengths'] ?? '')),
        'improvements' => xfusion_result_evaluation_strip_feedback_prefix((string) ($evaluation['improvements'] ?? '')),
        'evaluator_notes' => (string) ($evaluation['evaluator_notes'] ?? ''),
    ];
}

/**
 * Feedback section labels and icons (shared by admin + frontend shortcode).
 *
 * @return list<array{key: string, class: string, label: string, icon: string}>
 */
function xfusion_result_evaluation_unified_feedback_sections(): array
{
    $iconBase = content_url('uploads/2026/06');

    return [
        [
            'key' => 'strength',
            'class' => 'strengths',
            'label' => __('Greatest Strength', 'xfusion'),
            'icon' => $iconBase . '/icon-checkmark.png',
        ],
        [
            'key' => 'opportunity',
            'class' => 'improvements',
            'label' => __('Greatest Opportunity', 'xfusion'),
            'icon' => $iconBase . '/icon-alert.png',
        ],
    ];
}

function xfusion_result_evaluation_feedback_sections(): array
{
    $iconBase = content_url('uploads/2026/06');

    return [
        [
            'key' => 'strengths',
            'class' => 'strengths',
            'label' => __('Strength', 'xfusion'),
            'icon' => $iconBase . '/icon-checkmark.png',
        ],
        [
            'key' => 'improvements',
            'class' => 'improvements',
            'label' => __('Opportunity', 'xfusion'),
            'icon' => $iconBase . '/icon-alert.png',
        ],
        // Temporarily hidden — NEXT GROWTH OPPORTUNITY
        // [
        //     'key' => 'evaluator_notes',
        //     'class' => 'notes',
        //     'label' => __('NEXT GROWTH OPPORTUNITY', 'xfusion'),
        //     'icon' => $iconBase . '/icon-up.png',
        // ],
    ];
}

function xfusion_result_evaluation_feedback_sections_css(): string
{
    return <<<'CSS'
.xfusion-eval-feedback-rows{display:flex;flex-direction:column;gap:20px;}
.xfusion-eval-feedback-row{display:flex!important;align-items:flex-start;gap:14px;margin:0;padding:0;background:transparent;border:none;box-sizing:border-box;}
.xfusion-eval-feedback-row__icon{flex-shrink:0;width:48px;height:48px;object-fit:contain;display:block;}
.xfusion-eval-feedback-row__content{flex:1;min-width:0;}
.xfusion-eval-feedback-row__label{display:block!important;margin:0 0 6px;font-size:15px;font-weight:700;text-transform:uppercase;letter-spacing:.02em;color:#1e3a5f;line-height:1.3;}
.xfusion-eval-feedback-row__text{display:block!important;margin:0;font-size:14px;font-weight:400;color:#374151;line-height:1.5;white-space:pre-wrap;word-break:break-word;}
CSS;
}

/**
 * @param array<string, string> $feedback
 * @param list<array{key: string, class: string, label: string, icon: string}>|null $sections
 */
function xfusion_result_evaluation_render_feedback_sections(array $feedback, bool $shortcodeSafe = false, ?array $sections = null): string
{
    $html = $shortcodeSafe ? '' : '<div class="xfusion-eval-feedback-rows">';
    $sections = $sections ?? xfusion_result_evaluation_feedback_sections();

    foreach ($sections as $section) {
        $text = trim((string) ($feedback[$section['key']] ?? ''));
        $displayText = esc_html($text !== '' ? $text : '—');
        $class = 'xfusion-eval-feedback-row xfusion-eval-feedback-row--' . $section['class'];
        $icon = esc_url($section['icon']);
        $label = esc_html($section['label']);

        if ($shortcodeSafe) {
            $html .= sprintf(
                '<p class="%s"><img class="xfusion-eval-feedback-row__icon" src="%s" alt="" width="48" height="48" decoding="async" /><span class="xfusion-eval-feedback-row__content"><strong class="xfusion-eval-feedback-row__label">%s</strong><span class="xfusion-eval-feedback-row__text">%s</span></span></p>',
                esc_attr($class),
                $icon,
                $label,
                $displayText
            );
        } else {
            $html .= sprintf(
                '<section class="%s"><img class="xfusion-eval-feedback-row__icon" src="%s" alt="" width="48" height="48" decoding="async" /><div class="xfusion-eval-feedback-row__content"><h3 class="xfusion-eval-feedback-row__label">%s</h3><p class="xfusion-eval-feedback-row__text">%s</p></div></section>',
                esc_attr($class),
                $icon,
                $label,
                $displayText
            );
        }
    }

    if (! $shortcodeSafe) {
        $html .= '</div>';
    }

    return $html;
}

/**
 * @param object $row
 * @return array<string, mixed>
 */
function xfusion_result_evaluation_row_to_record(object $row): array
{
    return [
        'id' => (int) $row->id,
        'user_id' => (int) $row->user_id,
        'created_at' => (string) $row->created_at,
        'evaluated_at' => (string) $row->evaluated_at,
        'company_information' => (int) $row->company_information,
        'scoring_group_id' => (int) $row->scoring_group_id,
        'scoring_group_title' => (string) $row->scoring_group_title,
        'score' => (int) $row->score,
        'evaluation_input' => xfusion_result_evaluation_decode_json((string) $row->evaluation_input) ?? [],
        'evaluation' => xfusion_result_evaluation_decode_json((string) $row->evaluation) ?? [],
        'prompt_tokens' => (int) $row->prompt_tokens,
        'completion_tokens' => (int) $row->completion_tokens,
        'tokens_used' => (int) $row->tokens_used,
        'status' => xfusion_result_evaluation_normalize_status((string) ($row->status ?? xfusion_result_evaluation_status_published())),
        'insight_model' => (string) ($row->insight_model ?? ''),
        'prompt_version_id' => (string) ($row->prompt_version_id ?? ''),
        'prompt_version_label' => (string) ($row->prompt_version_label ?? ''),
        'cost_usd' => (float) ($row->cost_usd ?? 0),
        'inserted_at' => (string) $row->inserted_at,
    ];
}

/**
 * @return object|null
 */
function xfusion_result_evaluation_get(int $id): ?object
{
    if ($id < 1) {
        return null;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();

    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
    );
}

/**
 * Persist an evaluation result to the custom table.
 *
 * @param array<string, mixed> $apiData Response from /api/v1/evaluation/evaluate (OUT)
 * @param array<string, mixed> $requestBody Request body sent to the API (IN)
 * @return int Row ID, or 0 on failure
 */
function xfusion_result_evaluation_insert(
    int $userId,
    int $groupId,
    string $groupTitle,
    array $apiData,
    array $requestBody = []
): int {
    if ($userId < 1) {
        return 0;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $eval = isset($apiData['evaluation']) && is_array($apiData['evaluation']) ? $apiData['evaluation'] : [];
    $eval = xfusion_result_evaluation_normalize_category_eval($eval);
    $score = isset($eval['score']) ? (int) $eval['score'] : 0;
    $createdAt = isset($apiData['created_at']) ? (string) $apiData['created_at'] : gmdate('c');
    $evaluatedAt = isset($apiData['evaluated_at']) ? (string) $apiData['evaluated_at'] : gmdate('c');
    $companyInfo = isset($apiData['company_information']) ? (int) $apiData['company_information'] : 0;
    $tokenUsage = xfusion_result_evaluation_parse_token_usage($apiData);
    $legacyModel = 'gpt-4o-mini';
    $legacyCost = function_exists('xfusion_llm_estimate_cost')
        ? xfusion_llm_estimate_cost($legacyModel, $tokenUsage['prompt_tokens'], $tokenUsage['completion_tokens'])
        : xfusion_result_evaluation_estimate_cost($tokenUsage['prompt_tokens'], $tokenUsage['completion_tokens'], $legacyModel)['usd'];

    $inputJson = wp_json_encode($requestBody, JSON_UNESCAPED_UNICODE);
    $evalJson = wp_json_encode($eval, JSON_UNESCAPED_UNICODE);
    if (! is_string($inputJson) || ! is_string($evalJson)) {
        return 0;
    }

    $table = xfusion_result_evaluation_table_name();
    $now = gmdate('Y-m-d H:i:s');

    $inserted = $wpdb->insert(
        $table,
        [
            'user_id' => $userId,
            'created_at' => xfusion_result_evaluation_parse_datetime($createdAt),
            'evaluated_at' => xfusion_result_evaluation_parse_datetime($evaluatedAt),
            'company_information' => $companyInfo,
            'scoring_group_id' => $groupId,
            'scoring_group_title' => $groupTitle,
            'score' => max(0, min(100, $score)),
            'evaluation_input' => $inputJson,
            'evaluation' => $evalJson,
            'prompt_tokens' => $tokenUsage['prompt_tokens'],
            'completion_tokens' => $tokenUsage['completion_tokens'],
            'tokens_used' => $tokenUsage['tokens_used'],
            'status' => xfusion_result_evaluation_status_published(),
            'insight_model' => $legacyModel,
            'prompt_version_id' => '',
            'prompt_version_label' => '',
            'cost_usd' => round($legacyCost, 6),
            'inserted_at' => $now,
        ],
        ['%d', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s']
    );

    if ($inserted === false) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

/**
 * Persist unified COR insight (single long JSON row, scoring_group_id = 0).
 *
 * @param array<string, mixed> $apiData Response from /api/v1/evaluation/evaluate-unified
 * @param array<string, mixed> $requestBody Unified request payload (IN)
 * @return int Row ID, or 0 on failure
 */
function xfusion_result_evaluation_insert_unified(
    int $userId,
    array $apiData,
    array $requestBody = [],
    ?string $status = null
): int {
    if ($userId < 1) {
        return 0;
    }

    $eval = isset($apiData['evaluation']) && is_array($apiData['evaluation'])
        ? $apiData['evaluation']
        : $apiData;
    $eval = xfusion_result_evaluation_normalize_unified_eval($eval);

    if (function_exists('xfusion_cor_collect_gauge_snapshots')) {
        $gaugeSnapshots = xfusion_cor_collect_gauge_snapshots($userId);
        if ($gaugeSnapshots !== []) {
            $requestBody['gauge_snapshots'] = $gaugeSnapshots;
            $averages = array_map(
                static fn (array $row): float => (float) ($row['average'] ?? 0),
                array_values($gaugeSnapshots)
            );
            $requestBody['readiness_average'] = round(array_sum($averages) / count($averages), 2);
        }
    }

    $score = 0;
    $caps = is_array($requestBody['cor_organization_capabilities'] ?? null)
        ? $requestBody['cor_organization_capabilities']
        : [];
    $nums = array_filter($caps, static fn ($v): bool => $v !== null && $v !== '' && is_numeric($v));
    if ($nums !== []) {
        $avg = array_sum(array_map('floatval', $nums)) / count($nums);
        $score = (int) round(min(100, max(0, ($avg / 5.0) * 100)));
    }

    $createdAt = isset($apiData['created_at']) ? (string) $apiData['created_at'] : gmdate('c');
    $evaluatedAt = isset($apiData['evaluated_at']) ? (string) $apiData['evaluated_at'] : gmdate('c');
    $companyInfo = isset($apiData['company_information']) ? (int) $apiData['company_information'] : 0;
    $tokenUsage = xfusion_result_evaluation_parse_token_usage($apiData);

    if (isset($apiData['generation_context']) && is_array($apiData['generation_context'])) {
        $requestBody['generation_context'] = $apiData['generation_context'];
    }

    $genCtx = is_array($requestBody['generation_context'] ?? null) ? $requestBody['generation_context'] : [];
    $insightModel = trim((string) ($genCtx['model'] ?? $requestBody['model'] ?? ''));
    if ($insightModel === '' && function_exists('xfusion_llm_insight_model')) {
        $insightModel = xfusion_llm_insight_model();
    }
    if ($insightModel === '') {
        $insightModel = 'gpt-4o-mini';
    }
    $promptVersionId = trim((string) ($genCtx['prompt_version_id'] ?? $requestBody['prompt_version_id'] ?? ''));
    $promptVersionLabel = trim((string) ($genCtx['prompt_version_label'] ?? $requestBody['prompt_version_label'] ?? ''));
    $rates = function_exists('xfusion_llm_model_token_pricing')
        ? xfusion_llm_model_token_pricing($insightModel)
        : ['input_usd' => 0.15, 'output_usd' => 0.60];
    $costUsd = function_exists('xfusion_llm_estimate_cost')
        ? xfusion_llm_estimate_cost($insightModel, $tokenUsage['prompt_tokens'], $tokenUsage['completion_tokens'])
        : xfusion_result_evaluation_estimate_cost($tokenUsage['prompt_tokens'], $tokenUsage['completion_tokens'], $insightModel)['usd'];

    $genCtx['model'] = $insightModel;
    $genCtx['prompt_version_id'] = $promptVersionId;
    $genCtx['prompt_version_label'] = $promptVersionLabel;
    $genCtx['cost_usd'] = round($costUsd, 6);
    $genCtx['pricing_input_per_1m'] = $rates['input_usd'];
    $genCtx['pricing_output_per_1m'] = $rates['output_usd'];
    $requestBody['generation_context'] = $genCtx;

    $rowStatus = $status !== null && $status !== ''
        ? xfusion_result_evaluation_normalize_status($status)
        : (function_exists('xfusion_llm_insight_default_status')
            ? xfusion_llm_insight_default_status()
            : xfusion_result_evaluation_status_draft());

    $inputJson = wp_json_encode($requestBody, JSON_UNESCAPED_UNICODE);
    $evalJson = wp_json_encode($eval, JSON_UNESCAPED_UNICODE);
    if (! is_string($inputJson) || ! is_string($evalJson)) {
        return 0;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $now = gmdate('Y-m-d H:i:s');

    $inserted = $wpdb->insert(
        $table,
        [
            'user_id' => $userId,
            'created_at' => xfusion_result_evaluation_parse_datetime($createdAt),
            'evaluated_at' => xfusion_result_evaluation_parse_datetime($evaluatedAt),
            'company_information' => $companyInfo,
            'scoring_group_id' => XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID,
            'scoring_group_title' => XFUSION_RESULT_EVAL_UNIFIED_TITLE,
            'score' => max(0, min(100, $score)),
            'evaluation_input' => $inputJson,
            'evaluation' => $evalJson,
            'prompt_tokens' => $tokenUsage['prompt_tokens'],
            'completion_tokens' => $tokenUsage['completion_tokens'],
            'tokens_used' => $tokenUsage['tokens_used'],
            'status' => $rowStatus,
            'insight_model' => $insightModel,
            'prompt_version_id' => $promptVersionId,
            'prompt_version_label' => $promptVersionLabel,
            'cost_usd' => round($costUsd, 6),
            'inserted_at' => $now,
        ],
        ['%d', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s']
    );

    if ($inserted === false) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

/**
 * Gauge snapshots from a unified evaluation row's evaluation_input JSON.
 *
 * @return array<string, array{group_id: int, title: string, average: float}>
 */
function xfusion_result_evaluation_parse_gauge_snapshots(?array $evaluationInput): array
{
    if ($evaluationInput === null || $evaluationInput === []) {
        return [];
    }

    $raw = $evaluationInput['gauge_snapshots'] ?? null;
    if (! is_array($raw)) {
        return [];
    }

    $snapshots = [];
    foreach ($raw as $key => $row) {
        if (! is_array($row)) {
            continue;
        }
        $groupId = (int) ($row['group_id'] ?? $key);
        if ($groupId < 1 || ! isset($row['average']) || ! is_numeric($row['average'])) {
            continue;
        }
        $snapshots[(string) $groupId] = [
            'group_id' => $groupId,
            'title' => (string) ($row['title'] ?? ''),
            'average' => round((float) $row['average'], 2),
        ];
    }

    return $snapshots;
}

/**
 * Latest unified evaluation_input for a user (optionally skip the newest row).
 *
 * @return array<string, mixed>|null
 */
function xfusion_result_evaluation_latest_unified_input(int $userId, int $skipLatest = 0): ?array
{
    if ($userId < 1) {
        return null;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $skipLatest = max(0, $skipLatest);
    $visibleStatuses = xfusion_result_evaluation_user_visible_statuses();
    $placeholders = implode(', ', array_fill(0, count($visibleStatuses), '%s'));

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT evaluation_input FROM {$table}
            WHERE user_id = %d AND scoring_group_id = %d AND status IN ({$placeholders})
            ORDER BY evaluated_at DESC, id DESC
            LIMIT 1 OFFSET %d",
            ...array_merge([$userId, XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID], $visibleStatuses, [$skipLatest])
        )
    );

    if ($row === null) {
        return null;
    }

    $decoded = xfusion_result_evaluation_decode_json((string) $row->evaluation_input);

    return is_array($decoded) ? $decoded : null;
}

/**
 * Gauge average saved at the user's last Generate Insights run (baseline for delta).
 */
function xfusion_result_evaluation_gauge_baseline_for_group(int $userId, int $groupId): ?float
{
    if ($userId < 1 || $groupId < 1) {
        return null;
    }

    $input = xfusion_result_evaluation_latest_unified_input($userId, 0);
    $snapshots = xfusion_result_evaluation_parse_gauge_snapshots($input);
    $key = (string) $groupId;

    if (! isset($snapshots[$key]['average'])) {
        return null;
    }

    return (float) $snapshots[$key]['average'];
}

/**
 * @return array{
 *   id: int,
 *   post_id: int,
 *   group_title: string,
 *   evaluated_at: string,
 *   status: string,
 *   evaluation: array<string, mixed>
 * }|null
 */
function xfusion_result_evaluation_row_to_unified_summary(array $record): ?array
{
    $evaluation = is_array($record['evaluation'] ?? null) ? $record['evaluation'] : [];

    return [
        'id' => (int) ($record['id'] ?? 0),
        'post_id' => (int) ($record['id'] ?? 0),
        'group_title' => (string) ($record['scoring_group_title'] ?? ''),
        'evaluated_at' => (string) ($record['evaluated_at'] ?? ''),
        'status' => xfusion_result_evaluation_normalize_status((string) ($record['status'] ?? xfusion_result_evaluation_status_published())),
        'evaluation' => $evaluation,
    ];
}

/**
 * Latest user-visible unified insight for frontend display (published or sandbox).
 *
 * @return array{
 *   id: int,
 *   post_id: int,
 *   group_title: string,
 *   evaluated_at: string,
 *   status: string,
 *   evaluation: array<string, mixed>
 * }|null
 */
function xfusion_result_evaluation_latest_unified(int $userId, bool $userVisibleOnly = true): ?array
{
    if ($userId < 1) {
        return null;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $sql = "SELECT * FROM {$table} WHERE user_id = %d AND scoring_group_id = %d";
    $params = [$userId, XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID];

    if ($userVisibleOnly) {
        $visibleStatuses = xfusion_result_evaluation_user_visible_statuses();
        $placeholders = implode(', ', array_fill(0, count($visibleStatuses), '%s'));
        $sql .= " AND status IN ({$placeholders})";
        $params = array_merge($params, $visibleStatuses);
    }

    $sql .= ' ORDER BY evaluated_at DESC, id DESC LIMIT 1';

    $row = $wpdb->get_row($wpdb->prepare($sql, ...$params));

    if ($row === null) {
        return null;
    }

    $record = xfusion_result_evaluation_row_to_record($row);

    return xfusion_result_evaluation_row_to_unified_summary($record);
}

/**
 * Latest published unified row for cooldown tracking (sandbox and draft excluded).
 *
 * @return array<string, mixed>|null
 */
function xfusion_result_evaluation_latest_unified_published(int $userId): ?array
{
    if ($userId < 1) {
        return null;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE user_id = %d AND scoring_group_id = %d AND status = %s
            ORDER BY evaluated_at DESC, id DESC
            LIMIT 1",
            $userId,
            XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID,
            xfusion_result_evaluation_status_published()
        )
    );

    if ($row === null) {
        return null;
    }

    $record = xfusion_result_evaluation_row_to_record($row);

    return xfusion_result_evaluation_row_to_unified_summary($record);
}

function xfusion_result_evaluation_update_status(int $recordId, string $status): bool
{
    if ($recordId < 1) {
        return false;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $updated = $wpdb->update(
        $table,
        ['status' => xfusion_result_evaluation_normalize_status($status)],
        ['id' => $recordId],
        ['%s'],
        ['%d']
    );

    return $updated !== false;
}

/** @deprecated Use xfusion_result_evaluation_insert() instead. */
function xfusion_result_evaluation_save_post(
    int $userId,
    int $groupId,
    string $groupTitle,
    array $apiData,
    array $requestBody = []
): int {
    return xfusion_result_evaluation_insert($userId, $groupId, $groupTitle, $apiData, $requestBody);
}

/**
 * Latest evaluation for a user + scoring group.
 *
 * @return array{
 *   id: int,
 *   group_title: string,
 *   evaluated_at: string,
 *   evaluation: array<string, mixed>
 * }|null
 */
function xfusion_result_evaluation_latest_for_group(int $userId, int $groupId): ?array
{
    if ($userId < 1 || $groupId < 1) {
        return null;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d AND scoring_group_id = %d ORDER BY evaluated_at DESC, id DESC LIMIT 1",
            $userId,
            $groupId
        )
    );

    if ($row === null) {
        return null;
    }

    $record = xfusion_result_evaluation_row_to_record($row);
    $evaluation = $record['evaluation'];

    return [
        'id' => $record['id'],
        'post_id' => $record['id'],
        'group_title' => $record['scoring_group_title'],
        'evaluated_at' => $record['evaluated_at'],
        'evaluation' => is_array($evaluation) ? $evaluation : [],
    ];
}

function xfusion_result_evaluation_admin_url(int $id = 0, string $view = ''): string
{
    $args = ['page' => 'xfusion-result-evaluations'];
    if ($id > 0) {
        $args['id'] = $id;
    }
    if ($view !== '') {
        $args['view'] = $view;
    }

    return add_query_arg($args, admin_url('admin.php'));
}

function xfusion_result_evaluation_ai_notify_count(): int
{
    return function_exists('xfusion_once_popup_dismissed_user_count')
        ? xfusion_once_popup_dismissed_user_count()
        : 0;
}

/**
 * @return array{accent: string, bg: string, label: string}
 */
function xfusion_result_evaluation_score_theme(int $score): array
{
    if ($score >= 80) {
        return ['accent' => '#16a34a', 'bg' => '#ecfdf5', 'label' => __('Excellent', 'xfusion')];
    }
    if ($score >= 60) {
        return ['accent' => '#ca8a04', 'bg' => '#fefce8', 'label' => __('Progressing', 'xfusion')];
    }

    return ['accent' => '#dc2626', 'bg' => '#fef2f2', 'label' => __('Needs improvement', 'xfusion')];
}

/**
 * @param array{
 *   score: int,
 *   strengths: string,
 *   improvements: string,
 *   evaluator_notes: string,
 *   group_title?: string,
 *   evaluated_at?: string,
 *   post_id?: int,
 *   tokens_used?: int,
 *   prompt_tokens?: int,
 *   completion_tokens?: int
 * } $data
 */
function xfusion_result_evaluation_render_card(array $data): string
{
    $score = max(0, min(100, (int) ($data['score'] ?? 0)));
    $theme = xfusion_result_evaluation_score_theme($score);
    $strengths = (string) ($data['strengths'] ?? '');
    $improvements = (string) ($data['improvements'] ?? '');
    $notes = (string) ($data['evaluator_notes'] ?? '');
    $groupTitle = (string) ($data['group_title'] ?? '');
    $evaluatedAt = (string) ($data['evaluated_at'] ?? '');
    $recordId = isset($data['post_id']) ? (int) $data['post_id'] : (isset($data['id']) ? (int) $data['id'] : 0);
    $tokensUsed = (int) ($data['tokens_used'] ?? 0);
    $promptTokens = (int) ($data['prompt_tokens'] ?? 0);
    $completionTokens = (int) ($data['completion_tokens'] ?? 0);
    $dateLabel = $evaluatedAt !== '' ? esc_html($evaluatedAt) : '—';

    ob_start();
    ?>
<div class="xfusion-eval-card" style="--xf-eval-accent:<?php echo esc_attr($theme['accent']); ?>;--xf-eval-bg:<?php echo esc_attr($theme['bg']); ?>;">
    <div class="xfusion-eval-card__header">
        <div class="xfusion-eval-card__score-ring" aria-label="<?php echo esc_attr(sprintf(__('Score %d out of 100', 'xfusion'), $score)); ?>">
            <span class="xfusion-eval-card__score-value"><?php echo (int) $score; ?></span>
            <span class="xfusion-eval-card__score-max">/100</span>
        </div>
        <div class="xfusion-eval-card__meta">
            <?php if ($groupTitle !== '') : ?>
                <h2 class="xfusion-eval-card__title"><?php echo esc_html($groupTitle); ?></h2>
            <?php else : ?>
                <h2 class="xfusion-eval-card__title"><?php esc_html_e('Evaluation result', 'xfusion'); ?></h2>
            <?php endif; ?>
            <p class="xfusion-eval-card__badge"><?php echo esc_html($theme['label']); ?></p>
            <p class="xfusion-eval-card__date">
                <span><?php esc_html_e('Evaluated', 'xfusion'); ?>:</span> <?php echo $dateLabel; ?>
                <?php if ($recordId > 0) : ?>
                    <span class="xfusion-eval-card__ref">#<?php echo (int) $recordId; ?></span>
                <?php endif; ?>
            </p>
            <p class="xfusion-eval-card__tokens">
                <span><?php esc_html_e('Tokens used', 'xfusion'); ?>:</span>
                <?php echo esc_html(number_format_i18n($tokensUsed)); ?>
                <span class="xfusion-eval-card__tokens-detail">
                    (<?php
                    echo esc_html(sprintf(
                        /* translators: 1: prompt tokens, 2: completion tokens */
                        __('prompt: %1$s, completion: %2$s', 'xfusion'),
                        number_format_i18n($promptTokens),
                        number_format_i18n($completionTokens)
                    ));
                    ?>)
                </span>
            </p>
        </div>
    </div>
    <div class="xfusion-eval-card__body">
        <?php
        echo xfusion_result_evaluation_render_feedback_sections([
            'strengths' => $strengths,
            'improvements' => $improvements,
            'evaluator_notes' => $notes,
        ]);
        ?>
    </div>
</div>
    <?php
    
    return (string) ob_get_clean();
}

/**
 * Feedback-only card (no score header).
 *
 * @param array{
 *   strengths?: string,
 *   improvements?: string,
 *   evaluator_notes?: string
 * } $data
 */
function xfusion_result_evaluation_render_feedback(array $data): string
{
    $feedback = xfusion_result_evaluation_extract_feedback($data);

    ob_start();
    ?>
<div class="xfusion-eval-card xfusion-eval-card--feedback-only">
    <div class="xfusion-eval-card__body">
        <?php echo xfusion_result_evaluation_render_feedback_sections($feedback); ?>
    </div>
</div>
    <?php

    return (string) ob_get_clean();
}

function xfusion_result_evaluation_is_unified_record(array $record): bool
{
    return (int) ($record['scoring_group_id'] ?? -1) === XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID;
}

function xfusion_result_evaluation_unified_category_label(string $slug): string
{
    return ucwords(str_replace('_', ' ', trim($slug)));
}

/**
 * @param array<string, mixed> $evaluation Normalized unified evaluation payload
 * @return list<string>
 */
function xfusion_result_evaluation_unified_performance_order(array $evaluation): array
{
    $performance = is_array($evaluation['performance'] ?? null) ? $evaluation['performance'] : [];
    if ($performance === []) {
        return [];
    }

    $preferred = [];
    if (function_exists('xfusion_cor_readiness_categories')) {
        foreach (xfusion_cor_readiness_categories() as $title) {
            if (! is_string($title) || $title === '') {
                continue;
            }
            $key = function_exists('xfusion_cor_unified_category_key')
                ? xfusion_cor_unified_category_key($title)
                : str_replace('-', '_', sanitize_title($title));
            if (isset($performance[$key])) {
                $preferred[] = $key;
            }
        }
    }

    $remaining = array_values(array_diff(array_keys($performance), $preferred));
    sort($remaining, SORT_STRING);

    return array_merge($preferred, $remaining);
}

/**
 * Admin card for unified COR insights (scoring_group_id = 0).
 *
 * @param array<string, mixed> $record
 */
function xfusion_result_evaluation_render_unified_admin_card(array $record): string
{
    $evaluation = is_array($record['evaluation'] ?? null) ? $record['evaluation'] : [];
    $evaluation = xfusion_result_evaluation_normalize_unified_eval($evaluation);

    $score = max(0, min(100, (int) ($record['score'] ?? 0)));
    $theme = xfusion_result_evaluation_score_theme($score);
    $groupTitle = (string) ($record['scoring_group_title'] ?? XFUSION_RESULT_EVAL_UNIFIED_TITLE);
    $evaluatedAt = (string) ($record['evaluated_at'] ?? '');
    $recordId = (int) ($record['id'] ?? 0);
    $tokensUsed = (int) ($record['tokens_used'] ?? 0);
    $promptTokens = (int) ($record['prompt_tokens'] ?? 0);
    $completionTokens = (int) ($record['completion_tokens'] ?? 0);
    $insightModel = xfusion_result_evaluation_record_insight_model($record);
    $modelMeta = function_exists('xfusion_llm_insight_model_meta') ? xfusion_llm_insight_model_meta($insightModel) : null;
    $modelLabel = $modelMeta !== null ? (string) ($modelMeta['label'] ?? $insightModel) : $insightModel;
    $costLabel = xfusion_result_evaluation_format_record_cost($record);
    $dateLabel = $evaluatedAt !== '' ? esc_html($evaluatedAt) : '—';

    $corInsight = trim((string) ($evaluation['cor_organization_capabilities'] ?? ''));
    $overallInsight = trim((string) ($evaluation['key_observation'] ?? ''));
    $recommendedFocus = trim((string) ($evaluation['recommended_focus_area'] ?? ''));
    $performance = is_array($evaluation['performance'] ?? null) ? $evaluation['performance'] : [];
    $categoryKeys = xfusion_result_evaluation_unified_performance_order($evaluation);

    ob_start();
    ?>
<div class="xfusion-eval-card xfusion-eval-card--unified" style="--xf-eval-accent:<?php echo esc_attr($theme['accent']); ?>;--xf-eval-bg:<?php echo esc_attr($theme['bg']); ?>;">
    <div class="xfusion-eval-card__header">
        <div class="xfusion-eval-card__score-ring" aria-label="<?php echo esc_attr(sprintf(__('COR readiness score %d out of 100', 'xfusion'), $score)); ?>">
            <span class="xfusion-eval-card__score-value"><?php echo (int) $score; ?></span>
            <span class="xfusion-eval-card__score-max">/100</span>
        </div>
        <div class="xfusion-eval-card__meta">
            <h2 class="xfusion-eval-card__title"><?php echo esc_html($groupTitle); ?></h2>
            <p class="xfusion-eval-card__badge"><?php echo esc_html($theme['label']); ?></p>
            <?php
            $rowStatus = xfusion_result_evaluation_normalize_status((string) ($record['status'] ?? xfusion_result_evaluation_status_published()));
            if ($rowStatus === xfusion_result_evaluation_status_draft()) :
                ?>
                <p class="xfusion-eval-card__badge" style="background:#646970;margin-left:6px;"><?php esc_html_e('Draft', 'xfusion'); ?></p>
            <?php elseif ($rowStatus === xfusion_result_evaluation_status_sandbox()) : ?>
                <p class="xfusion-eval-card__badge" style="background:#2271b1;margin-left:6px;"><?php esc_html_e('Sandbox', 'xfusion'); ?></p>
            <?php endif; ?>
            <p class="xfusion-eval-card__date">
                <span><?php esc_html_e('Evaluated', 'xfusion'); ?>:</span> <?php echo $dateLabel; ?>
                <?php if ($recordId > 0) : ?>
                    <span class="xfusion-eval-card__ref">#<?php echo (int) $recordId; ?></span>
                <?php endif; ?>
            </p>
            <p class="xfusion-eval-card__tokens">
                <span><?php esc_html_e('Model', 'xfusion'); ?>:</span>
                <?php echo esc_html($modelLabel); ?>
                <code style="margin-left:4px;"><?php echo esc_html($insightModel); ?></code>
            </p>
            <p class="xfusion-eval-card__tokens">
                <span><?php esc_html_e('Est. cost', 'xfusion'); ?>:</span>
                <?php echo esc_html($costLabel); ?>
            </p>
            <p class="xfusion-eval-card__tokens">
                <span><?php esc_html_e('Tokens used', 'xfusion'); ?>:</span>
                <?php echo esc_html(number_format_i18n($tokensUsed)); ?>
                <span class="xfusion-eval-card__tokens-detail">
                    (<?php
                    echo esc_html(sprintf(
                        /* translators: 1: prompt tokens, 2: completion tokens */
                        __('prompt: %1$s, completion: %2$s', 'xfusion'),
                        number_format_i18n($promptTokens),
                        number_format_i18n($completionTokens)
                    ));
                    ?>)
                </span>
            </p>
        </div>
    </div>
    <div class="xfusion-eval-card__body xfusion-eval-card__body--unified">
        <section class="xfusion-eval-unified-block">
            <h3 class="xfusion-eval-unified-block__title"><?php esc_html_e('COR Insight', 'xfusion'); ?></h3>
            <p class="xfusion-eval-unified-block__text"><?php echo esc_html($corInsight !== '' ? $corInsight : '—'); ?></p>
        </section>

        <section class="xfusion-eval-unified-block">
            <h3 class="xfusion-eval-unified-block__title"><?php esc_html_e('Overall Insight', 'xfusion'); ?></h3>
            <p class="xfusion-eval-unified-block__text"><?php echo esc_html($overallInsight !== '' ? $overallInsight : '—'); ?></p>
        </section>

        <section class="xfusion-eval-unified-block">
            <h3 class="xfusion-eval-unified-block__title"><?php esc_html_e('Recommended Focus Area', 'xfusion'); ?></h3>
            <p class="xfusion-eval-unified-block__text"><?php echo esc_html($recommendedFocus !== '' ? $recommendedFocus : '—'); ?></p>
        </section>

        <?php if ($categoryKeys !== []) : ?>
            <section class="xfusion-eval-unified-performance">
                <h3 class="xfusion-eval-unified-performance__title"><?php esc_html_e('Performance by FUSION Dimension', 'xfusion'); ?></h3>
                <?php foreach ($categoryKeys as $categoryKey) :
                    $block = is_array($performance[$categoryKey] ?? null) ? $performance[$categoryKey] : [];
                    $strength = trim((string) ($block['strength'] ?? ''));
                    $opportunity = trim((string) ($block['opportunity'] ?? $block['weakness'] ?? ''));
                    ?>
                    <div class="xfusion-eval-unified-category">
                        <h4 class="xfusion-eval-unified-category__title"><?php echo esc_html(xfusion_result_evaluation_unified_category_label($categoryKey)); ?></h4>
                        <?php
                        echo xfusion_result_evaluation_render_feedback_sections([
                            'strength' => $strength,
                            'opportunity' => $opportunity,
                        ], false, xfusion_result_evaluation_unified_feedback_sections());
                        ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Resolve insight model slug stored on a record (column → generation_context → default).
 */
function xfusion_result_evaluation_record_insight_model(array $record): string
{
    $model = trim((string) ($record['insight_model'] ?? ''));
    if ($model !== '') {
        return $model;
    }

    $input = is_array($record['evaluation_input'] ?? null) ? $record['evaluation_input'] : [];
    $ctx = is_array($input['generation_context'] ?? null) ? $input['generation_context'] : [];
    $fromCtx = trim((string) ($ctx['model'] ?? $input['model'] ?? ''));

    return $fromCtx !== '' ? $fromCtx : 'gpt-4o-mini';
}

/**
 * Estimated USD cost for a stored record.
 */
function xfusion_result_evaluation_record_cost_usd(array $record): float
{
    $stored = (float) ($record['cost_usd'] ?? 0);
    if ($stored > 0) {
        return $stored;
    }

    $model = xfusion_result_evaluation_record_insight_model($record);

    return function_exists('xfusion_llm_estimate_cost')
        ? xfusion_llm_estimate_cost($model, (int) ($record['prompt_tokens'] ?? 0), (int) ($record['completion_tokens'] ?? 0))
        : xfusion_result_evaluation_estimate_cost((int) ($record['prompt_tokens'] ?? 0), (int) ($record['completion_tokens'] ?? 0))['usd'];
}

/**
 * @return array{input_usd: float, output_usd: float}
 */
function xfusion_result_evaluation_token_pricing(?string $model = null): array
{
    if ($model !== null && $model !== '' && function_exists('xfusion_llm_model_token_pricing')) {
        return xfusion_llm_model_token_pricing($model);
    }

    return ['input_usd' => 0.15, 'output_usd' => 0.60];
}

/**
 * @return array{usd: float}
 */
function xfusion_result_evaluation_estimate_cost(int $promptTokens, int $completionTokens, ?string $model = null): array
{
    $rates = xfusion_result_evaluation_token_pricing($model);
    $usd = ($promptTokens / 1000000) * $rates['input_usd']
        + ($completionTokens / 1000000) * $rates['output_usd'];

    return ['usd' => max(0.0, $usd)];
}

/**
 * @param array{usd: float} $cost
 */
function xfusion_result_evaluation_format_cost_estimate(array $cost): string
{
    $usd = (float) ($cost['usd'] ?? 0);

    if (function_exists('xfusion_llm_format_cost_usd')) {
        return xfusion_llm_format_cost_usd($usd);
    }

    if ($usd <= 0) {
        return '~$0.00';
    }

    $usdDecimals = $usd < 0.01 ? 4 : 2;

    return '~$' . number_format_i18n($usd, $usdDecimals);
}

/**
 * Format stored or estimated cost for a single evaluation record.
 */
function xfusion_result_evaluation_format_record_cost(array $record): string
{
    $usd = xfusion_result_evaluation_record_cost_usd($record);

    return function_exists('xfusion_llm_format_cost_usd')
        ? xfusion_llm_format_cost_usd($usd)
        : xfusion_result_evaluation_format_cost_estimate(['usd' => $usd]);
}

/**
 * @return array{
 *   total_evaluations: int,
 *   unique_users: int,
 *   total_tokens: int,
 *   total_prompt_tokens: int,
 *   total_completion_tokens: int,
 *   estimated_cost: array{usd: float}
 * }
 */
function xfusion_result_evaluation_admin_stats(?string $evaluatedFrom = null, ?string $evaluatedBefore = null): array
{
    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $where = '';
    if ($evaluatedFrom !== null && $evaluatedBefore !== null) {
        $where = $wpdb->prepare(' WHERE evaluated_at >= %s AND evaluated_at < %s', $evaluatedFrom, $evaluatedBefore);
    }

    $row = $wpdb->get_row(
        "SELECT
            COUNT(*) AS total_evaluations,
            COUNT(DISTINCT user_id) AS unique_users,
            COALESCE(SUM(tokens_used), 0) AS total_tokens,
            COALESCE(SUM(prompt_tokens), 0) AS total_prompt_tokens,
            COALESCE(SUM(completion_tokens), 0) AS total_completion_tokens,
            COALESCE(SUM(cost_usd), 0) AS total_cost_usd
        FROM {$table}{$where}",
        ARRAY_A
    );

    if (! is_array($row)) {
        return [
            'total_evaluations' => 0,
            'unique_users' => 0,
            'total_tokens' => 0,
            'total_prompt_tokens' => 0,
            'total_completion_tokens' => 0,
            'estimated_cost' => ['usd' => 0.0],
        ];
    }

    $promptTokens = (int) ($row['total_prompt_tokens'] ?? 0);
    $completionTokens = (int) ($row['total_completion_tokens'] ?? 0);
    $totalCostUsd = (float) ($row['total_cost_usd'] ?? 0);

    return [
        'total_evaluations' => (int) ($row['total_evaluations'] ?? 0),
        'unique_users' => (int) ($row['unique_users'] ?? 0),
        'total_tokens' => (int) ($row['total_tokens'] ?? 0),
        'total_prompt_tokens' => $promptTokens,
        'total_completion_tokens' => $completionTokens,
        'estimated_cost' => [
            'usd' => $totalCostUsd > 0
                ? $totalCostUsd
                : xfusion_result_evaluation_estimate_cost($promptTokens, $completionTokens)['usd'],
        ],
    ];
}

/**
 * @return array{start: string, end: string}
 */
function xfusion_result_evaluation_admin_month_range(int $year, int $month): array
{
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end = $month === 12
        ? sprintf('%04d-01-01 00:00:00', $year + 1)
        : sprintf('%04d-%02d-01 00:00:00', $year, $month + 1);

    return [
        'start' => $start,
        'end' => $end,
    ];
}

/**
 * @return array{
 *   all_time: array<string, mixed>,
 *   this_month: array<string, mixed>,
 *   last_month: array<string, mixed>,
 *   month_label: string,
 *   last_month_label: string
 * }
 */
function xfusion_result_evaluation_admin_stats_bundle(): array
{
    $year = (int) gmdate('Y');
    $month = (int) gmdate('n');
    $thisMonth = xfusion_result_evaluation_admin_month_range($year, $month);

    if ($month === 1) {
        $lastYear = $year - 1;
        $lastMonthNum = 12;
    } else {
        $lastYear = $year;
        $lastMonthNum = $month - 1;
    }
    $lastMonth = xfusion_result_evaluation_admin_month_range($lastYear, $lastMonthNum);

    return [
        'all_time' => xfusion_result_evaluation_admin_stats(),
        'this_month' => xfusion_result_evaluation_admin_stats($thisMonth['start'], $thisMonth['end']),
        'last_month' => xfusion_result_evaluation_admin_stats($lastMonth['start'], $lastMonth['end']),
        'month_label' => gmdate('F Y'),
        'last_month_label' => gmdate('F Y', gmmktime(0, 0, 0, $lastMonthNum, 1, $lastYear)),
    ];
}

/**
 * @param array<string, mixed> $record
 */
function xfusion_result_evaluation_render_admin_details(array $record): string
{
    $userId = (int) ($record['user_id'] ?? 0);
    $employeeName = xfusion_result_evaluation_user_first_name($userId);

    $evaluationInput = $record['evaluation_input'] ?? [];
    $generationContext = is_array($evaluationInput['generation_context'] ?? null)
        ? $evaluationInput['generation_context']
        : [];
    $inputJson = is_array($evaluationInput)
        ? wp_json_encode($evaluationInput, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        : (string) $evaluationInput;

    $evaluationPayload = $record['evaluation'] ?? [];
    $evaluationJson = is_array($evaluationPayload)
        ? wp_json_encode($evaluationPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        : (string) $evaluationPayload;

    $insightModel = xfusion_result_evaluation_record_insight_model($record);
    $modelMeta = function_exists('xfusion_llm_insight_model_meta') ? xfusion_llm_insight_model_meta($insightModel) : null;
    $modelLabel = $modelMeta !== null ? (string) ($modelMeta['label'] ?? $insightModel) : $insightModel;
    $promptVersionLabel = trim((string) ($record['prompt_version_label'] ?? ''));
    $promptVersionId = trim((string) ($record['prompt_version_id'] ?? ''));
    if ($promptVersionLabel === '' && $promptVersionId === '') {
        $promptVersionLabel = trim((string) ($generationContext['prompt_version_label'] ?? ''));
        $promptVersionId = trim((string) ($generationContext['prompt_version_id'] ?? ''));
    }
    $costLabel = xfusion_result_evaluation_format_record_cost($record);

    $rows = [
        __('Record ID', 'xfusion') => (string) ((int) ($record['id'] ?? 0)),
        __('Status', 'xfusion') => xfusion_result_evaluation_status_label((string) ($record['status'] ?? xfusion_result_evaluation_status_published())),
        __('User', 'xfusion') => $employeeName !== '' ? sprintf('%s (#%d)', $employeeName, $userId) : (string) $userId,
        __('Score', 'xfusion') => sprintf('%d/100', (int) ($record['score'] ?? 0)),
        __('Insight model', 'xfusion') => $modelLabel !== '' ? sprintf('%s (%s)', $modelLabel, $insightModel) : '—',
        __('Prompt version', 'xfusion') => ($promptVersionLabel !== '' || $promptVersionId !== '')
            ? trim($promptVersionLabel . ($promptVersionId !== '' ? ' (' . $promptVersionId . ')' : ''))
            : '—',
        __('Estimated cost (USD)', 'xfusion') => $costLabel,
        __('Created at', 'xfusion') => (string) ($record['created_at'] ?? '—'),
        __('Evaluated at', 'xfusion') => (string) ($record['evaluated_at'] ?? '—'),
        __('Inserted at', 'xfusion') => (string) ($record['inserted_at'] ?? '—'),
        __('Company information (post ID)', 'xfusion') => (string) ((int) ($record['company_information'] ?? 0)),
        __('Scoring group ID', 'xfusion') => (string) ((int) ($record['scoring_group_id'] ?? 0)),
        __('Scoring group title', 'xfusion') => (string) ($record['scoring_group_title'] ?? '—'),
        __('Tokens used (total)', 'xfusion') => number_format_i18n((int) ($record['tokens_used'] ?? 0)),
        __('Prompt tokens', 'xfusion') => number_format_i18n((int) ($record['prompt_tokens'] ?? 0)),
        __('Completion tokens', 'xfusion') => number_format_i18n((int) ($record['completion_tokens'] ?? 0)),
    ];

    ob_start();
    ?>
<div class="xfusion-eval-details">
    <h3 class="xfusion-eval-details__title"><?php esc_html_e('Evaluation data (read-only)', 'xfusion'); ?></h3>
    <dl class="xfusion-eval-details__list">
        <?php foreach ($rows as $label => $value) : ?>
            <div class="xfusion-eval-details__row">
                <dt><?php echo esc_html($label); ?></dt>
                <dd><?php echo esc_html($value !== '' ? $value : '—'); ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
    <?php if ($generationContext !== []) : ?>
        <div class="xfusion-eval-details__json">
            <h4 class="xfusion-eval-details__json-title"><?php esc_html_e('Generation context (model, prompt, knowledge)', 'xfusion'); ?></h4>
            <dl class="xfusion-eval-details__list">
                <?php if (! empty($generationContext['model'])) : ?>
                    <div class="xfusion-eval-details__row">
                        <dt><?php esc_html_e('Model', 'xfusion'); ?></dt>
                        <dd><?php echo esc_html((string) $generationContext['model']); ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (isset($generationContext['cost_usd']) && is_numeric($generationContext['cost_usd'])) : ?>
                    <div class="xfusion-eval-details__row">
                        <dt><?php esc_html_e('Cost (USD)', 'xfusion'); ?></dt>
                        <dd><?php echo esc_html(function_exists('xfusion_llm_format_cost_usd') ? xfusion_llm_format_cost_usd((float) $generationContext['cost_usd']) : '$' . number_format((float) $generationContext['cost_usd'], 4)); ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (isset($generationContext['pricing_input_per_1m'], $generationContext['pricing_output_per_1m'])) : ?>
                    <div class="xfusion-eval-details__row">
                        <dt><?php esc_html_e('Pricing (list)', 'xfusion'); ?></dt>
                        <dd><?php
                        echo esc_html(sprintf(
                            /* translators: 1: input USD, 2: output USD */
                            __('$%1$s input / $%2$s output per 1M tokens', 'xfusion'),
                            number_format((float) $generationContext['pricing_input_per_1m'], 2),
                            number_format((float) $generationContext['pricing_output_per_1m'], 2)
                        ));
                        ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (! empty($generationContext['prompt_version_label']) || ! empty($generationContext['prompt_version_id'])) : ?>
                    <div class="xfusion-eval-details__row">
                        <dt><?php esc_html_e('Prompt version', 'xfusion'); ?></dt>
                        <dd><?php echo esc_html(trim((string) ($generationContext['prompt_version_label'] ?? '') . ' (' . (string) ($generationContext['prompt_version_id'] ?? '') . ')')); ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (isset($generationContext['knowledge_chunk_total'])) : ?>
                    <div class="xfusion-eval-details__row">
                        <dt><?php esc_html_e('Knowledge chunks used', 'xfusion'); ?></dt>
                        <dd><?php echo esc_html(number_format_i18n((int) $generationContext['knowledge_chunk_total'])); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
            <?php
            $sources = is_array($generationContext['knowledge_sources'] ?? null) ? $generationContext['knowledge_sources'] : [];
            if ($sources !== []) :
                ?>
                <table class="widefat striped" style="margin-top:10px;">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('WP post ID', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Category', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Chunks', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Updated', 'xfusion'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sources as $source) :
                        if (! is_array($source)) {
                            continue;
                        }
                        $postId = (int) ($source['wordpress_post_id'] ?? 0);
                        ?>
                        <tr>
                            <td><?php echo $postId > 0 ? (int) $postId : '—'; ?></td>
                            <td><?php echo esc_html((string) ($source['category'] ?? '')); ?></td>
                            <td><?php echo esc_html(number_format_i18n((int) ($source['chunk_count'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) ($source['updated_at'] ?? '—')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (is_string($inputJson) && $inputJson !== '') : ?>
        <div class="xfusion-eval-details__json">
            <h4 class="xfusion-eval-details__json-title"><?php esc_html_e('Evaluation input (API request)', 'xfusion'); ?></h4>
            <pre class="xfusion-eval-details__pre"><?php echo esc_html($inputJson); ?></pre>
        </div>
    <?php endif; ?>
    <?php if (is_string($evaluationJson) && $evaluationJson !== '' && $evaluationJson !== '[]' && $evaluationJson !== '{}') : ?>
        <div class="xfusion-eval-details__json">
            <h4 class="xfusion-eval-details__json-title"><?php esc_html_e('Evaluation output (API response)', 'xfusion'); ?></h4>
            <pre class="xfusion-eval-details__pre"><?php echo esc_html($evaluationJson); ?></pre>
        </div>
    <?php endif; ?>
</div>
    <?php

    return (string) ob_get_clean();
}

function xfusion_result_evaluation_admin_card_css(): string
{
    return xfusion_result_evaluation_feedback_sections_css() . <<<'CSS'
.xfusion-result-eval-admin-wrap{margin:12px 0 20px;max-width:960px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card{box-sizing:border-box;border:1px solid #c3c4c7;border-radius:8px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;line-height:1.5;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__header{display:flex;gap:16px;align-items:center;padding:20px 20px 16px;background:var(--xf-eval-bg,#f6f7f7);border-bottom:1px solid #dcdcde;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__score-ring{flex-shrink:0;width:80px;height:80px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#fff;border:4px solid var(--xf-eval-accent,#646970);box-shadow:0 2px 6px rgba(0,0,0,.06);}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__score-value{font-size:26px;font-weight:700;color:var(--xf-eval-accent,#1d2327);line-height:1;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__score-max{font-size:11px;font-weight:600;color:#646970;margin-top:2px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__meta{min-width:0;flex:1;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__title{margin:0 0 6px;font-size:18px;font-weight:600;color:#1d2327;line-height:1.3;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__badge{display:inline-block;margin:0 0 6px;padding:3px 10px;font-size:12px;font-weight:600;border-radius:999px;background:var(--xf-eval-accent,#646970);color:#fff;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__date{margin:0;font-size:12px;color:#646970;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__tokens{margin:4px 0 0;font-size:12px;color:#646970;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__tokens-detail{color:#a7aaad;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__ref{margin-left:6px;color:#a7aaad;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__body{padding:16px 20px 20px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-feedback-rows{gap:20px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-feedback-row__label{font-size:13px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-feedback-row__text{font-size:14px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__body--unified{display:flex;flex-direction:column;gap:20px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-block{margin:0;padding:0 0 16px;border-bottom:1px solid #dcdcde;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-block:last-child{border-bottom:none;padding-bottom:0;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-block__title{margin:0 0 8px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1d2327;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-block__text{margin:0;font-size:14px;line-height:1.55;color:#374151;white-space:pre-wrap;word-break:break-word;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-performance{margin:0;padding:0;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-performance__title{margin:0 0 14px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1d2327;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-category{margin:0 0 18px;padding:14px 16px;border:1px solid #dcdcde;border-radius:8px;background:#f9fafb;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-category:last-child{margin-bottom:0;}
.xfusion-result-eval-admin-wrap .xfusion-eval-unified-category__title{margin:0 0 12px;font-size:15px;font-weight:700;color:#1e3a5f;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details{margin-top:16px;padding:16px 20px;border:1px solid #c3c4c7;border-radius:8px;background:#fff;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__title{margin:0 0 12px;font-size:14px;font-weight:600;color:#1d2327;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__list{margin:0;display:grid;grid-template-columns:minmax(160px,220px) 1fr;gap:8px 16px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__row{display:contents;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__list dt{margin:0;font-size:12px;font-weight:600;color:#646970;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__list dd{margin:0;font-size:13px;color:#1d2327;word-break:break-word;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__json{margin-top:16px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__json-title{margin:0 0 8px;font-size:12px;font-weight:600;color:#646970;text-transform:uppercase;letter-spacing:.04em;}
.xfusion-result-eval-admin-wrap .xfusion-eval-details__pre{margin:0;padding:12px;max-height:320px;overflow:auto;font-size:12px;line-height:1.45;background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;white-space:pre-wrap;word-break:break-word;color:#1d2327;}
.xfusion-result-eval-list{margin-top:12px;}
.xfusion-result-eval-list .column-score{width:72px;}
.xfusion-result-eval-list .column-tokens{width:88px;}
.xfusion-result-eval-stats{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0 14px;}
.xfusion-result-eval-stats__card{flex:1 1 120px;max-width:168px;margin:0;padding:8px 10px;border:1px solid #c3c4c7;border-radius:6px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04);}
.xfusion-result-eval-stats__label{margin:0 0 2px;font-size:10px;font-weight:600;color:#646970;text-transform:uppercase;letter-spacing:.03em;line-height:1.25;}
.xfusion-result-eval-stats__value{margin:0;font-size:16px;font-weight:700;color:#1d2327;line-height:1.2;}
.xfusion-result-eval-stats__sub{margin:4px 0 0;font-size:10px;color:#646970;line-height:1.35;}
.xfusion-result-eval-stats__cost{margin:4px 0 0;font-size:10px;font-weight:600;color:#1d2327;}
.xfusion-result-eval-stats__link{display:inline-block;margin-top:5px;font-size:10px;font-weight:600;text-decoration:none;}
.xfusion-result-eval-stats__card--month{border-color:#2271b1;background:#f0f6fc;}
.xfusion-result-eval-stats__card--last-month{border-color:#646970;background:#f6f7f7;}
.xfusion-result-eval-stats__card--ai-notify{flex:1 1 140px;max-width:200px;border-color:#00a32a;background:#edfaef;}
.xfusion-result-eval-pricing-note{margin:0 0 10px;font-size:11px;color:#646970;}
.xfusion-result-eval-ai-notify-detail{margin-top:12px;}
.xfusion-result-eval-ai-notify-detail .column-user{min-width:160px;}
CSS;
}

add_action('admin_enqueue_scripts', 'xfusion_result_evaluation_admin_enqueue_styles');

function xfusion_result_evaluation_admin_enqueue_styles(string $hook): void
{
    if ($hook !== 'toplevel_page_xfusion-result-evaluations') {
        return;
    }

    wp_register_style('xfusion-result-eval-admin', false, [], '1.4');
    wp_enqueue_style('xfusion-result-eval-admin');
    wp_add_inline_style('xfusion-result-eval-admin', xfusion_result_evaluation_admin_card_css());
}

add_action('admin_init', 'xfusion_result_evaluation_admin_handle_status_update');

function xfusion_result_evaluation_admin_handle_status_update(): void
{
    if (! is_admin() || ! current_user_can('edit_posts')) {
        return;
    }

    if (! isset($_POST['xfusion_eval_status_update'], $_POST['record_id'], $_POST['xfusion_eval_status'])) {
        return;
    }

    if (! isset($_GET['page']) || sanitize_key((string) $_GET['page']) !== 'xfusion-result-evaluations') {
        return;
    }

    check_admin_referer('xfusion_eval_status_update');

    $recordId = absint((string) $_POST['record_id']);
    $status = sanitize_key((string) $_POST['xfusion_eval_status']);

    if ($recordId > 0) {
        xfusion_result_evaluation_update_status($recordId, $status);
    }

    wp_safe_redirect(xfusion_result_evaluation_admin_url($recordId));
    exit;
}

add_action('admin_menu', 'xfusion_result_evaluation_register_admin_menu');

function xfusion_result_evaluation_register_admin_menu(): void
{
    add_menu_page(
        __('Result Evaluations', 'xfusion'),
        __('Result Evaluations', 'xfusion'),
        'edit_posts',
        'xfusion-result-evaluations',
        'xfusion_result_evaluation_admin_page',
        'dashicons-awards',
        27
    );
}

function xfusion_result_evaluation_admin_page(): void
{
    if (! current_user_can('edit_posts')) {
        wp_die(esc_html__('You do not have permission to view this page.', 'xfusion'));
    }

    $recordId = isset($_GET['id']) ? absint((string) $_GET['id']) : 0;
    $view = isset($_GET['view']) ? sanitize_key((string) $_GET['view']) : '';

    echo '<div class="wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

    if ($recordId > 0) {
        xfusion_result_evaluation_admin_detail_page($recordId);
        echo '</div>';

        return;
    }

    if ($view === 'ai-notify') {
        xfusion_result_evaluation_admin_ai_notify_page();
        echo '</div>';

        return;
    }

    xfusion_result_evaluation_admin_list_page();
    echo '</div>';
}

function xfusion_result_evaluation_admin_render_stats(): void
{
    $bundle = xfusion_result_evaluation_admin_stats_bundle();
    $stats = $bundle['all_time'];
    $monthStats = $bundle['this_month'];
    $lastMonthStats = $bundle['last_month'];

    echo '<p class="xfusion-result-eval-pricing-note">';
    esc_html_e('Cost totals sum stored per-evaluation costs (model-specific list pricing). Legacy rows without stored cost fall back to gpt-4o-mini rates.', 'xfusion');
    echo '</p>';

    $aiNotifyCount = xfusion_result_evaluation_ai_notify_count();
    $aiNotifyUrl = xfusion_result_evaluation_admin_url(0, 'ai-notify');

    echo '<div class="xfusion-result-eval-stats" role="region" aria-label="' . esc_attr__('Evaluation statistics', 'xfusion') . '">';

    echo '<div class="xfusion-result-eval-stats__card xfusion-result-eval-stats__card--ai-notify">';
    echo '<p class="xfusion-result-eval-stats__label">' . esc_html__('AI Notify', 'xfusion') . '</p>';
    echo '<p class="xfusion-result-eval-stats__value">' . esc_html(number_format_i18n($aiNotifyCount)) . '</p>';
    echo '<p class="xfusion-result-eval-stats__sub">' . esc_html__('Users who read and confirmed', 'xfusion') . '</p>';
    echo '<a class="xfusion-result-eval-stats__link" href="' . esc_url($aiNotifyUrl) . '">';
    esc_html_e('View details', 'xfusion');
    echo '</a>';
    echo '</div>';

    echo '<div class="xfusion-result-eval-stats__card">';
    echo '<p class="xfusion-result-eval-stats__label">' . esc_html__('Users evaluated', 'xfusion') . '</p>';
    echo '<p class="xfusion-result-eval-stats__value">' . esc_html(number_format_i18n($stats['unique_users'])) . '</p>';
    echo '<p class="xfusion-result-eval-stats__sub">' . esc_html__('Distinct users with at least one evaluation', 'xfusion') . '</p>';
    echo '</div>';

    echo '<div class="xfusion-result-eval-stats__card">';
    echo '<p class="xfusion-result-eval-stats__label">' . esc_html__('Total evaluations', 'xfusion') . '</p>';
    echo '<p class="xfusion-result-eval-stats__value">' . esc_html(number_format_i18n($stats['total_evaluations'])) . '</p>';
    echo '</div>';

    echo '<div class="xfusion-result-eval-stats__card">';
    echo '<p class="xfusion-result-eval-stats__label">' . esc_html__('Total token use', 'xfusion') . '</p>';
    echo '<p class="xfusion-result-eval-stats__value">' . esc_html(number_format_i18n($stats['total_tokens'])) . '</p>';
    echo '<p class="xfusion-result-eval-stats__sub">' . esc_html(sprintf(
        /* translators: 1: prompt tokens, 2: completion tokens */
        __('Prompt: %1$s · Completion: %2$s', 'xfusion'),
        number_format_i18n($stats['total_prompt_tokens']),
        number_format_i18n($stats['total_completion_tokens'])
    )) . '</p>';
    echo '<p class="xfusion-result-eval-stats__cost">' . esc_html(sprintf(
        /* translators: %s: formatted cost estimate */
        __('Est. cost: %s', 'xfusion'),
        xfusion_result_evaluation_format_cost_estimate($stats['estimated_cost'])
    )) . '</p>';
    echo '</div>';

    echo '<div class="xfusion-result-eval-stats__card xfusion-result-eval-stats__card--month">';
    echo '<p class="xfusion-result-eval-stats__label">' . esc_html(sprintf(
        /* translators: %s: month and year, e.g. June 2026 */
        __('Use this month (%s)', 'xfusion'),
        $bundle['month_label']
    )) . '</p>';
    echo '<p class="xfusion-result-eval-stats__value">' . esc_html(number_format_i18n($monthStats['total_tokens'])) . '</p>';
    echo '<p class="xfusion-result-eval-stats__sub">' . esc_html(sprintf(
        /* translators: 1: prompt tokens, 2: completion tokens, 3: evaluation count */
        __('Prompt: %1$s · Completion: %2$s · %3$s evaluations', 'xfusion'),
        number_format_i18n($monthStats['total_prompt_tokens']),
        number_format_i18n($monthStats['total_completion_tokens']),
        number_format_i18n($monthStats['total_evaluations'])
    )) . '</p>';
    echo '<p class="xfusion-result-eval-stats__cost">' . esc_html(sprintf(
        /* translators: %s: formatted cost estimate */
        __('Est. cost this month: %s', 'xfusion'),
        xfusion_result_evaluation_format_cost_estimate($monthStats['estimated_cost'])
    )) . '</p>';
    echo '</div>';

    echo '<div class="xfusion-result-eval-stats__card xfusion-result-eval-stats__card--last-month">';
    echo '<p class="xfusion-result-eval-stats__label">' . esc_html(sprintf(
        /* translators: %s: month and year, e.g. May 2026 */
        __('Use last month (%s)', 'xfusion'),
        $bundle['last_month_label']
    )) . '</p>';
    echo '<p class="xfusion-result-eval-stats__value">' . esc_html(number_format_i18n($lastMonthStats['total_tokens'])) . '</p>';
    echo '<p class="xfusion-result-eval-stats__sub">' . esc_html(sprintf(
        /* translators: 1: prompt tokens, 2: completion tokens, 3: evaluation count */
        __('Prompt: %1$s · Completion: %2$s · %3$s evaluations', 'xfusion'),
        number_format_i18n($lastMonthStats['total_prompt_tokens']),
        number_format_i18n($lastMonthStats['total_completion_tokens']),
        number_format_i18n($lastMonthStats['total_evaluations'])
    )) . '</p>';
    echo '<p class="xfusion-result-eval-stats__cost">' . esc_html(sprintf(
        /* translators: %s: formatted cost estimate */
        __('Est. cost last month: %s', 'xfusion'),
        xfusion_result_evaluation_format_cost_estimate($lastMonthStats['estimated_cost'])
    )) . '</p>';
    echo '</div>';

    echo '</div>';
}

function xfusion_result_evaluation_admin_ai_notify_page(): void
{
    $perPage = 50;
    $page = isset($_GET['paged']) ? max(1, absint((string) $_GET['paged'])) : 1;
    $offset = ($page - 1) * $perPage;
    $total = xfusion_result_evaluation_ai_notify_count();

    $users = function_exists('xfusion_once_popup_dismissed_users')
        ? xfusion_once_popup_dismissed_users($perPage, $offset)
        : [];

    $popupTitle = '';
    if (function_exists('xfusion_once_popup_get_settings')) {
        $popupSettings = xfusion_once_popup_get_settings();
        $popupTitle = trim((string) ($popupSettings['title'] ?? ''));
    }

    echo '<p><a href="' . esc_url(xfusion_result_evaluation_admin_url()) . '">&larr; ';
    esc_html_e('Back to Result Evaluations', 'xfusion');
    echo '</a></p>';

    echo '<div class="xfusion-result-eval-ai-notify-detail">';
    echo '<h2>' . esc_html__('AI Notify — confirmed readers', 'xfusion') . '</h2>';
    echo '<p class="description">';
    esc_html_e('Users who closed the AI Notify popup (agreed / acknowledged). Stored in user meta.', 'xfusion');
    if ($popupTitle !== '') {
        echo ' ';
        echo esc_html(sprintf(
            /* translators: %s: popup title from settings */
            __('Current popup title: "%s".', 'xfusion'),
            $popupTitle
        ));
    }
    echo '</p>';

    if ($users === []) {
        echo '<p>' . esc_html__('No users have confirmed the AI Notify popup yet.', 'xfusion') . '</p>';
        echo '</div>';

        return;
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th class="column-user">' . esc_html__('User', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Email', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('User ID', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Confirmed at (UTC)', 'xfusion') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($users as $user) {
        $userId = (int) ($user['user_id'] ?? 0);
        $firstName = trim((string) ($user['first_name'] ?? ''));
        $displayName = trim((string) ($user['display_name'] ?? ''));
        $userLabel = $firstName !== '' ? $firstName : ($displayName !== '' ? $displayName : sprintf(__('User %d', 'xfusion'), $userId));
        $editUrl = get_edit_user_link($userId);

        echo '<tr>';
        echo '<td>';
        if (is_string($editUrl) && $editUrl !== '') {
            echo '<a href="' . esc_url($editUrl) . '">' . esc_html($userLabel) . '</a>';
        } else {
            echo esc_html($userLabel);
        }
        echo '</td>';
        echo '<td>' . esc_html((string) ($user['user_email'] ?? '—')) . '</td>';
        echo '<td>' . (int) $userId . '</td>';
        echo '<td>' . esc_html((string) ($user['dismissed_at'] ?? '—')) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links([
            'base' => add_query_arg(['view' => 'ai-notify', 'paged' => '%#%']),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $totalPages,
            'current' => $page,
        ]);
        echo '</div></div>';
    }

    echo '</div>';
}

function xfusion_result_evaluation_admin_list_page(): void
{
    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $perPage = 20;
    $page = isset($_GET['paged']) ? max(1, absint((string) $_GET['paged'])) : 1;
    $offset = ($page - 1) * $perPage;

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, user_id, scoring_group_title, score, tokens_used, prompt_tokens, completion_tokens, insight_model, cost_usd, evaluated_at, status FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
            $perPage,
            $offset
        )
    );

    echo '<p class="description">';
    esc_html_e('Evaluation results are stored in a dedicated database table (not WordPress posts). Data is read-only.', 'xfusion');
    echo '</p>';

    xfusion_result_evaluation_admin_render_stats();

    if ($rows === []) {
        echo '<p>' . esc_html__('No evaluations yet. Run Send Evaluation on the front end to create a record.', 'xfusion') . '</p>';

        return;
    }

    echo '<table class="widefat striped xfusion-result-eval-list">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('ID', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('User', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Scoring group', 'xfusion') . '</th>';
    echo '<th class="column-score">' . esc_html__('Score', 'xfusion') . '</th>';
    echo '<th class="column-tokens">' . esc_html__('Tokens', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Model', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Est. cost', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Status', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Evaluated at', 'xfusion') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $userLabel = xfusion_result_evaluation_user_first_name((int) $row->user_id);
        if ($userLabel === '') {
            $userLabel = '#' . (int) $row->user_id;
        }
        $viewUrl = xfusion_result_evaluation_admin_url((int) $row->id);

        echo '<tr>';
        echo '<td><a href="' . esc_url($viewUrl) . '">#' . (int) $row->id . '</a></td>';
        echo '<td>' . esc_html($userLabel) . '</td>';
        echo '<td>' . esc_html((string) $row->scoring_group_title) . '</td>';
        echo '<td>' . (int) $row->score . '/100</td>';
        echo '<td>' . esc_html(number_format_i18n((int) $row->tokens_used)) . '</td>';
        $listModel = trim((string) ($row->insight_model ?? ''));
        if ($listModel === '') {
            $listModel = 'gpt-4o-mini';
        }
        $listMeta = function_exists('xfusion_llm_insight_model_meta') ? xfusion_llm_insight_model_meta($listModel) : null;
        $listModelLabel = $listMeta !== null ? (string) ($listMeta['label'] ?? $listModel) : $listModel;
        echo '<td><code>' . esc_html($listModel) . '</code><br/><span class="description">' . esc_html($listModelLabel) . '</span></td>';
        $listCostUsd = (float) ($row->cost_usd ?? 0);
        if ($listCostUsd <= 0 && function_exists('xfusion_llm_estimate_cost')) {
            $listCostUsd = xfusion_llm_estimate_cost($listModel, (int) ($row->prompt_tokens ?? 0), (int) ($row->completion_tokens ?? 0));
        }
        echo '<td>' . esc_html(function_exists('xfusion_llm_format_cost_usd') ? xfusion_llm_format_cost_usd($listCostUsd) : xfusion_result_evaluation_format_cost_estimate(['usd' => $listCostUsd])) . '</td>';
        $status = function_exists('xfusion_result_evaluation_normalize_status')
            ? xfusion_result_evaluation_normalize_status((string) ($row->status ?? 'published'))
            : (string) ($row->status ?? 'published');
        echo '<td>' . esc_html(function_exists('xfusion_result_evaluation_status_label')
            ? xfusion_result_evaluation_status_label($status)
            : ucfirst($status)) . '</td>';
        echo '<td>' . esc_html((string) $row->evaluated_at) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $totalPages,
            'current' => $page,
        ]);
        echo '</div></div>';
    }
}

function xfusion_result_evaluation_admin_detail_page(int $recordId): void
{
    $row = xfusion_result_evaluation_get($recordId);

    echo '<p><a href="' . esc_url(xfusion_result_evaluation_admin_url()) . '">&larr; ';
    esc_html_e('Back to list', 'xfusion');
    echo '</a></p>';

    echo '<div class="xfusion-result-eval-admin-wrap">';

    if ($row === null) {
        echo '<div class="notice notice-error inline"><p>';
        esc_html_e('Evaluation record not found.', 'xfusion');
        echo '</p></div></div>';

        return;
    }

    $record = xfusion_result_evaluation_row_to_record($row);
    $evaluation = is_array($record['evaluation']) ? $record['evaluation'] : [];

    if ($evaluation === []) {
        echo '<div class="notice notice-info inline"><p>';
        esc_html_e('No evaluation payload on this record. Metadata and token usage are shown below.', 'xfusion');
        echo '</p></div>';
    }

    if (xfusion_result_evaluation_is_unified_record($record)) {
        echo xfusion_result_evaluation_render_unified_admin_card($record);
    } else {
        $feedback = xfusion_result_evaluation_extract_feedback($evaluation);

        echo xfusion_result_evaluation_render_card([
            'score' => (int) ($evaluation['score'] ?? $record['score']),
            'strengths' => $feedback['strengths'],
            'improvements' => $feedback['improvements'],
            'evaluator_notes' => $feedback['evaluator_notes'],
            'group_title' => $record['scoring_group_title'],
            'evaluated_at' => $record['evaluated_at'],
            'id' => $record['id'],
            'tokens_used' => $record['tokens_used'],
            'prompt_tokens' => $record['prompt_tokens'],
            'completion_tokens' => $record['completion_tokens'],
        ]);
    }

    $currentStatus = xfusion_result_evaluation_normalize_status((string) ($record['status'] ?? xfusion_result_evaluation_status_published()));
    ?>
    <form method="post" action="" class="xfusion-eval-status-form" style="margin:16px 0;padding:12px 16px;border:1px solid #c3c4c7;border-radius:8px;background:#fff;max-width:960px;">
        <?php wp_nonce_field('xfusion_eval_status_update'); ?>
        <input type="hidden" name="record_id" value="<?php echo (int) $record['id']; ?>"/>
        <p style="margin:0 0 8px;"><strong><?php esc_html_e('Insight status', 'xfusion'); ?></strong></p>
        <p class="description" style="margin:0 0 10px;"><?php esc_html_e('Draft: hidden from dashboard, no cooldown. Sandbox: visible on dashboard, user can generate again without cooldown. Published: visible, cooldown applies.', 'xfusion'); ?></p>
        <label>
            <select name="xfusion_eval_status">
                <option value="<?php echo esc_attr(xfusion_result_evaluation_status_draft()); ?>" <?php selected($currentStatus, xfusion_result_evaluation_status_draft()); ?>><?php esc_html_e('Draft', 'xfusion'); ?></option>
                <option value="<?php echo esc_attr(xfusion_result_evaluation_status_sandbox()); ?>" <?php selected($currentStatus, xfusion_result_evaluation_status_sandbox()); ?>><?php esc_html_e('Sandbox', 'xfusion'); ?></option>
                <option value="<?php echo esc_attr(xfusion_result_evaluation_status_published()); ?>" <?php selected($currentStatus, xfusion_result_evaluation_status_published()); ?>><?php esc_html_e('Published', 'xfusion'); ?></option>
            </select>
        </label>
        <?php submit_button(__('Update status', 'xfusion'), 'secondary', 'xfusion_eval_status_update', false); ?>
    </form>
    <?php

    echo xfusion_result_evaluation_render_admin_details($record);
    echo '</div>';
}
