<?php
/**
 * Shortcode: RPM-style gauge for Laravel "course scoring group" (same DB tables / scale as UserDetail).
 *
 * Usage:
 *   [xfusion_scoring_gauge group_id="1"]
 *   [xfusion_scoring_gauge group_id="1" user_id="89"]
 *   [xfusion_scoring_gauge group_id="1" show_title="1"]
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/** @var float Same as App\Livewire\UserDetail::SCORING_GROUP_GAUGE_MAX */
const XFUSION_CSG_GAUGE_MAX = 5.0;

/** @var float Red / yellow boundary */
const XFUSION_CSG_ZONE_RED_BELOW = 3.0;

/** @var float Yellow / green boundary */
const XFUSION_CSG_ZONE_AMBER_BELOW = 4.5;

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

function xfusion_csg_latest_entry_id(int $form_id, int $user_id): int
{
    global $wpdb;

    if ($form_id < 1 || $user_id < 1) {
        return 0;
    }

    $t = $wpdb->prefix . 'gf_entry';
    $id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$t} WHERE form_id = %d AND created_by = %d AND status IN ('active','Active','ACTIVE') ORDER BY id DESC LIMIT 1",
            $form_id,
            $user_id
        )
    );

    return $id ? (int) $id : 0;
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
 * @return array{title: string, needle_deg: float, average: ?float}|null Null if group missing or not readable
 */
function xfusion_csg_gauge_payload(int $group_id, int $user_id): ?array
{
    global $wpdb;

    if ($group_id < 1) {
        return null;
    }

    $gtable = $wpdb->prefix . 'course_scoring_groups';
    $dtable = $wpdb->prefix . 'course_scoring_group_details';

    $group = $wpdb->get_row(
        $wpdb->prepare("SELECT id, title FROM {$gtable} WHERE id = %d", $group_id),
        ARRAY_A
    );

    if ($group === null) {
        return null;
    }

    $details = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT form_id, field_id FROM {$dtable} WHERE course_scoring_group_id = %d ORDER BY id ASC",
            $group_id
        ),
        ARRAY_A
    );

    if ($details === []) {
        return null;
    }

    $numericValues = [];

    foreach ($details as $d) {
        $formId = (int) ($d['form_id'] ?? 0);
        $fieldId = (int) ($d['field_id'] ?? 0);
        $entryId = xfusion_csg_latest_entry_id($formId, $user_id);
        if ($entryId < 1) {
            continue;
        }
        $raw = xfusion_csg_entry_field_value($entryId, $formId, $fieldId);
        $num = xfusion_csg_parse_numeric($raw);
        if ($num !== null) {
            $numericValues[] = $num;
        }
    }

    $avg = $numericValues === [] ? null : round(array_sum($numericValues) / count($numericValues), 2);
    $gaugeMax = XFUSION_CSG_GAUGE_MAX;
    $gaugeValue = $avg === null ? null : min(max($avg, 0.0), $gaugeMax);
    $needleDeg = $gaugeValue === null
        ? -90.0
        : -90.0 + ($gaugeValue / $gaugeMax) * 180.0;

    return [
        'title' => (string) ($group['title'] ?? ''),
        'needle_deg' => $needleDeg,
        'average' => $avg,
    ];
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
            'show_title' => '0',
            'class' => '',
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

    $data = xfusion_csg_gauge_payload($gid, $uid);
    if ($data === null) {
        return '';
    }

    $segments = xfusion_csg_arc_segments();
    $needleColor = '#000000';
    $needleDeg = number_format($data['needle_deg'], 2, '.', '');
    $titleEsc = esc_html($data['title']);
    $titleAttr = esc_attr($data['title']);
    $wrapClass = trim('xfusion-scoring-gauge ' . (string) $atts['class']);
    $showTitle = $atts['show_title'] === '1' || strtolower((string) $atts['show_title']) === 'true';

    ob_start();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" style="max-width:9rem;margin-left:auto;margin-right:auto;text-align:center;">
<?php if ($showTitle) : ?>
    <div class="xfusion-scoring-gauge__title" style="font-size:11px;font-weight:600;line-height:1.2;margin-bottom:4px;"><?php echo $titleEsc; ?></div>
<?php endif; ?>
<svg style="width:100%;height:auto;max-height:4.75rem;" viewBox="0 0 220 130" role="img" aria-label="<?php echo $titleAttr; ?> — 0 to <?php echo (int) XFUSION_CSG_GAUGE_MAX; ?>">
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
</div>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('xfusion_scoring_gauge', 'xfusion_csg_scoring_gauge_shortcode');
