<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\IrrReview;
use App\Models\User;
use App\Services\OneOnOneCompanyGroupSyncService;
use Illuminate\Http\Request;

/**
 * IRR picker + wizard bridge for the WordPress [fusion_irr_wizard] shortcode.
 *
 * One IRR per (employee, calendar year). Leaders start reviews for employees
 * in groups they lead — same roster source as the 1-on-1 meeting picker.
 */
class IrrController extends Controller
{
    public function __construct(
        private readonly OneOnOneCompanyGroupSyncService $companyGroupSync,
    ) {}

    /** Groups (leader role + members) and accessible reviews for the picker gate. */
    public function pickerDashboard(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $this->companyGroupSync->syncAllFromCompanyGroups();

        $groups = array_values(array_filter(
            $this->companyGroupSync->groupsForUser($userId),
            fn (array $g) => ($g['role'] ?? '') === 'leader'
        ));

        $reviews = $this->reviewsForUser($userId);
        $hasAccess = $groups !== [] || $reviews !== [];

        return response()->json([
            'success' => true,
            'has_access' => $hasAccess,
            'can_create' => $groups !== [],
            'data' => [
                'groups' => $groups,
                'reviews' => $reviews,
            ],
        ]);
    }

    public function show(Request $request, IrrReview $irr)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        if (! $this->canAccessReview($userId, $irr)) {
            return $this->forbidden();
        }

        $irr->load([
            'employee:ID,display_name,user_nicename',
            'manager:ID,display_name,user_nicename',
            'companyGroup:id,title,company_id',
            'companyGroup.company:id,title',
            'company:id,title',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->reviewDetailPayload($irr, $userId),
        ]);
    }

    /** Create or resume an IRR for employee + year in a group the user leads. */
    public function store(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $data = $request->validate([
            'company_group_id' => 'required|integer|min:1',
            'employee_user_id' => 'required|integer|min:1',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $groupId = (int) $data['company_group_id'];
        $employeeId = (int) $data['employee_user_id'];

        if ($userId < 1 || ! $this->companyGroupSync->isMemberOfLeaderGroup($userId, $employeeId, $groupId)) {
            return response()->json(['success' => false, 'message' => 'You do not lead this employee in the selected group.'], 403);
        }

        $existing = IrrReview::query()
            ->where('employee_user_id', $employeeId)
            ->where('year', $data['year'])
            ->first();

        if ($existing !== null) {
            if (! $this->canAccessReview($userId, $existing)) {
                return $this->forbidden();
            }

            return response()->json([
                'success' => true,
                'data' => ['id' => $existing->id],
                'already_existed' => true,
            ]);
        }

        $group = CompanyGroup::query()->find($groupId);
        if ($group === null) {
            return response()->json(['success' => false, 'message' => 'Group not found.'], 404);
        }

        $review = IrrReview::create([
            'employee_user_id' => $employeeId,
            'manager_user_id' => $userId,
            'company_id' => $group->company_id,
            'company_group_id' => $group->id,
            'year' => (int) $data['year'],
            'status' => IrrReview::STATUS_DRAFT,
            'created_by' => $userId,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['id' => $review->id],
            'already_existed' => false,
        ], 201);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reviewsForUser(int $userId): array
    {
        $teamMemberIds = $this->companyGroupSync->teamMemberUserIdsForLeader($userId);

        return IrrReview::query()
            ->with([
                'employee:ID,display_name,user_nicename',
                'manager:ID,display_name,user_nicename',
                'companyGroup:id,title',
                'company:id,title',
            ])
            ->where(function ($q) use ($userId, $teamMemberIds) {
                $q->where('employee_user_id', $userId);
                if ($teamMemberIds !== []) {
                    $q->orWhereIn('employee_user_id', $teamMemberIds);
                }
            })
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get()
            ->map(fn (IrrReview $review) => $this->reviewListPayload($review, $userId))
            ->values()
            ->all();
    }

    private function reviewListPayload(IrrReview $review, int $userId): array
    {
        return [
            'id' => $review->id,
            'employee_user_id' => $review->employee_user_id,
            'employee_name' => $this->userDisplayName($review->employee),
            'manager_name' => $this->userDisplayName($review->manager),
            'group_name' => $review->companyGroup?->title ?? $review->company?->title ?? '—',
            'year' => (int) $review->year,
            'status' => $review->status,
            'can_edit' => $this->canEditReview($userId, $review),
            'is_self' => (int) $review->employee_user_id === $userId,
        ];
    }

    private function reviewDetailPayload(IrrReview $review, int $userId): array
    {
        $employeeName = $this->userDisplayName($review->employee);
        $managerName = $this->userDisplayName($review->manager);

        return [
            'id' => $review->id,
            'employee_user_id' => $review->employee_user_id,
            'employee_name' => $employeeName,
            'manager_user_id' => $review->manager_user_id,
            'manager_name' => $managerName,
            'company_id' => $review->company_id,
            'company_group_id' => $review->company_group_id,
            'group_name' => $review->companyGroup?->title ?? '—',
            'organization_name' => $review->company?->title ?? '—',
            'year' => (int) $review->year,
            'status' => $review->status,
            'step_progress' => is_array($review->step_progress) ? $review->step_progress : [],
            'can_edit' => $this->canEditReview($userId, $review),
            'updated_at' => $review->updated_at?->toIso8601String(),
        ];
    }

    private function canAccessReview(int $userId, IrrReview $review): bool
    {
        if ((int) $review->employee_user_id === $userId) {
            return true;
        }

        return in_array(
            (int) $review->employee_user_id,
            $this->companyGroupSync->teamMemberUserIdsForLeader($userId),
            true
        );
    }

    private function canEditReview(int $userId, IrrReview $review): bool
    {
        if ((int) $review->manager_user_id === $userId) {
            return true;
        }

        if ($review->company_group_id !== null
            && $this->leadableGroupIds($userId)->contains((int) $review->company_group_id)) {
            return $this->companyGroupSync->isMemberOfLeaderGroup(
                $userId,
                (int) $review->employee_user_id,
                (int) $review->company_group_id
            );
        }

        return in_array(
            (int) $review->employee_user_id,
            $this->companyGroupSync->teamMemberUserIdsForLeader($userId),
            true
        );
    }

    private function leadableGroupIds(int $userId)
    {
        return CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->whereHas('companyGroup')
            ->pluck('company_group_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function userDisplayName(?User $user): string
    {
        if ($user === null) {
            return '—';
        }

        $name = trim((string) ($user->display_name ?: $user->user_nicename));

        return $name !== '' ? $name : '—';
    }

    private function forbidden()
    {
        return response()->json(['success' => false, 'message' => 'You do not have access to this review.'], 403);
    }
}
