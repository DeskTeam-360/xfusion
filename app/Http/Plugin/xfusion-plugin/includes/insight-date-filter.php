<?php
/**
 * Date filter for stored insight / evaluation snapshots.
 *
 * Usage:
 *   [xfusion_insight_date_filter]  — static "Last Evaluation" + date from latest unified insight
 *   [xfusion_insight_date_filter user_id="89"]  (admin only)
 *
 * Optional URL filter ?xf_eval_date=YYYY-MM-DD still supported by resolve helpers (no UI).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_INSIGHT_DATE_QUERY_VAR = 'xf_eval_date';

/**
 * Active filter date (Y-m-d) from the current request, or null for latest/live data.
 */
function xfusion_insight_date_filter_get_active(): ?string
{
    if (! isset($_GET[XFUSION_INSIGHT_DATE_QUERY_VAR])) {
        return null;
    }

    $raw = sanitize_text_field(wp_unslash((string) $_GET[XFUSION_INSIGHT_DATE_QUERY_VAR]));
    if ($raw === '' || strtolower($raw) === 'latest') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
    if ($dt === false || $dt->format('Y-m-d') !== $raw) {
        return null;
    }

    return $raw;
}

function xfusion_insight_date_filter_is_active(): bool
{
    return xfusion_insight_date_filter_get_active() !== null;
}

/**
 * GF scoring period: latest entries created before the end of the selected day.
 *
 * @return array{start: string, end: string}|null
 */
function xfusion_insight_date_filter_score_period(?string $dateYmd = null): ?array
{
    if ($dateYmd === null) {
        $dateYmd = xfusion_insight_date_filter_get_active();
    }

    if ($dateYmd === null || $dateYmd === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd, wp_timezone());
    if ($dt === false) {
        return null;
    }

    return [
        'start' => '1970-01-01 00:00:00',
        'end' => $dt->modify('+1 day')->format('Y-m-d 00:00:00'),
    ];
}

/**
 * Unified insight group id used for date filter options.
 */
function xfusion_insight_date_filter_unified_group_id(): int
{
    return defined('XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID')
        ? (int) XFUSION_RESULT_EVAL_UNIFIED_GROUP_ID
        : 0;
}

/**
 * Distinct Generate Insights dates for a user (unified rows only).
 *
 * @return list<string> Y-m-d strings, newest first
 */
function xfusion_insight_date_filter_dates_for_user(int $userId): array
{
    if ($userId < 1 || ! function_exists('xfusion_result_evaluation_table_name')) {
        return [];
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $visibleStatuses = function_exists('xfusion_result_evaluation_user_visible_statuses')
        ? xfusion_result_evaluation_user_visible_statuses()
        : ['published', 'sandbox'];
    $placeholders = implode(', ', array_fill(0, count($visibleStatuses), '%s'));
    $rows = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT DATE(evaluated_at) AS eval_day
            FROM {$table}
            WHERE user_id = %d AND scoring_group_id = %d AND status IN ({$placeholders})
            ORDER BY eval_day DESC",
            ...array_merge([$userId, xfusion_insight_date_filter_unified_group_id()], $visibleStatuses)
        )
    );

    if (! is_array($rows)) {
        return [];
    }

    $dates = [];
    foreach ($rows as $row) {
        $day = trim((string) $row);
        if ($day !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $dates[] = $day;
        }
    }

    return $dates;
}

/**
 * Most recent unified evaluation date (Y-m-d) for the user.
 */
function xfusion_insight_date_filter_latest_date_for_user(int $userId): ?string
{
    $dates = xfusion_insight_date_filter_dates_for_user($userId);

    return $dates[0] ?? null;
}

/**
 * Stored evaluation for one scoring group on a specific calendar day.
 *
 * @return array{
 *   id: int,
 *   group_title: string,
 *   evaluated_at: string,
 *   evaluation: array<string, mixed>
 * }|null
 */
function xfusion_insight_date_filter_evaluation_for_group(int $userId, int $groupId, string $dateYmd): ?array
{
    if ($userId < 1 || $groupId < 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
        return null;
    }

    global $wpdb;

    xfusion_result_evaluation_maybe_create_table();

    $table = xfusion_result_evaluation_table_name();
    $visibleStatuses = function_exists('xfusion_result_evaluation_user_visible_statuses')
        ? xfusion_result_evaluation_user_visible_statuses()
        : ['published', 'sandbox'];
    $isUnified = $groupId === xfusion_insight_date_filter_unified_group_id();
    $sql = "SELECT * FROM {$table}
            WHERE user_id = %d AND scoring_group_id = %d AND DATE(evaluated_at) = %s";
    $params = [$userId, $groupId, $dateYmd];
    if ($isUnified) {
        $placeholders = implode(', ', array_fill(0, count($visibleStatuses), '%s'));
        $sql .= " AND status IN ({$placeholders})";
        $params = array_merge($params, $visibleStatuses);
    }
    $sql .= ' ORDER BY evaluated_at DESC, id DESC LIMIT 1';

    $row = $wpdb->get_row($wpdb->prepare($sql, ...$params));

    if ($row === null) {
        return null;
    }

    if (! function_exists('xfusion_result_evaluation_row_to_record')) {
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

/**
 * Resolve evaluation row for display: historical date or latest.
 *
 * @return array{
 *   id: int,
 *   group_title: string,
 *   evaluated_at: string,
 *   evaluation: array<string, mixed>
 * }|null
 */
function xfusion_insight_date_filter_resolve_evaluation(int $userId, int $groupId): ?array
{
    $dateYmd = xfusion_insight_date_filter_get_active();
    if ($dateYmd !== null) {
        return xfusion_insight_date_filter_evaluation_for_group($userId, $groupId, $dateYmd);
    }

    return function_exists('xfusion_result_evaluation_latest_for_group')
        ? xfusion_result_evaluation_latest_for_group($userId, $groupId)
        : null;
}

/**
 * Unified COR insight row (scoring_group_id = 0) for active date filter or latest.
 *
 * @return array{
 *   id: int,
 *   group_title: string,
 *   evaluated_at: string,
 *   evaluation: array<string, mixed>
 * }|null
 */
function xfusion_insight_date_filter_resolve_unified(int $userId): ?array
{
    if ($userId < 1) {
        return null;
    }

    $groupId = xfusion_insight_date_filter_unified_group_id();

    $dateYmd = xfusion_insight_date_filter_get_active();
    if ($dateYmd !== null) {
        return xfusion_insight_date_filter_evaluation_for_group($userId, $groupId, $dateYmd);
    }

    return function_exists('xfusion_result_evaluation_latest_unified')
        ? xfusion_result_evaluation_latest_unified($userId)
        : null;
}

function xfusion_insight_date_filter_format_label(string $dateYmd): string
{
    $ts = strtotime($dateYmd . ' 12:00:00 UTC');
    if ($ts === false) {
        return $dateYmd;
    }

    return wp_date(get_option('date_format') ?: 'M j, Y', $ts);
}

function xfusion_insight_date_filter_notice_html(): string
{
    $dateYmd = xfusion_insight_date_filter_get_active();
    if ($dateYmd === null) {
        return '';
    }

    return '<p class="xfusion-insight-date-filter__notice">'
        . esc_html(sprintf(
            /* translators: %s: formatted date */
            __('Viewing insights generated on %s. Generate buttons are disabled while a historical date is selected.', 'xfusion'),
            xfusion_insight_date_filter_format_label($dateYmd)
        ))
        . '</p>';
}

function xfusion_insight_date_filter_css(): string
{
    return <<<'CSS'
.xfusion-insight-date-filter{box-sizing:border-box;display:inline-flex;align-items:center;gap:8px;margin:0 0 20px;font-family:inherit;}
.xfusion-insight-date-filter__icon{font-size:18px;width:18px;height:18px;color:#64748b;flex-shrink:0;}
.xfusion-insight-date-filter__text{margin:0;font-size:15px;line-height:1.4;color:#1e3a5f;}
.xfusion-insight-date-filter__label{font-weight:600;letter-spacing:.02em;}
.xfusion-insight-date-filter__date{font-weight:400;color:#475569;}
.xfusion-insight-date-filter__notice{margin:0 0 30px;padding:10px 12px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;font-size:13px;line-height:1.45;color:#1e40af;}
CSS;
}

function xfusion_insight_date_filter_print_styles(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;

    wp_enqueue_style('dashicons');
    echo '<style id="xfusion-insight-date-filter-css">' . xfusion_insight_date_filter_css() . '</style>';
}

/**
 * @param array<string, string> $atts
 */
function xfusion_insight_date_filter_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'user_id' => '0',
            'class' => '',
        ],
        $atts,
        'xfusion_insight_date_filter'
    );

    if (! is_user_logged_in()) {
        return '';
    }

    $userId = (int) get_current_user_id();
    $attrUserId = absint($atts['user_id']);
    if ($attrUserId > 0 && current_user_can('edit_users')) {
        $userId = $attrUserId;
    }

    $latestDate = xfusion_insight_date_filter_latest_date_for_user($userId);
    if ($latestDate === null) {
        return '';
    }

    $wrapClass = trim('xfusion-insight-date-filter ' . (string) $atts['class']);
    $dateLabel = xfusion_insight_date_filter_format_label($latestDate);

    ob_start();
    xfusion_insight_date_filter_print_styles();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>">
    <span class="xfusion-insight-date-filter__icon dashicons dashicons-calendar-alt" aria-hidden="true"></span>
    <p class="xfusion-insight-date-filter__text">
        <span class="xfusion-insight-date-filter__label"><?php esc_html_e('Last Evaluation', 'xfusion'); ?></span>
        <span class="xfusion-insight-date-filter__date"> <?php echo esc_html($dateLabel); ?></span>
    </p>
</div>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('xfusion_insight_date_filter', 'xfusion_insight_date_filter_shortcode');
