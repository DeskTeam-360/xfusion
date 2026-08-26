<?php

namespace App\Services;

use App\Models\Arp;
use App\Models\ArpStrategicPriority;
use App\Models\Arr;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\CourseGroup;
use App\Models\CourseGroupDetail;
use App\Models\CourseList;
use App\Models\CourseScoringGroup;
use App\Models\IrrCommitment;
use App\Models\IrrReview;
use App\Models\OneOnOne;
use App\Models\OneOnOneCommitment;
use App\Models\OneOnOneConversation;
use App\Models\Qbr;
use App\Models\QbrCommitment;
use App\Models\QbrKpi;
use App\Models\ResultEvaluation;
use App\Models\WpGfEntry;
use App\Models\WpGfEntryMeta;
use Carbon\Carbon;

/**
 * ARR Step 1/2 — annual organization-wide evidence snapshot. ARR sits above
 * ARP/QBR/1-on-1/IRR in the FUSION cycle, so unlike those (per-group or
 * per-employee), this aggregates across every group and every member of one
 * company for one calendar year.
 *
 * Aggregates only sources that already exist in FUSION today. Fields
 * without a tracked source return null/false and are listed in
 * evidence_sources as unavailable — never fabricated.
 */
class ArrEvidenceService
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

    public function buildSnapshot(Arr $arr): array
    {
        $companyId = (int) $arr->company_id;
        $year = (int) $arr->year;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        $memberIds = $this->companyMemberUserIds($companyId);

        $arpSummary = $this->arpSummary($companyId, $year);
        $qbrSummary = $this->qbrSummary($companyId, $year);
        $oneOnOneSummary = $this->oneOnOneSummary($companyId, $start, $end);
        $irrSummary = $this->irrSummary($companyId, $year);
        $individualInsights = $this->individualInsightsSummary($memberIds, $start, $end);
        $activities = $this->activitiesSummary($memberIds, $start, $end);
        $selfAssessments = $this->companyScoringAverages($memberIds);
        $toolUsage = $this->toolUsageSummary($memberIds, $start, $end);
        $operationalKpis = $this->operationalKpisSummary($qbrSummary['qbr_ids']);
        $organizationalKpis = $this->organizationalKpisSummary($arpSummary['arp_ids']);
        $historicalCommitments = $this->historicalCommitmentsSummary($oneOnOneSummary['one_on_one_ids'], $qbrSummary['qbr_ids'], $irrSummary['irr_ids']);

        return [
            'review_period' => ['year' => $year, 'start' => $start->toDateString(), 'end' => $end->toDateString()],
            'company_id' => $companyId,
            'evidence_sources' => $this->evidenceSourcesChecklist(
                $arpSummary,
                $qbrSummary,
                $oneOnOneSummary,
                $irrSummary,
                $individualInsights,
                $activities,
                $selfAssessments,
                $toolUsage,
                $operationalKpis,
                $organizationalKpis,
                $historicalCommitments
            ),
            'annual_readiness_plan' => $arpSummary,
            'quarterly_business_reviews' => $qbrSummary,
            'one_on_one' => $oneOnOneSummary,
            'individual_readiness_reviews' => $irrSummary,
            'individual_insights' => $individualInsights,
            'activities' => $activities,
            'self_assessments' => $selfAssessments,
            'tool_usage' => $toolUsage,
            'operational_kpis' => $operationalKpis,
            'organizational_kpis' => $organizationalKpis,
            'historical_commitments' => $historicalCommitments,
            // No tracked source anywhere in FUSION yet - never fabricated.
            'group_readiness_trends' => null,
            'executive_dashboard_trends' => null,
            'reflection_themes' => null,
            'additional_platform_intelligence' => null,
        ];
    }

    /** @return list<int> Every user_id belonging to any group in this company. */
    private function companyMemberUserIds(int $companyId): array
    {
        $groupIds = CompanyGroup::query()->where('company_id', $companyId)->pluck('id');
        if ($groupIds->isEmpty()) {
            return [];
        }

        return CompanyGroupDetail::query()
            ->whereIn('company_group_id', $groupIds)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array{count: int, active_count: int, arp_ids: list<int>} */
    private function arpSummary(int $companyId, int $year): array
    {
        $arps = Arp::query()->where('company_id', $companyId)->where('year', $year)->get(['id', 'status']);

        return [
            'count' => $arps->count(),
            'active_count' => $arps->where('status', Arp::STATUS_ACTIVE)->count(),
            'arp_ids' => $arps->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /** @return array{count: int, held_count: int, qbr_ids: list<int>} */
    private function qbrSummary(int $companyId, int $year): array
    {
        $qbrs = Qbr::query()->where('company_id', $companyId)->where('year', $year)->get(['id', 'status']);

        return [
            'count' => $qbrs->count(),
            'held_count' => $qbrs->whereIn('status', [Qbr::STATUS_HELD, Qbr::STATUS_CLOSED])->count(),
            'qbr_ids' => $qbrs->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /** @return array{total: int, completed: int, one_on_one_ids: list<int>} */
    private function oneOnOneSummary(int $companyId, Carbon $start, Carbon $end): array
    {
        $pairIds = OneOnOne::query()->where('company_id', $companyId)->pluck('id');

        if ($pairIds->isEmpty()) {
            return ['total' => 0, 'completed' => 0, 'one_on_one_ids' => []];
        }

        $conversations = OneOnOneConversation::query()
            ->whereIn('one_on_one_id', $pairIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('held_at', [$start, $end])
                    ->orWhereBetween('scheduled_at', [$start, $end]);
            })
            ->get(['id', 'status']);

        return [
            'total' => $conversations->count(),
            'completed' => $conversations->where('status', OneOnOneConversation::STATUS_COMPLETED)->count(),
            'one_on_one_ids' => $pairIds->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /** @return array{count: int, published_count: int, irr_ids: list<int>} */
    private function irrSummary(int $companyId, int $year): array
    {
        $irrs = IrrReview::query()->where('company_id', $companyId)->where('year', $year)->get(['id', 'status']);

        return [
            'count' => $irrs->count(),
            'published_count' => $irrs->where('status', IrrReview::STATUS_PUBLISHED)->count(),
            'irr_ids' => $irrs->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /** @param  list<int>  $memberIds */
    private function individualInsightsSummary(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return ['count' => 0];
        }

        $count = ResultEvaluation::query()
            ->whereIn('user_id', $memberIds)
            ->where('scoring_group_id', 0)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return ['count' => $count];
    }

    /** @param  list<int>  $memberIds */
    private function activitiesSummary(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return ['total_submissions' => 0, 'programs_with_data' => 0, 'programs_total' => count(self::ACTIVITY_PROGRAM_TYPES)];
        }

        $groupsByType = CourseGroup::query()
            ->whereIn('type', self::ACTIVITY_PROGRAM_TYPES)
            ->get()
            ->groupBy('type');

        $total = 0;
        $programsWithData = 0;
        foreach (self::ACTIVITY_PROGRAM_TYPES as $type) {
            $groupIds = $groupsByType->get($type, collect())->pluck('id')->map(fn ($id) => (int) $id)->all();
            $count = $this->submissionCountForCourseGroups($groupIds, $memberIds, $start, $end);
            $total += $count;
            if ($count > 0) {
                $programsWithData++;
            }
        }

        return [
            'total_submissions' => $total,
            'programs_with_data' => $programsWithData,
            'programs_total' => count(self::ACTIVITY_PROGRAM_TYPES),
        ];
    }

    /** @param  list<int>  $memberIds */
    private function toolUsageSummary(array $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds === []) {
            return ['submissions' => 0];
        }

        $toolGroupIds = CourseGroup::query()->where('tools', 1)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return ['submissions' => $this->submissionCountForCourseGroups($toolGroupIds, $memberIds, $start, $end)];
    }

    /** @param  list<int>  $courseGroupIds  @param  list<int>  $userIds */
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
            ->whereNotNull('wp_gf_form_id')
            ->where('wp_gf_form_id', '>', 0)
            ->pluck('wp_gf_form_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($formIds === []) {
            return 0;
        }

        return WpGfEntry::query()
            ->whereIn('form_id', $formIds)
            ->whereIn('created_by', $userIds)
            ->whereIn('status', ['active', 'Active', 'ACTIVE'])
            ->whereBetween('date_created', [$start, $end])
            ->count();
    }

    /**
     * Company-wide average of the 5 Behavioral Drivers™ + 5 COR
     * capabilities, averaged across every member who has a score. Same
     * weighted-scoring-group logic as IrrEvidenceService, just averaged
     * across many users instead of one.
     *
     * @param  list<int>  $memberIds
     */
    private function companyScoringAverages(array $memberIds): array
    {
        $slugs = array_keys(self::BEHAVIORAL_DRIVERS + self::SELF_ASSESSMENT_KEYS);
        $out = array_fill_keys($slugs, null);

        if ($memberIds === []) {
            return $out;
        }

        $groups = CourseScoringGroup::with('details')->get();
        $bySlug = array_fill_keys($slugs, []);

        foreach ($memberIds as $userId) {
            foreach ($groups as $group) {
                $slug = $this->titleToSlug((string) $group->title);
                if (! array_key_exists($slug, $bySlug)) {
                    continue;
                }
                $avg = $this->weightedGroupAverage($group, $userId);
                if ($avg !== null) {
                    $bySlug[$slug][] = $avg;
                }
            }
        }

        foreach ($slugs as $slug) {
            $values = $bySlug[$slug];
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

        if ($latestEntryByForm === []) {
            return null;
        }

        $entryIds = array_values($latestEntryByForm);
        $valueMap = [];
        WpGfEntryMeta::query()
            ->whereIn('entry_id', $entryIds)
            ->get(['entry_id', 'meta_key', 'meta_value'])
            ->each(function ($m) use (&$valueMap) {
                $key = explode('.', (string) $m->meta_key)[0] ?? '';
                if ($key === '') {
                    return;
                }
                $valueMap[(int) $m->entry_id][(int) $key] = $m->meta_value;
            });

        $weightedSum = 0.0;
        $weightTotal = 0.0;
        foreach ($details as $detail) {
            $formId = (int) $detail->form_id;
            $entryId = $latestEntryByForm[$formId] ?? null;
            if ($entryId === null) {
                continue;
            }
            $raw = $valueMap[$entryId][(int) $detail->field_id] ?? null;
            $score = $this->parseScaleScore($raw);
            if ($score === null) {
                continue;
            }
            $weight = (float) ($detail->weight ?? 1.0);
            $weightedSum += $score * $weight;
            $weightTotal += $weight;
        }

        return $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null;
    }

    private function parseScaleScore(?string $raw): ?float
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $num = (float) $raw;

        return $num >= 1 && $num <= 5 ? $num : null;
    }

    private function titleToSlug(string $title): string
    {
        $t = strtolower(trim($title));
        $t = preg_replace('/[^\w\s]/u', '', $t) ?? $t;
        $t = preg_replace('/\s+/', '_', trim($t)) ?? $t;

        return $t;
    }

    /** @param  list<int>  $qbrIds */
    private function operationalKpisSummary(array $qbrIds): array
    {
        if ($qbrIds === []) {
            return ['count' => 0, 'on_track' => 0, 'at_risk' => 0, 'off_track' => 0];
        }

        $kpis = QbrKpi::query()->whereIn('qbr_id', $qbrIds)->get(['status']);

        return [
            'count' => $kpis->count(),
            'on_track' => $kpis->where('status', QbrKpi::STATUS_ON_TRACK)->count(),
            'at_risk' => $kpis->where('status', QbrKpi::STATUS_AT_RISK)->count(),
            'off_track' => $kpis->where('status', QbrKpi::STATUS_OFF_TRACK)->count(),
        ];
    }

    /**
     * ARP Strategic Priorities' org_kpi field, across every ARP for this
     * company/year — the closest real match to "Organizational KPIs" (as
     * opposed to QBR's per-quarter "Operational KPIs").
     *
     * @param  list<int>  $arpIds
     */
    private function organizationalKpisSummary(array $arpIds): array
    {
        if ($arpIds === []) {
            return ['count' => 0, 'items' => []];
        }

        $items = ArpStrategicPriority::query()
            ->whereIn('arp_id', $arpIds)
            ->whereNotNull('org_kpi')
            ->where('org_kpi', '!=', '')
            ->get(['title', 'org_kpi', 'status'])
            ->map(fn (ArpStrategicPriority $p) => [
                'title' => $p->title,
                'org_kpi' => $p->org_kpi,
                'status' => $p->status,
            ])
            ->values()
            ->all();

        return ['count' => count($items), 'items' => $items];
    }

    /**
     * @param  list<int>  $oneOnOneIds
     * @param  list<int>  $qbrIds
     * @param  list<int>  $irrIds
     */
    private function historicalCommitmentsSummary(array $oneOnOneIds, array $qbrIds, array $irrIds): array
    {
        $oneOnOneConversationIds = $oneOnOneIds === [] ? [] : OneOnOneConversation::query()
            ->whereIn('one_on_one_id', $oneOnOneIds)
            ->pluck('id');

        $counts = ['total' => 0, 'done' => 0, 'in_progress' => 0, 'open' => 0];

        if ($oneOnOneConversationIds !== []) {
            $this->addCommitmentCounts($counts, OneOnOneCommitment::query()->whereIn('conversation_id', $oneOnOneConversationIds)->get(['status']));
        }
        if ($qbrIds !== []) {
            $this->addCommitmentCounts($counts, QbrCommitment::query()->whereIn('qbr_id', $qbrIds)->get(['status']));
        }
        if ($irrIds !== []) {
            $this->addCommitmentCounts($counts, IrrCommitment::query()->whereIn('review_id', $irrIds)->get(['status']));
        }

        return $counts;
    }

    /** @param array{total: int, done: int, in_progress: int, open: int} $counts */
    private function addCommitmentCounts(array &$counts, $rows): void
    {
        foreach ($rows as $row) {
            $counts['total']++;
            $status = (string) $row->status;
            if ($status === 'done') {
                $counts['done']++;
            } elseif ($status === 'in_progress') {
                $counts['in_progress']++;
            } else {
                $counts['open']++;
            }
        }
    }

    /** @return list<array{key: string, label: string, available: bool}> */
    private function evidenceSourcesChecklist(
        array $arpSummary,
        array $qbrSummary,
        array $oneOnOneSummary,
        array $irrSummary,
        array $individualInsights,
        array $activities,
        array $selfAssessments,
        array $toolUsage,
        array $operationalKpis,
        array $organizationalKpis,
        array $historicalCommitments
    ): array {
        $hasSelfAssessment = collect($selfAssessments)->contains(fn ($v) => $v !== null);

        return [
            ['key' => 'annual_readiness_plan', 'label' => 'Annual Readiness Plan™', 'available' => $arpSummary['count'] > 0],
            ['key' => 'quarterly_business_reviews', 'label' => 'Quarterly Business Reviews™', 'available' => $qbrSummary['count'] > 0],
            ['key' => 'one_on_one', 'label' => '1-on-1 Alignment Capture™', 'available' => $oneOnOneSummary['total'] > 0],
            ['key' => 'individual_readiness_reviews', 'label' => 'Individual Readiness Reviews™', 'available' => $irrSummary['count'] > 0],
            ['key' => 'individual_insights', 'label' => 'Individual Insights™', 'available' => $individualInsights['count'] > 0],
            ['key' => 'group_readiness_trends', 'label' => 'Group Readiness Trends', 'available' => false],
            ['key' => 'executive_dashboard_trends', 'label' => 'Executive Dashboard Trends', 'available' => false],
            ['key' => 'activities', 'label' => 'Activities', 'available' => $activities['total_submissions'] > 0],
            ['key' => 'self_assessments', 'label' => 'Self-Assessments', 'available' => $hasSelfAssessment],
            ['key' => 'reflection_themes', 'label' => 'Reflection Themes (AI extracted only)', 'available' => false],
            ['key' => 'tool_usage', 'label' => 'Tool Usage', 'available' => $toolUsage['submissions'] > 0],
            ['key' => 'operational_kpis', 'label' => 'Operational KPIs', 'available' => $operationalKpis['count'] > 0],
            ['key' => 'organizational_kpis', 'label' => 'Organizational KPIs', 'available' => $organizationalKpis['count'] > 0],
            ['key' => 'historical_commitments', 'label' => 'Historical Commitments', 'available' => $historicalCommitments['total'] > 0],
            ['key' => 'additional_platform_intelligence', 'label' => 'Additional Platform Intelligence', 'available' => false],
        ];
    }
}
