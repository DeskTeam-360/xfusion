<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseScoringGroup;
use App\Models\OneOnOne;
use App\Models\OneOnOneCommitment;
use App\Models\OneOnOneConversation;
use App\Models\OneOnOneNote;
use App\Models\OneOnOnePreparation;
use App\Models\WpGfEntry;
use App\Models\WpGfEntryMeta;
use App\Services\OneOnOneAiService;
use Illuminate\Http\Request;

/**
 * Bridge for the WordPress [fusion_one_on_one] shortcode. All preparation /
 * reveal logic lives here (not in the WP plugin) so the privacy rule —
 * "neither side sees the other's preparation before the meeting starts" —
 * is enforced server-side, not just hidden in the UI.
 */
class OneOnOneController extends Controller
{
    /** Pairs where the given user is leader or employee. */
    public function pairsForUser(Request $request)
    {
        $userId = (int) $request->query('user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

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
        $brief = $ai->meetingBrief($conversation);

        if ($brief === null) {
            return response()->json(['success' => false, 'message' => 'Brief unavailable (AI service not configured or request failed).'], 503);
        }

        return response()->json(['success' => true, 'data' => $brief->brief]);
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
            'data' => $conversation->commitments()->orderBy('id')->get(['id', 'title', 'description', 'owner_role', 'status', 'due_date']),
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
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_role' => 'required|in:employee,leader,shared',
            'owner_user_id' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date',
        ]);

        $commitment = OneOnOneCommitment::create([
            'conversation_id' => $conversation->id,
            'status' => OneOnOneCommitment::STATUS_OPEN,
            ...$data,
        ]);

        return response()->json(['success' => true, 'data' => $commitment], 201);
    }

    public function updateCommitment(Request $request, OneOnOneCommitment $commitment)
    {
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,done',
        ]);

        $commitment->update($data);

        return response()->json(['success' => true, 'data' => $commitment]);
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
}
