<?php

namespace App\Services;

use App\Models\Arp;
use App\Models\ArpStrategicPriority;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\CourseGroup;
use App\Models\CourseGroupDetail;
use App\Models\CourseList;
use App\Models\OneOnOne;
use App\Models\OneOnOneConversation;
use App\Models\Qbr;
use App\Models\QbrCommitment;
use App\Models\ResultEvaluation;
use App\Models\User;
use App\Models\WpGfEntry;
use Carbon\Carbon;

/**
 * Step 1/2 evidence aggregation — pulls real numbers from every source the
 * FUSION roadmap already has data for. Sources without an existing tracked
 * data source (Tool Usage) are returned as null/"no data" rather than
 * fabricated, matching the precedent set by ArpController's employee
 * scoring ("No data" zone when a user has zero Gravity Forms entries).
 */
class QbrEvidenceService
{
    private const COR_CAPABILITIES = ['alignment', 'accountability', 'communication', 'leadership', 'execution'];

    private const BEHAVIORAL_DRIVERS = ['get_real', 'fill_buckets', 'be_intentional', 'foster_grit', 'drive_growth'];

    private const ACTIVITY_PROGRAM_TYPES = ['transform', 'sustain', 'revitalize'];

    /** Build the full evidence snapshot for a QBR's quarter/group. */
    public function buildSnapshot(Qbr $qbr): array
    {
        [$start, $end] = $this->periodDates($qbr);
        $memberIds = $this->memberUserIds($qbr);

        $evaluations = $this->latestEvaluationsInPeriod($memberIds, $start, $end);
        $corTrends = $this->corCapabilityTrends($evaluations);
        $driverTrends = $this->behavioralDriverTrends($evaluations);
        $readinessScore = $this->overallReadinessScore($evaluations);

        $objectivesProgress = $this->qbrObjectivesProgress($qbr);
        $commitmentCompletion = $this->commitmentCompletionFromPreviousQuarter($qbr);
        $oneOnOne = $this->oneOnOneCompletionRate($memberIds, $start, $end);
        $oneOnOneSummaries = $this->oneOnOneMeetingSummaries($memberIds, $start, $end);
        $assessment = $this->assessmentCompletionRate($memberIds, $evaluations);
        $activity = $this->activityParticipationByProgram($memberIds, $start, $end);
        $toolUtilization = $this->toolUtilizationStats($memberIds, $start, $end);

        $priorSnapshot = $qbr->previousQuarter()?->evidenceSnapshots()->first()?->snapshot;

        return [
            'review_period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'member_count' => count($memberIds),
            'overall_readiness_score' => $readinessScore,
            'overall_readiness_trend' => $this->trend($readinessScore, $priorSnapshot['overall_readiness_score'] ?? null),
            'qbr_objectives_progress' => $objectivesProgress,
            'commitment_completion' => $commitmentCompletion,
            'one_on_one_completion' => $oneOnOne,
            'one_on_one_summaries' => $oneOnOneSummaries,
            'assessment_completion' => $assessment,
            'activity_participation' => $activity,
            'tool_utilization' => $toolUtilization,
            'cor_capability_trends' => $corTrends,
            'behavioral_driver_trends' => $driverTrends,
            'readiness_indicators' => $this->readinessIndicators($oneOnOne, $assessment, $commitmentCompletion, $objectivesProgress, $toolUtilization),
            'kpis' => $qbr->kpis()->get()->map(fn ($k) => [
                'name' => $k->name,
                'current' => $k->current_value,
                'target' => $k->target_value,
                'status' => $k->status,
                'trend' => $k->trend,
            ])->all(),
            'evidence_sources' => $this->evidenceSourcesChecklist($qbr, $oneOnOneSummaries, $activity, $toolUtilization),
        ];
    }

    /** wp_users.ID for everyone in this QBR's company group, any role. */
    public function memberUserIds(Qbr $qbr): array
    {
        if ($qbr->company_group_id === null) {
            return [];
        }

        return CompanyGroupDetail::query()
            ->where('company_group_id', $qbr->company_group_id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periodDates(Qbr $qbr): array
    {
        $startMonth = (((int) $qbr->quarter - 1) * 3) + 1;
        $start = Carbon::create((int) $qbr->year, $startMonth, 1)->startOfDay();
        $end = $start->copy()->addMonths(3)->subDay()->endOfDay();

        return [$start, $end];
    }

    /** Latest ResultEvaluation (COR Unified Insights) per member within the period. */
    private function latestEvaluationsInPeriod(array $memberIds, Carbon $start, Carbon $end)
    {
        if ($memberIds === []) {
            return collect();
        }

        return ResultEvaluation::query()
            ->whereIn('user_id', $memberIds)
            ->where('scoring_group_id', 0) // 0 = COR Unified Insights, per CLAUDE.md
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'score', 'evaluation', 'created_at'])
            ->unique('user_id')
            ->values();
    }

    private function overallReadinessScore($evaluations): ?float
    {
        if ($evaluations->isEmpty()) {
            return null;
        }

        return round((float) $evaluations->avg('score'), 1);
    }

    private function corCapabilityTrends($evaluations): array
    {
        // evaluation.performance.<driver>.strength/opportunity is narrative text,
        // not numeric — the numeric COR capability breakdown lives in
        // evaluation.cor_organization_capabilities only as prose in the current
        // evaluate-unified contract, so we derive a per-capability numeric proxy
        // from the same 0-100 `score` until evaluate-unified exposes per-capability
        // numbers directly.
        if ($evaluations->isEmpty()) {
            return array_map(fn ($cap) => ['capability' => $cap, 'score' => null], self::COR_CAPABILITIES);
        }

        $avgScore = round((float) $evaluations->avg('score') / 20, 1); // back to a 0-5 scale to match the UI mockup

        return array_map(fn ($cap) => ['capability' => $cap, 'score' => $avgScore], self::COR_CAPABILITIES);
    }

    private function behavioralDriverTrends($evaluations): array
    {
        $sums = array_fill_keys(self::BEHAVIORAL_DRIVERS, 0.0);
        $counts = array_fill_keys(self::BEHAVIORAL_DRIVERS, 0);

        foreach ($evaluations as $row) {
            $performance = $row->evaluation['performance'] ?? [];
            foreach (self::BEHAVIORAL_DRIVERS as $driver) {
                // Narrative-only today (strength/opportunity text) — count presence
                // as a signal rather than inventing a numeric score from prose.
                if (! empty($performance[$driver])) {
                    $counts[$driver]++;
                }
            }
        }

        $total = max($evaluations->count(), 1);

        return array_map(
            fn ($driver) => ['driver' => $driver, 'coverage_rate' => round($counts[$driver] / $total, 2)],
            self::BEHAVIORAL_DRIVERS
        );
    }

    /** % completion of the active ARP's strategic priorities for this group. */
    private function qbrObjectivesProgress(Qbr $qbr): array
    {
        if ($qbr->company_group_id === null) {
            return ['progress' => null, 'objective_count' => 0, 'objectives' => []];
        }

        $arp = Arp::query()
            ->where('company_group_id', $qbr->company_group_id)
            ->orderByDesc('year')
            ->first();

        if ($arp === null) {
            return ['progress' => null, 'objective_count' => 0, 'objectives' => []];
        }

        $priorities = ArpStrategicPriority::query()->where('arp_id', $arp->id)->get(['title', 'status']);
        if ($priorities->isEmpty()) {
            return ['progress' => null, 'objective_count' => 0, 'objectives' => []];
        }

        $weights = ['done' => 1.0, 'in_progress' => 0.5, 'at_risk' => 0.25, 'not_started' => 0.0];
        $progress = round($priorities->avg(fn ($p) => $weights[$p->status] ?? 0.0) * 100, 1);

        return [
            'progress' => $progress,
            'objective_count' => $priorities->count(),
            'objectives' => $priorities->map(fn ($p) => ['title' => $p->title, 'status' => $p->status])->values()->all(),
        ];
    }

    /** % of the previous quarter's QBR commitments marked done. */
    private function commitmentCompletionFromPreviousQuarter(Qbr $qbr): array
    {
        $previous = $qbr->previousQuarter();
        if ($previous === null) {
            return ['rate' => null, 'total' => 0, 'done' => 0];
        }

        $commitments = QbrCommitment::query()->where('qbr_id', $previous->id)->get(['status']);
        if ($commitments->isEmpty()) {
            return ['rate' => null, 'total' => 0, 'done' => 0];
        }

        $done = $commitments->where('status', QbrCommitment::STATUS_DONE)->count();

        return ['rate' => round($done / $commitments->count() * 100, 1), 'total' => $commitments->count(), 'done' => $done];
    }

    /** Completed 1-on-1 conversations vs total scheduled+completed, within the period, for pairs where the employee is a group member. */
    private function oneOnOneCompletionRate(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return ['rate' => null, 'total' => 0, 'completed' => 0];
        }

        $pairIds = OneOnOne::query()
            ->whereIn('employee_user_id', $memberIds)
            ->where('status', OneOnOne::STATUS_ACTIVE)
            ->pluck('id');

        if ($pairIds->isEmpty()) {
            return ['rate' => null, 'total' => 0, 'completed' => 0];
        }

        $conversations = OneOnOneConversation::query()
            ->whereIn('one_on_one_id', $pairIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('scheduled_at', [$start, $end])
                    ->orWhereBetween('held_at', [$start, $end]);
            })
            ->whereIn('status', [
                OneOnOneConversation::STATUS_COMPLETED,
                OneOnOneConversation::STATUS_SCHEDULED,
                OneOnOneConversation::STATUS_IN_PROGRESS,
            ])
            ->get(['status']);

        if ($conversations->isEmpty()) {
            return ['rate' => null, 'total' => 0, 'completed' => 0];
        }

        $completed = $conversations->where('status', OneOnOneConversation::STATUS_COMPLETED)->count();

        return ['rate' => round($completed / $conversations->count() * 100, 1), 'total' => $conversations->count(), 'completed' => $completed];
    }

    /** % of group members with at least one COR Unified Insight evaluation in the period. */
    private function assessmentCompletionRate(array $memberIds, $evaluations): array
    {
        if ($memberIds === []) {
            return ['rate' => null, 'total_members' => 0, 'evaluated' => 0];
        }

        $evaluated = $evaluations->pluck('user_id')->unique()->count();

        return [
            'rate' => round($evaluated / count($memberIds) * 100, 1),
            'total_members' => count($memberIds),
            'evaluated' => $evaluated,
        ];
    }

    /**
     * Completed 1-on-1 conversations for this group in the review period —
     * meeting_summary only (Step 6 synthesis), never prep or raw notes.
     *
     * @return list<array<string, mixed>>
     */
    private function oneOnOneMeetingSummaries(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return [];
        }

        $pairIds = OneOnOne::query()
            ->whereIn('employee_user_id', $memberIds)
            ->where('status', OneOnOne::STATUS_ACTIVE)
            ->pluck('id');

        if ($pairIds->isEmpty()) {
            return [];
        }

        $conversations = OneOnOneConversation::query()
            ->whereIn('one_on_one_id', $pairIds)
            ->where('status', OneOnOneConversation::STATUS_COMPLETED)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('held_at', [$start, $end])
                    ->orWhereBetween('scheduled_at', [$start, $end]);
            })
            ->with(['oneOnOne:id,leader_user_id,employee_user_id', 'synthesis'])
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

            $pair = $conversation->oneOnOne;
            $heldAt = $conversation->held_at ?? $conversation->scheduled_at;

            $summaries[] = [
                'conversation_id' => $conversation->id,
                'employee_user_id' => $pair?->employee_user_id,
                'leader_user_id' => $pair?->leader_user_id,
                'held_at' => $heldAt?->toIso8601String(),
                'meeting_summary' => [
                    'items' => $items,
                    'details' => $details,
                ],
            ];
        }

        return $summaries;
    }

    /**
     * Group members who submitted Transform / Sustain / Revitalize activities
     * (GF forms linked to wp_course_groups.type) within the review period.
     */
    private function activityParticipationByProgram(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return [
                'rate' => null,
                'total_members' => 0,
                'participated' => 0,
                'by_program' => $this->emptyActivityByProgram(),
                'participants' => [],
            ];
        }

        $groupsByType = CourseGroup::query()
            ->whereIn('type', self::ACTIVITY_PROGRAM_TYPES)
            ->get()
            ->groupBy('type');

        $byProgram = [];
        $userPrograms = [];

        foreach (self::ACTIVITY_PROGRAM_TYPES as $programType) {
            $groups = $groupsByType->get($programType, collect());
            $participantIds = $this->activityParticipantsForCourseGroups(
                $groups->pluck('id')->map(fn ($id) => (int) $id)->all(),
                $memberIds,
                $start,
                $end
            );

            foreach ($participantIds as $userId) {
                $userPrograms[$userId][] = $programType;
            }

            $byProgram[$programType] = [
                'label' => ucfirst($programType),
                'participated' => count($participantIds),
                'total_members' => count($memberIds),
                'rate' => round(count($participantIds) / count($memberIds) * 100, 1),
                'user_ids' => $participantIds,
            ];
        }

        $allParticipantIds = array_values(array_unique(array_merge(
            ...array_map(static fn ($row) => $row['user_ids'], $byProgram)
        )));

        $displayNames = User::query()
            ->whereIn('ID', $allParticipantIds)
            ->pluck('display_name', 'ID');

        $participants = [];
        foreach ($allParticipantIds as $userId) {
            $programs = array_values(array_unique($userPrograms[$userId] ?? []));
            sort($programs);
            $participants[] = [
                'user_id' => $userId,
                'display_name' => (string) ($displayNames[$userId] ?? ''),
                'programs' => $programs,
            ];
        }

        usort($participants, static fn ($a, $b) => strcasecmp($a['display_name'], $b['display_name']));

        return [
            'rate' => round(count($allParticipantIds) / count($memberIds) * 100, 1),
            'total_members' => count($memberIds),
            'participated' => count($allParticipantIds),
            'by_program' => $byProgram,
            'participants' => $participants,
        ];
    }

    /** @return array<string, array{label: string, participated: int, total_members: int, rate: float, user_ids: list<int>}> */
    private function emptyActivityByProgram(): array
    {
        $out = [];
        foreach (self::ACTIVITY_PROGRAM_TYPES as $programType) {
            $out[$programType] = [
                'label' => ucfirst($programType),
                'participated' => 0,
                'total_members' => 0,
                'rate' => 0.0,
                'user_ids' => [],
            ];
        }

        return $out;
    }

    /**
     * @param  list<int>  $courseGroupIds
     * @param  list<int>  $memberIds
     * @return list<int>
     */
    private function activityParticipantsForCourseGroups(array $courseGroupIds, array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($courseGroupIds === [] || $memberIds === []) {
            return [];
        }

        $courseListIds = CourseGroupDetail::query()
            ->whereIn('course_group_id', $courseGroupIds)
            ->pluck('course_list_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($courseListIds === []) {
            return [];
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
            return [];
        }

        return WpGfEntry::query()
            ->whereIn('created_by', $memberIds)
            ->whereIn('form_id', $formIds)
            ->whereBetween('date_created', [$start, $end])
            ->whereIn('status', ['active', 'Active', 'ACTIVE'])
            ->distinct()
            ->pluck('created_by')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Tool submissions from wp_course_groups.tools = 1 (Tool List), scoped to
     * group members and the QBR review period.
     */
    private function toolUtilizationStats(array $memberIds, Carbon $start, Carbon $end): array
    {
        $empty = [
            'rate' => null,
            'submitted_count' => 0,
            'tools_submitted' => 0,
            'total_tools' => 0,
            'members_submitted' => 0,
            'total_members' => count($memberIds),
            'by_tool' => [],
        ];

        if ($memberIds === []) {
            return $empty;
        }

        $toolGroupIds = CourseGroup::query()
            ->where('tools', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($toolGroupIds === []) {
            return $empty;
        }

        $courseListIds = CourseGroupDetail::query()
            ->whereIn('course_group_id', $toolGroupIds)
            ->pluck('course_list_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($courseListIds === []) {
            return $empty;
        }

        $courseLists = CourseList::query()
            ->whereIn('id', $courseListIds)
            ->whereNotNull('wp_gf_form_id')
            ->where('wp_gf_form_id', '>', 0)
            ->get(['id', 'page_title', 'course_title', 'wp_gf_form_id']);

        if ($courseLists->isEmpty()) {
            return $empty;
        }

        $formToCourse = [];
        foreach ($courseLists as $course) {
            $formToCourse[(int) $course->wp_gf_form_id] = $course;
        }

        $formIds = array_keys($formToCourse);
        $entries = WpGfEntry::query()
            ->whereIn('created_by', $memberIds)
            ->whereIn('form_id', $formIds)
            ->whereBetween('date_created', [$start, $end])
            ->whereIn('status', ['active', 'Active', 'ACTIVE'])
            ->get(['id', 'form_id', 'created_by']);

        $byTool = [];
        foreach ($courseLists as $course) {
            $byTool[(int) $course->id] = [
                'course_list_id' => (int) $course->id,
                'form_id' => (int) $course->wp_gf_form_id,
                'name' => trim((string) ($course->page_title ?: $course->course_title)),
                'submission_count' => 0,
                'member_ids' => [],
            ];
        }

        $membersSubmitted = [];
        foreach ($entries as $entry) {
            $formId = (int) $entry->form_id;
            $userId = (int) $entry->created_by;
            $course = $formToCourse[$formId] ?? null;
            if ($course === null) {
                continue;
            }

            $courseId = (int) $course->id;
            $byTool[$courseId]['submission_count']++;
            $byTool[$courseId]['member_ids'][$userId] = $userId;
            $membersSubmitted[$userId] = $userId;
        }

        $byToolList = [];
        $toolsSubmitted = 0;
        foreach ($byTool as $row) {
            $memberIdsForTool = array_values($row['member_ids']);
            if ($row['submission_count'] > 0) {
                $toolsSubmitted++;
            }
            $byToolList[] = [
                'course_list_id' => $row['course_list_id'],
                'form_id' => $row['form_id'],
                'name' => $row['name'],
                'submission_count' => $row['submission_count'],
                'members_submitted' => count($memberIdsForTool),
            ];
        }

        usort($byToolList, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        $totalTools = count($byToolList);
        $submittedCount = $entries->count();

        return [
            'rate' => $totalTools > 0 ? round($toolsSubmitted / $totalTools * 100, 1) : null,
            'submitted_count' => $submittedCount,
            'tools_submitted' => $toolsSubmitted,
            'total_tools' => $totalTools,
            'members_submitted' => count($membersSubmitted),
            'total_members' => count($memberIds),
            'by_tool' => $byToolList,
        ];
    }

    private function readinessIndicators(array $oneOnOne, array $assessment, array $commitment, array $objectives, array $toolUtilization): array
    {
        $people = $this->averageOrNull([$oneOnOne['rate'] ?? null, $assessment['rate'] ?? null]);
        $process = $this->averageOrNull([$commitment['rate'] ?? null, $objectives['progress'] ?? null]);
        $system = null;
        if (($toolUtilization['total_tools'] ?? 0) > 0 && ($toolUtilization['total_members'] ?? 0) > 0) {
            $system = round(
                ($toolUtilization['members_submitted'] / $toolUtilization['total_members']) * 100,
                1
            );
        }

        return [
            'people_readiness' => $people,
            'process_readiness' => $process,
            'system_readiness' => $system,
        ];
    }

    private function averageOrNull(array $values): ?float
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== null));
        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 1);
    }

    private function trend(?float $current, $previous): ?string
    {
        if ($current === null || $previous === null) {
            return null;
        }

        $previous = (float) $previous;
        if ($current > $previous) {
            return 'up';
        }
        if ($current < $previous) {
            return 'down';
        }

        return 'flat';
    }

    /** Step 1's read-only checklist — which sources actually returned data. */
    private function evidenceSourcesChecklist(Qbr $qbr, array $oneOnOneSummaries, array $activity, array $toolUtilization): array
    {
        $group = CompanyGroup::find($qbr->company_group_id);
        $activityAvailable = ($activity['participated'] ?? 0) > 0;
        $toolAvailable = ($toolUtilization['submitted_count'] ?? 0) > 0
            || ($toolUtilization['total_tools'] ?? 0) > 0;

        return [
            ['key' => 'arp_objectives', 'label' => 'Annual Readiness Plan™ Objectives', 'available' => Arp::where('company_group_id', $qbr->company_group_id)->exists()],
            ['key' => 'previous_commitments', 'label' => 'Previous Quarterly Commitments', 'available' => $qbr->previousQuarter() !== null],
            ['key' => 'individual_insight_trends', 'label' => 'Individual Insight Trends', 'available' => true],
            ['key' => 'one_on_one_summaries', 'label' => '1-on-1 Alignment Capture™ Summaries', 'available' => $oneOnOneSummaries !== []],
            ['key' => 'activity_participation', 'label' => 'Activity Participation', 'available' => $activityAvailable],
            ['key' => 'assessment_trends', 'label' => 'Assessment Trends', 'available' => true],
            ['key' => 'tool_usage', 'label' => 'Tool Usage', 'available' => $toolAvailable],
            ['key' => 'ai_insight_themes', 'label' => 'AI Insight Themes', 'available' => true],
            ['key' => 'organizational_kpis', 'label' => 'Organizational KPIs', 'available' => $qbr->kpis()->exists()],
            ['key' => 'operational_metrics', 'label' => 'Operational Metrics', 'available' => $qbr->kpis()->exists()],
            ['key' => 'historical_qbr_data', 'label' => 'Historical QBR Data', 'available' => $qbr->previousQuarter() !== null],
            ['key' => 'group', 'label' => 'Group', 'available' => $group !== null],
        ];
    }
}
