<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\IrrCommitment;
use App\Models\IrrConversationAgreement;
use App\Models\IrrEvidenceSnapshot;
use App\Models\IrrReview;
use App\Models\User;
use App\Services\IrrAiService;
use App\Services\IrrEvidenceService;
use App\Services\OneOnOneCompanyGroupSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Previous Individual Readiness Review™ evidence card for the 1-on-1
     * wizard's Step 1 — most recent published IRR's AI Development
     * Synthesis™ for this employee (any manager), read-only AI-synthesized
     * figures only. Same shape/rendering as QBR/ARP priorities.
     */
    public function readinessSummaryForEmployee(Request $request, IrrAiService $ai)
    {
        $employeeUserId = (int) $request->query('employee_user_id');
        if ($employeeUserId < 1) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $review = IrrReview::query()
            ->where('employee_user_id', $employeeUserId)
            ->where('status', IrrReview::STATUS_PUBLISHED)
            ->orderByDesc('year')
            ->first();
        if ($review === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $latest = $ai->latestSynthesis($review);
        $synthesis = $latest?->synthesis;
        $readiness = is_array($synthesis['readiness_indicators'] ?? null) ? $synthesis['readiness_indicators'] : null;
        if ($synthesis === null || ($readiness['overall_score'] ?? null) === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'score' => $readiness['overall_score'],
                'label' => $readiness['overall_label'] ?? null,
                'narrative' => $synthesis['annual_development_summary'] ?? null,
                'year' => $review->year,
                'status' => $review->status,
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

    /** Step 1: build and persist annual evidence snapshot. */
    public function generateEvidence(Request $request, IrrReview $irr, IrrEvidenceService $evidenceService)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->canEditReview($userId, $irr)) {
            return $this->forbidden();
        }

        $snapshot = $evidenceService->buildSnapshot($irr);
        $row = IrrEvidenceSnapshot::create([
            'review_id' => $irr->id,
            'snapshot' => $snapshot,
            'captured_at' => now(),
        ]);

        $this->refreshStepProgress($irr, 'evidence', true);

        return response()->json([
            'success' => true,
            'data' => $snapshot,
            'captured_at' => $row->captured_at?->toIso8601String(),
        ]);
    }

    /** Step 2: latest evidence snapshot; auto-build on first view if none exists. */
    public function getEvidence(Request $request, IrrReview $irr, IrrEvidenceService $evidenceService)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->canAccessReview($userId, $irr)) {
            return $this->forbidden();
        }

        $latest = $irr->evidenceSnapshots()->first();
        if ($latest === null) {
            $snapshot = $evidenceService->buildSnapshot($irr);
            $latest = IrrEvidenceSnapshot::create([
                'review_id' => $irr->id,
                'snapshot' => $snapshot,
                'captured_at' => now(),
            ]);
            $this->refreshStepProgress($irr, 'evidence', true);
        }

        return response()->json([
            'success' => true,
            'data' => $latest->snapshot,
            'captured_at' => $latest->captured_at?->toIso8601String(),
        ]);
    }

    /** Step 3: generate AI Development Assessment™ from the latest evidence snapshot. */
    public function generateAssessment(Request $request, IrrReview $irr, IrrEvidenceService $evidenceService, IrrAiService $ai)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->canEditReview($userId, $irr)) {
            return $this->forbidden();
        }

        $latestSnapshot = $irr->evidenceSnapshots()->first();
        $snapshot = $latestSnapshot?->snapshot ?? $evidenceService->buildSnapshot($irr);
        $readinessIndicators = $evidenceService->computeReadinessIndicators($irr);

        $assessment = $ai->generateAssessment($irr, $snapshot, $readinessIndicators);
        if ($assessment === null) {
            return response()->json([
                'success' => false,
                'message' => $ai->getLastError() ?? 'Failed to generate AI Development Assessment.',
            ], 200);
        }

        $this->refreshStepProgress($irr, 'assessment', true);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $assessment->id,
                'assessment' => $assessment->assessment,
                'insight_model' => $assessment->insight_model,
                'generated_at' => $assessment->created_at?->toIso8601String(),
            ],
        ]);
    }

    /** Step 3: latest AI Development Assessment™ (empty until first generated). */
    public function getAssessment(Request $request, IrrReview $irr, IrrAiService $ai)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->canAccessReview($userId, $irr)) {
            return $this->forbidden();
        }

        $latest = $ai->latestAssessment($irr);

        return response()->json([
            'success' => true,
            'data' => [
                'has_assessment' => $latest !== null,
                'assessment' => $latest?->assessment,
                'insight_model' => $latest?->insight_model,
                'generated_at' => $latest?->created_at?->toIso8601String(),
                'can_edit' => $this->canEditReview($userId, $irr),
            ],
        ]);
    }

    /** Step 6: generate AI Development Synthesis™ from evidence, assessment, conversation notes, and commitments. */
    public function generateSynthesis(Request $request, IrrReview $irr, IrrEvidenceService $evidenceService, IrrAiService $ai)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->canEditReview($userId, $irr)) {
            return $this->forbidden();
        }

        $context = $this->synthesisContext($irr, $evidenceService, $ai);
        $readinessIndicators = $evidenceService->computeReadinessIndicators($irr);
        $behavioralGrowth = $evidenceService->computeBehavioralGrowth($irr);

        $synthesis = $ai->generateSynthesis($irr, $context, $readinessIndicators, $behavioralGrowth);
        if ($synthesis === null) {
            return response()->json([
                'success' => false,
                'message' => $ai->getLastError() ?? 'Failed to generate AI Development Synthesis.',
            ], 200);
        }

        $this->refreshStepProgress($irr, 'synthesis', true);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $synthesis->id,
                'synthesis' => $synthesis->synthesis,
                'insight_model' => $synthesis->insight_model,
                'generated_at' => $synthesis->created_at?->toIso8601String(),
            ],
        ]);
    }

    /** Step 6: latest AI Development Synthesis™ (empty until first generated). */
    public function getSynthesis(Request $request, IrrReview $irr, IrrAiService $ai)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->canAccessReview($userId, $irr)) {
            return $this->forbidden();
        }

        $latest = $ai->latestSynthesis($irr);

        return response()->json([
            'success' => true,
            'data' => [
                'has_synthesis' => $latest !== null,
                'synthesis' => $latest?->synthesis,
                'insight_model' => $latest?->insight_model,
                'generated_at' => $latest?->created_at?->toIso8601String(),
                'can_edit' => $this->canEditReview($userId, $irr),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function synthesisContext(IrrReview $irr, IrrEvidenceService $evidenceService, IrrAiService $ai): array
    {
        $latestSnapshot = $irr->evidenceSnapshots()->first();
        $evidence = $latestSnapshot?->snapshot ?? $evidenceService->buildSnapshot($irr);

        $assessment = $ai->latestAssessment($irr)?->assessment;

        $agreement = IrrConversationAgreement::query()->where('review_id', $irr->id)->first();

        $commitments = $irr->commitments()->get()->map(fn (IrrCommitment $c) => [
            'title' => $c->title,
            'description' => $c->description,
            'priority' => $c->priority,
            'success_indicator' => $c->success_indicator,
            'behavioral_driver' => $c->behavioral_driver,
            'status' => $c->status,
            'due_date' => $c->due_date?->format('Y-m-d'),
        ])->values()->all();

        return [
            'evidence' => $evidence,
            'assessment' => $assessment,
            'conversation_notes' => $agreement?->conversation_notes,
            'commitments' => $commitments,
        ];
    }

    /** Step 7: lock the review and mark it published. */
    public function publish(Request $request, IrrReview $irr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->canEditReview($userId, $irr)) {
            return $this->forbidden();
        }

        if ($irr->status === IrrReview::STATUS_PUBLISHED) {
            return response()->json(['success' => false, 'message' => 'This review is already published.'], 422);
        }

        $progress = is_array($irr->step_progress) ? $irr->step_progress : [];
        $requiredSteps = ['evidence', 'assessment', 'conversation', 'commitments', 'synthesis'];
        $missing = array_values(array_filter($requiredSteps, fn ($step) => empty($progress[$step])));

        if ($missing !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Complete all steps before publishing. Missing: '.implode(', ', $missing).'.',
            ], 422);
        }

        $irr->update([
            'status' => IrrReview::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $irr->status,
                'published_at' => $irr->published_at?->toIso8601String(),
            ],
        ]);
    }

    /** Step 4: shared conversation notes + digital signature status. */
    public function getConversationAgreement(Request $request, IrrReview $irr)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->canAccessReview($userId, $irr)) {
            return $this->forbidden();
        }

        $agreement = IrrConversationAgreement::query()->where('review_id', $irr->id)->first();

        return response()->json([
            'success' => true,
            'data' => $this->conversationAgreementPayload($irr, $agreement, $userId),
        ]);
    }

    /** Step 4: save conversation notes/date, and/or sign as employee or leader. */
    public function saveConversationAgreement(Request $request, IrrReview $irr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->canAccessReview($userId, $irr)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'conversation_notes' => 'nullable|string',
            'conversation_date' => 'nullable|date',
            'sign_role' => 'nullable|in:employee,leader',
        ]);

        $agreement = IrrConversationAgreement::query()->firstOrNew(['review_id' => $irr->id]);
        $agreement->review_id = $irr->id;

        if ($request->has('conversation_notes')) {
            $agreement->conversation_notes = (string) $data['conversation_notes'];
        }
        if (! empty($data['conversation_date'])) {
            $agreement->conversation_date = $data['conversation_date'];
        }

        if (! empty($data['sign_role'])) {
            $isEmployee = (int) $irr->employee_user_id === $userId;
            $isLeader = (int) $irr->manager_user_id === $userId;

            if ($data['sign_role'] === 'employee') {
                if (! $isEmployee) {
                    return response()->json(['success' => false, 'message' => 'Only the employee can sign as employee.'], 403);
                }
                $agreement->employee_signed_at = now();
                $agreement->employee_signature_name = $this->userDisplayName($irr->employee);
            } else {
                if (! $isLeader) {
                    return response()->json(['success' => false, 'message' => 'Only the leader can sign as leader.'], 403);
                }
                $agreement->leader_signed_at = now();
                $agreement->leader_signature_name = $this->userDisplayName($irr->manager);
            }
        }

        $agreement->save();

        $bothSigned = $agreement->employee_signed_at !== null && $agreement->leader_signed_at !== null;
        $this->refreshStepProgress($irr, 'conversation', $bothSigned);

        return response()->json([
            'success' => true,
            'saved_at' => now()->format('g:i A'),
            'data' => $this->conversationAgreementPayload($irr, $agreement, $userId),
        ]);
    }

    private function conversationAgreementPayload(IrrReview $irr, ?IrrConversationAgreement $agreement, int $userId): array
    {
        $yourRole = null;
        if ((int) $irr->employee_user_id === $userId) {
            $yourRole = 'employee';
        } elseif ((int) $irr->manager_user_id === $userId) {
            $yourRole = 'leader';
        }

        return [
            'conversation_notes' => $agreement?->conversation_notes ?? '',
            'conversation_date' => $agreement?->conversation_date?->toDateString(),
            'employee_signed_at' => $agreement?->employee_signed_at?->toIso8601String(),
            'employee_signature_name' => $agreement?->employee_signature_name,
            'leader_signed_at' => $agreement?->leader_signed_at?->toIso8601String(),
            'leader_signature_name' => $agreement?->leader_signature_name,
            'your_role' => $yourRole,
        ];
    }

    /** Step 5: Annual Development Commitments™ — up to 5, ordered by priority_rank. */
    public function getCommitments(Request $request, IrrReview $irr)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1 || ! $this->canAccessReview($userId, $irr)) {
            return $this->forbidden();
        }

        return response()->json([
            'success' => true,
            'data' => $irr->commitments()
                ->with('owner:ID,display_name,user_nicename')
                ->get()
                ->map(fn (IrrCommitment $row) => [
                    'id' => $row->id,
                    'title' => $row->title,
                    'description' => $row->description,
                    'owner_user_id' => $row->owner_user_id,
                    'owner_name' => $row->owner_name ?: $this->userDisplayName($row->owner),
                    'priority' => $row->priority,
                    'success_indicator' => $row->success_indicator,
                    'behavioral_driver' => $row->behavioral_driver,
                    'org_priority_type' => $row->org_priority_type,
                    'org_priority_label' => $row->org_priority_label,
                    'status' => $row->status,
                    'due_date' => $row->due_date?->format('Y-m-d'),
                    'priority_rank' => $row->priority_rank,
                ])
                ->values(),
        ]);
    }

    /** Step 5 save — replace-all semantics (max 5), same pattern as QBR/ARP commitments. */
    public function saveCommitments(Request $request, IrrReview $irr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->canEditReview($userId, $irr)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'items' => 'present|array|max:5',
            'items.*.title' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.owner_user_id' => 'nullable|integer',
            'items.*.owner_name' => 'nullable|string|max:255',
            'items.*.priority' => 'nullable|in:high,medium,low',
            'items.*.success_indicator' => 'nullable|string|max:255',
            'items.*.behavioral_driver' => 'nullable|in:get_real,fill_buckets,be_intentional,foster_grit,drive_growth',
            'items.*.org_priority_type' => 'nullable|in:arp,qbr',
            'items.*.org_priority_label' => 'nullable|string|max:255',
            'items.*.due_date' => 'nullable|string',
            'items.*.status' => 'nullable|in:open,in_progress,done',
        ]);

        if (count($data['items']) > 5) {
            return response()->json(['success' => false, 'message' => 'An Individual Readiness Review™ may have at most 5 commitments.'], 422);
        }

        DB::transaction(function () use ($irr, $data) {
            IrrCommitment::where('review_id', $irr->id)->delete();
            foreach (array_values($data['items']) as $index => $item) {
                $ownerId = filter_var($item['owner_user_id'] ?? null, FILTER_VALIDATE_INT);
                $ownerName = trim((string) ($item['owner_name'] ?? ''));
                IrrCommitment::create([
                    'review_id' => $irr->id,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? null,
                    'owner_user_id' => $ownerId !== false ? $ownerId : null,
                    'owner_name' => $ownerName !== '' ? $ownerName : null,
                    'priority' => $item['priority'] ?? 'medium',
                    'success_indicator' => $item['success_indicator'] ?? null,
                    'behavioral_driver' => $item['behavioral_driver'] ?? null,
                    'org_priority_type' => $item['org_priority_type'] ?? null,
                    'org_priority_label' => $item['org_priority_label'] ?? null,
                    'due_date' => ! empty($item['due_date']) ? $item['due_date'] : null,
                    'status' => $item['status'] ?? IrrCommitment::STATUS_OPEN,
                    'priority_rank' => $index,
                ]);
            }
        });

        $this->refreshStepProgress($irr, 'commitments', collect($data['items'])->contains(
            fn ($item) => trim((string) ($item['title'] ?? '')) !== ''
        ));

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
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

    private function refreshStepProgress(IrrReview $irr, string $step, bool $complete): void
    {
        $progress = is_array($irr->step_progress) ? $irr->step_progress : [];
        $progress[$step] = $complete;
        $irr->update(['step_progress' => $progress]);
    }
}
