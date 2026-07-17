<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arp;
use App\Models\ArpReadinessPriority;
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

        $companyIds = $this->leadableCompanyIds($userId);
        if ($companyIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => [], 'has_access' => false]);
        }

        $arps = Arp::query()
            ->whereIn('company_id', $companyIds)
            ->with('company:id,title')
            ->orderByDesc('year')
            ->get();

        return response()->json([
            'success' => true,
            'has_access' => true,
            'data' => $arps->map(fn (Arp $a) => [
                'id' => $a->id,
                'company_id' => $a->company_id,
                'company_name' => $a->company?->title,
                'year' => $a->year,
                'title' => $a->title,
                'status' => $a->status,
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
            ],
        ]);
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
}
