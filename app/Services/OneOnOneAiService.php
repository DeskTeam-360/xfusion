<?php

namespace App\Services;

use App\Models\OneOnOneAiBrief;
use App\Models\OneOnOneAiSynthesis;
use App\Models\OneOnOneConversation;
use App\Models\OneOnOnePreparation;
use App\Services\WordPressLlmPromptService;
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
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

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
            $briefPrompt = app(WordPressLlmPromptService::class)->getActivePrompt(WordPressLlmPromptService::SLUG_OO_BRIEF);

            $response = $this->client()
                ->post('/api/v1/one-on-one/meeting-brief', array_filter([
                    'conversation_id' => $conversation->id,
                    'leader_user_id' => $pair->leader_user_id,
                    'employee_user_id' => $pair->employee_user_id,
                    'prior_syntheses' => $priorSyntheses->values()->all(),
                    'evidence_context' => $evidenceContext,
                    'system_prompt' => $briefPrompt['content'] ?? null,
                    'prompt_version_id' => $briefPrompt['id'] ?? null,
                    'prompt_version_label' => $briefPrompt['label'] ?? null,
                ], static fn ($v) => $v !== null && $v !== ''))
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
    public function meetingSynthesis(
        OneOnOneConversation $conversation,
        bool $forceRefresh = false,
        array $contextOverrides = []
    ): ?OneOnOneAiSynthesis {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL is not configured.';

            return null;
        }

        if (! $forceRefresh) {
            $existing = $conversation->synthesis;
            if ($existing !== null) {
                return $existing;
            }
        }

        $pair = $conversation->oneOnOne;
        if ($pair === null) {
            $this->lastError = '1-on-1 pair not found for this conversation.';

            return null;
        }

        $preparations = isset($contextOverrides['preparations']) && is_array($contextOverrides['preparations'])
            ? $this->normalizePreparations($contextOverrides['preparations'])
            : $this->normalizePreparations(
                $conversation->preparations()
                    ->get(['author_role', 'content'])
                    ->mapWithKeys(fn (OneOnOnePreparation $p) => [$p->author_role => $p->content])
                    ->all()
            );

        $notes = isset($contextOverrides['notes']) && is_array($contextOverrides['notes'])
            ? $contextOverrides['notes']
            : $conversation->notes()->get(['section', 'note'])->map(
                fn ($n) => ['section' => $n->section, 'note' => $n->note]
            )->values()->all();

        $commitments = isset($contextOverrides['commitments']) && is_array($contextOverrides['commitments'])
            ? $contextOverrides['commitments']
            : $conversation->commitments()->get(['title', 'description', 'owner_role', 'status'])->map(
                fn ($c) => [
                    'title' => $c->title,
                    'description' => $c->description,
                    'owner_role' => $c->owner_role,
                    'status' => $c->status,
                ]
            )->values()->all();

        try {
            $synthesisPrompt = app(WordPressLlmPromptService::class)->getActivePrompt(WordPressLlmPromptService::SLUG_OO_SYNTHESIS);

            $response = $this->client()
                ->post('/api/v1/one-on-one/meeting-synthesis', array_filter([
                    'conversation_id' => $conversation->id,
                    'leader_user_id' => $pair->leader_user_id,
                    'employee_user_id' => $pair->employee_user_id,
                    'preparations' => $preparations,
                    'notes' => $notes,
                    'commitments' => $commitments,
                    'system_prompt' => $synthesisPrompt['content'] ?? null,
                    'prompt_version_id' => $synthesisPrompt['id'] ?? null,
                    'prompt_version_label' => $synthesisPrompt['label'] ?? null,
                ], static fn ($v) => $v !== null && $v !== ''))
                ->throw();

            $body = $response->json();
            $synthesisPayload = $body['synthesis'] ?? $body;
            if (! is_array($synthesisPayload)) {
                $this->lastError = 'LLM returned an invalid synthesis payload.';

                return null;
            }

            $synthesisPayload['commitment_summary'] = app(SynthesisCommitmentSummaryNormalizer::class)
                ->fromCommitments(
                    $commitments,
                    is_array($synthesisPayload['commitment_summary'] ?? null) ? $synthesisPayload['commitment_summary'] : []
                );

            return OneOnOneAiSynthesis::create([
                'conversation_id' => $conversation->id,
                'synthesis' => $synthesisPayload,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
            ]);
        } catch (RequestException $e) {
            $responseBody = $e->response?->json();
            $detail = is_array($responseBody)
                ? ($responseBody['detail'] ?? $responseBody['message'] ?? null)
                : null;
            if (is_array($detail)) {
                $detail = json_encode($detail);
            }
            $this->lastError = is_string($detail) && $detail !== ''
                ? $detail
                : ($e->response?->body() ?: $e->getMessage());

            Log::warning('[xfusion-llm] one-on-one meeting-synthesis failed', [
                'conversation_id' => $conversation->id,
                'error' => $this->lastError,
            ]);

            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();

            Log::warning('[xfusion-llm] one-on-one meeting-synthesis failed', [
                'conversation_id' => $conversation->id,
                'error' => $this->lastError,
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

    /**
     * @param  array<string, mixed>  $preparations
     * @return array<string, array<string, mixed>>
     */
    private function normalizePreparations(array $preparations): array
    {
        $normalized = [];
        foreach ($preparations as $role => $content) {
            if (! is_string($role) || $role === '') {
                continue;
            }
            if (is_array($content)) {
                $normalized[$role] = $content;
                continue;
            }
            if (! is_string($content) || trim($content) === '') {
                $normalized[$role] = [];
                continue;
            }
            $decoded = json_decode($content, true);
            $normalized[$role] = is_array($decoded) ? $decoded : ['summary' => $content];
        }

        return $normalized;
    }
}
