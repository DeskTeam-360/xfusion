<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arr;
use App\Models\ArrAiAssessment;
use App\Models\ArrAiSynthesis;
use App\Models\ArrEvidenceSnapshot;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Services\ArrAiService;
use App\Services\ArrEvidenceService;
use App\Services\OneOnOneCompanyGroupSyncService;
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

    /**
     * Executive Summary™ from the most recent ARR belonging to a 1-on-1
     * leader/employee pair's organization — feeds the "Organizational
     * Context" evidence card in the 1-on-1 wizard's Step 1. Read-only,
     * AI-synthesized text only; returns null (not an error) when the
     * organization has no ARR yet, or the ARR has no synthesis yet (ARR's
     * AI Strategic Renewal Synthesis generation isn't wired up yet).
     */
    public function executiveSummaryForPair(Request $request, OneOnOneCompanyGroupSyncService $groupSync)
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

        $arr = Arr::query()
            ->where('company_id', $group->company_id)
            ->orderByDesc('year')
            ->first();
        if ($arr === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $synthesis = ArrAiSynthesis::query()
            ->where('arr_id', $arr->id)
            ->orderByDesc('id')
            ->first();
        $summary = $synthesis?->synthesis['executive_summary'] ?? null;
        if (! is_string($summary) || trim($summary) === '') {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'company_name' => $group->company?->title,
                'year' => $arr->year,
                'status' => $arr->status,
            ],
        ]);
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

    /** Step 1: build and persist annual organization-wide evidence snapshot. */
    public function generateEvidence(Request $request, Arr $arr, ArrEvidenceService $evidenceService)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->leadableCompanyIds($userId)->contains($arr->company_id)) {
            return $this->forbidden();
        }

        $snapshot = $evidenceService->buildSnapshot($arr);
        $row = ArrEvidenceSnapshot::create([
            'arr_id' => $arr->id,
            'snapshot' => $snapshot,
            'captured_at' => now(),
        ]);

        $this->refreshStepProgress($arr, 'evidence', true);

        return response()->json([
            'success' => true,
            'data' => $snapshot,
            'captured_at' => $row->captured_at?->toIso8601String(),
        ]);
    }

    /** Step 2: latest evidence snapshot; auto-build on first view if none exists. */
    public function getEvidence(Request $request, Arr $arr, ArrEvidenceService $evidenceService)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arr->company_id)) {
            return $this->forbidden();
        }

        $latest = $arr->evidenceSnapshots()->first();
        if ($latest === null) {
            $snapshot = $evidenceService->buildSnapshot($arr);
            $latest = ArrEvidenceSnapshot::create([
                'arr_id' => $arr->id,
                'snapshot' => $snapshot,
                'captured_at' => now(),
            ]);
            $this->refreshStepProgress($arr, 'evidence', true);
        }

        return response()->json([
            'success' => true,
            'data' => $latest->snapshot,
            'captured_at' => $latest->captured_at?->toIso8601String(),
        ]);
    }

    /** Step 2: real this-year-vs-last-year comparisons computed on demand (not persisted — always fresh). */
    public function getDashboard(Request $request, Arr $arr, ArrEvidenceService $evidenceService)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arr->company_id)) {
            return $this->forbidden();
        }

        return response()->json(['success' => true, 'data' => $evidenceService->buildDashboard($arr)]);
    }

    /** Step 3: generate a new AI Annual Readiness Assessment™ (always appends a new row). */
    public function generateAssessment(Request $request, Arr $arr, ArrEvidenceService $evidenceService, ArrAiService $aiService)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->leadableCompanyIds($userId)->contains($arr->company_id)) {
            return $this->forbidden();
        }

        $snapshot = $arr->evidenceSnapshots()->first()?->snapshot ?? $evidenceService->buildSnapshot($arr);
        $readinessIndicators = $evidenceService->computeReadinessIndicators($arr);

        $assessment = $aiService->generateAssessment($arr, $snapshot, $readinessIndicators);
        if ($assessment === null) {
            return response()->json(['success' => false, 'message' => $aiService->getLastError() ?? 'Failed to generate assessment.'], 502);
        }

        $this->refreshStepProgress($arr, 'assessment', true);

        return response()->json(['success' => true, 'data' => $this->assessmentPayload($assessment)]);
    }

    /** Step 3: latest AI assessment; auto-computes readiness_indicators (real, never persisted stale) even if the assessment row is old. */
    public function getAssessment(Request $request, Arr $arr, ArrEvidenceService $evidenceService)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->memberCompanyIds($userId)->contains($arr->company_id)) {
            return $this->forbidden();
        }

        $assessment = ArrAiAssessment::query()->where('arr_id', $arr->id)->orderByDesc('id')->first();
        if ($assessment === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json(['success' => true, 'data' => $this->assessmentPayload($assessment)]);
    }

    /** Step 3: Executive Agreement rating on the latest assessment. */
    public function saveAgreement(Request $request, Arr $arr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->leadableCompanyIds($userId)->contains($arr->company_id)) {
            return $this->forbidden();
        }

        $rating = (string) $request->input('agreement_rating', '');
        $allowed = ['strongly_agree', 'agree', 'neutral', 'disagree', 'strongly_disagree'];
        if (! in_array($rating, $allowed, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid agreement_rating.'], 422);
        }

        $assessment = ArrAiAssessment::query()->where('arr_id', $arr->id)->orderByDesc('id')->first();
        if ($assessment === null) {
            return response()->json(['success' => false, 'message' => 'Generate the AI assessment before recording agreement.'], 422);
        }

        $assessment->update(['agreement_rating' => $rating]);

        return response()->json(['success' => true]);
    }

    /** Step 3: Executive Strategic Context free-text on the latest assessment. */
    public function saveContext(Request $request, Arr $arr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->leadableCompanyIds($userId)->contains($arr->company_id)) {
            return $this->forbidden();
        }

        $context = (string) $request->input('executive_context', '');
        if (mb_strlen($context) > 2000) {
            return response()->json(['success' => false, 'message' => 'executive_context must be 2000 characters or fewer.'], 422);
        }

        $assessment = ArrAiAssessment::query()->where('arr_id', $arr->id)->orderByDesc('id')->first();
        if ($assessment === null) {
            return response()->json(['success' => false, 'message' => 'Generate the AI assessment before saving context.'], 422);
        }

        $assessment->update(['executive_context' => $context]);

        return response()->json(['success' => true]);
    }

    private function assessmentPayload(ArrAiAssessment $assessment): array
    {
        return [
            'assessment' => $assessment->assessment,
            'agreement_rating' => $assessment->agreement_rating,
            'executive_context' => $assessment->executive_context,
            'insight_model' => $assessment->insight_model,
            'created_at' => $assessment->created_at?->toIso8601String(),
        ];
    }

    private function forbidden()
    {
        return response()->json(['success' => false, 'message' => 'You do not have access to this ARR.'], 403);
    }

    private function refreshStepProgress(Arr $arr, string $step, bool $complete): void
    {
        $progress = is_array($arr->step_progress) ? $arr->step_progress : [];
        $progress[$step] = $complete;
        $arr->update(['step_progress' => $progress]);
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
