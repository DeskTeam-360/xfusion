<?php

namespace App\Services;

use App\Models\OneOnOneAiBrief;
use App\Models\OneOnOneAiSynthesis;
use App\Models\OneOnOneConversation;
use App\Models\OneOnOnePreparation;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bridges Laravel to the Xfusion-llm (Python FastAPI) service for 1-on-1
 * AI Meeting Brief / Meeting Synthesis. Mirrors XfusionLlmKnowledgeService's
 * client setup (same base URL / token / timeout config).
 *
 * Privacy: only AI synthesis from prior conversations is sent as context for
 * the brief — never raw preparation text. Preparation text is only sent for
 * the *current* conversation's synthesis, after the meeting has happened.
 */
class OneOnOneAiService
{
    public function isConfigured(): bool
    {
        return config('xfusion-llm.api_url') !== '';
    }

    /**
     * Generate (or return cached) AI Meeting Brief for a conversation.
     */
    public function meetingBrief(OneOnOneConversation $conversation, bool $forceRefresh = false): ?OneOnOneAiBrief
    {
        return $this->meetingBriefFromEvidence($conversation, [], $forceRefresh);
    }

    /**
     * Generate AI Meeting Brief using Step 1 continuous evidence context.
     *
     * @param  array<string, mixed>  $evidenceContext
     */
    public function meetingBriefFromEvidence(
        OneOnOneConversation $conversation,
        array $evidenceContext = [],
        bool $forceRefresh = false
    ): ?OneOnOneAiBrief {
        if (! $forceRefresh) {
            $existing = $conversation->brief;
            if ($existing !== null) {
                return $existing;
            }
        } else {
            OneOnOneAiBrief::query()->where('conversation_id', $conversation->id)->delete();
        }

        if (! $this->isConfigured()) {
            return null;
        }

        $pair = $conversation->oneOnOne;
        $priorSyntheses = OneOnOneAiSynthesis::query()
            ->whereIn('conversation_id', $pair->conversations()->where('id', '!=', $conversation->id)->pluck('id'))
            ->orderByDesc('id')
            ->limit(6)
            ->pluck('synthesis');

        try {
            $response = $this->client()
                ->post('/api/v1/one-on-one/meeting-brief', [
                    'conversation_id' => $conversation->id,
                    'leader_user_id' => $pair->leader_user_id,
                    'employee_user_id' => $pair->employee_user_id,
                    'prior_syntheses' => $priorSyntheses->values()->all(),
                    'evidence_context' => $evidenceContext,
                ])
                ->throw();

            $body = $response->json();
            $briefPayload = $body['brief'] ?? $body;

            return OneOnOneAiBrief::create([
                'conversation_id' => $conversation->id,
                'brief' => is_array($briefPayload) ? $briefPayload : ['raw' => $briefPayload],
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
            ]);
        } catch (RequestException $e) {
            Log::warning('[xfusion-llm] one-on-one meeting-brief failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->response?->body() ?? $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('[xfusion-llm] one-on-one meeting-brief failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate AI Meeting Synthesis after a conversation is held.
     * Uses preparation content (current conversation only) + notes — never
     * forwarded to QBR/ARR/360, which only ever read the synthesis JSON.
     */
    public function meetingSynthesis(OneOnOneConversation $conversation): ?OneOnOneAiSynthesis
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $pair = $conversation->oneOnOne;

        $preparations = $conversation->preparations()
            ->get(['author_role', 'content'])
            ->mapWithKeys(fn (OneOnOnePreparation $p) => [$p->author_role => $p->content]);

        $notes = $conversation->notes()->get(['section', 'note']);
        $commitments = $conversation->commitments()->get(['title', 'description', 'owner_role', 'status']);

        try {
            $response = $this->client()
                ->post('/api/v1/one-on-one/meeting-synthesis', [
                    'conversation_id' => $conversation->id,
                    'leader_user_id' => $pair->leader_user_id,
                    'employee_user_id' => $pair->employee_user_id,
                    'preparations' => $preparations,
                    'notes' => $notes,
                    'commitments' => $commitments,
                ])
                ->throw();

            $body = $response->json();

            return OneOnOneAiSynthesis::create([
                'conversation_id' => $conversation->id,
                'synthesis' => $body['synthesis'] ?? $body,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
            ]);
        } catch (RequestException $e) {
            Log::warning('[xfusion-llm] one-on-one meeting-synthesis failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->response?->body() ?? $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('[xfusion-llm] one-on-one meeting-synthesis failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::baseUrl(config('xfusion-llm.api_url'))
            ->timeout((int) config('xfusion-llm.timeout_seconds', 60))
            ->acceptJson();

        $key = config('xfusion-llm.api_key');
        if (is_string($key) && $key !== '') {
            $request = $request->withToken($key);
        }

        return $request;
    }
}
