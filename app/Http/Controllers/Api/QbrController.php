<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\FusionEvidenceLog;
use App\Models\Qbr;
use App\Models\QbrCommitment;
use App\Models\QbrDecision;
use App\Models\QbrEvidenceSnapshot;
use App\Models\QbrKpi;
use App\Services\QbrAiService;
use App\Services\QbrAssessmentFromEvidenceService;
use App\Services\QbrEvidenceService;
use App\Services\QbrSynthesisFromContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * QBR picker + wizard bridge for the WordPress [fusion_qbr_wizard] shortcode.
 *
 * Business rule: one QBR exists per (company GROUP, quarter, year) — enforced
 * by the `qbr_group_period_uq` unique key on wp_fusion_qbrs. Same
 * leader-edits / member-views access model as ArpController.
 */
class QbrController extends Controller
{
    /** Groups the given user leads, for the create dropdown. */
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
                'name' => $g->title.($g->company ? ' ('.$g->company->title.')' : ''),
                'company_id' => $g->company_id,
            ]),
        ]);
    }

    /** QBRs for groups this user belongs to (any role), newest first. */
    public function index(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $memberGroupIds = $this->memberGroupIds($userId);
        if ($memberGroupIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => [], 'has_access' => false]);
        }

        $leadableGroupIds = $this->leadableGroupIds($userId);

        $qbrs = Qbr::query()
            ->whereIn('company_group_id', $memberGroupIds)
            ->with(['company:id,title', 'companyGroup:id,title'])
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get();

        return response()->json([
            'success' => true,
            'has_access' => true,
            'can_create' => $leadableGroupIds->isNotEmpty(),
            'data' => $qbrs->map(fn (Qbr $q) => [
                'id' => $q->id,
                'company_id' => $q->company_id,
                'company_group_id' => $q->company_group_id,
                'company_name' => $q->companyGroup?->title ?? $q->company?->title,
                'quarter' => $q->quarter,
                'year' => $q->year,
                'status' => $q->status,
                'can_edit' => $leadableGroupIds->contains($q->company_group_id),
            ]),
        ]);
    }

    /** Create a new QBR. Resumes the existing record if group+quarter+year already exists. */
    public function store(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $data = $request->validate([
            'company_group_id' => 'required|integer|min:1',
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        if ($userId < 1 || ! $this->leadableGroupIds($userId)->contains((int) $data['company_group_id'])) {
            return response()->json(['success' => false, 'message' => 'You do not lead this group.'], 403);
        }

        $group = CompanyGroup::find($data['company_group_id']);
        if (! $group) {
            return response()->json(['success' => false, 'message' => 'Group not found.'], 404);
        }

        $existing = Qbr::where('company_group_id', $data['company_group_id'])
            ->where('quarter', $data['quarter'])
            ->where('year', $data['year'])
            ->first();
        if ($existing) {
            return response()->json(['success' => true, 'data' => $existing, 'already_existed' => true]);
        }

        $qbr = Qbr::create([
            'company_id' => $group->company_id,
            'company_group_id' => $group->id,
            'facilitator_user_id' => $userId,
            'quarter' => $data['quarter'],
            'year' => $data['year'],
            'status' => Qbr::STATUS_DRAFT,
            'created_by' => $userId,
        ]);

        return response()->json(['success' => true, 'data' => $qbr, 'already_existed' => false], 201);
    }

    public function show(Request $request, Qbr $qbr)
    {
        $userId = (int) $request->query('user_id');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $qbr->id,
                'company_id' => $qbr->company_id,
                'company_group_id' => $qbr->company_group_id,
                'company_name' => $qbr->company?->title,
                'group_name' => $qbr->companyGroup?->title,
                'quarter' => $qbr->quarter,
                'year' => $qbr->year,
                'status' => $qbr->status,
                'facilitator_name' => $qbr->facilitator?->display_name,
                'discussion_notes' => $qbr->discussion_notes,
                'created_at' => $qbr->created_at?->toIso8601String(),
                'updated_at' => $qbr->updated_at?->toIso8601String(),
                'held_at' => $qbr->held_at?->toIso8601String(),
                'step_progress' => is_array($qbr->step_progress) ? $qbr->step_progress : [],
                'can_edit' => $this->leadableGroupIds($userId)->contains($qbr->company_group_id),
            ],
        ]);
    }

    // -------------------------------------------------------------------
    // Step 1/2 — Evidence
    // -------------------------------------------------------------------

    /** Step 1: (re)generate the evidence snapshot and persist it. */
    public function generateEvidence(Request $request, Qbr $qbr, QbrEvidenceService $evidenceService)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $snapshot = $evidenceService->buildSnapshot($qbr);
        $row = QbrEvidenceSnapshot::create([
            'qbr_id' => $qbr->id,
            'snapshot' => $snapshot,
            'captured_at' => now(),
        ]);

        $this->refreshStepProgress($qbr, 'evidence', true);

        return response()->json(['success' => true, 'data' => $snapshot, 'captured_at' => $row->captured_at?->toIso8601String()]);
    }

    /** Step 2: latest evidence snapshot, generating one on first view if none exists. */
    public function getEvidence(Qbr $qbr, QbrEvidenceService $evidenceService)
    {
        $latest = $qbr->evidenceSnapshots()->first();
        if ($latest === null) {
            $snapshot = $evidenceService->buildSnapshot($qbr);
            $latest = QbrEvidenceSnapshot::create(['qbr_id' => $qbr->id, 'snapshot' => $snapshot, 'captured_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => $latest->snapshot,
            'captured_at' => $latest->captured_at?->toIso8601String(),
        ]);
    }

    // -------------------------------------------------------------------
    // Step 3 — AI Organizational Assessment™
    // -------------------------------------------------------------------

    public function getAssessment(Qbr $qbr, QbrAiService $ai)
    {
        $latest = $ai->latestAssessment($qbr);
        if ($latest === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json(['success' => true, 'data' => $this->assessmentPayload($latest)]);
    }

    public function generateAssessment(
        Request $request,
        Qbr $qbr,
        QbrAiService $ai,
        QbrEvidenceService $evidenceService,
        QbrAssessmentFromEvidenceService $composer
    ) {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $snapshot = $qbr->evidenceSnapshots()->first()?->snapshot ?? $evidenceService->buildSnapshot($qbr);

        $llmError = null;
        $assessment = $ai->generateAssessment($qbr, $snapshot);

        if ($assessment === null) {
            $llmError = $ai->getLastError();
            $previous = $ai->latestAssessment($qbr);
            $composed = $composer->compose($snapshot);

            $assessment = \App\Models\QbrAiAssessment::create([
                'qbr_id' => $qbr->id,
                'assessment' => $composed,
                'leadership_context' => $previous?->leadership_context,
                'agreement_rating' => $previous?->agreement_rating,
                'insight_model' => 'evidence-composer',
                'tokens_used' => 0,
                'cost_usd' => 0,
            ]);
        }

        $this->refreshStepProgress($qbr, 'assessment', true);

        return response()->json([
            'success' => true,
            'data' => $this->assessmentPayload($assessment),
            'meta' => ['llm_fallback' => $llmError !== null, 'llm_error' => $llmError],
        ]);
    }

    public function saveLeadershipContext(Request $request, Qbr $qbr, QbrAiService $ai)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'leadership_context' => 'nullable|string|max:2000',
            'agreement_rating' => 'nullable|in:strongly_agree,agree,neutral,disagree,strongly_disagree',
        ]);

        $assessment = $ai->saveLeadershipContext($qbr, $data['leadership_context'] ?? null, $data['agreement_rating'] ?? null);

        return response()->json(['success' => true, 'data' => $this->assessmentPayload($assessment)]);
    }

    private function assessmentPayload(\App\Models\QbrAiAssessment $a): array
    {
        return [
            'assessment' => $a->assessment,
            'leadership_context' => $a->leadership_context,
            'agreement_rating' => $a->agreement_rating,
            'insight_model' => $a->insight_model,
            'generated_at' => $a->created_at?->toIso8601String(),
        ];
    }

    // -------------------------------------------------------------------
    // Step 4 — Leadership Collaboration™ (discussion notes + key decisions)
    // -------------------------------------------------------------------

    public function saveDiscussionNotes(Request $request, Qbr $qbr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $data = $request->validate(['discussion_notes' => 'nullable|string|max:20000']);
        $qbr->update(['discussion_notes' => $data['discussion_notes'] ?? null]);
        $hasNotes = trim(strip_tags($data['discussion_notes'] ?? '')) !== '';
        $hasDecisions = $qbr->decisions()->where('decision', '!=', '')->exists();
        $this->refreshStepProgress($qbr, 'collaboration', $hasNotes || $hasDecisions);

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    public function getDecisions(Qbr $qbr)
    {
        return response()->json([
            'success' => true,
            'data' => $qbr->decisions()
                ->with('owner:ID,display_name')
                ->orderBy('priority_rank')
                ->get()
                ->map(fn (QbrDecision $row) => [
                    'id' => $row->id,
                    'decision' => $row->decision,
                    'owner_user_id' => $row->owner_user_id,
                    'owner_name' => $row->owner_name ?: $row->owner?->display_name,
                    'impact_area' => $row->impact_area,
                    'next_step' => $row->next_step,
                    'target_date' => $row->target_date?->format('Y-m-d'),
                    'priority_rank' => $row->priority_rank,
                ])
                ->values(),
        ]);
    }

    public function saveDecisions(Request $request, Qbr $qbr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'items' => 'present|array',
            'items.*.decision' => 'nullable|string|max:255',
            'items.*.owner_user_id' => 'nullable|integer',
            'items.*.owner_name' => 'nullable|string|max:255',
            'items.*.impact_area' => 'nullable|string|max:80',
            'items.*.next_step' => 'nullable|string',
            'items.*.target_date' => 'nullable|string',
        ]);

        DB::transaction(function () use ($qbr, $data) {
            QbrDecision::where('qbr_id', $qbr->id)->delete();
            foreach (array_values($data['items']) as $index => $item) {
                $ownerId = filter_var($item['owner_user_id'] ?? null, FILTER_VALIDATE_INT);
                $ownerName = trim((string) ($item['owner_name'] ?? ''));
                QbrDecision::create([
                    'qbr_id' => $qbr->id,
                    'decision' => $item['decision'] ?? '',
                    'owner_user_id' => $ownerId !== false ? $ownerId : null,
                    'owner_name' => $ownerName !== '' ? $ownerName : null,
                    'impact_area' => $item['impact_area'] ?? null,
                    'next_step' => $item['next_step'] ?? null,
                    'target_date' => ! empty($item['target_date']) ? $item['target_date'] : null,
                    'priority_rank' => $index,
                ]);
            }
        });

        $hasDecisions = collect($data['items'])->contains(
            fn ($item) => trim((string) ($item['decision'] ?? '')) !== ''
        );
        $hasNotes = trim(strip_tags((string) ($qbr->fresh()->discussion_notes ?? ''))) !== '';
        $this->refreshStepProgress($qbr, 'collaboration', $hasDecisions || $hasNotes);

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    // -------------------------------------------------------------------
    // Step 5 — Quarterly Commitments™ (max 5, auto carry-forward)
    // -------------------------------------------------------------------

    public function getCommitments(Qbr $qbr)
    {
        $this->carryForwardIncomplete($qbr);

        return response()->json([
            'success' => true,
            'data' => $qbr->commitments()
                ->with('owner:ID,display_name')
                ->orderBy('priority_rank')
                ->get()
                ->map(fn (QbrCommitment $row) => [
                    'id' => $row->id,
                    'title' => $row->title,
                    'description' => $row->description,
                    'owner_user_id' => $row->owner_user_id,
                    'owner_name' => $row->owner_name ?: $row->owner?->display_name,
                    'priority' => $row->priority,
                    'related_arp_objective' => $row->related_arp_objective,
                    'success_measure' => $row->success_measure,
                    'due_date' => $row->due_date?->format('Y-m-d'),
                    'status' => $row->status,
                    'carried_forward_from_id' => $row->carried_forward_from_id,
                    'priority_rank' => $row->priority_rank,
                ])
                ->values(),
        ]);
    }

    public function saveCommitments(Request $request, Qbr $qbr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'items' => 'present|array|max:5',
            'items.*.title' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.owner_user_id' => 'nullable|integer',
            'items.*.owner_name' => 'nullable|string|max:255',
            'items.*.priority' => 'nullable|in:high,medium,low',
            'items.*.related_arp_objective' => 'nullable|string|max:255',
            'items.*.success_measure' => 'nullable|string|max:255',
            'items.*.due_date' => 'nullable|string',
            'items.*.status' => 'nullable|in:open,in_progress,done,carried_forward',
            'items.*.carried_forward_from_id' => 'nullable|integer',
        ]);

        if (count($data['items']) > 5) {
            return response()->json(['success' => false, 'message' => 'A QBR may have at most 5 commitments.'], 422);
        }

        DB::transaction(function () use ($qbr, $data) {
            QbrCommitment::where('qbr_id', $qbr->id)->delete();
            foreach (array_values($data['items']) as $index => $item) {
                $ownerId = filter_var($item['owner_user_id'] ?? null, FILTER_VALIDATE_INT);
                $ownerName = trim((string) ($item['owner_name'] ?? ''));
                $carriedFromId = filter_var($item['carried_forward_from_id'] ?? null, FILTER_VALIDATE_INT);
                QbrCommitment::create([
                    'qbr_id' => $qbr->id,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? null,
                    'owner_user_id' => $ownerId !== false ? $ownerId : null,
                    'owner_name' => $ownerName !== '' ? $ownerName : null,
                    'priority' => $item['priority'] ?? 'medium',
                    'related_arp_objective' => $item['related_arp_objective'] ?? null,
                    'success_measure' => $item['success_measure'] ?? null,
                    'due_date' => ! empty($item['due_date']) ? $item['due_date'] : null,
                    'status' => $item['status'] ?? QbrCommitment::STATUS_OPEN,
                    'carried_forward_from_id' => $carriedFromId !== false ? $carriedFromId : null,
                    'priority_rank' => $index,
                ]);
            }
        });

        $this->refreshStepProgress($qbr, 'commitments', collect($data['items'])->contains(
            fn ($item) => trim((string) ($item['title'] ?? '')) !== ''
        ));

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    /** Pull incomplete commitments from the previous quarter's QBR, once, if this QBR has none yet. */
    private function carryForwardIncomplete(Qbr $qbr): void
    {
        if ($qbr->commitments()->exists()) {
            return;
        }

        $previous = $qbr->previousQuarter();
        if ($previous === null) {
            return;
        }

        $incomplete = QbrCommitment::where('qbr_id', $previous->id)
            ->where('status', '!=', QbrCommitment::STATUS_DONE)
            ->orderBy('priority_rank')
            ->limit(5)
            ->get();

        foreach ($incomplete as $index => $source) {
            QbrCommitment::create([
                'qbr_id' => $qbr->id,
                'title' => $source->title,
                'description' => $source->description,
                'owner_user_id' => $source->owner_user_id,
                'owner_name' => $source->owner_name,
                'priority' => $source->priority,
                'related_arp_objective' => $source->related_arp_objective,
                'success_measure' => $source->success_measure,
                'due_date' => $source->due_date,
                'status' => QbrCommitment::STATUS_CARRIED_FORWARD,
                'carried_forward_from_id' => $source->id,
                'priority_rank' => $index,
            ]);
        }
    }

    // -------------------------------------------------------------------
    // Step 2 KPIs (leader-entered custom business KPIs — no other source exists)
    // -------------------------------------------------------------------

    public function getKpis(Qbr $qbr)
    {
        return response()->json(['success' => true, 'data' => $qbr->kpis()->get()]);
    }

    public function saveKpis(Request $request, Qbr $qbr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'items' => 'present|array',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.current_value' => 'nullable|string|max:60',
            'items.*.target_value' => 'nullable|string|max:60',
            'items.*.status' => 'nullable|in:on_track,at_risk,off_track',
            'items.*.trend' => 'nullable|in:up,down,flat',
        ]);

        DB::transaction(function () use ($qbr, $data) {
            QbrKpi::where('qbr_id', $qbr->id)->delete();
            foreach (array_values($data['items']) as $index => $item) {
                QbrKpi::create([
                    'qbr_id' => $qbr->id,
                    'name' => $item['name'] ?? '',
                    'current_value' => $item['current_value'] ?? null,
                    'target_value' => $item['target_value'] ?? null,
                    'status' => $item['status'] ?? QbrKpi::STATUS_ON_TRACK,
                    'trend' => $item['trend'] ?? null,
                    'priority_rank' => $index,
                ]);
            }
        });

        return response()->json(['success' => true, 'saved_at' => now()->format('g:i A')]);
    }

    // -------------------------------------------------------------------
    // Step 6 — AI Organizational Synthesis™
    // -------------------------------------------------------------------

    public function getSynthesis(Qbr $qbr, QbrAiService $ai)
    {
        $latest = $ai->latestSynthesis($qbr);
        if ($latest === null) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json(['success' => true, 'data' => $latest->synthesis, 'generated_at' => $latest->created_at?->toIso8601String()]);
    }

    public function generateSynthesis(
        Request $request,
        Qbr $qbr,
        QbrAiService $ai,
        QbrEvidenceService $evidenceService,
        QbrSynthesisFromContextService $composer
    ) {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $snapshot = $qbr->evidenceSnapshots()->first()?->snapshot ?? $evidenceService->buildSnapshot($qbr);
        $assessment = $ai->latestAssessment($qbr);
        $commitments = $qbr->commitments()->get(['title', 'description', 'owner_user_id', 'priority', 'status'])->toArray();

        $context = [
            'evidence' => $snapshot,
            'assessment' => $assessment?->assessment,
            'leadership_context' => $assessment?->leadership_context,
            'agreement_rating' => $assessment?->agreement_rating,
            'discussion_notes' => $qbr->discussion_notes,
            'commitments' => $commitments,
        ];

        $llmError = null;
        $synthesis = $ai->generateSynthesis($qbr, $context);

        if ($synthesis === null) {
            $llmError = $ai->getLastError();
            $composed = $composer->compose(
                $snapshot,
                $assessment?->assessment ?? [],
                $assessment?->leadership_context,
                $qbr->discussion_notes,
                $commitments,
            );

            $synthesis = \App\Models\QbrAiSynthesis::create([
                'qbr_id' => $qbr->id,
                'synthesis' => $composed,
                'insight_model' => 'context-composer',
                'tokens_used' => 0,
                'cost_usd' => 0,
            ]);
        }

        $this->refreshStepProgress($qbr, 'synthesis', true);

        return response()->json([
            'success' => true,
            'data' => $synthesis->synthesis,
            'meta' => ['llm_fallback' => $llmError !== null, 'llm_error' => $llmError],
        ]);
    }

    // -------------------------------------------------------------------
    // Step 7 — Publish / Archive
    // -------------------------------------------------------------------

    public function publish(Request $request, Qbr $qbr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $qbr->update(['status' => Qbr::STATUS_CLOSED, 'held_at' => now()]);
        $this->refreshStepProgress($qbr, 'publish', true);

        FusionEvidenceLog::create([
            'source_type' => FusionEvidenceLog::SOURCE_QBR,
            'source_id' => $qbr->id,
            'event_type' => FusionEvidenceLog::EVENT_QBR_PUBLISHED,
            'user_id' => $userId,
            'evidence_date' => now()->toDateString(),
            'metadata' => [
                'qbr_id' => $qbr->id,
                'company_group_id' => $qbr->company_group_id,
                'quarter' => $qbr->quarter,
                'year' => $qbr->year,
            ],
        ]);

        return response()->json(['success' => true, 'data' => ['status' => $qbr->status, 'held_at' => $qbr->held_at?->toIso8601String()]]);
    }

    public function archive(Request $request, Qbr $qbr)
    {
        $userId = (int) $request->input('user_id');
        if (! $this->assertEdit($userId, $qbr)) {
            return $this->forbidden();
        }

        $qbr->update(['status' => Qbr::STATUS_ARCHIVED]);

        FusionEvidenceLog::create([
            'source_type' => FusionEvidenceLog::SOURCE_QBR,
            'source_id' => $qbr->id,
            'event_type' => FusionEvidenceLog::EVENT_QBR_ARCHIVED,
            'user_id' => $userId,
            'evidence_date' => now()->toDateString(),
            'metadata' => ['qbr_id' => $qbr->id, 'company_group_id' => $qbr->company_group_id],
        ]);

        return response()->json(['success' => true, 'data' => ['status' => $qbr->status]]);
    }

    // -------------------------------------------------------------------
    // Access control helpers
    // -------------------------------------------------------------------

    private function assertEdit(int $userId, Qbr $qbr): bool
    {
        return $userId >= 1 && $this->leadableGroupIds($userId)->contains($qbr->company_group_id);
    }

    private function forbidden()
    {
        return response()->json(['success' => false, 'message' => 'You do not lead this QBR\'s company group.'], 403);
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

    private function memberGroupIds(int $userId)
    {
        return CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->whereHas('companyGroup')
            ->pluck('company_group_id')
            ->filter()
            ->unique()
            ->values();
    }

    /** Merge one step's completion flag into the JSON step_progress map. */
    private function refreshStepProgress(Qbr $qbr, string $step, bool $complete): void
    {
        $progress = is_array($qbr->step_progress) ? $qbr->step_progress : [];
        $progress[$step] = $complete;
        $qbr->update(['step_progress' => $progress]);
    }
}
