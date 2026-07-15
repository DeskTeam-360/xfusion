<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\CourseScoringGroup;
use App\Models\OneOnOne;
use App\Models\OneOnOneAiBrief;
use App\Models\OneOnOneCommitment;
use App\Models\OneOnOneConversation;
use App\Models\OneOnOneNote;
use App\Models\OneOnOnePreparation;
use App\Models\User;
use App\Models\WpGfEntry;
use App\Models\WpGfEntryMeta;
use App\Services\MeetingBriefFromEvidenceService;
use App\Services\OneOnOneAiService;
use App\Services\OneOnOneCompanyGroupSyncService;
use Illuminate\Http\Request;

/**
 * Bridge for the WordPress [fusion_one_on_one] shortcode. All preparation /
 * reveal logic lives here (not in the WP plugin) so the privacy rule —
 * "neither side sees the other's preparation before the meeting starts" —
 * is enforced server-side, not just hidden in the UI.
 */
class OneOnOneController extends Controller
{
    public function __construct(
        private readonly OneOnOneCompanyGroupSyncService $companyGroupSync,
    ) {}

    /**
     * Convert empty strings to null so nullable validation rules accept cleared fields.
     *
     * @return array<string, mixed>
     */
    private function normalizeCommitmentPayload(Request $request): array
    {
        $data = $request->all();

        foreach (['description', 'behavioral_driver', 'success_indicator', 'due_date', 'owner_user_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $request->merge($data);

        return $data;
    }

    /**
     * Ensure JSON request body is merged into input (needed for POST/PATCH updates).
     */
    private function mergeJsonPayload(Request $request): void
    {
        if ($request->isJson()) {
            $request->merge($request->json()->all());
        }
    }

    /**
     * Merge request fields + description JSON so driver/indicator always persist.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncCommitmentMeta(array $data): array
    {
        $meta = [];

        if (! empty($data['description']) && is_string($data['description'])) {
            $decoded = json_decode($data['description'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        foreach (['priority', 'status', 'behavioral_driver', 'success_indicator'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $meta[$field] = $data[$field];
            }
        }

        if ($meta !== []) {
            $data['description'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
            foreach (['priority', 'status', 'behavioral_driver', 'success_indicator'] as $field) {
                if (array_key_exists($field, $meta) && $meta[$field] !== '') {
                    $data[$field] = $meta[$field];
                }
            }
        }

        return $data;
    }

    /** Pairs derived from Company Group membership (leader ↔ member). */
    public function pairsForUser(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $this->companyGroupSync->syncAllFromCompanyGroups();

        $pairs = OneOnOne::query()
            ->where('status', OneOnOne::STATUS_ACTIVE)
            ->where(function ($q) use ($userId) {
                $q->where('leader_user_id', $userId)->orWhere('employee_user_id', $userId);
            })
            ->with(['leader:ID,display_name,user_nicename', 'employee:ID,display_name,user_nicename'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pairs->map(fn (OneOnOne $p) => [
                'id' => $p->id,
                'role' => $p->leader_user_id === $userId ? 'leader' : 'employee',
                'leader' => $p->leader ? ['id' => $p->leader->ID, 'name' => $p->leader->display_name ?: $p->leader->user_nicename] : null,
                'employee' => $p->employee ? ['id' => $p->employee->ID, 'name' => $p->employee->display_name ?: $p->employee->user_nicename] : null,
            ]),
        ]);
    }

    /**
     * @return list<int>
     */
    private function teamMemberUserIdsForLeader(int $leaderUserId): array
    {
        return $this->companyGroupSync->teamMemberUserIdsForLeader($leaderUserId);
    }

    private function canLeaderScheduleFor(int $leaderUserId, int $employeeUserId): bool
    {
        if ($leaderUserId < 1 || $employeeUserId < 1 || $leaderUserId === $employeeUserId) {
            return false;
        }

        return in_array($employeeUserId, $this->teamMemberUserIdsForLeader($leaderUserId), true);
    }

    private function resolveCompanyIdForPair(int $leaderUserId, int $employeeUserId): int
    {
        $existing = OneOnOne::query()
            ->where('leader_user_id', $leaderUserId)
            ->where('employee_user_id', $employeeUserId)
            ->value('company_id');

        if ($existing) {
            return (int) $existing;
        }

        $leaderCompanies = CompanyEmployee::query()
            ->where('user_id', $leaderUserId)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id);

        if ($leaderCompanies->isNotEmpty()) {
            $shared = CompanyEmployee::query()
                ->where('user_id', $employeeUserId)
                ->whereIn('company_id', $leaderCompanies)
                ->value('company_id');

            if ($shared) {
                return (int) $shared;
            }

            return (int) $leaderCompanies->first();
        }

        return 0;
    }

    /** Leader roster from Company Groups — members with synced pair ids. */
    public function leaderTeam(Request $request)
    {
        $leaderUserId = (int) $request->query('user_id');
        if ($leaderUserId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $this->companyGroupSync->syncAllFromCompanyGroups();

        $memberIds = $this->teamMemberUserIdsForLeader($leaderUserId);
        if ($memberIds === []) {
            return response()->json(['success' => true, 'data' => ['is_leader' => false, 'leader' => null, 'members' => []]]);
        }

        $leader = User::query()->find($leaderUserId, ['ID', 'display_name', 'user_nicename']);
        $employees = User::query()
            ->whereIn('ID', $memberIds)
            ->orderBy('display_name')
            ->get(['ID', 'display_name', 'user_nicename']);

        $pairs = OneOnOne::query()
            ->where('leader_user_id', $leaderUserId)
            ->whereIn('employee_user_id', $memberIds)
            ->where('status', OneOnOne::STATUS_ACTIVE)
            ->get(['id', 'employee_user_id'])
            ->keyBy('employee_user_id');

        return response()->json([
            'success' => true,
            'data' => [
                'is_leader' => true,
                'leader' => $leader ? [
                    'id' => (int) $leader->ID,
                    'name' => $leader->display_name ?: $leader->user_nicename,
                ] : null,
                'members' => $employees->map(fn (User $u) => [
                    'employee' => [
                        'id' => (int) $u->ID,
                        'name' => $u->display_name ?: $u->user_nicename,
                    ],
                    'pair_id' => isset($pairs[$u->ID]) ? (int) $pairs[$u->ID]->id : null,
                ])->values(),
            ],
        ]);
    }

    /** Schedule a meeting for a team member — creates the pair automatically when missing. */
    public function scheduleForEmployee(Request $request)
    {
        $data = $request->validate([
            'leader_user_id' => 'required|integer|min:1',
            'employee_user_id' => 'required|integer|min:1|different:leader_user_id',
            'scheduled_at' => 'required|date',
            'meeting_link' => 'nullable|url|max:500',
            'group_id' => 'nullable|integer|min:1',
        ]);

        $leaderUserId = (int) $data['leader_user_id'];
        $employeeUserId = (int) $data['employee_user_id'];
        $groupId = isset($data['group_id']) ? (int) $data['group_id'] : 0;

        if ($groupId > 0) {
            if (! $this->companyGroupSync->isMemberOfLeaderGroup($leaderUserId, $employeeUserId, $groupId)) {
                return response()->json(['success' => false, 'message' => 'Employee is not a member of this group.'], 403);
            }
        } elseif (! $this->canLeaderScheduleFor($leaderUserId, $employeeUserId)) {
            return response()->json(['success' => false, 'message' => 'Not allowed to schedule for this team member.'], 403);
        }

        $this->companyGroupSync->syncAllFromCompanyGroups();

        $companyId = $this->resolveCompanyIdForPair($leaderUserId, $employeeUserId);
        if ($companyId < 1) {
            return response()->json(['success' => false, 'message' => 'Could not resolve company for this pairing.'], 422);
        }

        $pair = OneOnOne::query()->firstOrCreate(
            [
                'leader_user_id' => $leaderUserId,
                'employee_user_id' => $employeeUserId,
            ],
            [
                'company_id' => $companyId,
                'status' => OneOnOne::STATUS_ACTIVE,
            ]
        );

        if ($pair->status !== OneOnOne::STATUS_ACTIVE) {
            $pair->update(['status' => OneOnOne::STATUS_ACTIVE, 'company_id' => $companyId]);
        }

        $conversation = $pair->conversations()->create([
            'scheduled_at' => $data['scheduled_at'],
            'meeting_link' => $data['meeting_link'] ?? null,
            'status' => OneOnOneConversation::STATUS_SCHEDULED,
        ]);

        $leader = User::query()->find($leaderUserId, ['ID', 'display_name', 'user_nicename']);
        $employee = User::query()->find($employeeUserId, ['ID', 'display_name', 'user_nicename']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'pair_id' => $pair->id,
                'scheduled_at' => $conversation->scheduled_at,
                'meeting_link' => $conversation->meeting_link,
                'status' => $conversation->status,
                'leader' => $leader ? ['id' => (int) $leader->ID, 'name' => $leader->display_name ?: $leader->user_nicename] : null,
                'employee' => $employee ? ['id' => (int) $employee->ID, 'name' => $employee->display_name ?: $employee->user_nicename] : null,
            ],
        ], 201);
    }

    /** Groups + all meetings for the meeting picker gate (supports leader and member in multiple groups). */
    public function meetingDashboard(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        $this->companyGroupSync->syncAllFromCompanyGroups();

        $user = User::query()->find($userId, ['ID', 'display_name', 'user_nicename']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user ? [
                    'id' => (int) $user->ID,
                    'name' => $user->display_name ?: $user->user_nicename,
                ] : null,
                'groups' => $this->companyGroupSync->groupsForUser($userId),
                'meetings' => $this->companyGroupSync->meetingsForUser($userId),
            ],
        ]);
    }

    /**
     * Per-scoring-group status badges (label only, no gauge/needle) for the
     * employee side of this pairing. Lets the leader see where the employee
     * currently stands without leaving the 1-on-1 conversation.
     */
    public function employeeScoring(OneOnOne $oneOnOne)
    {
        $userId = $oneOnOne->employee_user_id;

        $groups = CourseScoringGroup::with('details')->get();
        $result = [];

        foreach ($groups as $group) {
            $details = $group->details->filter(
                fn ($d) => (int) $d->form_id > 0 && (int) $d->field_id > 0 && (float) ($d->weight ?? 1.0) > 0
            );

            if ($details->isEmpty()) {
                continue;
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

            $entryIds = array_values($latestEntryByForm);
            $valueMap = [];
            if ($entryIds !== []) {
                WpGfEntryMeta::query()
                    ->whereIn('entry_id', $entryIds)
                    ->get(['entry_id', 'meta_key', 'meta_value'])
                    ->each(function ($m) use (&$valueMap) {
                        $k = explode('.', (string) $m->meta_key)[0];
                        $valueMap[(int) $m->entry_id][$k] = (string) $m->meta_value;
                    });
            }

            $weightedSum = 0.0;
            $weightTotal = 0.0;
            foreach ($details as $d) {
                $fid      = (int) $d->form_id;
                $fieldKey = (string) (int) $d->field_id;
                $weight   = (float) ($d->weight ?? 1.0);

                $entryId = $latestEntryByForm[$fid] ?? null;
                if ($entryId === null) {
                    continue;
                }

                $num = $this->parseScaleScore($valueMap[$entryId][$fieldKey] ?? null);
                if ($num === null) {
                    continue;
                }

                $weightedSum += $num * $weight;
                $weightTotal += $weight;
            }

            $avg  = $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null;
            $zone = $this->scoringZoneMeta($avg);

            $result[] = [
                'title'      => (string) $group->title,
                'average'    => $avg,
                'zone_label' => $zone['label'],
                'zone_color' => $zone['color'],
            ];
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /** @return array{color: string, label: string} */
    private function scoringZoneMeta(?float $v): array
    {
        if ($v === null) {
            return ['color' => '#6b7280', 'label' => 'No data'];
        }
        if ($v < 3.0) {
            return ['color' => '#dc2626', 'label' => 'Needs improvement'];
        }
        if ($v < 4.5) {
            return ['color' => '#ca8a04', 'label' => 'Progressing'];
        }

        return ['color' => '#16a34a', 'label' => 'Excellent'];
    }

    private function parseScaleScore(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $s = trim($raw);
        if ($s === '' || $s === '-') {
            return null;
        }
        $s = str_replace(',', '.', $s);
        if (! preg_match('/^-?\d+(\.\d+)?$/', $s)) {
            return null;
        }
        $num = (float) $s;
        if ($num < 1.0 || $num > 5.0 || abs($num - round($num)) > 0.00001) {
            return null;
        }

        return $num;
    }

    public function conversations(OneOnOne $oneOnOne)
    {
        return response()->json([
            'success' => true,
            'data' => $oneOnOne->conversations()->get(['id', 'scheduled_at', 'held_at', 'meeting_link', 'status']),
        ]);
    }

    public function scheduleConversation(Request $request, OneOnOne $oneOnOne)
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date',
            'meeting_link' => 'nullable|url|max:500',
        ]);

        $conversation = $oneOnOne->conversations()->create([
            'scheduled_at' => $data['scheduled_at'],
            'meeting_link' => $data['meeting_link'] ?? null,
            'status' => OneOnOneConversation::STATUS_SCHEDULED,
        ]);

        return response()->json(['success' => true, 'data' => $conversation], 201);
    }

    /**
     * Submit private preparation (employee or leader). Always hidden
     * (is_revealed=false) until reveal() is called.
     */
    public function submitPreparation(Request $request, OneOnOneConversation $conversation)
    {
        $data = $request->validate([
            'author_role' => 'required|in:employee,leader',
            'author_user_id' => 'required|integer|min:1',
            'content' => 'required|string',
        ]);

        $prep = OneOnOnePreparation::updateOrCreate(
            ['conversation_id' => $conversation->id, 'author_role' => $data['author_role']],
            [
                'author_user_id' => $data['author_user_id'],
                'content' => $data['content'],
            ]
        );

        return response()->json(['success' => true, 'data' => ['id' => $prep->id, 'is_revealed' => $prep->is_revealed]]);
    }

    /** Whether the current user's counterpart has submitted prep — never returns the content. */
    public function preparationStatus(OneOnOneConversation $conversation)
    {
        $rows = $conversation->preparations()->get(['author_role', 'is_revealed']);

        return response()->json([
            'success' => true,
            'data' => [
                'employee_submitted' => $rows->firstWhere('author_role', 'employee') !== null,
                'leader_submitted' => $rows->firstWhere('author_role', 'leader') !== null,
                'revealed' => (bool) ($rows->firstWhere('author_role', 'employee')?->is_revealed
                    ?? $rows->firstWhere('author_role', 'leader')?->is_revealed
                    ?? false),
            ],
        ]);
    }

    /** Reveal both preparations — call when the meeting actually starts. */
    public function reveal(OneOnOneConversation $conversation)
    {
        $conversation->preparations()->update([
            'is_revealed' => true,
            'revealed_at' => now(),
        ]);

        $conversation->update(['status' => OneOnOneConversation::STATUS_IN_PROGRESS]);

        $revealed = $conversation->preparations()->where('is_revealed', true)->get(['author_role', 'content']);

        return response()->json(['success' => true, 'data' => $revealed]);
    }

    public function brief(OneOnOneConversation $conversation, OneOnOneAiService $ai)
    {
        $brief = $conversation->brief;

        if ($brief === null) {
            return response()->json(['success' => false, 'message' => 'AI Meeting Brief has not been generated yet.'], 404);
        }

        return response()->json(['success' => true, 'data' => $brief->brief]);
    }

    /**
     * Generate AI Meeting Brief from Step 1 continuous evidence (all 12 sections).
     */
    public function generateBrief(
        Request $request,
        OneOnOneConversation $conversation,
        OneOnOneAiService $ai,
        MeetingBriefFromEvidenceService $composer
    ) {
        $this->mergeJsonPayload($request);

        $evidenceContext = $request->input('evidence_context', []);
        if (! is_array($evidenceContext)) {
            $evidenceContext = [];
        }

        $forceRefresh = $request->boolean('force_refresh', true);

        $briefModel = $ai->meetingBriefFromEvidence($conversation, $evidenceContext, $forceRefresh);

        if ($briefModel === null) {
            $composed = $composer->compose($evidenceContext);
            OneOnOneAiBrief::query()->where('conversation_id', $conversation->id)->delete();
            $briefModel = OneOnOneAiBrief::create([
                'conversation_id' => $conversation->id,
                'brief' => $composed,
                'insight_model' => 'evidence-composer',
                'tokens_used' => 0,
                'cost_usd' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $briefModel->brief,
            'meta' => [
                'insight_model' => $briefModel->insight_model,
                'generated_at' => $briefModel->created_at?->toIso8601String(),
            ],
        ]);
    }

    /** Generate AI Meeting Synthesis from prep, notes, and commitments for this conversation. */
    public function generateSynthesis(Request $request, OneOnOneConversation $conversation, OneOnOneAiService $ai)
    {
        $this->mergeJsonPayload($request);

        $forceRefresh = $request->boolean('force_refresh', true);
        $synthesis = $ai->meetingSynthesis($conversation, $forceRefresh);

        if ($synthesis === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate AI Meeting Synthesis. Check LLM configuration and try again.',
            ], 502);
        }

        $debugPayload = null;
        if ($request->boolean('debug', false)) {
            $pair = $conversation->oneOnOne;
            $debugPayload = [
                'conversation_id' => $conversation->id,
                'leader_user_id' => $pair?->leader_user_id,
                'employee_user_id' => $pair?->employee_user_id,
                'preparations' => $conversation->preparations()->get(['author_role', 'content'])->mapWithKeys(
                    fn ($p) => [$p->author_role => $p->content]
                ),
                'notes' => $conversation->notes()->get(['section', 'note']),
                'commitments' => $conversation->commitments()->get(['title', 'description', 'owner_role', 'status']),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $synthesis->synthesis,
            'meta' => [
                'insight_model' => $synthesis->insight_model,
                'generated_at' => $synthesis->created_at?->toIso8601String(),
            ],
            'debug_payload' => $debugPayload,
        ]);
    }

    /** Fetch the calling user's own preparation (never the other party's). */
    public function myPreparation(Request $request, OneOnOneConversation $conversation)
    {
        $userId = (int) $request->query('user_id');
        $prep = $conversation->preparations()->where('author_user_id', $userId)->first(['author_role', 'content', 'is_revealed']);

        return response()->json([
            'success' => true,
            'data' => $prep ? ['content' => $prep->content, 'author_role' => $prep->author_role, 'is_revealed' => (bool) $prep->is_revealed] : null,
        ]);
    }

    /** Fetch all notes for a conversation. */
    public function getNotes(OneOnOneConversation $conversation)
    {
        return response()->json([
            'success' => true,
            'data' => $conversation->notes()->orderBy('id')->get(['id', 'section', 'note', 'created_by', 'created_at']),
        ]);
    }

    /** Fetch all commitments for a conversation. */
    public function getCommitments(OneOnOneConversation $conversation)
    {
        return response()->json([
            'success' => true,
            'data' => $conversation->commitments()->orderBy('id')->get([
                'id', 'title', 'description', 'priority', 'behavioral_driver',
                'success_indicator', 'owner_role', 'status', 'due_date',
            ]),
        ]);
    }

    public function storeNote(Request $request, OneOnOneConversation $conversation)
    {
        $data = $request->validate([
            'section' => 'required|string|max:60',
            'note' => 'required|string',
            'created_by' => 'required|integer|min:1',
        ]);

        $note = OneOnOneNote::create([
            'conversation_id' => $conversation->id,
            ...$data,
        ]);

        return response()->json(['success' => true, 'data' => $note], 201);
    }

    public function storeCommitment(Request $request, OneOnOneConversation $conversation)
    {
        $this->mergeJsonPayload($request);
        $this->normalizeCommitmentPayload($request);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:high,medium,low',
            'status' => 'nullable|in:open,in_progress,done',
            'behavioral_driver' => 'nullable|in:get_real,fill_buckets,be_intentional,foster_grit,drive_growth',
            'success_indicator' => 'nullable|string',
            'owner_role' => 'required|in:employee,leader,shared',
            'owner_user_id' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date',
        ]);

        $data = $this->syncCommitmentMeta($data);

        $commitment = OneOnOneCommitment::create([
            'conversation_id' => $conversation->id,
            ...$data,
            'status' => $data['status'] ?? OneOnOneCommitment::STATUS_OPEN,
        ]);

        return response()->json(['success' => true, 'data' => $commitment], 201);
    }

    public function updateCommitment(Request $request, OneOnOneCommitment $commitment)
    {
        $this->mergeJsonPayload($request);
        $this->normalizeCommitmentPayload($request);

        // Wizard always sends the full row — same rules as create (not "sometimes").
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:high,medium,low',
            'status' => 'nullable|in:open,in_progress,done',
            'behavioral_driver' => 'nullable|in:get_real,fill_buckets,be_intentional,foster_grit,drive_growth',
            'success_indicator' => 'nullable|string',
            'owner_role' => 'required|in:employee,leader,shared',
            'owner_user_id' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date',
        ]);

        if ($data === []) {
            return response()->json(['success' => false, 'message' => 'No fields to update.'], 422);
        }

        $data = $this->syncCommitmentMeta($data);

        $commitment->forceFill($data);
        $commitment->save();

        return response()->json(['success' => true, 'data' => $commitment->fresh()]);
    }

    /**
     * Step 1 evidence — previous meetings + all commitments for the employee
     * in this pairing (regardless of which leader held prior meetings).
     */
    public function evidence(OneOnOneConversation $conversation)
    {
        $conversation->loadMissing('oneOnOne.leader:ID,display_name,user_nicename');
        $oneOnOne = $conversation->oneOnOne;
        if ($oneOnOne === null) {
            return response()->json(['success' => false, 'message' => 'Pairing not found.'], 404);
        }

        $employeeId = (int) $oneOnOne->employee_user_id;
        $currentId = (int) $conversation->id;

        $previousMeetings = OneOnOneConversation::query()
            ->where('id', '!=', $currentId)
            ->whereHas('oneOnOne', fn ($q) => $q->where('employee_user_id', $employeeId))
            ->with(['oneOnOne.leader:ID,display_name,user_nicename'])
            ->orderByRaw('COALESCE(held_at, scheduled_at) DESC')
            ->get()
            ->map(fn (OneOnOneConversation $c) => [
                'id' => $c->id,
                'scheduled_at' => $c->scheduled_at?->toIso8601String(),
                'held_at' => $c->held_at?->toIso8601String(),
                'status' => $c->status,
                'leader' => $c->oneOnOne?->leader ? [
                    'id' => (int) $c->oneOnOne->leader->ID,
                    'name' => $c->oneOnOne->leader->display_name ?: $c->oneOnOne->leader->user_nicename,
                ] : null,
            ])
            ->values();

        $commitments = OneOnOneCommitment::query()
            ->whereHas('conversation.oneOnOne', fn ($q) => $q->where('employee_user_id', $employeeId))
            ->with(['conversation.oneOnOne.leader:ID,display_name,user_nicename'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (OneOnOneCommitment $row) {
                $conv = $row->conversation;
                $leader = $conv?->oneOnOne?->leader;
                $meetingAt = $conv?->held_at ?? $conv?->scheduled_at;

                return [
                    'id' => $row->id,
                    'conversation_id' => $row->conversation_id,
                    'title' => $row->title,
                    'description' => $row->description,
                    'priority' => $row->priority,
                    'behavioral_driver' => $row->behavioral_driver,
                    'success_indicator' => $row->success_indicator,
                    'owner_role' => $row->owner_role,
                    'owner_user_id' => $row->owner_user_id,
                    'status' => $row->status,
                    'due_date' => $row->due_date?->format('Y-m-d'),
                    'created_at' => $row->created_at?->toIso8601String(),
                    'meeting' => [
                        'scheduled_at' => $conv?->scheduled_at?->toIso8601String(),
                        'held_at' => $conv?->held_at?->toIso8601String(),
                        'meeting_at' => $meetingAt?->toIso8601String(),
                        'status' => $conv?->status,
                        'leader_name' => $leader
                            ? ($leader->display_name ?: $leader->user_nicename)
                            : null,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'employee_id' => $employeeId,
                'current_conversation_id' => $currentId,
                'previous_meetings' => $previousMeetings,
                'commitments' => $commitments,
            ],
        ]);
    }

    /** Mark conversation held + trigger AI synthesis. */
    public function complete(OneOnOneConversation $conversation, OneOnOneAiService $ai)
    {
        $conversation->update([
            'status' => OneOnOneConversation::STATUS_COMPLETED,
            'held_at' => now(),
        ]);

        $synthesis = $ai->meetingSynthesis($conversation);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->fresh(),
                'synthesis' => $synthesis?->synthesis,
                'synthesis_available' => $synthesis !== null,
            ],
        ]);
    }

    /** Update meeting workflow status (leader or employee on the pair). */
    public function updateConversationStatus(Request $request, OneOnOneConversation $conversation)
    {
        $this->mergeJsonPayload($request);

        $data = $request->validate([
            'user_id' => 'required|integer|min:1',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        $userId = (int) $data['user_id'];
        $pair = $conversation->oneOnOne;
        if ($pair === null) {
            return response()->json(['success' => false, 'message' => 'Pairing not found.'], 404);
        }

        if ($pair->leader_user_id !== $userId && $pair->employee_user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Not authorized to update this meeting.'], 403);
        }

        $status = $data['status'];
        $updates = ['status' => $status];

        if ($status === OneOnOneConversation::STATUS_COMPLETED && $conversation->held_at === null) {
            $updates['held_at'] = now();
        }

        if ($status === OneOnOneConversation::STATUS_IN_PROGRESS) {
            $conversation->preparations()->update([
                'is_revealed' => true,
                'revealed_at' => now(),
            ]);
        }

        $conversation->update($updates);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $conversation->id,
                'status' => (string) $conversation->status,
                'held_at' => $conversation->held_at?->toIso8601String(),
            ],
        ]);
    }
}
