<?php

namespace App\Services;

use App\Models\CompanyGroupDetail;
use App\Models\CourseGroup;
use App\Models\CourseGroupDetail;
use App\Models\CourseList;
use App\Models\CourseScoringGroup;
use App\Models\IrrReview;
use App\Models\OneOnOneCommitment;
use App\Models\OneOnOneConversation;
use App\Models\ResultEvaluation;
use App\Models\WpGfEntry;
use App\Models\WpGfEntryMeta;
use Carbon\Carbon;

/**
 * IRR Step 1/2 — annual individual evidence snapshot for one employee.
 *
 * Aggregates only sources that already exist in FUSION today. Fields without
 * a tracked source return null and are listed in evidence_sources as unavailable.
 */
class IrrEvidenceService
{
    private const BEHAVIORAL_DRIVERS = [
        'get_real' => 'Get Real™',
        'fill_buckets' => 'Fill Buckets™',
        'be_intentional' => 'Be Intentional™',
        'foster_grit' => 'Foster Grit™',
        'drive_growth' => 'Drive Growth™',
    ];

    private const SELF_ASSESSMENT_KEYS = [
        'alignment' => 'Alignment',
        'accountability' => 'Accountability',
        'communication' => 'Communication',
        'leadership' => 'Leadership',
        'execution' => 'Execution',
    ];

    private const ACTIVITY_PROGRAM_TYPES = ['transform', 'sustain', 'revitalize'];

    public function buildSnapshot(IrrReview $review): array
    {
        [$start, $end] = $this->periodDates((int) $review->year);
        $employeeId = (int) $review->employee_user_id;
        $orgMemberIds = $this->orgMemberIds($review);

        $scoringBySlug = $this->scoringAveragesBySlug($employeeId);
        $orgScoringBySlug = $this->orgScoringAveragesBySlug($orgMemberIds);

        $unifiedInsight = $this->latestUnifiedInsight($employeeId, $start, $end);
        $oneOnOne = $this->oneOnOneStats($employeeId, $start, $end);
        $summaries = $this->oneOnOneSummaries($employeeId, $start, $end);
        $commitments = $this->commitmentCompletion($employeeId, $start, $end);
        $activities = $this->activityStats($employeeId, $start, $end);
        $tools = $this->toolStats($employeeId, $start, $end);
        $priorSnapshot = $this->priorYearSnapshot($review);

        $highlights = $this->evidenceHighlights(
            $oneOnOne,
            $commitments,
            $activities,
            $tools,
            $priorSnapshot
        );

        $driverTrends = $this->behavioralDriverTrends($scoringBySlug, $orgScoringBySlug);
        $selfScores = $this->selfAssessmentScores($scoringBySlug);

        return [
            'review_period' => [
                'year' => (int) $review->year,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'employee_user_id' => $employeeId,
            'evidence_sources' => $this->evidenceSourcesChecklist(
                $review,
                $unifiedInsight,
                $summaries,
                $activities,
                $tools,
                $commitments,
                $oneOnOne,
                $scoringBySlug
            ),
            'individual_insights' => $unifiedInsight,
            'behavioral_driver_trends' => $driverTrends,
            'self_assessment_scores' => $selfScores,
            'development_participation' => $activities,
            'commitment_completion' => $commitments,
            'one_on_one' => $oneOnOne,
            'one_on_one_summaries' => $summaries,
            'leader_observations' => $this->leaderObservations($summaries),
            'growth_timeline' => $this->growthTimeline($employeeId, $start, $end),
            'tool_utilization' => $tools,
            'evidence_highlights' => $highlights,
            'previous_irr' => $this->previousIrrSummary($review),
            'behavioral_driver_monthly' => null,
            'development_trends' => null,
            'reflection_themes' => null,
            'organizational_alignment' => null,
            'qbr_arp_priorities' => null,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periodDates(int $year): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        return [$start, $end];
    }

    /** @return list<int> */
    private function orgMemberIds(IrrReview $review): array
    {
        if ($review->company_group_id === null) {
            return [];
        }

        return CompanyGroupDetail::query()
            ->where('company_group_id', $review->company_group_id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<string, float|null> */
    private function scoringAveragesBySlug(int $userId): array
    {
        $out = [];
        foreach (array_keys(self::BEHAVIORAL_DRIVERS + self::SELF_ASSESSMENT_KEYS) as $slug) {
            $out[$slug] = null;
        }

        foreach (CourseScoringGroup::with('details')->get() as $group) {
            $slug = $this->titleToSlug((string) $group->title);
            if (! array_key_exists($slug, $out)) {
                continue;
            }
            $avg = $this->weightedGroupAverage($group, $userId);
            if ($avg !== null) {
                $out[$slug] = $avg;
            }
        }

        return $out;
    }

    /** @param  list<int>  $memberIds
     * @return array<string, float|null>
     */
    private function orgScoringAveragesBySlug(array $memberIds): array
    {
        $out = [];
        foreach (array_keys(self::BEHAVIORAL_DRIVERS) as $slug) {
            $values = [];
            foreach ($memberIds as $memberId) {
                $scores = $this->scoringAveragesBySlug($memberId);
                if ($scores[$slug] !== null) {
                    $values[] = $scores[$slug];
                }
            }
            $out[$slug] = $values !== [] ? round(array_sum($values) / count($values), 2) : null;
        }

        return $out;
    }

    private function weightedGroupAverage(CourseScoringGroup $group, int $userId): ?float
    {
        $details = $group->details->filter(
            fn ($d) => (int) $d->form_id > 0 && (int) $d->field_id > 0 && (float) ($d->weight ?? 1.0) > 0
        );

        if ($details->isEmpty()) {
            return null;
        }

        $formIds = $details->pluck('form_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $latestEntryByForm = [];
        WpGfEntry::query()
            ->whereIn('form_id', $formIds)
            ->where('created_by', $userId)
            ->whereIn('status', ['active', 'Active', 'ACTIVE'])
            ->select(['id', 'form_id'])
            ->orderByDesc('id')
            ->get()
            ->each(function ($e) use (&$latestEntryByForm) {
                $fid = (int) $e->form_id;
                if (! isset($latestEntryByForm[$fid])) {
                    $latestEntryByForm[$fid] = (int) $e->id;
                }
            });

        $entryIds = array_values($latestEntryByForm);
        $valueMap = [];
        if ($entryIds !== []) {
            WpGfEntryMeta::query()
                ->whereIn('entry_id', $entryIds)
                ->get(['entry_id', 'meta_key', 'meta_value'])
                ->each(function ($m) use (&$valueMap) {
                    $k = explode('.', (string) $m->meta_key)[0];
                    $valueMap[(int) $m->entry_id][$k] = (string) $m->meta_value;
                });
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;
        foreach ($details as $d) {
            $entryId = $latestEntryByForm[(int) $d->form_id] ?? null;
            if ($entryId === null) {
                continue;
            }
            $num = $this->parseScaleScore($valueMap[$entryId][(string) (int) $d->field_id] ?? null);
            if ($num === null) {
                continue;
            }
            $weight = (float) ($d->weight ?? 1.0);
            $weightedSum += $num * $weight;
            $weightTotal += $weight;
        }

        return $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null;
    }

    private function parseScaleScore(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $s = trim($raw);
        if ($s === '' || $s === '-') {
            return null;
        }
        $s = str_replace(',', '.', $s);
        if (! preg_match('/^-?\d+(\.\d+)?$/', $s)) {
            return null;
        }

        return (float) $s;
    }

    private function titleToSlug(string $title): string
    {
        $t = strtolower(trim($title));
        $t = preg_replace('/[^\w\s]/u', '', $t) ?? $t;
        $t = preg_replace('/\s+/', '_', trim($t)) ?? $t;

        return $t;
    }

    private function latestUnifiedInsight(int $employeeId, Carbon $start, Carbon $end): ?array
    {
        $row = ResultEvaluation::query()
            ->where('user_id', $employeeId)
            ->where('scoring_group_id', 0)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('id')
            ->first(['score', 'evaluation', 'evaluated_at', 'created_at']);

        if ($row === null) {
            return null;
        }

        return [
            'score' => $row->score !== null ? (float) $row->score : null,
            'evaluated_at' => $row->evaluated_at?->toIso8601String() ?? $row->created_at?->toIso8601String(),
            'key_observation' => is_array($row->evaluation)
                ? ($row->evaluation['key_observation'] ?? null)
                : null,
        ];
    }

    private function behavioralDriverTrends(array $employeeScores, array $orgScores): array
    {
        $drivers = [];
        foreach (self::BEHAVIORAL_DRIVERS as $slug => $label) {
            $drivers[] = [
                'slug' => $slug,
                'label' => $label,
                'you' => $employeeScores[$slug] ?? null,
                'org_avg' => $orgScores[$slug] ?? null,
            ];
        }

        return ['drivers' => $drivers];
    }

    private function selfAssessmentScores(array $employeeScores): array
    {
        $rows = [];
        foreach (self::SELF_ASSESSMENT_KEYS as $slug => $label) {
            $rows[] = [
                'slug' => $slug,
                'label' => $label,
                'score' => $employeeScores[$slug] ?? null,
            ];
        }

        return $rows;
    }

    private function oneOnOneStats(int $employeeId, Carbon $start, Carbon $end): array
    {
        $conversations = OneOnOneConversation::query()
            ->whereHas('oneOnOne', fn ($q) => $q->where('employee_user_id', $employeeId))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('held_at', [$start, $end])
                    ->orWhereBetween('scheduled_at', [$start, $end]);
            })
            ->get(['status']);

        if ($conversations->isEmpty()) {
            return ['rate' => null, 'total' => 0, 'completed' => 0];
        }

        $completed = $conversations->where('status', OneOnOneConversation::STATUS_COMPLETED)->count();

        return [
            'rate' => round($completed / $conversations->count() * 100, 1),
            'total' => $conversations->count(),
            'completed' => $completed,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function oneOnOneSummaries(int $employeeId, Carbon $start, Carbon $end): array
    {
        $conversations = OneOnOneConversation::query()
            ->whereHas('oneOnOne', fn ($q) => $q->where('employee_user_id', $employeeId))
            ->where('status', OneOnOneConversation::STATUS_COMPLETED)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('held_at', [$start, $end])
                    ->orWhereBetween('scheduled_at', [$start, $end]);
            })
            ->with(['synthesis', 'oneOnOne.leader:ID,display_name,user_nicename'])
            ->orderByDesc('held_at')
            ->orderByDesc('scheduled_at')
            ->get();

        $summaries = [];
        foreach ($conversations as $conversation) {
            $synthesis = $conversation->synthesis;
            if ($synthesis === null || ! is_array($synthesis->synthesis)) {
                continue;
            }
            $meetingSummary = $synthesis->synthesis['meeting_summary'] ?? null;
            if (! is_array($meetingSummary)) {
                continue;
            }
            $items = array_values(array_filter(
                array_map(static fn ($item) => is_string($item) ? trim($item) : '', $meetingSummary['items'] ?? []),
                static fn ($item) => $item !== ''
            ));
            $details = trim((string) ($meetingSummary['details'] ?? ''));
            if ($items === [] && $details === '') {
                continue;
            }
            $heldAt = $conversation->held_at ?? $conversation->scheduled_at;
            $leader = $conversation->oneOnOne?->leader;
            $summaries[] = [
                'conversation_id' => $conversation->id,
                'held_at' => $heldAt?->toIso8601String(),
                'leader_name' => $leader ? ($leader->display_name ?: $leader->user_nicename) : null,
                'meeting_summary' => ['items' => $items, 'details' => $details],
            ];
        }

        return $summaries;
    }

    private function commitmentCompletion(int $employeeId, Carbon $start, Carbon $end): array
    {
        $rows = OneOnOneCommitment::query()
            ->whereHas('conversation.oneOnOne', fn ($q) => $q->where('employee_user_id', $employeeId))
            ->whereBetween('created_at', [$start, $end])
            ->get(['status', 'due_date']);

        return $this->commitmentBreakdown($rows);
    }

    private function commitmentBreakdown($rows): array
    {
        if ($rows->isEmpty()) {
            return [
                'rate' => null,
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'overdue' => 0,
                'not_started' => 0,
            ];
        }

        $now = now();
        $completed = 0;
        $inProgress = 0;
        $overdue = 0;
        $notStarted = 0;

        foreach ($rows as $row) {
            $status = (string) $row->status;
            if ($status === OneOnOneCommitment::STATUS_DONE) {
                $completed++;
            } elseif ($status === OneOnOneCommitment::STATUS_IN_PROGRESS) {
                $inProgress++;
            } elseif ($row->due_date !== null && $row->due_date->lt($now) && $status === OneOnOneCommitment::STATUS_OPEN) {
                $overdue++;
            } else {
                $notStarted++;
            }
        }

        $total = $rows->count();

        return [
            'rate' => round($completed / $total * 100, 1),
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'not_started' => $notStarted,
        ];
    }

    private function activityStats(int $employeeId, Carbon $start, Carbon $end): array
    {
        $groupsByType = CourseGroup::query()
            ->whereIn('type', self::ACTIVITY_PROGRAM_TYPES)
            ->get()
            ->groupBy('type');

        $byProgram = [];
        $programsWithData = 0;
        $totalSubmissions = 0;

        foreach (self::ACTIVITY_PROGRAM_TYPES as $programType) {
            $groups = $groupsByType->get($programType, collect());
            $count = $this->submissionCountForCourseGroups(
                $groups->pluck('id')->map(fn ($id) => (int) $id)->all(),
                [$employeeId],
                $start,
                $end
            );
            if ($count > 0) {
                $programsWithData++;
            }
            $totalSubmissions += $count;
            $byProgram[] = [
                'program' => $programType,
                'label' => ucfirst($programType),
                'submissions' => $count,
            ];
        }

        return [
            'rate' => round($programsWithData / count(self::ACTIVITY_PROGRAM_TYPES) * 100, 1),
            'total_submissions' => $totalSubmissions,
            'programs_with_data' => $programsWithData,
            'programs_total' => count(self::ACTIVITY_PROGRAM_TYPES),
            'by_program' => $byProgram,
            'completed' => $totalSubmissions,
            'in_progress' => 0,
            'not_started' => 0,
            'not_assigned' => null,
        ];
    }

    private function toolStats(int $employeeId, Carbon $start, Carbon $end): array
    {
        $toolGroupIds = CourseGroup::query()->where('tools', 1)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $count = $this->submissionCountForCourseGroups($toolGroupIds, [$employeeId], $start, $end);

        $courseListIds = $toolGroupIds === [] ? [] : CourseGroupDetail::query()
            ->whereIn('course_group_id', $toolGroupIds)
            ->pluck('course_list_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $totalTools = $courseListIds === [] ? 0 : CourseList::query()
            ->whereIn('id', $courseListIds)
            ->whereNotNull('wp_gf_form_id')
            ->where('wp_gf_form_id', '>', 0)
            ->count();

        return [
            'submissions' => $count,
            'tools_available' => $totalTools,
            'tools_used' => $count > 0 ? min($count, $totalTools) : 0,
        ];
    }

    /** @param  list<int>  $courseGroupIds
     * @param  list<int>  $userIds
     */
    private function submissionCountForCourseGroups(array $courseGroupIds, array $userIds, Carbon $start, Carbon $end): int
    {
        if ($courseGroupIds === [] || $userIds === []) {
            return 0;
        }

        $courseListIds = CourseGroupDetail::query()
            ->whereIn('course_group_id', $courseGroupIds)
            ->pluck('course_list_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($courseListIds === []) {
            return 0;
        }

        $formIds = CourseList::query()
            ->whereIn('id', $courseListIds)
            ->pluck('wp_gf_form_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($formIds === []) {
            return 0;
        }

        return (int) WpGfEntry::query()
            ->whereIn('created_by', $userIds)
            ->whereIn('form_id', $formIds)
            ->whereBetween('date_created', [$start, $end])
            ->whereIn('status', ['active', 'Active', 'ACTIVE'])
            ->count();
    }

    /** @return list<string> */
    private function leaderObservations(array $summaries): array
    {
        $bullets = [];
        foreach ($summaries as $summary) {
            foreach ($summary['meeting_summary']['items'] ?? [] as $item) {
                if ($item !== '' && ! in_array($item, $bullets, true)) {
                    $bullets[] = $item;
                }
            }
        }

        return array_slice($bullets, 0, 8);
    }

    /** @return list<array<string, mixed>> */
    private function growthTimeline(int $employeeId, Carbon $start, Carbon $end): array
    {
        $quarters = [
            ['key' => 'Q1', 'label' => 'Jan – Mar', 'start' => 1, 'end' => 3],
            ['key' => 'Q2', 'label' => 'Apr – Jun', 'start' => 4, 'end' => 6],
            ['key' => 'Q3', 'label' => 'Jul – Sep', 'start' => 7, 'end' => 9],
            ['key' => 'Q4', 'label' => 'Oct – Dec', 'start' => 10, 'end' => 12],
        ];

        $rows = OneOnOneCommitment::query()
            ->whereHas('conversation.oneOnOne', fn ($q) => $q->where('employee_user_id', $employeeId))
            ->whereBetween('created_at', [$start, $end])
            ->get(['behavioral_driver', 'title', 'created_at']);

        $timeline = [];
        foreach ($quarters as $q) {
            $qStart = $start->copy()->month($q['start'])->startOfMonth();
            $qEnd = $start->copy()->month($q['end'])->endOfMonth();
            $inQuarter = $rows->filter(function ($row) use ($qStart, $qEnd) {
                $at = $row->created_at;

                return $at !== null && $at->between($qStart, $qEnd);
            });
            $first = $inQuarter->first();

            $timeline[] = [
                'quarter' => $q['key'],
                'period' => $q['label'],
                'focus' => $first?->title ?: ($first?->behavioral_driver ?: null),
                'commitment_count' => $inQuarter->count(),
            ];
        }

        return $timeline;
    }

    private function evidenceHighlights(
        array $oneOnOne,
        array $commitments,
        array $activities,
        array $tools,
        ?array $priorSnapshot
    ): array {
        $prior = is_array($priorSnapshot['evidence_highlights'] ?? null) ? $priorSnapshot['evidence_highlights'] : [];

        $current = [
            'activities_completed' => $activities['total_submissions'] ?? 0,
            'commitments_completed' => ($commitments['completed'] ?? 0).(isset($commitments['total']) && $commitments['total'] > 0 ? ' of '.$commitments['total'] : ''),
            'commitments_completed_count' => $commitments['completed'] ?? 0,
            'tools_used' => $tools['submissions'] ?? 0,
            'one_on_ones_completed' => $oneOnOne['completed'] ?? 0,
        ];

        foreach (['activities_completed', 'tools_used', 'one_on_ones_completed'] as $key) {
            $prev = (int) ($prior[$key] ?? 0);
            $cur = (int) ($current[$key] ?? 0);
            $current[$key.'_trend'] = $this->percentTrend($cur, $prev);
        }

        $prevDone = (int) ($prior['commitments_completed_count'] ?? 0);
        $curDone = (int) ($commitments['completed'] ?? 0);
        $current['commitments_completed_trend'] = $this->percentTrend($curDone, $prevDone);

        return $current;
    }

    private function percentTrend(int $current, int $previous): ?array
    {
        if ($previous < 1) {
            return null;
        }
        $pct = (int) round((($current - $previous) / $previous) * 100);

        return ['direction' => $pct >= 0 ? 'up' : 'down', 'percent' => abs($pct)];
    }

    private function priorYearSnapshot(IrrReview $review): ?array
    {
        $prior = IrrReview::query()
            ->where('employee_user_id', $review->employee_user_id)
            ->where('year', (int) $review->year - 1)
            ->first();

        if ($prior === null) {
            return null;
        }

        $snap = $prior->evidenceSnapshots()->first();

        return is_array($snap?->snapshot) ? $snap->snapshot : null;
    }

    private function previousIrrSummary(IrrReview $review): ?array
    {
        $prior = IrrReview::query()
            ->where('employee_user_id', $review->employee_user_id)
            ->where('year', (int) $review->year - 1)
            ->first(['id', 'year', 'status']);

        return $prior ? ['id' => $prior->id, 'year' => (int) $prior->year, 'status' => $prior->status] : null;
    }

    /** @return list<array{key: string, label: string, available: bool}> */
    private function evidenceSourcesChecklist(
        IrrReview $review,
        ?array $unifiedInsight,
        array $summaries,
        array $activities,
        array $tools,
        array $commitments,
        array $oneOnOne,
        array $scoringBySlug
    ): array {
        $hasDrivers = collect(self::BEHAVIORAL_DRIVERS)
            ->keys()
            ->contains(fn ($slug) => ($scoringBySlug[$slug] ?? null) !== null);
        $hasSelf = collect(self::SELF_ASSESSMENT_KEYS)
            ->keys()
            ->contains(fn ($slug) => ($scoringBySlug[$slug] ?? null) !== null);

        return [
            ['key' => 'individual_insights', 'label' => 'Individual Insights™', 'available' => $unifiedInsight !== null],
            ['key' => 'previous_irr', 'label' => 'Previous Individual Readiness Review™', 'available' => $this->previousIrrSummary($review) !== null],
            ['key' => 'activities', 'label' => 'Activities', 'available' => ($activities['total_submissions'] ?? 0) > 0],
            ['key' => 'commitment_completion', 'label' => 'Commitment Completion', 'available' => ($commitments['total'] ?? 0) > 0],
            ['key' => 'self_assessments', 'label' => 'Self-Assessments', 'available' => $hasSelf],
            ['key' => 'behavioral_driver_trends', 'label' => 'Behavioral Driver Trends', 'available' => $hasDrivers],
            ['key' => 'reflection_themes', 'label' => 'Reflection Themes', 'available' => false],
            ['key' => 'leader_observations', 'label' => 'Leader Observations', 'available' => $summaries !== []],
            ['key' => 'tool_usage', 'label' => 'Tool Usage', 'available' => ($tools['submissions'] ?? 0) > 0],
            ['key' => 'organizational_context', 'label' => 'Organizational Context', 'available' => false],
            ['key' => 'one_on_one', 'label' => '1-on-1 Alignment Capture™', 'available' => ($oneOnOne['total'] ?? 0) > 0],
            ['key' => 'qbr_arp_priorities', 'label' => 'QBR & ARP Priorities', 'available' => false],
        ];
    }
}
