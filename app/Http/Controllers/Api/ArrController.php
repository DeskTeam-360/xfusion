<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arr;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use Illuminate\Http\Request;

/**
 * ARR picker bridge for the WordPress [fusion_arr_wizard] shortcode.
 *
 * Business rule: one ARR exists per (company, calendar year) — enforced by
 * the `arr_company_year_uq` unique key on wp_fusion_arrs. Unlike ARP/QBR,
 * ARR is organization-wide rather than scoped to a single company group:
 * it sits above them in the FUSION cycle, synthesizing evidence from every
 * group's ARP/QBR/1-on-1/IRR activity. Any user who leads at least one
 * company group may create/edit that company's ARR; any member of any
 * group in the company may view it read-only.
 *
 * This controller currently covers the picker + creation flow (Step 0 of
 * the wizard). The 7 wizard steps themselves remain UI-only dummy content
 * pending a follow-up pass, same as ARP/QBR/IRR's incremental build-out.
 */
class ArrController extends Controller
{
    /**
     * Groups the given user leads, with their parent company for display —
     * same shape as ArpController::leadableCompanies(). The "create new
     * ARR" form picks a group (per the client's direction that this stay
     * per-group like ARP), and store() resolves company_id from it; the
     * underlying ARR row itself is still one-per-company-per-year.
     */
    public function leadableGroups(Request $request)
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
            'data' => $groups->map(fn (CompanyGroup $g) => [
                'id' => $g->id,
                'name' => $g->title . ($g->company ? ' (' . $g->company->title . ')' : ''),
                'company_id' => $g->company_id,
            ]),
        ]);
    }

    /** ARRs for companies this user belongs to (any role, via any group), newest year first. */
    public function index(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $memberCompanyIds = $this->memberCompanyIds($userId);
        if ($memberCompanyIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => [], 'has_access' => false]);
        }

        $leadableCompanyIds = $this->leadableCompanyIds($userId);

        $arrs = Arr::query()
            ->whereIn('company_id', $memberCompanyIds)
            ->with(['company:id,title', 'executiveOwner:ID,display_name,user_nicename'])
            ->orderByDesc('year')
            ->get();

        return response()->json([
            'success' => true,
            'has_access' => true,
            'can_create' => $leadableCompanyIds->isNotEmpty(),
            'data' => $arrs->map(fn (Arr $a) => [
                'id' => $a->id,
                'company_id' => $a->company_id,
                'company_name' => $a->company?->title,
                'year' => $a->year,
                'status' => $a->status,
                'executive_owner_name' => $a->executiveOwner?->display_name ?: $a->executiveOwner?->user_nicename,
                'can_edit' => $leadableCompanyIds->contains($a->company_id),
            ]),
        ]);
    }

    /**
     * Create (or resume) the ARR for the year, scoped via a group the user
     * leads. company_id is resolved from company_group_id server-side —
     * the client never sends company_id directly.
     */
    public function store(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $data = $request->validate([
            'company_group_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:2000|max:2100',
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

        $existing = Arr::query()
            ->where('company_id', $companyId)
            ->where('year', $data['year'])
            ->first();
        if ($existing) {
            return response()->json(['success' => true, 'data' => ['id' => $existing->id]]);
        }

        $arr = Arr::create([
            'company_id' => $companyId,
            'executive_owner_user_id' => $userId,
            'year' => $data['year'],
            'status' => Arr::STATUS_IN_PROGRESS,
            'created_by' => $userId,
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $arr->id]]);
    }

    /** Single ARR + the requesting user's access flag. */
    public function show(Request $request, Arr $arr)
    {
        $userId = (int) $request->query('user_id');

        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arr->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this ARR.'], 403);
        }

        $arr->loadMissing(['company:id,title', 'executiveOwner:ID,display_name,user_nicename']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $arr->id,
                'company_id' => $arr->company_id,
                'company_name' => $arr->company?->title,
                'year' => $arr->year,
                'status' => $arr->status,
                'executive_owner_user_id' => $arr->executive_owner_user_id,
                'executive_owner_name' => $arr->executiveOwner?->display_name ?: $arr->executiveOwner?->user_nicename,
                'created_at' => $arr->created_at?->toIso8601String(),
                'updated_at' => $arr->updated_at?->toIso8601String(),
                'published_at' => $arr->published_at?->toIso8601String(),
                'step_progress' => $arr->step_progress ?? new \stdClass(),
                'can_edit' => $this->leadableCompanyIds($userId)->contains($arr->company_id),
            ],
        ]);
    }

    /**
     * Roster across every group in this ARR's company — used to populate
     * the Executive Owner dropdown on Step 5 (Strategic Renewal
     * Recommendations). Wider than ARP's group-scoped roster because ARR
     * is organization-wide.
     */
    public function groupMembers(Request $request, Arr $arr)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arr->company_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this ARR.'], 403);
        }

        $groupIds = CompanyGroup::query()->where('company_id', $arr->company_id)->pluck('id');

        $members = CompanyGroupDetail::query()
            ->whereIn('company_group_id', $groupIds)
            ->whereHas('user')
            ->with('user:ID,display_name,user_nicename')
            ->get()
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
