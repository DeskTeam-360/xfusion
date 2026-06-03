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

const XFUSION_RESULT_EVAL_DB_VERSION = '1.1';

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
        inserted_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_group (user_id, scoring_group_id),
        KEY evaluated_at (evaluated_at)
    ) {$charset};";

    dbDelta($sql);
    update_option('xfusion_result_evaluations_db_version', XFUSION_RESULT_EVAL_DB_VERSION);
}

add_action('plugins_loaded', 'xfusion_result_evaluation_maybe_create_table', 5);

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
        'strengths' => (string) ($evaluation['strengths'] ?? ''),
        'improvements' => (string) ($evaluation['improvements'] ?? ''),
        'evaluator_notes' => (string) ($evaluation['evaluator_notes'] ?? ''),
    ];
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
    $score = isset($eval['score']) ? (int) $eval['score'] : 0;
    $createdAt = isset($apiData['created_at']) ? (string) $apiData['created_at'] : gmdate('c');
    $evaluatedAt = isset($apiData['evaluated_at']) ? (string) $apiData['evaluated_at'] : gmdate('c');
    $companyInfo = isset($apiData['company_information']) ? (int) $apiData['company_information'] : 0;
    $tokenUsage = xfusion_result_evaluation_parse_token_usage($apiData);

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
            'inserted_at' => $now,
        ],
        ['%d', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s']
    );

    if ($inserted === false) {
        return 0;
    }

    return (int) $wpdb->insert_id;
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

function xfusion_result_evaluation_admin_url(int $id = 0): string
{
    $args = ['page' => 'xfusion-result-evaluations'];
    if ($id > 0) {
        $args['id'] = $id;
    }

    return add_query_arg($args, admin_url('admin.php'));
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
        <section class="xfusion-eval-card__section xfusion-eval-card__section--strengths">
            <h3 class="xfusion-eval-card__section-title"><?php esc_html_e('Strengths', 'xfusion'); ?></h3>
            <p class="xfusion-eval-card__section-text"><?php echo esc_html($strengths !== '' ? $strengths : '—'); ?></p>
        </section>
        <section class="xfusion-eval-card__section xfusion-eval-card__section--improvements">
            <h3 class="xfusion-eval-card__section-title"><?php esc_html_e('Improvements', 'xfusion'); ?></h3>
            <p class="xfusion-eval-card__section-text"><?php echo esc_html($improvements !== '' ? $improvements : '—'); ?></p>
        </section>
        <section class="xfusion-eval-card__section xfusion-eval-card__section--notes">
            <h3 class="xfusion-eval-card__section-title"><?php esc_html_e('Evaluator notes', 'xfusion'); ?></h3>
            <p class="xfusion-eval-card__section-text"><?php echo esc_html($notes !== '' ? $notes : '—'); ?></p>
        </section>
    </div>
</div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Feedback-only card for the frontend shortcode (Strengths, Improvements, Evaluator notes — no score).
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
        <div class="xfusion-eval-card__section xfusion-eval-card__section--strengths">
            <div class="xfusion-eval-card__section-title"><?php esc_html_e('Strengths', 'xfusion'); ?></div>
            <div class="xfusion-eval-card__section-text"><?php echo esc_html($feedback['strengths'] !== '' ? $feedback['strengths'] : '—'); ?></div>
        </div>
        <div class="xfusion-eval-card__section xfusion-eval-card__section--improvements">
            <div class="xfusion-eval-card__section-title"><?php esc_html_e('Improvements', 'xfusion'); ?></div>
            <div class="xfusion-eval-card__section-text"><?php echo esc_html($feedback['improvements'] !== '' ? $feedback['improvements'] : '—'); ?></div>
        </div>
        <div class="xfusion-eval-card__section xfusion-eval-card__section--notes">
            <div class="xfusion-eval-card__section-title"><?php esc_html_e('Evaluator notes', 'xfusion'); ?></div>
            <div class="xfusion-eval-card__section-text"><?php echo esc_html($feedback['evaluator_notes'] !== '' ? $feedback['evaluator_notes'] : '—'); ?></div>
        </div>
    </div>
</div>
    <?php

    return (string) ob_get_clean();
}

/**
 * gpt-4o-mini list pricing (per 1M tokens) — used for admin cost estimates only.
 *
 * @return array{input_usd: float, output_usd: float}
 */
function xfusion_result_evaluation_token_pricing(): array
{
    return [
        'input_usd' => 0.15,
        'output_usd' => 0.60,
    ];
}

/**
 * @return array{usd: float}
 */
function xfusion_result_evaluation_estimate_cost(int $promptTokens, int $completionTokens): array
{
    $rates = xfusion_result_evaluation_token_pricing();

    $usd = ($promptTokens / 1000000) * $rates['input_usd']
        + ($completionTokens / 1000000) * $rates['output_usd'];

    return [
        'usd' => max(0.0, $usd),
    ];
}

/**
 * @param array{usd: float} $cost
 */
function xfusion_result_evaluation_format_cost_estimate(array $cost): string
{
    $usd = (float) ($cost['usd'] ?? 0);

    if ($usd <= 0) {
        return '~$0.00';
    }

    $usdDecimals = $usd < 0.01 ? 4 : 2;

    return '~$' . number_format_i18n($usd, $usdDecimals);
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
            COALESCE(SUM(completion_tokens), 0) AS total_completion_tokens
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

    return [
        'total_evaluations' => (int) ($row['total_evaluations'] ?? 0),
        'unique_users' => (int) ($row['unique_users'] ?? 0),
        'total_tokens' => (int) ($row['total_tokens'] ?? 0),
        'total_prompt_tokens' => $promptTokens,
        'total_completion_tokens' => $completionTokens,
        'estimated_cost' => xfusion_result_evaluation_estimate_cost($promptTokens, $completionTokens),
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
    $inputJson = is_array($evaluationInput)
        ? wp_json_encode($evaluationInput, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        : (string) $evaluationInput;

    $evaluationPayload = $record['evaluation'] ?? [];
    $evaluationJson = is_array($evaluationPayload)
        ? wp_json_encode($evaluationPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        : (string) $evaluationPayload;

    $rows = [
        __('Record ID', 'xfusion') => (string) ((int) ($record['id'] ?? 0)),
        __('User', 'xfusion') => $employeeName !== '' ? sprintf('%s (#%d)', $employeeName, $userId) : (string) $userId,
        __('Score', 'xfusion') => sprintf('%d/100', (int) ($record['score'] ?? 0)),
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
    return <<<'CSS'
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
.xfusion-result-eval-admin-wrap .xfusion-eval-card__body{padding:4px 20px 20px;display:flex;flex-direction:column;gap:12px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section{margin:0;padding:12px 14px;border-radius:6px;background:#f6f7f7;border-left:4px solid #c3c4c7;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section--strengths{border-left-color:#00a32a;background:#edfaef;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section--improvements{border-left-color:#dba617;background:#fcf9e8;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section--notes{border-left-color:#2271b1;background:#f0f6fc;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section-title{margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1d2327;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section-text{margin:0;font-size:13px;color:#1d2327;white-space:pre-wrap;}
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
.xfusion-result-eval-stats{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0 20px;}
.xfusion-result-eval-stats__card{flex:1 1 180px;max-width:280px;margin:0;padding:14px 16px;border:1px solid #c3c4c7;border-radius:8px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04);}
.xfusion-result-eval-stats__label{margin:0 0 4px;font-size:12px;font-weight:600;color:#646970;text-transform:uppercase;letter-spacing:.03em;}
.xfusion-result-eval-stats__value{margin:0;font-size:22px;font-weight:700;color:#1d2327;line-height:1.2;}
.xfusion-result-eval-stats__sub{margin:6px 0 0;font-size:12px;color:#646970;}
.xfusion-result-eval-stats__cost{margin:8px 0 0;font-size:13px;font-weight:600;color:#1d2327;}
.xfusion-result-eval-stats__card--month{border-color:#2271b1;background:#f0f6fc;}
.xfusion-result-eval-stats__card--last-month{border-color:#646970;background:#f6f7f7;}
.xfusion-result-eval-pricing-note{margin:0 0 16px;font-size:12px;color:#646970;}
CSS;
}

add_action('admin_enqueue_scripts', 'xfusion_result_evaluation_admin_enqueue_styles');

function xfusion_result_evaluation_admin_enqueue_styles(string $hook): void
{
    if ($hook !== 'toplevel_page_xfusion-result-evaluations') {
        return;
    }

    wp_register_style('xfusion-result-eval-admin', false, [], '1.1');
    wp_enqueue_style('xfusion-result-eval-admin');
    wp_add_inline_style('xfusion-result-eval-admin', xfusion_result_evaluation_admin_card_css());
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

    echo '<div class="wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

    if ($recordId > 0) {
        xfusion_result_evaluation_admin_detail_page($recordId);
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
    $rates = xfusion_result_evaluation_token_pricing();

    echo '<p class="xfusion-result-eval-pricing-note">';
    echo esc_html(sprintf(
        /* translators: 1: input USD, 2: output USD */
        __('Cost estimates use gpt-4o-mini list pricing: $%1$s per 1M input tokens and $%2$s per 1M output tokens.', 'xfusion'),
        number_format_i18n($rates['input_usd'], 2),
        number_format_i18n($rates['output_usd'], 2)
    ));
    echo '</p>';

    echo '<div class="xfusion-result-eval-stats" role="region" aria-label="' . esc_attr__('Evaluation statistics', 'xfusion') . '">';

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
            "SELECT id, user_id, scoring_group_title, score, tokens_used, evaluated_at FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
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
    $feedback = xfusion_result_evaluation_extract_feedback($evaluation);

    if ($evaluation === []) {
        echo '<div class="notice notice-info inline"><p>';
        esc_html_e('No evaluation payload on this record. Metadata and token usage are shown below.', 'xfusion');
        echo '</p></div>';
    }

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

    echo xfusion_result_evaluation_render_admin_details($record);
    echo '</div>';
}
