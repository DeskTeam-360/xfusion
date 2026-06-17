<?php
/**
 * Shortcode: RPM-style gauge for Laravel "course scoring group" (same DB tables / scale as UserDetail).
 *
 * Usage:
 *   [xfusion_scoring_gauge group_id="1"]
 *   [xfusion_scoring_gauge group_id="1" user_id="89"]
 *   [xfusion_scoring_gauge_separate group_id="1" type="graphic"]
 *   [xfusion_scoring_gauge_separate group_id="1" type="label" scale="1.1"]
 *   Size: size="xs|sm|md|lg|xl" (default xl), scale="1.2", max_width="12rem", svg_width="7rem", svg_max_height="6rem"
 *   Optional: class="my-class" (appended to wrapper)
 *   compare="month" (default) — delta vs previous calendar month; compare="off" to hide
 *   Separate shortcode type: graphic (SVG + title) | label (score, zone, delta, responses)
 *
 *   Score per group (dimension): weighted mean of question scores using wp_course_scoring_group_details.weight
 *   sum(score × weight) / sum(weight) for applicable rows (pure numeric GF value = radio/number, weight > 0).
 *   Only whole-number scale values 1, 2, 3, 4, or 5 count; all other values are ignored (treated as no data).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/** @var float Same as App\Livewire\UserDetail::SCORING_GROUP_GAUGE_MAX */
const XFUSION_CSG_GAUGE_MAX = 5.0;

/** @var int Valid GF radio / number scale for gauge scoring (inclusive). */
const XFUSION_CSG_SCALE_MIN = 1;

/** @var int Valid GF radio / number scale for gauge scoring (inclusive). */
const XFUSION_CSG_SCALE_MAX = 5;

/** @var float Red / yellow boundary */
const XFUSION_CSG_ZONE_RED_BELOW = 3.0;

/** @var float Yellow / green boundary */
const XFUSION_CSG_ZONE_AMBER_BELOW = 4.5;

/** Hex for zone label colour (matches UserDetail gauge_zone / needle meta colour). */
const XFUSION_CSG_COLOR_NEUTRAL = '#6b7280';

/**
 * Zone label + accent colour for footer (same rules as App\Livewire\UserDetail::gaugeZoneMeta).
 *
 * @return array{color: string, label: string}
 */
function xfusion_csg_gauge_zone_meta(?float $gaugeValue): array
{
    if ($gaugeValue === null) {
        return [
            'color' => XFUSION_CSG_COLOR_NEUTRAL,
            'label' => 'No data',
        ];
    }

    if ($gaugeValue < XFUSION_CSG_ZONE_RED_BELOW) {
        return [
            'color' => '#dc2626',
            'label' => 'Needs improvement',
        ];
    }

    if ($gaugeValue < XFUSION_CSG_ZONE_AMBER_BELOW) {
        return [
            'color' => '#ca8a04',
            'label' => 'Progressing',
        ];
    }

    return [
        'color' => '#16a34a',
        'label' => 'Excellent',
    ];
}

/**
 * Allow common CSS lengths for shortcode size attributes.
 */
function xfusion_csg_sanitize_css_length(string $s): ?string
{
    $s = trim($s);
    if ($s === '') {
        return null;
    }
    if (preg_match('/^\d+(\.\d+)?(rem|em|px|ch|%)$/i', $s)) {
        return $s;
    }

    return null;
}

/**
 * @param  array<string, string>  $atts
 * @return array{wrap_max: string, svg_max_w: string, svg_max_h: string, fs_title: int, fs_avg: int, fs_zone: int, fs_resp: int, h3_min_h: string}
 */
function xfusion_csg_shortcode_dimensions(array $atts): array
{
    $presets = [
		'xs' => ['w' => 8.5, 'svg' => 5.5, 'svgh' => 4.6, 't' => 12, 'a' => 15, 'z' => 18, 'r' => 11, 'h3mh' => 2.3],
		'sm' => ['w' => 10.5, 'svg' => 6.8, 'svgh' => 5.6, 't' => 13, 'a' => 16, 'z' => 19, 'r' => 12, 'h3mh' => 2.5],
		'md' => ['w' => 12.5, 'svg' => 8.0, 'svgh' => 6.6, 't' => 14, 'a' => 17, 'z' => 20, 'r' => 13, 'h3mh' => 2.8],
		'lg' => ['w' => 14.8, 'svg' => 9.5, 'svgh' => 7.8, 't' => 15, 'a' => 18, 'z' => 21, 'r' => 14, 'h3mh' => 3.0],
		'xl' => ['w' => 18.0, 'svg' => 12.0, 'svgh' => 9.8, 't' => 17, 'a' => 21, 'z' => 22, 'r' => 15, 'h3mh' => 3.3],
	];
    $sizeKey = strtolower((string) ($atts['size'] ?? 'xl'));
    if (! isset($presets[$sizeKey])) {
        $sizeKey = 'xl';
    }
    $p = $presets[$sizeKey];
    $scale = (float) ($atts['scale'] ?? 1);
    if ($scale <= 0 || $scale > 3) {
        $scale = 1.0;
    }
    foreach (['w', 'svg', 'svgh', 'h3mh'] as $k) {
        $p[$k] = round($p[$k] * $scale, 4);
    }
    foreach (['t', 'a', 'z', 'r'] as $k) {
        $p[$k] = max(8, (int) round($p[$k] * $scale));
    }

    $wrapMw = xfusion_csg_sanitize_css_length((string) ($atts['max_width'] ?? ''));
    $svgMw = xfusion_csg_sanitize_css_length((string) ($atts['svg_width'] ?? ''));
    $svgMh = xfusion_csg_sanitize_css_length((string) ($atts['svg_max_height'] ?? ''));

    return [
        'wrap_max' => $wrapMw ?? ($p['w'] . 'rem'),
        'svg_max_w' => $svgMw ?? ($p['svg'] . 'rem'),
        'svg_max_h' => $svgMh ?? ($p['svgh'] . 'rem'),
        'fs_title' => $p['t'],
        'fs_avg' => $p['a'],
        'fs_zone' => $p['z'],
        'fs_resp' => $p['r'],
        'h3_min_h' => $p['h3mh'] . 'rem',
    ];
}

/**
 * @return list<array{d: string, stroke: string}>
 */
function xfusion_csg_arc_segments(): array
{
    $max = XFUSION_CSG_GAUGE_MAX;
    $b1 = XFUSION_CSG_ZONE_RED_BELOW;
    $b2 = XFUSION_CSG_ZONE_AMBER_BELOW;

    return [
        ['d' => xfusion_csg_arc_d_path(0.0, min($b1, $max), $max), 'stroke' => '#dc2626'],
        ['d' => xfusion_csg_arc_d_path(min($b1, $max), min($b2, $max), $max), 'stroke' => '#ca8a04'],
        ['d' => xfusion_csg_arc_d_path(min($b2, $max), $max, $max), 'stroke' => '#16a34a'],
    ];
}

function xfusion_csg_arc_d_path(float $valueFrom, float $valueTo, float $max): string
{
    $r = 75.0;
    $cx = 110.0;
    $cy = 110.0;

    if ($max <= 0.00001 || $valueTo <= $valueFrom) {
        return 'M 35 110 A 75 75 0 0 1 35 110';
    }

    $t1 = M_PI * (1 - $valueFrom / $max);
    $t2 = M_PI * (1 - $valueTo / $max);
    $x1 = $cx + $r * cos($t1);
    $y1 = $cy - $r * sin($t1);
    $x2 = $cx + $r * cos($t2);
    $y2 = $cy - $r * sin($t2);

    return sprintf('M %.3f %.3f A 75 75 0 0 1 %.3f %.3f', $x1, $y1, $x2, $y2);
}

function xfusion_csg_parse_single_number(string $s): ?float
{
    $s = str_replace(',', '.', preg_replace('/[^\d\.\-]/', '', $s) ?? '');
    if ($s === '' || $s === '-' || $s === '.' || $s === '-.') {
        return null;
    }
    if (!is_numeric($s)) {
        return null;
    }

    return (float) $s;
}

function xfusion_csg_parse_numeric(?string $raw): ?float
{
    if ($raw === null) {
        return null;
    }
    $s = trim($raw);
    if ($s === '' || $s === '-') {
        return null;
    }
    if (str_contains($s, '|')) {
        $parts = array_filter(array_map('trim', explode('|', $s)));
        $nums = [];
        foreach ($parts as $p) {
            $n = xfusion_csg_parse_single_number($p);
            if ($n !== null) {
                $nums[] = $n;
            }
        }

        return $nums === [] ? null : array_sum($nums) / count($nums);
    }

    return xfusion_csg_parse_single_number($s);
}

/**
 * Gauge scoring: only pure numeric strings (radio value or number input), not free text.
 */
function xfusion_csg_parse_pure_numeric_score(?string $raw): ?float
{
    if ($raw === null) {
        return null;
    }

    $s = trim((string) $raw);
    if ($s === '') {
        return null;
    }

    $s = str_replace(',', '.', $s);
    if (! preg_match('/^-?\d+(\.\d+)?$/', $s)) {
        return null;
    }

    return (float) $s;
}

/**
 * Whether a parsed score is a valid FUSION/COR scale point (whole number 1–5).
 */
function xfusion_csg_is_valid_scale_score(?float $num): bool
{
    if ($num === null) {
        return false;
    }

    if ($num < (float) XFUSION_CSG_SCALE_MIN || $num > (float) XFUSION_CSG_SCALE_MAX) {
        return false;
    }

    return abs($num - round($num)) < 0.00001;
}

/**
 * Parse GF field value for gauge/dimension input — only 1, 2, 3, 4, or 5 (integer scale).
 */
function xfusion_csg_parse_scale_score(?string $raw): ?float
{
    $num = xfusion_csg_parse_pure_numeric_score($raw);
    if ($num === null || ! xfusion_csg_is_valid_scale_score($num)) {
        return null;
    }

    return (float) (int) round($num);
}

function xfusion_csg_latest_entry_id(int $form_id, int $user_id, ?array $period = null): int
{
    global $wpdb;

    if ($form_id < 1 || $user_id < 1) {
        return 0;
    }

    $t = $wpdb->prefix . 'gf_entry';
    if ($period !== null && isset($period['start'], $period['end'])) {
        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$t} WHERE form_id = %d AND created_by = %d AND status IN ('active','Active','ACTIVE')
                 AND date_created >= %s AND date_created < %s ORDER BY id DESC LIMIT 1",
                $form_id,
                $user_id,
                $period['start'],
                $period['end']
            )
        );

        return $id ? (int) $id : 0;
    }

    $id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$t} WHERE form_id = %d AND created_by = %d AND status IN ('active','Active','ACTIVE') ORDER BY id DESC LIMIT 1",
            $form_id,
            $user_id
        )
    );

    return $id ? (int) $id : 0;
}

/**
 * Previous calendar month in site timezone (half-open interval [start, end)).
 *
 * @return array{start: string, end: string}
 */
function xfusion_csg_previous_calendar_month_period(): array
{
    $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
    $now = new DateTimeImmutable('now', $tz);
    $thisMonthStart = $now->modify('first day of this month midnight');
    $prevMonthStart = $thisMonthStart->modify('-1 month');

    return [
        'start' => $prevMonthStart->format('Y-m-d H:i:s'),
        'end' => $thisMonthStart->format('Y-m-d H:i:s'),
    ];
}

function xfusion_csg_entry_field_value(int $entry_id, int $form_id, int $field_id): ?string
{
    global $wpdb;

    if ($entry_id < 1 || $form_id < 1 || $field_id < 1) {
        return null;
    }

    $t = $wpdb->prefix . 'gf_entry_meta';
    $prefix = (string) $field_id;
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$t} WHERE entry_id = %d AND form_id = %d",
            $entry_id,
            $form_id
        ),
        ARRAY_A
    );

    if ($rows === []) {
        return null;
    }

    foreach ($rows as $row) {
        $k = explode('.', (string) ($row['meta_key'] ?? ''))[0] ?? '';
        if ($k === $prefix) {
            return (string) ($row['meta_value'] ?? '');
        }
    }

    return null;
}

/**
 * Whether course_scoring_group_details has a weight column (post-migration).
 */
function xfusion_csg_details_table_has_weight(): bool
{
    static $has = null;

    if ($has !== null) {
        return $has;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'course_scoring_group_details';
    $has = in_array('weight', $wpdb->get_col("DESCRIBE {$table}", 0) ?: [], true);

    return $has;
}

/**
 * @return list<array{form_id: int, field_id: int, weight: float}>
 */
function xfusion_csg_group_details_for_scoring(int $group_id): array
{
    global $wpdb;

    $dtable = $wpdb->prefix . 'course_scoring_group_details';
    $select = xfusion_csg_details_table_has_weight()
        ? 'form_id, field_id, weight'
        : 'form_id, field_id';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT {$select} FROM {$dtable} WHERE course_scoring_group_id = %d ORDER BY id ASC",
            $group_id
        ),
        ARRAY_A
    );

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'form_id' => (int) ($row['form_id'] ?? 0),
            'field_id' => (int) ($row['field_id'] ?? 0),
            'weight' => xfusion_csg_details_table_has_weight()
                ? max(0.0, (float) ($row['weight'] ?? 1.0))
                : 1.0,
        ];
    }

    return $out;
}

/**
 * Weighted average for one scoring group: Σ(score × weight) / Σ(weight) over applicable questions.
 *
 * @param  array{start: string, end: string}|null  $period  When set, use latest GF entry per form within [start, end).
 * @return array{average: ?float, responses_answered: int, numeric_count: int, weight_total: float}
 */
function xfusion_csg_group_score_stats(int $group_id, int $user_id, ?array $period = null): array
{
    $details = xfusion_csg_group_details_for_scoring($group_id);

    $responsesAnswered = 0;
    $numericCount = 0;
    $weightedSum = 0.0;
    $weightTotal = 0.0;

    foreach ($details as $d) {
        $formId = $d['form_id'];
        $fieldId = $d['field_id'];
        $weight = $d['weight'];

        if ($formId < 1 || $fieldId < 1 || $weight <= 0) {
            continue;
        }

        $entryId = xfusion_csg_latest_entry_id($formId, $user_id, $period);
        $raw = null;
        if ($entryId > 0) {
            $raw = xfusion_csg_entry_field_value($entryId, $formId, $fieldId);
        }
        $num = xfusion_csg_parse_scale_score($raw);
        if ($num === null) {
            continue;
        }

        ++$responsesAnswered;
        ++$numericCount;
        $weightedSum += $num * $weight;
        $weightTotal += $weight;
    }

    $avg = $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null;

    return [
        'average' => $avg,
        'responses_answered' => $responsesAnswered,
        'numeric_count' => $numericCount,
        'weight_total' => round($weightTotal, 2),
    ];
}

/**
 * @return array{
 *   title: string,
 *   needle_deg: float,
 *   average: ?float,
 *   gauge_zone_label: string,
 *   gauge_zone_color: string,
 *   total_fields: int,
 *   responses_answered: int,
 *   compare_show: bool,
 *   month_delta: ?float,
 *   month_delta_available: bool,
 *   previous_month_average: ?float
 * }|null
 */
function xfusion_csg_gauge_payload(int $group_id, int $user_id, bool $comparePreviousMonth = true, ?array $period = null): ?array
{
    global $wpdb;

    if ($group_id < 1) {
        return null;
    }

    if ($period === null && function_exists('xfusion_insight_date_filter_score_period')) {
        $period = xfusion_insight_date_filter_score_period();
    }

    $gtable = $wpdb->prefix . 'course_scoring_groups';

    $group = $wpdb->get_row(
        $wpdb->prepare("SELECT id, title FROM {$gtable} WHERE id = %d", $group_id),
        ARRAY_A
    );

    if ($group === null) {
        return null;
    }

    $details = xfusion_csg_group_details_for_scoring($group_id);

    if ($details === []) {
        return null;
    }

    $current = xfusion_csg_group_score_stats($group_id, $user_id, $period);
    $avg = $current['average'];
    $gaugeMax = XFUSION_CSG_GAUGE_MAX;
    $gaugeValue = $avg === null ? null : min(max($avg, 0.0), $gaugeMax);
    $needleDeg = $gaugeValue === null
        ? -90.0
        : -90.0 + ($gaugeValue / $gaugeMax) * 180.0;

    $zone = xfusion_csg_gauge_zone_meta($gaugeValue);

    $monthDelta = null;
    $previousMonthAverage = null;
    $monthDeltaAvailable = false;

    if ($comparePreviousMonth && $avg !== null && $period === null) {
        $previousEvalAverage = function_exists('xfusion_result_evaluation_gauge_baseline_for_group')
            ? xfusion_result_evaluation_gauge_baseline_for_group($user_id, $group_id)
            : null;

        if ($previousEvalAverage !== null) {
            $previousMonthAverage = $previousEvalAverage;
            $monthDelta = round($avg - $previousEvalAverage, 2);
            $monthDeltaAvailable = true;
        }
    }

    return [
        'title' => (string) ($group['title'] ?? ''),
        'needle_deg' => $needleDeg,
        'average' => $avg,
        'gauge_zone_label' => $zone['label'],
        'gauge_zone_color' => $zone['color'],
        'total_fields' => count($details),
        'responses_answered' => $current['responses_answered'],
        'compare_show' => $comparePreviousMonth,
        'month_delta' => $monthDelta,
        'month_delta_available' => $monthDeltaAvailable,
        'previous_month_average' => $previousMonthAverage,
    ];
}

/**
 * @param  array{compare_show?: bool, month_delta: ?float, month_delta_available: bool}  $data
 */
function xfusion_csg_month_delta_markup(array $data, int $fontSize): string
{
    if (empty($data['compare_show'])) {
        return '';
    }

    $hasDelta = ! empty($data['month_delta_available']) && $data['month_delta'] !== null;

    if ($hasDelta) {
        $delta = (float) $data['month_delta'];
        if ($delta > 0) {
            $formatted = '+' . number_format($delta, 2, '.', '');
            $color = '#16a34a';
            $arrow = '▲';
        } elseif ($delta < 0) {
            $formatted = '-' . number_format(abs($delta), 2, '.', '');
            $color = '#dc2626';
            $arrow = '▼';
        } else {
            $formatted = '0.00';
            $color = '#6b7280';
            $arrow = '▲';
        }
    } else {
        $formatted = '-';
        $color = '#6b7280';
        $arrow = '';
    }

    ob_start();
    ?>
    <div class="xfusion-scoring-gauge__delta" style="margin:6px 0 0;display:flex;flex-direction:column;align-items:center;gap:2px;">
        <p style="margin:0;font-size:<?php echo (int) $fontSize; ?>px;font-weight:600;line-height:1.2;color:<?php echo esc_attr($color); ?>;font-variant-numeric:tabular-nums;">
            <?php if ($arrow !== '') : ?><span aria-hidden="true" style="font-size:0.75em;vertical-align:baseline;margin-right:2px;"><?php echo esc_html($arrow); ?></span><?php endif; ?><?php echo esc_html($formatted); ?>
        </p>
        <p style="margin:0;font-size:<?php echo max(9, (int) round($fontSize * 0.85)); ?>px;line-height:1.25;color:rgba(0,0,0,.45);"><?php echo esc_html__('Since Last Evaluation', 'xfusion'); ?></p>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * @param array<string, string> $atts
 */
function xfusion_csg_scoring_gauge_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'group_id' => '0',
            'id' => '0',
            'user_id' => '0',
            'class' => '',
            'size' => 'xl',
            'scale' => '1',
            'max_width' => '',
            'svg_width' => '',
            'svg_max_height' => '',
            'compare' => 'month',
        ],
        $atts,
        'xfusion_scoring_gauge'
    );

    $gid = absint($atts['group_id'] ?: $atts['id']);
    $uid = absint($atts['user_id']);
    if ($uid < 1) {
        $uid = (int) get_current_user_id();
    }
    if ($gid < 1 || $uid < 1) {
        return '';
    }

    $compareMode = strtolower(trim((string) $atts['compare']));
    $comparePreviousMonth = ! in_array($compareMode, ['off', '0', 'false', 'no', 'none'], true);

    $data = xfusion_csg_gauge_payload($gid, $uid, $comparePreviousMonth);
    if ($data === null) {
        return '';
    }

    $segments = xfusion_csg_arc_segments();
    $needleColor = '#000000';
    $needleDeg = number_format($data['needle_deg'], 2, '.', '');
    $titleEsc = esc_html($data['title']);
    $titleAttr = esc_attr($data['title']);
    $wrapClass = trim('xfusion-scoring-gauge ' . (string) $atts['class']);
    $zoneColor = $data['gauge_zone_color'];
    $zoneLabelEsc = esc_html($data['gauge_zone_label']);
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $zoneColor)) {
        $zoneColor = XFUSION_CSG_COLOR_NEUTRAL;
    }

    $dim = xfusion_csg_shortcode_dimensions($atts);

    ob_start();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" style="box-sizing:border-box;width:100%;max-width:<?php echo esc_attr($dim['wrap_max']); ?>;margin-left:auto;margin-right:auto;padding:0.75rem;display:flex;flex-direction:column;align-items:center;text-align:center;">
<svg style="width:100%;max-width:<?php echo esc_attr($dim['svg_max_w']); ?>;height:auto;max-height:<?php echo esc_attr($dim['svg_max_h']); ?>;display:block;margin:0 auto;" viewBox="0 0 220 130" role="img" aria-label="<?php echo $titleAttr; ?> gauge, 0 to <?php echo (int) XFUSION_CSG_GAUGE_MAX; ?>">
<?php foreach ($segments as $seg) : ?>
    <path fill="none" stroke="<?php echo esc_attr($seg['stroke']); ?>" stroke-width="10" stroke-linecap="round" d="<?php echo esc_attr($seg['d']); ?>" />
<?php endforeach; ?>
<?php for ($i = 0; $i <= 5; $i++) : ?>
    <?php
    $theta = M_PI * (1 - $i / 5);
    $x1 = 110 + 72 * cos($theta);
    $y1 = 110 - 72 * sin($theta);
    $x2 = 110 + 62 * cos($theta);
    $y2 = 110 - 62 * sin($theta);
    $lx = 110 + 52 * cos($theta);
    $ly = 110 - 52 * sin($theta);
    ?>
    <line x1="<?php echo esc_attr((string) $x1); ?>" y1="<?php echo esc_attr((string) $y1); ?>" x2="<?php echo esc_attr((string) $x2); ?>" y2="<?php echo esc_attr((string) $y2); ?>" stroke="#9ca3af" stroke-opacity="0.45" stroke-width="2" stroke-linecap="round"/>
    <text x="<?php echo esc_attr((string) $lx); ?>" y="<?php echo esc_attr((string) ($ly + 4)); ?>" text-anchor="middle" fill="#6b7280" font-size="11" font-weight="600"><?php echo (int) $i; ?></text>
<?php endfor; ?>
    <g transform="rotate(<?php echo esc_attr($needleDeg); ?> 110 110)">
        <line x1="110" y1="112" x2="110" y2="36" fill="none" stroke="<?php echo esc_attr($needleColor); ?>" stroke-width="4" stroke-linecap="round"/>
    </g>
    <circle cx="110" cy="110" r="7" fill="#1f2937"/>
    <circle cx="110" cy="110" r="4" fill="#ffffff"/>
</svg>
    <div style="margin-top:2px;width:100%;">
        <h3 style="margin:0 0 4px;font-size:<?php echo (int) $dim['fs_title']; ?>px;font-weight:600;line-height:1.25;min-height:<?php echo esc_attr($dim['h3_min_h']); ?>;width:100%;padding:0 2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" title="<?php echo $titleAttr; ?>"><?php echo $titleEsc; ?></h3>
        <p style="margin:0;font-size:<?php echo (int) $dim['fs_avg']; ?>px;font-weight:700;line-height:1.25;font-variant-numeric:tabular-nums;"><?php echo $data['average'] !== null ? esc_html((string) $data['average']) : esc_html('—'); ?></p>
        <p style="margin:0;font-size:<?php echo (int) $dim['fs_zone']; ?>px;font-weight:600;line-height:1.25;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:<?php echo esc_attr($zoneColor); ?>;"><?php echo $zoneLabelEsc; ?></p>
        <?php echo xfusion_csg_month_delta_markup($data, (int) round($dim['fs_avg'] * 0.85)); ?>
        <p style="margin:2px 0 0;font-size:<?php echo (int) $dim['fs_resp']; ?>px;line-height:1.25;color:rgba(0,0,0,.45);font-variant-numeric:tabular-nums;"><?php echo (int) $data['responses_answered']; ?> responses</p>
    </div>
</div>
    <?php

    return (string) ob_get_clean();
}

/**
 * @param array<string, string> $atts
 */
function xfusion_csg_scoring_gauge_separate_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'group_id' => '0',
            'id' => '0',
            'user_id' => '0',
            'class' => '',
            'size' => 'xl',
            'scale' => '1',
            'max_width' => '',
            'svg_width' => '',
            'svg_max_height' => '',
            'compare' => 'month',
            'type' => '',
        ],
        $atts,
        'xfusion_scoring_gauge_separate'
    );

    $type = strtolower(trim((string) $atts['type']));
    if (! in_array($type, ['graphic', 'label'], true)) {
        return '';
    }

    $gid = absint($atts['group_id'] ?: $atts['id']);
    $uid = absint($atts['user_id']);
    if ($uid < 1) {
        $uid = (int) get_current_user_id();
    }
    if ($gid < 1 || $uid < 1) {
        return '';
    }

    $compareMode = strtolower(trim((string) $atts['compare']));
    $comparePreviousMonth = ! in_array($compareMode, ['off', '0', 'false', 'no', 'none'], true);

    $data = xfusion_csg_gauge_payload($gid, $uid, $comparePreviousMonth);
    if ($data === null) {
        return '';
    }

    $segments = xfusion_csg_arc_segments();
    $needleColor = '#000000';
    $needleDeg = number_format($data['needle_deg'], 2, '.', '');
    $titleEsc = esc_html($data['title']);
    $titleAttr = esc_attr($data['title']);
    $wrapClass = trim('xfusion-scoring-gauge xfusion-scoring-gauge--' . $type . ' ' . (string) $atts['class']);
    $zoneColor = $data['gauge_zone_color'];
    $zoneLabelEsc = esc_html($data['gauge_zone_label']);
    if (! preg_match('/^#[0-9a-fA-F]{6}$/', $zoneColor)) {
        $zoneColor = XFUSION_CSG_COLOR_NEUTRAL;
    }

    $dim = xfusion_csg_shortcode_dimensions($atts);

    ob_start();

    if ($type === 'graphic') {
        ?>
<div class="<?php echo esc_attr($wrapClass); ?>" style="box-sizing:border-box;width:100%;max-width:<?php echo esc_attr($dim['wrap_max']); ?>;margin-left:auto;margin-right:auto;padding:0.75rem;display:flex;flex-direction:column;align-items:center;text-align:center;">
<svg style="width:100%;max-width:<?php echo esc_attr($dim['svg_max_w']); ?>;height:auto;max-height:<?php echo esc_attr($dim['svg_max_h']); ?>;display:block;margin:0 auto;" viewBox="0 0 220 130" role="img" aria-label="<?php echo $titleAttr; ?> gauge, 0 to <?php echo (int) XFUSION_CSG_GAUGE_MAX; ?>">
<?php foreach ($segments as $seg) : ?>
    <path fill="none" stroke="<?php echo esc_attr($seg['stroke']); ?>" stroke-width="10" stroke-linecap="round" d="<?php echo esc_attr($seg['d']); ?>" />
<?php endforeach; ?>
<?php for ($i = 0; $i <= 5; $i++) : ?>
    <?php
    $theta = M_PI * (1 - $i / 5);
    $x1 = 110 + 72 * cos($theta);
    $y1 = 110 - 72 * sin($theta);
    $x2 = 110 + 62 * cos($theta);
    $y2 = 110 - 62 * sin($theta);
    $lx = 110 + 52 * cos($theta);
    $ly = 110 - 52 * sin($theta);
    ?>
    <line x1="<?php echo esc_attr((string) $x1); ?>" y1="<?php echo esc_attr((string) $y1); ?>" x2="<?php echo esc_attr((string) $x2); ?>" y2="<?php echo esc_attr((string) $y2); ?>" stroke="#9ca3af" stroke-opacity="0.45" stroke-width="2" stroke-linecap="round"/>
    <text x="<?php echo esc_attr((string) $lx); ?>" y="<?php echo esc_attr((string) ($ly + 4)); ?>" text-anchor="middle" fill="#6b7280" font-size="11" font-weight="600"><?php echo (int) $i; ?></text>
<?php endfor; ?>
    <g transform="rotate(<?php echo esc_attr($needleDeg); ?> 110 110)">
        <line x1="110" y1="112" x2="110" y2="36" fill="none" stroke="<?php echo esc_attr($needleColor); ?>" stroke-width="4" stroke-linecap="round"/>
    </g>
    <circle cx="110" cy="110" r="7" fill="#1f2937"/>
    <circle cx="110" cy="110" r="4" fill="#ffffff"/>
</svg>
    <div style="margin-top:2px;width:100%;">
        <h3 style="margin:0 0 4px;font-size:<?php echo (int) $dim['fs_title']; ?>px;font-weight:600;line-height:1.25;min-height:<?php echo esc_attr($dim['h3_min_h']); ?>;width:100%;padding:0 2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" title="<?php echo $titleAttr; ?>"><?php echo $titleEsc; ?></h3>
    </div>
</div>
        <?php
    } else {
        ?>
<div class="<?php echo esc_attr($wrapClass); ?>" style="box-sizing:border-box;width:100%;max-width:<?php echo esc_attr($dim['wrap_max']); ?>;margin-left:auto;margin-right:auto;padding:0.75rem;display:flex;flex-direction:column;align-items:center;text-align:center;">
    <div style="width:100%;">
        <p style="margin:0;font-size:<?php echo (int) $dim['fs_avg']; ?>px;font-weight:700;line-height:1.25;font-variant-numeric:tabular-nums;"><?php echo $data['average'] !== null ? esc_html((string) $data['average']) : esc_html('—'); ?></p>
        <p style="margin:0;font-size:<?php echo (int) $dim['fs_zone']; ?>px;font-weight:600;line-height:1.25;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:<?php echo esc_attr($zoneColor); ?>;"><?php echo $zoneLabelEsc; ?></p>
        <?php echo xfusion_csg_month_delta_markup($data, (int) round($dim['fs_avg'] * 0.85)); ?>
        <p style="margin:2px 0 0;font-size:<?php echo (int) $dim['fs_resp']; ?>px;line-height:1.25;color:rgba(0,0,0,.45);font-variant-numeric:tabular-nums;"><?php echo (int) $data['responses_answered']; ?> responses</p>
    </div>
</div>
        <?php
    }

    return (string) ob_get_clean();
}

add_shortcode('xfusion_scoring_gauge', 'xfusion_csg_scoring_gauge_shortcode');
add_shortcode('xfusion_scoring_gauge_separate', 'xfusion_csg_scoring_gauge_separate_shortcode');