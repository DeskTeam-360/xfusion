<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arp;
use App\Models\ArpReadinessPriority;
use App\Models\ArpStrategicPriority;
use App\Models\ArpVersion;
use App\Models\Company;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ARP picker bridge for the WordPress [fusion_arp_wizard] shortcode.
 *
 * Business rule: one ARP exists per (company, calendar year) — enforced by
 * the `arp_company_year_uq` unique key on wp_fusion_arps. Only users who
 * lead at least one company group (wp_company_group_details.status = leader)
 * may view or create an ARP for that company.
 */
class ArpController extends Controller
{
    /** Companies where the given user leads at least one group. */
    public function leadableCompanies(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $companyIds = CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->whereHas('companyGroup')
            ->with('companyGroup:id,company_id')
            ->get()
            ->pluck('companyGroup.company_id')
            ->filter()
            ->unique()
            ->values();

        $companies = Company::query()
            ->whereIn('id', $companyIds)
            ->get(['id', 'title']);

        return response()->json([
            'success' => true,
            'data' => $companies->map(fn (Company $c) => ['id' => $c->id, 'name' => $c->title]),
        ]);
    }

    /** ARPs for companies this user leads, newest year first. */
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
            ->with('company:id,title')
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

    /** Single ARP + the requesting user's leading group name for its company. */
    public function show(Request $request, Arp $arp)
    {
        $userId = (int) $request->query('user_id');

        $groupName = CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->whereHas('companyGroup', fn ($q) => $q->where('company_id', $arp->company_id))
            ->with('companyGroup:id,title')
            ->first()
            ?->companyGroup
            ?->title;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $arp->id,
                'company_id' => $arp->company_id,
                'company_name' => $arp->company?->title,
                'group_name' => $groupName,
                'year' => $arp->year,
                'title' => $arp->title,
                'status' => $arp->status,
                'version' => (string) $arp->version,
                'created_at' => $arp->created_at?->toIso8601String(),
                'updated_at' => $arp->updated_at?->toIso8601String(),
                'published_at' => $arp->published_at?->toIso8601String(),
                'can_edit' => $this->leadableCompanyIds($userId)->contains($arp->company_id),
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
     * Archive the ARP's CURRENT state as a snapshot without changing its
     * version number or status — used by the "Archive Previous Version"
     * action on the Publish step, which archives the last-saved draft
     * before the user proceeds to publish a new one.
     */
    public function archiveVersion(Request $request, Arp $arp)
    {
        $userId = (int) $request->input('user_id');
        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains($arp->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group(s).'], 403);
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
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group(s).'], 403);
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

            return $versionRow;
        });

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
        return [
            'arp' => $arp->only(['id', 'company_id', 'year', 'title', 'mission', 'vision', 'status', 'version']),
            'readiness_priorities' => ArpReadinessPriority::where('arp_id', $arp->id)->orderBy('priority_rank')->get()->toArray(),
            'strategic_priorities' => ArpStrategicPriority::where('arp_id', $arp->id)->orderBy('priority_rank')->get()->toArray(),
            'learnings' => DB::table('wp_fusion_arp_learnings')->where('arp_id', $arp->id)->get()->toArray(),
        ];
    }

    /**
     * Create a new ARP. If one already exists for this company+year, return
     * the existing record instead of erroring — the picker resumes it.
     */
    public function store(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $data = $request->validate([
            'company_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:2000|max:2100',
            'title' => 'nullable|string|max:255',
        ]);

        if ($userId < 1 || ! $this->leadableCompanyIds($userId)->contains((int) $data['company_id'])) {
            return response()->json(['success' => false, 'message' => 'You do not lead this company\'s group(s).'], 403);
        }

        $existing = Arp::where('company_id', $data['company_id'])->where('year', $data['year'])->first();
        if ($existing) {
            return response()->json(['success' => true, 'data' => $existing, 'already_existed' => true]);
        }

        $arp = Arp::create([
            'company_id' => $data['company_id'],
            'year' => $data['year'],
            'title' => $data['title'] ?? ('ARP ' . $data['year']),
            'status' => Arp::STATUS_DRAFT,
            'created_by' => $userId,
        ]);

        return response()->json(['success' => true, 'data' => $arp, 'already_existed' => false], 201);
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
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group(s).'], 403);
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
            'items.*.executive_owner_user_id' => 'nullable',
            'items.*.expected_impact' => 'nullable|string',
        ]);

        DB::transaction(function () use ($arp, $data) {
            ArpReadinessPriority::where('arp_id', $arp->id)->delete();

            foreach (array_values($data['items']) as $index => $item) {
                // executive_owner_user_id must be a real wp_users.ID — the UI
                // still ships a dummy name list (see step-3-readiness.php),
                // so anything non-numeric is dropped rather than stored.
                $ownerId = filter_var($item['executive_owner_user_id'] ?? null, FILTER_VALIDATE_INT);

                ArpReadinessPriority::create([
                    'arp_id' => $arp->id,
                    'name' => $item['name'] ?? '',
                    'cor_capability' => $item['cor_capability'] ?? 'leadership',
                    'primary_driver' => $item['primary_driver'] ?? 'be_intentional',
                    'secondary_driver' => $item['secondary_driver'] ?? null,
                    'priority_level' => $item['priority_level'] ?? 'medium',
                    'description' => $item['description'] ?? null,
                    'business_rationale' => $item['business_rationale'] ?? null,
                    'executive_owner_user_id' => $ownerId !== false ? $ownerId : null,
                    'expected_impact' => $item['expected_impact'] ?? null,
                    'priority_rank' => $index,
                ]);
            }
        });

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
            return response()->json(['success' => false, 'message' => 'You do not lead this ARP\'s company group(s).'], 403);
        }

        $data = $request->validate([
            'items' => 'present|array',
            'items.*.title' => 'nullable|string|max:255',
            'items.*.related_readiness' => 'nullable|string',
            'items.*.executive_owner_user_id' => 'nullable',
            'items.*.target_date' => 'nullable|string',
            'items.*.description' => 'nullable|string',
            'items.*.success_measures' => 'nullable|string',
            'items.*.org_kpi' => 'nullable|string|max:80',
            'items.*.readiness_indicator' => 'nullable|string|max:80',
            'items.*.related_groups' => 'nullable|string|max:80',
        ]);

        $readinessByName = ArpReadinessPriority::where('arp_id', $arp->id)
            ->get(['id', 'name'])
            ->keyBy('name');

        DB::transaction(function () use ($arp, $data, $readinessByName) {
            ArpStrategicPriority::where('arp_id', $arp->id)->delete();

            foreach (array_values($data['items']) as $index => $item) {
                $ownerId = filter_var($item['executive_owner_user_id'] ?? null, FILTER_VALIDATE_INT);
                $readinessName = $item['related_readiness'] ?? null;
                $readinessId = $readinessName !== null ? ($readinessByName->get($readinessName)?->id) : null;
                $targetDate = ! empty($item['target_date']) ? $item['target_date'] : null;

                ArpStrategicPriority::create([
                    'arp_id' => $arp->id,
                    'readiness_priority_id' => $readinessId,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? null,
                    'owner_user_id' => $ownerId !== false ? $ownerId : null,
                    'target_date' => $targetDate,
                    'success_measures' => $item['success_measures'] ?? null,
                    'org_kpi' => $item['org_kpi'] ?? null,
                    'readiness_indicator' => $item['readiness_indicator'] ?? null,
                    'related_groups' => $item['related_groups'] ?? null,
                    'status' => ArpStrategicPriority::STATUS_NOT_STARTED,
                    'priority_rank' => $index,
                ]);
            }
        });

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

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

    /** Companies where the user belongs to any group, regardless of role — view-only access. */
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
