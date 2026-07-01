<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OneOnOne;
use App\Models\OneOnOneCommitment;
use App\Models\OneOnOneConversation;
use App\Models\OneOnOneNote;
use App\Models\OneOnOnePreparation;
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
