<?php

namespace App\Services;

use App\Models\CompanyEmployee;
use App\Models\CourseGroup;
use App\Models\CourseGroupDetail;
use App\Models\CourseList;
use App\Models\User;
use App\Models\WpGfEntryMeta;
use App\Models\WpGfFormMeta;
use App\Models\WpPost;
use App\Models\WpPostMeta;
use App\Models\WpUserMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds participation chart payloads (same rules as ExportResult company dashboard, radio fields).
 */
final class ParticipationChartsService
{
    private const CHART_BAR_PASTELS = [
        '#93c5fd', '#86efac', '#fca5a5', '#d8b4fe', '#fcd34d', '#67e8f9',
        '#fdba74', '#a5b4fc', '#f9a8d4', '#5eead4', '#c4b5fd', '#bef264',
        '#fda4af', '#94a3b8',
    ];

    /**
     * @param  list<string>  $fieldTypes
     * @return array{
     *   success: bool,
     *   message?: string,
     *   pie: array{participating: int, non_participating: int, pct: float},
     *   pie_by_work_type: list<array{label: string, participating: int, non_participating: int, pct: float}>,
     *   bar: list<array{label: string, axis_label: string, count: int, color: string}>,
     *   meta: array{user_count: int, activities_count: int, company_id: int, course_group_id: int}
     * }
     */
    public static function forCompanyAndCourseGroup(int $companyId, int $courseGroupId, array $fieldTypes = ['radio']): array
    {
        $emptyPie = ['participating' => 0, 'non_participating' => 0, 'pct' => 0.0];
        $metaBase = [
            'user_count' => 0,
            'activities_count' => 0,
            'company_id' => $companyId,
            'course_group_id' => $courseGroupId,
        ];

        $group = CourseGroup::query()->find($courseGroupId);
        if ($group === null) {
            return [
                'success' => false,
                'message' => 'Course group not found.',
                'pie' => $emptyPie,
                'pie_by_work_type' => [],
                'bar' => [],
                'meta' => $metaBase,
            ];
        }

        $courseIds = $group->courseGroupDetails()
            ->orderBy('orders')
            ->pluck('course_list_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($courseIds === []) {
            return [
                'success' => true,
                'pie' => $emptyPie,
                'pie_by_work_type' => [],
                'bar' => [],
                'meta' => array_merge($metaBase, ['user_count' => 0, 'activities_count' => 0]),
            ];
        }

        $employeeUserIds = CompanyEmployee::query()
            ->where('company_id', $companyId)
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $userLists = $employeeUserIds !== []
            ? User::query()->whereIn('ID', $employeeUserIds)->get()
            : collect();

        if ($userLists->isEmpty()) {
            return [
                'success' => true,
                'pie' => $emptyPie,
                'pie_by_work_type' => [],
                'bar' => [],
                'meta' => array_merge($metaBase, ['user_count' => 0, 'activities_count' => 0]),
            ];
        }

        $userIds = $userLists->pluck('ID')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $formIds = CourseList::whereIn('id', $courseIds)->pluck('wp_gf_form_id')->toArray();

        if ($formIds === []) {
            return [
                'success' => true,
                'pie' => $emptyPie,
                'pie_by_work_type' => [],
                'bar' => [],
                'meta' => array_merge($metaBase, ['user_count' => $userLists->count(), 'activities_count' => 0]),
            ];
        }

        $formMeta = WpGfFormMeta::query()
            ->whereIn('form_id', $formIds)
            ->with('wpGfForm')
            ->get(['display_meta', 'form_id']);

        $fieldTarget = [];
        $typesLower = array_map('strtolower', $fieldTypes);

        foreach ($formMeta as $meta) {
            $courseList = CourseList::where('wp_gf_form_id', $meta->form_id)->first();
            $cgDetail = $courseList
                ? CourseGroupDetail::where('course_list_id', $courseList->id)->where('course_group_id', $courseGroupId)->first()
                : null;

            $sortOrder = (int) (optional($cgDetail)->orders ?? 999999);
            $ordersLabel = ($cgDetail !== null && $cgDetail->orders !== null && $cgDetail->orders !== '')
                ? (string) $cgDetail->orders
                : 'No Orders';
            $gfTitle = $meta->wpGfForm->title ?? '';

            $fieldTarget[$meta->form_id]['sort_order'] = $sortOrder;
            $fieldTarget[$meta->form_id]['form_title'] = $ordersLabel . ' - ' . $gfTitle;

            $f = json_decode($meta->display_meta)->fields ?? [];
            foreach ($f as $field) {
                $ftype = is_string($field->type ?? null) ? strtolower((string) $field->type) : '';
                if (in_array($ftype, $typesLower, true)) {
                    $fieldTarget[$meta->form_id]['id'][] = $field->id;
                    $fieldTarget[$meta->form_id]['title'][$field->id] = $field->label;
                }
            }
        }

        self::sortFieldTargetByCourseGroupOrder($fieldTarget);

        $entries = WpGfEntryMeta::whereIn('form_id', $formIds)->whereHas('wpGfEntry', function ($q) use ($userIds) {
            $q->whereIn('created_by', $userIds)
                ->whereIn('status', ['active', 'Active', 'ACTIVE']);
        })->get();

        $results = [];
        foreach ($entries as $entry) {
            $k = explode('.', $entry->meta_key)[0];
            if (! isset($fieldTarget[$entry->form_id]['id'])) {
                continue;
            }
            $allowed = array_map('strval', $fieldTarget[$entry->form_id]['id']);
            if (in_array((string) $k, $allowed, true)) {
                $results[$entry->wpGfEntry->created_by][$entry->form_id]['data'][$k] = $entry->meta_value;
            }
        }

        foreach ($fieldTarget as $formId => $field) {
            if (! isset($field['title']) || $field['title'] === []) {
                unset($fieldTarget[$formId]);
            }
        }

        $stats = self::computeStats($userLists, $fieldTarget, $results);

        return [
            'success' => true,
            'pie' => $stats['pie'],
            'pie_by_work_type' => $stats['pie_by_work_type'],
            'bar' => $stats['bar'],
            'meta' => array_merge($metaBase, [
                'user_count' => $userLists->count(),
                'activities_count' => $stats['activities_count'],
            ]),
        ];
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $fieldTarget
     * @param  array<int, array<int|string, array<string, mixed>>>  $results
     * @return array{pie: array, pie_by_work_type: array, bar: array, activities_count: int}
     */
    private static function computeStats(Collection $userLists, array $fieldTarget, array $results): array
    {
        $emptyPie = ['participating' => 0, 'non_participating' => 0, 'pct' => 0.0];

        if ($userLists->isEmpty() || $fieldTarget === []) {
            return [
                'pie' => $emptyPie,
                'pie_by_work_type' => [],
                'bar' => [],
                'activities_count' => 0,
            ];
        }

        $activities = self::flattenActivityColumns($fieldTarget);
        $totalActivities = count($activities);

        $userIds = $userLists->map(fn ($u) => (int) ($u->ID ?? $u->id))->unique()->values()->all();
        $workTypeByUser = WpUserMeta::query()
            ->where('meta_key', 'work_type')
            ->whereIn('user_id', $userIds)
            ->pluck('meta_value', 'user_id')
            ->map(fn ($v) => (string) $v)
            ->all();

        $participantCount = $userLists->count();
        $userRowStats = [];

        foreach ($userLists as $user) {
            $complete = 0;
            foreach ($activities as $act) {
                $raw = self::rawAnswerFor($results, $user->ID, $act['form_id'], $act['field_id']);
                if (self::isAnswered($raw)) {
                    $complete++;
                }
            }
            $userRowStats[$user->ID] = ['complete' => $complete];
        }

        $activityFooterStats = [];
        foreach ($activities as $act) {
            $participation = 0;
            $numericValues = [];
            foreach ($userLists as $user) {
                $raw = self::rawAnswerFor($results, $user->ID, $act['form_id'], $act['field_id']);
                if (self::isAnswered($raw)) {
                    $participation++;
                    $n = self::parseNumericScore($raw);
                    if ($n !== null) {
                        $numericValues[] = $n;
                    }
                }
            }
            $rate = $participantCount > 0 ? round(($participation / $participantCount) * 100, 1) : null;
            $colAvg = count($numericValues) > 0 ? round(array_sum($numericValues) / count($numericValues), 2) : null;
            $activityFooterStats[] = [
                'form_id' => $act['form_id'],
                'field_id' => $act['field_id'],
                'label' => $act['label'],
                'form_title' => $act['form_title'],
                'participation_count' => $participation,
                'participation_rate' => $rate,
                'avg_assessment' => $colAvg,
            ];
        }

        $participatingUsers = 0;
        $nonParticipatingUsers = 0;
        foreach ($userLists as $user) {
            $c = $userRowStats[$user->ID]['complete'] ?? 0;
            if ($c > 0) {
                $participatingUsers++;
            } else {
                $nonParticipatingUsers++;
            }
        }
        $userTotal = $participatingUsers + $nonParticipatingUsers;
        $pie = [
            'participating' => $participatingUsers,
            'non_participating' => $nonParticipatingUsers,
            'pct' => $userTotal > 0 ? ($participatingUsers / $userTotal) * 100.0 : 0.0,
        ];

        $wtBuckets = [];
        foreach ($userLists as $user) {
            $wt = $workTypeByUser[$user->ID] ?? '';
            $wtLabel = $wt !== '' ? $wt : '(none)';
            if (! isset($wtBuckets[$wtLabel])) {
                $wtBuckets[$wtLabel] = ['participating' => 0, 'non_participating' => 0];
            }
            $c = $userRowStats[$user->ID]['complete'] ?? 0;
            if ($c > 0) {
                $wtBuckets[$wtLabel]['participating']++;
            } else {
                $wtBuckets[$wtLabel]['non_participating']++;
            }
        }
        $pieByWt = [];
        foreach ($wtBuckets as $label => $b) {
            $t = $b['participating'] + $b['non_participating'];
            $pieByWt[] = [
                'label' => $label,
                'participating' => $b['participating'],
                'non_participating' => $b['non_participating'],
                'pct' => $t > 0 ? ($b['participating'] / $t) * 100.0 : 0.0,
            ];
        }
        usort(
            $pieByWt,
            fn ($a, $b) => ($b['participating'] + $b['non_participating']) <=> ($a['participating'] + $a['non_participating']),
        );

        $counts = array_column($activityFooterStats, 'participation_count');
        $maxPart = $counts === [] ? 1 : max(max($counts), 1);
        $barRows = [];
        foreach ($activityFooterStats as $row) {
            $headerLabel = self::headerFormat($row['form_title'], $row['label']);
            $axisLabel = Str::limit($headerLabel, 55);
            $cnt = $row['participation_count'];
            $barRows[] = [
                'label' => Str::limit($headerLabel, 140),
                'axis_label' => $axisLabel,
                'count' => $cnt,
                'width_pct' => ($cnt / $maxPart) * 100.0,
            ];
        }
        self::sortBarRowsByLeadingCourseOrder($barRows);
        $pastels = self::CHART_BAR_PASTELS;
        $nPastel = count($pastels);
        $bar = [];
        foreach ($barRows as $idx => $r) {
            $r['color'] = $pastels[$idx % $nPastel];
            $bar[] = $r;
        }

        return [
            'pie' => $pie,
            'pie_by_work_type' => array_values($pieByWt),
            'bar' => $bar,
            'activities_count' => $totalActivities,
        ];
    }

    /**
     * Sort activity bars by leading course order in the label (e.g. "1 - Get Real …"), not by response count.
     *
     * @param  list<array{label?: string, axis_label?: string}>  $barRows
     */
    public static function sortBarRowsByLeadingCourseOrder(array &$barRows): void
    {
        usort($barRows, function (array $a, array $b): int {
            $ka = self::barRowLeadingOrderKey($a['axis_label'] ?? '', $a['label'] ?? '');
            $kb = self::barRowLeadingOrderKey($b['axis_label'] ?? '', $b['label'] ?? '');
            if ($ka['n'] !== $kb['n']) {
                return $ka['n'] <=> $kb['n'];
            }

            return strnatcasecmp($ka['tie'], $kb['tie']);
        });
    }

    /**
     * @return array{n: int, tie: string}
     */
    private static function barRowLeadingOrderKey(string $axisLabel, string $fullLabel): array
    {
        $s = trim($axisLabel !== '' ? $axisLabel : $fullLabel);
        $n = PHP_INT_MAX;
        if ($s !== '' && preg_match('/^\s*(\d+)\s*[\-–—]/u', $s, $m)) {
            $n = (int) $m[1];
        } elseif ($s !== '' && preg_match('/^\s*(\d+)/', $s, $m)) {
            $n = (int) $m[1];
        }

        return ['n' => $n, 'tie' => $s];
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $fieldTarget
     * @return list<array{form_id: int|string, field_id: mixed, label: string, form_title: string}>
     */
    private static function flattenActivityColumns(array $fieldTarget): array
    {
        $list = [];
        foreach ($fieldTarget as $formId => $field) {
            if (! isset($field['title']) || ! is_array($field['title'])) {
                continue;
            }
            $formTitle = (string) ($field['form_title'] ?? '');
            foreach ($field['title'] as $fieldId => $label) {
                $list[] = [
                    'form_id' => $formId,
                    'field_id' => $fieldId,
                    'label' => (string) $label,
                    'form_title' => $formTitle,
                ];
            }
        }

        return $list;
    }

    /**
     * @param  array<int, array<int|string, array<string, mixed>>>  $results
     */
    private static function rawAnswerFor(array $results, int|string $userId, int|string $formId, int|string $fieldId): mixed
    {
        return data_get($results, "{$userId}.{$formId}.data.{$fieldId}");
    }

    private static function isAnswered(mixed $raw): bool
    {
        if ($raw === null) {
            return false;
        }
        $s = trim((string) $raw);

        return $s !== '' && $s !== '-';
    }

    private static function parseNumericScore(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
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

    private static function headerFormat(string $courseTitle, string $question): string
    {
        $headerFormat = '[clean_course_title] - [clean_question]';
        $cleanQuestion = self::cleanLabel($question);
        $cleanCourse = self::cleanLabel($courseTitle);

        $headerFormat = str_replace('[course_title]', $courseTitle, $headerFormat);
        $headerFormat = str_replace('[question]', $question, $headerFormat);
        $headerFormat = str_replace('[clean_question]', $cleanQuestion, $headerFormat);
        $headerFormat = str_replace('[clean_course_title]', $cleanCourse, $headerFormat);

        return $headerFormat;
    }

    private static function cleanLabel(string $label): string
    {
        $blacklist = [
            'Rate your current ability to',
            '1-5 (5 highest)',
            '1-5 (5 highest).',
            'with yourself and others',
            'Rate your current level of',
            'Rate your ability to',
            'Rate the current status of',
            'Rate your current practices for maintaining',
            'Rate your',
            '(1-5-highest)',
            '(1-5-highest).',
            'Course - Topic : ',
            'Course - Topic',
        ];
        $clean = str_replace(["'", '"', '“', '”', ' .', '.'], '', $label);
        $clean = str_ireplace($blacklist, '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean) ?? '';

        return trim($clean);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $fieldTarget
     */
    private static function sortFieldTargetByCourseGroupOrder(array &$fieldTarget): void
    {
        uasort($fieldTarget, function ($a, $b) {
            return ($a['sort_order'] ?? 999999) <=> ($b['sort_order'] ?? 999999);
        });
    }
}
