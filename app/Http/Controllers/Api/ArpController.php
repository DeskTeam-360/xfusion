<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arp;
use App\Models\Company;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use Illuminate\Http\Request;

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
