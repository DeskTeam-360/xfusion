<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arp;
use App\Models\ArpLearning;
use App\Models\ArpReadinessPriority;
use App\Models\ArpStrategicPriority;
use App\Models\ArpVersion;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Services\ArpAiService;
use App\Services\ArpEvidenceService;
use App\Services\ArpPlanService;
use App\Services\OneOnOneCompanyGroupSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ARP picker bridge for the WordPress [fusion_arp_wizard] shortcode.
 *
 * Business rule: one ARP exists per (company, calendar year) — enforced by
 * the `arp_company_year_uq` unique key on wp_fusion_arps. ARP is
 * organization-wide rather than scoped to a single company group: any user
 * who leads at least one company group may create/edit that company's ARP;
 * any member of any group in the company may view it read-only. The
 * "create new ARP" form still picks a group (leader-identification UX),
 * and store() resolves company_id from it, but the underlying ARP row
 * itself is one-per-company-per-year — same pattern as ARR.
 */
class ArpController extends Controller
{
    /** Groups the given user leads, with their parent company name for display. */
    public function leadableCompanies(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $groups = CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->whereHas('companyGroup')
            ->with('companyGroup:id,company_id,title', 'companyGroup.company:id,title')
            ->get()
            ->pluck('companyGroup')
            ->filter()
            ->unique('id')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $groups->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->title . ($g->company ? ' (' . $g->company->title . ')' : ''),
                'company_id' => $g->company_id,
            ]),
        ]);
    }

    /** ARPs for companies this user belongs to (any role, via any group), newest year first. */
    public function index(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        // Members see the ARPs of any company they belong to (view-only);
        // leaders additionally get edit rights, flagged per-row via can_edit.
        $memberCompanyIds = $this->memberCompanyIds($userId);
        if ($memberCompanyIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => [], 'has_access' => false]);
        }

        $leadableCompanyIds = $this->leadableCompanyIds($userId);

        $arps = Arp::query()
            ->whereIn('company_id', $memberCompanyIds)
            ->with(['company:id,title'])
            ->orderByDesc('year')
            ->get();

        return response()->json([
            'success' => true,
            'has_access' => true,
            'can_create' => $leadableCompanyIds->isNotEmpty(),
            'data' => $arps->map(fn (Arp $a) => [
                'id' => $a->id,
                'company_id' => $a->company_id,
                'company_name' => $a->company?->title,
                'year' => $a->year,
                'title' => $a->title,
                'status' => $a->status,
                'can_edit' => $leadableCompanyIds->contains($a->company_id),
            ]),
        ]);
    }

    /** Single ARP + the requesting user's company name. */
    public function show(Request $request, Arp $arp)
    {
        $userId = (int) $request->query('user_id');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $arp->id,
                'company_id' => $arp->company_id,
                'company_name' => $arp->company?->title,
                'year' => $arp->year,
                'title' => $arp->title,
                'status' => $arp->status,
                'version' => (string) $arp->version,
                'created_at' => $arp->created_at?->toIso8601String(),
                'updated_at' => $arp->updated_at?->toIso8601String(),
                'published_at' => $arp->published_at?->toIso8601String(),
                'step_progress' => $arp->step_progress ?? app(ArpPlanService::class)->computeStepProgress($arp),
                'can_edit' => $this->leadableCompanyIds($userId)->contains($arp->company_id),
            ],
        ]);
    }

    /**
     * Members across every group in this ARP's company — used to populate
     * the Executive Owner dropdown on Steps 3 (Readiness Priorities) and 4
     * (Strategic Priorities) instead of a hardcoded name list.
     */
    public function groupMembers(Request $request, Arp $arp)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this ARP.'], 403);
        }

        $groupIds = CompanyGroup::query()->where('company_id', $arp->company_id)->pluck('id');

        // whereHas('user') would try to compile a single SQL statement
        // spanning two different DB connections (this model's default
        // `mysql` connection vs. User's Corcel `wordpress` connection),
        // which fails with "table 'users' doesn't exist" - Eloquent can't
        // combine connections in one query, so the exists-subquery runs
        // unprefixed under `mysql`. Eager-load instead (a separate query,
        // correctly scoped to User's own connection) and filter afterward.
        $members = CompanyGroupDetail::query()
            ->whereIn('company_group_id', $groupIds)
            ->with('user:ID,display_name,user_nicename')
            ->get()
            ->filter(fn (CompanyGroupDetail $d) => $d->user !== null)
            ->map(fn (CompanyGroupDetail $d) => [
                'id' => (int) $d->user_id,
                'name' => $d->user?->display_name ?: $d->user?->user_nicename,
                'is_leader' => $d->isLeader(),
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();

        return response()->json(['success' => true, 'data' => $members]);
    }

    /**
     * Every group in this ARP's company — used to populate the
     * "Related Group(s)" multi-select on Step 4 (Strategic Priorities)
     * instead of a hardcoded pseudo-scope list (all_leaders, etc).
     */
    public function companyGroups(Request $request, Arp $arp)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this ARP.'], 403);
        }

        $groups = CompanyGroup::query()
            ->where('company_id', $arp->company_id)
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json([
            'success' => true,
            'data' => $groups->map(fn (CompanyGroup $g) => ['id' => $g->id, 'name' => $g->title])->values(),
        ]);
    }

    /**
     * AI Readiness Review™ summary from the most recent ARP belonging to a
     * 1-on-1 leader/employee pair's organization — feeds the "ARP
     * Priorities" evidence card in the 1-on-1 wizard's Step 1. Read-only;
     * only the AI-generated readiness figures are exposed, never raw
     * evidence.
     */
    public function readinessSummaryForPair(Request $request, OneOnOneCompanyGroupSyncService $groupSync, ArpAiService $ai)
    {
        $leaderUserId = (int) $request->query('leader_user_id');
        $employeeUserId = (int) $request->query('employee_user_id');
        if ($leaderUserId < 1 || $employeeUserId < 1) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $group = $groupSync->findGroupForPair($leaderUserId, $employeeUserId);
        if ($group === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $arp = Arp::query()
            ->where('company_id', $group->company_id)
            ->orderByDesc('year')
            ->first();
        if ($arp === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $latest = $ai->latestAssessment($arp);
        $readiness = is_array($latest?->assessment['readiness_assessment'] ?? null) ? $latest->assessment['readiness_assessment'] : null;
        if ($readiness === null || ($readiness['score'] ?? null) === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'score' => $readiness['score'],
                'label' => $readiness['label'] ?? null,
                'narrative' => $readiness['summary'] ?? null,
                'company_name' => $group->company?->title,
                'year' => $arp->year,
                'status' => $arp->status,
            ],
        ]);
    }

    /** Version history — newest first. Snapshot bodies excluded from the list (fetch by id if needed). */
    public function listVersions(Arp $arp)
    {
        $versions = $arp->versions()->get(['id', 'arp_id', 'version', 'status', 'published_by_user_id', 'published_at', 'created_at']);

        return response()->json(['success' => true, 'data' => $versions]);
    }

    /**
     * Single version's full snapshot — powers the "Version History" sidebar
     * card's detail view (Step 7 "Archive Previous Version" / "Publish"
     * both write a snapshot here; this is the only place to read one back).
     */
    public function getVersion(Request $request, Arp $arp, ArpVersion $version)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this ARP.'], 403);
        }

        if ((int) $version->arp_id !== $arp->id) {
            return response()->json(['success' => false, 'message' => 'Version not found for this ARP.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $version->id,
                'version' => (string) $version->version,
                'status' => $version->status,
                'snapshot' => $version->snapshot,
                'published_by_user_id' => $version->published_by_user_id,
                'published_at' => $version->published_at?->toIso8601String(),
                'created_at' => $version->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Archive the ARP's CURRENT state as a snapshot without changing its
     * version number or status — used by the "Archive Previous Version"
     * action on the Publish step, which archives the last-saved draft
     * before the user proceeds to publish a new one.
     */
    public function archiveVersion(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $version = ArpVersion::create([
            'arp_id' => $arp->id,
            'version' => $arp->version,
            'status' => ArpVersion::STATUS_ARCHIVED,
            'snapshot' => $this->buildSnapshot($arp),
            'published_by_user_id' => $userId,
            'published_at' => null,
            'created_at' => now(),
        ]);

        app(ArpEvidenceService::class)->logArchived($arp, $version, $userId);

        return response()->json(['success' => true, 'data' => ['id' => $version->id, 'version' => (string) $version->version]]);
    }

    /**
     * Publish: snapshot the current state as the new PUBLISHED version, bump
     * the ARP's version number by 0.1, and mark it active. The prior draft
     * is not silently lost — callers are expected to hit archiveVersion()
     * first (the UI's "Archive Previous Version" button does this), but
     * publish() does not require it.
     */
    public function publish(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $newVersion = round(((float) $arp->version) + 0.1, 1);

        $result = DB::transaction(function () use ($arp, $userId, $newVersion) {
            $versionRow = ArpVersion::create([
                'arp_id' => $arp->id,
                'version' => $newVersion,
                'status' => ArpVersion::STATUS_PUBLISHED,
                'snapshot' => $this->buildSnapshot($arp),
                'published_by_user_id' => $userId,
                'published_at' => now(),
                'created_at' => now(),
            ]);

            $arp->update([
                'version' => $newVersion,
                'status' => Arp::STATUS_ACTIVE,
                'published_at' => now(),
            ]);

            app(ArpEvidenceService::class)->logPublished($arp->fresh(), $versionRow, $userId);

            return $versionRow;
        });

        app(ArpPlanService::class)->refreshStepProgress($arp->fresh());

        return response()->json([
            'success' => true,
            'data' => [
                'version' => (string) $result->version,
                'status' => Arp::STATUS_ACTIVE,
                'published_at' => $arp->fresh()->published_at?->toIso8601String(),
            ],
        ]);
    }

    /** Full plan state at the moment of archive/publish — the version history's source of truth. */
    private function buildSnapshot(Arp $arp): array
    {
        $plan = app(ArpPlanService::class);

        return [
            'arp' => $arp->only([
                'id', 'company_id', 'year', 'title',
                'mission', 'vision', 'core_values', 'organizational_description',
                'business_environment', 'executive_narrative', 'status', 'version',
            ]),
            'foundation' => $plan->foundationValues($arp),
            'future_state' => $plan->futureStateValues($arp),
            'readiness_priorities' => ArpReadinessPriority::where('arp_id', $arp->id)->orderBy('priority_rank')->get()->toArray(),
            'strategic_priorities' => ArpStrategicPriority::where('arp_id', $arp->id)->orderBy('priority_rank')->get()->toArray(),
            'learning' => $plan->learningValues($arp),
            'learnings' => ArpLearning::where('arp_id', $arp->id)->get()->toArray(),
            'step_progress' => $arp->step_progress ?? $plan->computeStepProgress($arp),
        ];
    }

    /**
     * Create (or resume) the ARP for the year, scoped via a group the user
     * leads. company_id is resolved from company_group_id server-side — the
     * client never sends company_id directly. If one already exists for
     * this company+year, return the existing record instead of erroring —
     * the picker resumes it.
     */
    public function store(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $data = $request->validate([
            'company_group_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:2000|max:2100',
            'title' => 'nullable|string|max:255',
        ]);

        $group = CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->where('company_group_id', $data['company_group_id'])
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->with('companyGroup:id,company_id')
            ->first();

        if ($userId < 1 || ! $group || ! $group->companyGroup) {
            return response()->json(['success' => false, 'message' => 'You do not lead this group.'], 403);
        }

        $companyId = $group->companyGroup->company_id;

        $existing = Arp::where('company_id', $companyId)->where('year', $data['year'])->first();
        if ($existing) {
            return response()->json(['success' => true, 'data' => $existing, 'already_existed' => true]);
        }

        $arp = Arp::create([
            'company_id' => $companyId,
            'year' => $data['year'],
            'title' => $data['title'] ?? ('ARP ' . $data['year']),
            'status' => Arp::STATUS_DRAFT,
            'created_by' => $userId,
        ]);

        return response()->json(['success' => true, 'data' => $arp, 'already_existed' => false], 201);
    }

    /** Full wizard draft payload (Steps 1, 2, 5) — Laravel canonical storage. */
    public function getPlan(Request $request, Arp $arp)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this ARP.'], 403);
        }

        $draft = app(ArpPlanService::class)->wizardDraftPayload($arp);

        return response()->json([
            'success' => true,
            'data' => array_merge($draft, [
                'arp_id' => $arp->id,
                'company_id' => $arp->company_id,
                'plan_year' => $arp->year,
                'step_progress' => $arp->step_progress ?? app(ArpPlanService::class)->computeStepProgress($arp),
            ]),
        ]);
    }

    /** Step 1 — Organizational Foundation™ */
    public function getFoundation(Arp $arp)
    {
        return response()->json([
            'success' => true,
            'data' => app(ArpPlanService::class)->foundationValues($arp),
        ]);
    }

    public function saveFoundation(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $values = $request->input('values', []);
        if (! is_array($values)) {
            return response()->json(['success' => false, 'message' => 'values must be an object.'], 422);
        }

        app(ArpPlanService::class)->saveFoundation($arp, $values);
        app(ArpPlanService::class)->refreshStepProgress($arp);

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    /** Step 2 — Future State™ */
    public function getFutureState(Arp $arp)
    {
        return response()->json([
            'success' => true,
            'data' => app(ArpPlanService::class)->futureStateValues($arp),
        ]);
    }

    public function saveFutureState(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $values = $request->input('values', []);
        if (! is_array($values)) {
            return response()->json(['success' => false, 'message' => 'values must be an object.'], 422);
        }

        app(ArpPlanService::class)->saveFutureState($arp, $values);
        app(ArpPlanService::class)->refreshStepProgress($arp);

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    /** Step 5 — Organizational Learning™ */
    public function getLearning(Arp $arp)
    {
        return response()->json([
            'success' => true,
            'data' => app(ArpPlanService::class)->learningValues($arp),
        ]);
    }

    public function saveLearning(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $values = $request->input('values', []);
        if (! is_array($values)) {
            return response()->json(['success' => false, 'message' => 'values must be an object.'], 422);
        }

        app(ArpPlanService::class)->saveLearning($arp, $values);
        app(ArpPlanService::class)->refreshStepProgress($arp);

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    /** Step 3 — Organizational Readiness™: list priorities for an ARP. */
    public function getReadinessPriorities(Arp $arp)
    {
        $items = ArpReadinessPriority::where('arp_id', $arp->id)
            ->orderBy('priority_rank')
            ->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Step 3 — replace-all save: the UI edits the whole repeatable list at
     * once, so the simplest correct semantics is delete-then-insert rather
     * than diffing individual rows.
     */
    public function saveReadinessPriorities(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $data = $request->validate([
            'items' => 'present|array',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.cor_capability' => 'nullable|string|max:40',
            'items.*.primary_driver' => 'nullable|string|max:40',
            'items.*.secondary_driver' => 'nullable|string|max:40',
            'items.*.priority_level' => 'nullable|string|max:20',
            'items.*.description' => 'nullable|string',
            'items.*.business_rationale' => 'nullable|string',
            'items.*.executive_owner_user_ids' => 'nullable|array',
            'items.*.executive_owner_user_ids.*' => 'nullable',
            'items.*.expected_impact' => 'nullable|string',
        ]);

        DB::transaction(function () use ($arp, $data) {
            ArpReadinessPriority::where('arp_id', $arp->id)->delete();

            foreach (array_values($data['items']) as $index => $item) {
                // Each id must be a real wp_users.ID — anything non-numeric
                // is dropped rather than stored.
                $ownerIds = array_values(array_filter(array_map(
                    fn ($id) => filter_var($id, FILTER_VALIDATE_INT),
                    $item['executive_owner_user_ids'] ?? []
                ), fn ($id) => $id !== false));

                ArpReadinessPriority::create([
                    'arp_id' => $arp->id,
                    'name' => $item['name'] ?? '',
                    'cor_capability' => $item['cor_capability'] ?? 'leadership',
                    'primary_driver' => $item['primary_driver'] ?? 'be_intentional',
                    'secondary_driver' => $item['secondary_driver'] ?? null,
                    'priority_level' => $item['priority_level'] ?? 'medium',
                    'description' => $item['description'] ?? null,
                    'business_rationale' => $item['business_rationale'] ?? null,
                    'executive_owner_user_ids' => $ownerIds,
                    'expected_impact' => $item['expected_impact'] ?? null,
                    'priority_rank' => $index,
                ]);
            }
        });

        app(ArpPlanService::class)->refreshStepProgress($arp);

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    /**
     * Step 4 — Strategic Priorities™: list, with readiness_priority_id
     * resolved back to the readiness priority's name for the UI's
     * "Related Readiness Priority" select (which matches by name, not id).
     */
    public function getStrategicPriorities(Arp $arp)
    {
        $items = ArpStrategicPriority::where('arp_id', $arp->id)
            ->with('readinessPriority:id,name')
            ->orderBy('priority_rank')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(function (ArpStrategicPriority $p) {
                $arr = $p->toArray();
                $arr['related_readiness'] = $p->readinessPriority?->name;
                unset($arr['readiness_priority']);

                return $arr;
            }),
        ]);
    }

    /**
     * Step 4 — replace-all save. `related_readiness` arrives as the
     * readiness priority's NAME (the UI matches by name, not id) — resolved
     * here against this ARP's saved readiness priorities before insert.
     */
    public function saveStrategicPriorities(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $data = $request->validate([
            'items' => 'present|array',
            'items.*.title' => 'nullable|string|max:255',
            'items.*.related_readiness' => 'nullable|string',
            'items.*.executive_owner_user_ids' => 'nullable|array',
            'items.*.executive_owner_user_ids.*' => 'nullable',
            'items.*.target_date' => 'nullable|string',
            'items.*.description' => 'nullable|string',
            'items.*.success_measures' => 'nullable|string',
            'items.*.org_kpi' => 'nullable|string|max:80',
            'items.*.readiness_indicator' => 'nullable|string|max:80',
            'items.*.related_groups' => 'nullable|array',
            'items.*.related_groups.*' => 'nullable',
        ]);

        $readinessByName = ArpReadinessPriority::where('arp_id', $arp->id)
            ->get(['id', 'name'])
            ->keyBy('name');

        DB::transaction(function () use ($arp, $data, $readinessByName) {
            ArpStrategicPriority::where('arp_id', $arp->id)->delete();

            foreach (array_values($data['items']) as $index => $item) {
                $ownerIds = array_values(array_filter(array_map(
                    fn ($id) => filter_var($id, FILTER_VALIDATE_INT),
                    $item['executive_owner_user_ids'] ?? []
                ), fn ($id) => $id !== false));
                $groupIds = array_values(array_filter(array_map(
                    fn ($id) => filter_var($id, FILTER_VALIDATE_INT),
                    $item['related_groups'] ?? []
                ), fn ($id) => $id !== false));
                $readinessName = $item['related_readiness'] ?? null;
                $readinessId = $readinessName !== null ? ($readinessByName->get($readinessName)?->id) : null;
                $targetDate = ! empty($item['target_date']) ? $item['target_date'] : null;

                ArpStrategicPriority::create([
                    'arp_id' => $arp->id,
                    'readiness_priority_id' => $readinessId,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? null,
                    'owner_user_ids' => $ownerIds,
                    'target_date' => $targetDate,
                    'success_measures' => $item['success_measures'] ?? null,
                    'org_kpi' => $item['org_kpi'] ?? null,
                    'readiness_indicator' => $item['readiness_indicator'] ?? null,
                    'related_groups' => $groupIds,
                    'status' => ArpStrategicPriority::STATUS_NOT_STARTED,
                    'priority_rank' => $index,
                ]);
            }
        });

        app(ArpPlanService::class)->refreshStepProgress($arp);

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    /** Step 6 — latest AI Readiness Review assessment + leadership context. */
    public function getReadinessReview(Request $request, Arp $arp)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this ARP.'], 403);
        }

        $ai = app(ArpAiService::class);
        $latest = $ai->latestAssessment($arp);
        $assessment = $latest?->assessment;
        $hasAssessment = is_array($assessment) && $assessment !== [];

        return response()->json([
            'success' => true,
            'data' => [
                'has_assessment' => $hasAssessment,
                'assessment' => $hasAssessment ? $assessment : null,
                'leadership_context' => (string) ($latest?->leadership_context ?? ''),
                'insight_model' => $latest?->insight_model,
                'generated_at' => $latest?->created_at?->toIso8601String(),
                'can_edit' => $this->leadableCompanyIds($userId)->contains($arp->company_id),
            ],
        ]);
    }

    /** Step 6 — generate (or regenerate) AI Readiness Review from Steps 1–5. */
    public function generateReadinessReview(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $ai = app(ArpAiService::class);
        if (! $ai->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => $ai->getLastError()
                    ?? 'AI service is not configured. Set XFUSION_LLM_API_URL and XFUSION_LLM_API_KEY in Laravel .env (key must match Xfusion-llm API_KEY).',
            ], 503);
        }

        $row = $ai->generateReadinessReview($arp);
        if ($row === null) {
            return response()->json([
                'success' => false,
                'message' => $ai->getLastError() ?? 'AI generation failed.',
            ], 502);
        }

        app(ArpEvidenceService::class)->logAiReadinessReview($arp, $row, $userId);
        app(ArpPlanService::class)->refreshStepProgress($arp);

        $assessment = $row->assessment;

        return response()->json([
            'success' => true,
            'data' => [
                'has_assessment' => is_array($assessment) && $assessment !== [],
                'assessment' => $assessment,
                'leadership_context' => (string) ($row->leadership_context ?? ''),
                'insight_model' => $row->insight_model,
                'generated_at' => $row->created_at?->toIso8601String(),
                'tokens_used' => (int) $row->tokens_used,
                'cost_usd' => (float) $row->cost_usd,
                'can_edit' => true,
            ],
        ]);
    }

    /** Step 6 — save leadership context (editable by group leaders). */
    public function saveReadinessReviewContext(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group.'], 403);
        }

        $data = $request->validate([
            'leadership_context' => 'nullable|string|max:2000',
        ]);

        $row = app(ArpAiService::class)->saveLeadershipContext(
            $arp,
            (string) ($data['leadership_context'] ?? '')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'leadership_context' => (string) ($row->leadership_context ?? ''),
                'saved_at' => now()->format('g:i A'),
            ],
        ]);
    }

    /** Company ids where the user leads at least one group. */
    private function leadableCompanyIds(int $userId)
    {
        return CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->whereHas('companyGroup')
            ->with('companyGroup:id,company_id')
            ->get()
            ->pluck('companyGroup.company_id')
            ->filter()
            ->unique()
            ->values();
    }

    /** Company ids where the user belongs to at least one group (any role). */
    private function memberCompanyIds(int $userId)
    {
        return CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->whereHas('companyGroup')
            ->with('companyGroup:id,company_id')
            ->get()
            ->pluck('companyGroup.company_id')
            ->filter()
            ->unique()
            ->values();
    }
}
