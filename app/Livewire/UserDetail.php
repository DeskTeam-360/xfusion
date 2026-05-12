<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\CourseScoringGroup as CourseScoringGroupModel;
use App\Models\User;
use App\Models\WpGfEntry;
use App\Models\WpGfEntryMeta;
use App\Models\WpGfForm;
use App\Models\WpGfFormMeta;
use App\Models\WpPost;
use App\Models\WpPostMeta;
use Livewire\Component;

class UserDetail extends Component
{
    /** Maximum value on the group-average gauge (scale 0 → max). */
    public const SCORING_GROUP_GAUGE_MAX = 5.0;

    /**
     * Zone thresholds on the same scale as {@see SCORING_GROUP_GAUGE_MAX}.
     * Red: [0, redBelow), Yellow: [redBelow, amberBelow), Green: [amberBelow, max].
     * Matches: red ~ (1–3), yellow ~ (3–4.5), green ~ (4.5–5) with boundaries at 3 and 4.5.
     */
    public const SCORING_GAUGE_ZONE_RED_BELOW = 3.0;

    public const SCORING_GAUGE_ZONE_AMBER_BELOW = 4.5;

    public const SCORING_GAUGE_COLOR_RED = '#dc2626';

    public const SCORING_GAUGE_COLOR_AMBER = '#ca8a04';

    public const SCORING_GAUGE_COLOR_GREEN = '#16a34a';

    public const SCORING_GAUGE_COLOR_NEUTRAL = '#6b7280';

    /** WordPress user ID */
    public int $userId;

    /** @var array<string, string> */
    public array $identity = [];

    /**
     * @var list<array{course_id: int, title: string, completed: int, total: int, percent: int}>
     */
    public array $courseProgress = [];

    /**
     * @var list<array{
     *     id: int,
     *     title: string,
     *     description: string|null,
     *     average: float|null,
     *     gauge_value: float|null,
     *     gauge_needle_deg: float,
     *     gauge_needle_color: string,
     *     gauge_zone_label: string,
     *     rows: list<array{form_title: string, field_label: string, value: string, numeric: float|null}>
     * }>
     */
    public array $scoringGroups = [];

    public function mount(int|string $user): void
    {
        $this->userId = (int) $user;

        $u = User::with('meta')->findOrFail($this->userId);

        $meta = $u->meta;

        $fn = self::metaScalar($meta, 'first_name');
        $ln = self::metaScalar($meta, 'last_name');
        $fullName = trim("$fn $ln");
        $company = '—';
        foreach ($meta->where('meta_key', '=', 'company') as $r) {
            $cid = is_object($r) ? ($r->meta_value ?? null) : ($r['meta_value'] ?? null);
            if ($cid !== null && $cid !== '') {
                $c = Company::find($cid);
                $company = $c !== null ? (string) $c->title : 'Company not found';
            }
        }

        $this->identity = [
            'name' => $fullName !== '' ? $fullName : (string) ($u->display_name ?? $u->user_nicename ?? ''),
            'login' => (string) ($u->user_login ?? ''),
            'email' => (string) ($u->user_email ?? ''),
            'nicename' => (string) ($u->user_nicename ?? ''),
            'role' => self::metaScalar($meta, 'user_role') ?: '—',
            'company' => $company,
        ];

        $progressMeta = $meta->where('meta_key', '=', '_sfwd-course_progress')->first();
        $raw = [];
        if ($progressMeta !== null) {
            $mv = is_object($progressMeta) ? ($progressMeta->meta_value ?? '') : ($progressMeta['meta_value'] ?? '');
            $un = @unserialize(is_string($mv) ? $mv : '');
            $raw = is_array($un) ? $un : [];
        }

        $this->courseProgress = self::buildCourseProgressRows($raw);
        $this->scoringGroups = self::buildScoringGroups($this->userId);
    }

    /**
     * @param  \Illuminate\Support\Collection|object|array  $metaCollection
     */
    private static function metaScalar(mixed $metaCollection, string $key): string
    {
        if (! is_object($metaCollection) || ! method_exists($metaCollection, 'where')) {
            return '';
        }

        $row = $metaCollection->where('meta_key', '=', $key)->first();

        if ($row === null) {
            return '';
        }

        $v = is_object($row) ? ($row->meta_value ?? '') : ($row['meta_value'] ?? '');
        if (is_array($v)) {
            $v = $v[0] ?? '';
        }

        return trim((string) $v);
    }

    /**
     * @param  array<string|int, mixed>  $courseUser
     * @return list<array{course_id: int, title: string, completed: int, total: int, percent: int}>
     */
    private static function buildCourseProgressRows(array $courseUser): array
    {
        $topicDone = self::collectTopicCompletionFlags($courseUser);

        if ($topicDone === []) {
            return [];
        }

        $courseIds = [];

        foreach (array_keys($topicDone) as $topicId) {
            $cid = self::courseIdForTopic((int) $topicId);
            if ($cid > 0) {
                $courseIds[$cid] = true;
            }
        }

        $courseIds = array_keys($courseIds);
        sort($courseIds);

        $rows = [];

        foreach ($courseIds as $courseId) {
            $fromLd = self::topicIdsForCourse($courseId);
            $fromProgress = [];

            foreach (array_keys($topicDone) as $tid) {
                if (self::courseIdForTopic((int) $tid) === $courseId) {
                    $fromProgress[] = (int) $tid;
                }
            }

            $union = array_values(array_unique(array_merge($fromLd, $fromProgress)));
            $total = count($union);

            if ($total < 1) {
                continue;
            }

            $done = 0;

            foreach ($union as $tid) {
                if (! empty($topicDone[(int) $tid])) {
                    $done++;
                }
            }

            $percent = (int) round($done / $total * 100);

            $coursePost = WpPost::find($courseId);

            $rows[] = [
                'course_id' => $courseId,
                'title' => $coursePost->post_title ?? "Course #{$courseId}",
                'completed' => $done,
                'total' => $total,
                'percent' => $percent,
            ];
        }

        usort($rows, static fn ($a, $b) => strcmp((string) $a['title'], (string) $b['title']));

        return $rows;
    }

    /**
     * @param  array<string|int, mixed>  $courseUser
     * @return array<int, bool> topic ID → selesai
     */
    private static function collectTopicCompletionFlags(array $courseUser): array
    {
        $out = [];

        foreach ($courseUser as $block) {
            if (! is_array($block)) {
                continue;
            }

            $topics = $block['topics'] ?? null;

            if (! is_array($topics)) {
                continue;
            }

            foreach ($topics as $inner) {
                if (! is_array($inner)) {
                    continue;
                }

                foreach ($inner as $topicId => $flag) {
                    $tid = (int) $topicId;

                    if ($tid < 1) {
                        continue;
                    }

                    $done = $flag === 1 || $flag === true || $flag === '1';

                    if ($done) {
                        $out[$tid] = true;
                    } elseif (! array_key_exists($tid, $out)) {
                        $out[$tid] = false;
                    }
                }
            }
        }

        return $out;
    }

    private static function courseIdForTopic(int $topicId): int
    {
        if ($topicId < 1) {
            return 0;
        }

        $row = WpPostMeta::query()
            ->where('post_id', $topicId)
            ->where('meta_key', '=', 'course_id')
            ->first();

        if ($row === null) {
            return 0;
        }

        return (int) $row->meta_value;
    }

    /**
     * @return list<int>
     */
    private static function topicIdsForCourse(int $courseId): array
    {
        if ($courseId < 1) {
            return [];
        }

        $ids = WpPostMeta::query()
            ->where('meta_key', '=', 'course_id')
            ->where('meta_value', '=', (string) $courseId)
            ->pluck('post_id');

        if ($ids->isEmpty()) {
            return [];
        }

        return WpPost::query()
            ->whereIn('ID', $ids)
            ->where('post_type', '=', 'sfwd-topic')
            ->where('post_status', '=', 'publish')
            ->orderBy('menu_order')
            ->pluck('ID')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Arc segments (semicircle) for zone colours; geometry matches tick marks (0 → max).
     *
     * @return list<array{d: string, stroke: string}>
     */
    public static function scoringGaugeArcSegmentPaths(): array
    {
        $max = self::SCORING_GROUP_GAUGE_MAX;
        $b1 = self::SCORING_GAUGE_ZONE_RED_BELOW;
        $b2 = self::SCORING_GAUGE_ZONE_AMBER_BELOW;

        return [
            ['d' => self::gaugeArcSegmentDPath(0.0, min($b1, $max), $max), 'stroke' => self::SCORING_GAUGE_COLOR_RED],
            ['d' => self::gaugeArcSegmentDPath(min($b1, $max), min($b2, $max), $max), 'stroke' => self::SCORING_GAUGE_COLOR_AMBER],
            ['d' => self::gaugeArcSegmentDPath(min($b2, $max), $max, $max), 'stroke' => self::SCORING_GAUGE_COLOR_GREEN],
        ];
    }

    private static function gaugeArcSegmentDPath(float $valueFrom, float $valueTo, float $max): string
    {
        $r = 75.0;
        $cx = 110.0;
        $cy = 110.0;

        if ($max <= 0.00001 || $valueTo <= $valueFrom) {
            return 'M 35 110 A 75 75 0 0 1 35 110';
        }

        $t1 = pi() * (1 - $valueFrom / $max);
        $t2 = pi() * (1 - $valueTo / $max);
        $x1 = $cx + $r * cos($t1);
        $y1 = $cy - $r * sin($t1);
        $x2 = $cx + $r * cos($t2);
        $y2 = $cy - $r * sin($t2);

        return sprintf('M %.3f %.3f A 75 75 0 0 1 %.3f %.3f', $x1, $y1, $x2, $y2);
    }

    /**
     * @return array{needle: string, label: string}
     */
    private static function gaugeZoneMeta(?float $gaugeValue): array
    {
        if ($gaugeValue === null) {
            return [
                'needle' => self::SCORING_GAUGE_COLOR_NEUTRAL,
                'label' => 'No data',
            ];
        }

        if ($gaugeValue < self::SCORING_GAUGE_ZONE_RED_BELOW) {
            return [
                'needle' => self::SCORING_GAUGE_COLOR_RED,
                'label' => 'Needs improvement',
            ];
        }

        if ($gaugeValue < self::SCORING_GAUGE_ZONE_AMBER_BELOW) {
            return [
                'needle' => self::SCORING_GAUGE_COLOR_AMBER,
                'label' => 'Progressing',
            ];
        }

        return [
            'needle' => self::SCORING_GAUGE_COLOR_GREEN,
            'label' => 'Excellent',
        ];
    }

    /**
     * @return list<array{id: int, title: string, description: string|null, average: float|null, gauge_value: float|null, gauge_needle_deg: float, gauge_needle_color: string, gauge_zone_label: string, rows: list<array{form_title: string, field_label: string, value: string, numeric: float|null}>}>
     */
    private static function buildScoringGroups(int $userId): array
    {
        $groups = CourseScoringGroupModel::query()
            ->with('details')
            ->orderBy('title')
            ->get();

        $formLabels = [];

        $out = [];

        foreach ($groups as $group) {
            if ($group->details->isEmpty()) {
                continue;
            }

            $rows = [];
            $numericValues = [];

            foreach ($group->details as $detail) {
                $formId = (int) $detail->form_id;
                $fieldId = (int) $detail->field_id;

                $formTitle = self::formTitleCached($formId, $formLabels);
                $fieldLabel = self::gfFieldLabel($formId, $fieldId);

                $raw = self::latestEntryFieldValue($userId, $formId, $fieldId);
                $display = self::displayScoreValue($raw);
                $num = self::parseNumericScore($raw);

                $rows[] = [
                    'form_title' => $formTitle,
                    'field_label' => $fieldLabel,
                    'value' => $display,
                    'numeric' => $num,
                ];

                if ($num !== null) {
                    $numericValues[] = $num;
                }
            }

            $avg = $numericValues === [] ? null : round(array_sum($numericValues) / count($numericValues), 2);

            $gaugeMax = self::SCORING_GROUP_GAUGE_MAX;
            $gaugeValue = $avg === null ? null : min(max($avg, 0.0), $gaugeMax);
            $needleDeg = $gaugeValue === null
                ? -90.0
                : -90.0 + ($gaugeValue / $gaugeMax) * 180.0;

            $zone = self::gaugeZoneMeta($gaugeValue);

            $out[] = [
                'id' => (int) $group->id,
                'title' => (string) $group->title,
                'description' => $group->description,
                'average' => $avg,
                'gauge_value' => $gaugeValue,
                'gauge_needle_deg' => $needleDeg,
                'gauge_needle_color' => $zone['needle'],
                'gauge_zone_label' => $zone['label'],
                'rows' => $rows,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $cache
     */
    private static function formTitleCached(int $formId, array &$cache): string
    {
        if ($formId < 1) {
            return '—';
        }

        if (isset($cache[$formId])) {
            return $cache[$formId];
        }

        $f = WpGfForm::find($formId);

        $cache[$formId] = $f !== null ? (string) $f->title : "Form #{$formId}";

        return $cache[$formId];
    }

    private static function gfFieldLabel(int $formId, int $fieldId): string
    {
        if ($formId < 1 || $fieldId < 1) {
            return "Field #{$fieldId}";
        }

        $meta = WpGfFormMeta::query()->where('form_id', $formId)->first();

        if ($meta === null || ! is_string($meta->display_meta) || $meta->display_meta === '') {
            return "Field #{$fieldId}";
        }

        $decoded = json_decode($meta->display_meta);

        if (! is_object($decoded)) {
            return "Field #{$fieldId}";
        }

        $fields = $decoded->fields ?? [];

        if (! is_array($fields)) {
            return "Field #{$fieldId}";
        }

        foreach ($fields as $field) {
            if (! is_object($field)) {
                continue;
            }

            if ((int) ($field->id ?? 0) === $fieldId) {
                $label = $field->label ?? null;

                return is_string($label) && $label !== '' ? $label : "Field #{$fieldId}";
            }
        }

        return "Field #{$fieldId}";
    }

    private static function latestEntryFieldValue(int $userId, int $formId, int $fieldId): ?string
    {
        if ($formId < 1 || $fieldId < 1 || $userId < 1) {
            return null;
        }

        $entry = WpGfEntry::query()
            ->where('created_by', $userId)
            ->where('form_id', $formId)
            ->whereIn('status', ['active', 'Active', 'ACTIVE'])
            ->orderByDesc('id')
            ->first();

        if ($entry === null) {
            return null;
        }

        $fieldKey = (string) $fieldId;

        $metas = WpGfEntryMeta::query()
            ->where('entry_id', $entry->id)
            ->where('form_id', $formId)
            ->get();

        foreach ($metas as $m) {
            $k = explode('.', (string) $m->meta_key)[0] ?? '';
            if ($k === $fieldKey) {
                return (string) $m->meta_value;
            }
        }

        return null;
    }

    private static function displayScoreValue(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return '—';
        }

        return $raw;
    }

    private static function parseNumericScore(?string $raw): ?float
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
                $n = self::parseSingleNumber($p);
                if ($n !== null) {
                    $nums[] = $n;
                }
            }

            return $nums === [] ? null : array_sum($nums) / count($nums);
        }

        return self::parseSingleNumber($s);
    }

    private static function parseSingleNumber(string $s): ?float
    {
        $s = str_replace(',', '.', preg_replace('/[^\d\.\-]/', '', $s) ?? '');
        if ($s === '' || $s === '-' || $s === '.' || $s === '-.') {
            return null;
        }
        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    public function render()
    {
        return view('livewire.user-detail');
    }
}
