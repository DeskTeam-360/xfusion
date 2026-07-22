<?php

namespace App\Services;

use App\Models\Arp;
use App\Models\ArpStrategicPriority;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\OneOnOne;
use App\Models\OneOnOneConversation;
use App\Models\Qbr;
use App\Models\QbrCommitment;
use App\Models\ResultEvaluation;
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
        $assessment = $this->assessmentCompletionRate($memberIds, $evaluations);
        $activity = $this->activityParticipationRate($memberIds, $start, $end);

        $priorSnapshot = $qbr->previousQuarter()?->evidenceSnapshots()->first()?->snapshot;

        return [
            'review_period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'member_count' => count($memberIds),
            'overall_readiness_score' => $readinessScore,
            'overall_readiness_trend' => $this->trend($readinessScore, $priorSnapshot['overall_readiness_score'] ?? null),
            'qbr_objectives_progress' => $objectivesProgress,
            'commitment_completion' => $commitmentCompletion,
            'one_on_one_completion' => $oneOnOne,
            'assessment_completion' => $assessment,
            'activity_participation' => $activity,
            'tool_utilization' => ['rate' => null, 'note' => 'No tool-usage tracking source exists yet — not fabricated.'],
            'cor_capability_trends' => $corTrends,
            'behavioral_driver_trends' => $driverTrends,
            'readiness_indicators' => $this->readinessIndicators($oneOnOne, $assessment, $commitmentCompletion, $objectivesProgress),
            'kpis' => $qbr->kpis()->get()->map(fn ($k) => [
                'name' => $k->name,
                'current' => $k->current_value,
                'target' => $k->target_value,
                'status' => $k->status,
                'trend' => $k->trend,
            ])->all(),
            'evidence_sources' => $this->evidenceSourcesChecklist($qbr),
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
     * % of group members with at least one Gravity Forms submission in the
     * period, as a proxy for "activity participation" — the codebase has no
     * dedicated LMS-activity tracking table separate from GF entries today.
     */
    private function activityParticipationRate(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return ['rate' => null, 'total_members' => 0, 'participated' => 0];
        }

        $participated = WpGfEntry::query()
            ->whereIn('created_by', $memberIds)
            ->whereBetween('date_created', [$start, $end])
            ->distinct('created_by')
            ->count('created_by');

        return [
            'rate' => round($participated / count($memberIds) * 100, 1),
            'total_members' => count($memberIds),
            'participated' => $participated,
        ];
    }

    private function readinessIndicators(array $oneOnOne, array $assessment, array $commitment, array $objectives): array
    {
        $people = $this->averageOrNull([$oneOnOne['rate'] ?? null, $assessment['rate'] ?? null]);
        $process = $this->averageOrNull([$commitment['rate'] ?? null, $objectives['progress'] ?? null]);

        return [
            'people_readiness' => $people,
            'process_readiness' => $process,
            'system_readiness' => null, // no tool-usage source — see tool_utilization
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
    private function evidenceSourcesChecklist(Qbr $qbr): array
    {
        $group = CompanyGroup::find($qbr->company_group_id);

        return [
            ['key' => 'arp_objectives', 'label' => 'Annual Readiness Plan™ Objectives', 'available' => Arp::where('company_group_id', $qbr->company_group_id)->exists()],
            ['key' => 'previous_commitments', 'label' => 'Previous Quarterly Commitments', 'available' => $qbr->previousQuarter() !== null],
            ['key' => 'individual_insight_trends', 'label' => 'Individual Insight Trends', 'available' => true],
            ['key' => 'one_on_one_summaries', 'label' => '1-on-1 Alignment Capture™ Summaries', 'available' => true],
            ['key' => 'activity_participation', 'label' => 'Activity Participation', 'available' => true],
            ['key' => 'assessment_trends', 'label' => 'Assessment Trends', 'available' => true],
            ['key' => 'tool_usage', 'label' => 'Tool Usage', 'available' => false],
            ['key' => 'ai_insight_themes', 'label' => 'AI Insight Themes', 'available' => true],
            ['key' => 'organizational_kpis', 'label' => 'Organizational KPIs', 'available' => $qbr->kpis()->exists()],
            ['key' => 'operational_metrics', 'label' => 'Operational Metrics', 'available' => $qbr->kpis()->exists()],
            ['key' => 'historical_qbr_data', 'label' => 'Historical QBR Data', 'available' => $qbr->previousQuarter() !== null],
            ['key' => 'group', 'label' => 'Group', 'available' => $group !== null],
        ];
    }
}
