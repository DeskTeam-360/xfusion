<?php

namespace App\Services;

use App\Models\IrrAiAssessment;
use App\Models\IrrAiSynthesis;
use App\Models\IrrReview;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Calls Xfusion-llm for Step 3 (AI Development Assessment™) and Step 6
 * (AI Development Synthesis™). Mirrors ArpAiService/QbrAiService's client
 * setup, error handling, and prompt registry wiring.
 */
class IrrAiService
{
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function isConfigured(): bool
    {
        return app(XfusionLlmHttpClient::class)->isConfigured();
    }

    public function latestAssessment(IrrReview $review): ?IrrAiAssessment
    {
        return IrrAiAssessment::query()->where('review_id', $review->id)->orderByDesc('id')->first();
    }

    /**
     * Step 3 — AI Development Assessment™. Always appends a new row.
     *
     * @param  array<string, mixed>  $evidenceSnapshot
     * @param  array<string, mixed>  $readinessIndicators  Pre-computed by IrrEvidenceService::computeReadinessIndicators() -
     *                                                       never trust the LLM's own numbers for these (see CLAUDE.md scoring principle).
     */
    public function generateAssessment(IrrReview $review, array $evidenceSnapshot, array $readinessIndicators): ?IrrAiAssessment
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL / XFUSION_LLM_API_KEY are not configured in Laravel .env.';

            return null;
        }

        $systemPrompt = app(WordPressLlmPromptService::class)->getActivePrompt(WordPressLlmPromptService::SLUG_IRR_ASSESSMENT);

        try {
            $body = $this->client()
                ->post('/api/v1/360/development-assessment', array_filter([
                    'review_id' => $review->id,
                    'evidence' => $evidenceSnapshot,
                    'readiness_indicators' => $readinessIndicators,
                    'system_prompt' => $systemPrompt['content'] ?? null,
                    'prompt_version_id' => $systemPrompt['id'] ?? null,
                    'prompt_version_label' => $systemPrompt['label'] ?? null,
                ], static fn ($v) => $v !== null && $v !== ''))
                ->throw()
                ->json();

            $assessmentPayload = $body['assessment'] ?? $body;
            if (! is_array($assessmentPayload)) {
                $this->lastError = 'LLM returned an invalid assessment payload.';

                return null;
            }

            // Readiness scores are always the server-computed values, even if
            // the LLM echoed its own numbers back — never trust generated scores.
            $assessmentPayload['readiness_indicators'] = $readinessIndicators;

            return IrrAiAssessment::create([
                'review_id' => $review->id,
                'assessment' => $assessmentPayload,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
                'prompt_version_id' => $systemPrompt['id'] ?? null,
                'prompt_version_label' => $systemPrompt['label'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->recordFailure($e, '/api/v1/360/development-assessment', (int) $review->id);

            return null;
        }
    }

    public function latestSynthesis(IrrReview $review): ?IrrAiSynthesis
    {
        return IrrAiSynthesis::query()->where('review_id', $review->id)->orderByDesc('id')->first();
    }

    /**
     * Step 6 — AI Development Synthesis™. Always appends a new row.
     *
     * @param  array<string, mixed>  $context  evidence, assessment, readiness_indicators, conversation_notes, commitments
     * @param  array<string, mixed>  $readinessIndicators  Same computed values as Step 3 - re-supplied here since the
     *                                                       synthesis's own "readiness_indicators" key gets overwritten with these.
     * @param  array{average_score: ?float, trend_note: ?string}  $behavioralGrowth  Server-computed, never from the LLM.
     */
    public function generateSynthesis(IrrReview $review, array $context, array $readinessIndicators, array $behavioralGrowth): ?IrrAiSynthesis
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL / XFUSION_LLM_API_KEY are not configured in Laravel .env.';

            return null;
        }

        $systemPrompt = app(WordPressLlmPromptService::class)->getActivePrompt(WordPressLlmPromptService::SLUG_IRR_SYNTHESIS);

        try {
            $body = $this->client()
                ->post('/api/v1/360/development-synthesis', array_filter([
                    'review_id' => $review->id,
                    'context' => array_merge($context, [
                        'readiness_indicators' => $readinessIndicators,
                        'behavioral_growth' => $behavioralGrowth,
                    ]),
                    'system_prompt' => $systemPrompt['content'] ?? null,
                    'prompt_version_id' => $systemPrompt['id'] ?? null,
                    'prompt_version_label' => $systemPrompt['label'] ?? null,
                ], static fn ($v) => $v !== null && $v !== ''))
                ->throw()
                ->json();

            $synthesisPayload = $body['synthesis'] ?? $body;
            if (! is_array($synthesisPayload)) {
                $this->lastError = 'LLM returned an invalid synthesis payload.';

                return null;
            }

            // Numeric scores are always the server-computed values, even if
            // the LLM echoed its own numbers back — never trust generated scores.
            $synthesisPayload['readiness_indicators'] = $readinessIndicators;
            $synthesisPayload['behavioral_growth'] = $behavioralGrowth;

            return IrrAiSynthesis::create([
                'review_id' => $review->id,
                'synthesis' => $synthesisPayload,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
                'prompt_version_id' => $systemPrompt['id'] ?? null,
                'prompt_version_label' => $systemPrompt['label'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->recordFailure($e, '/api/v1/360/development-synthesis', (int) $review->id);

            return null;
        }
    }

    private function recordFailure(\Throwable $e, string $path, int $reviewId): void
    {
        if ($e instanceof RequestException) {
            $detail = $e->response?->json('detail') ?? $e->response?->body() ?? $e->getMessage();
            $status = (int) ($e->response?->status() ?? 0);
            $llmUrl = app(XfusionLlmHttpClient::class)->apiUrl();

            if ($status === 401 || str_contains((string) $detail, 'Bearer token')) {
                $detail = "LLM returned HTTP {$status} from {$llmUrl}{$path}. "
                    .'Check: (1) XFUSION_LLM_API_KEY matches API_KEY in xfusion-llm .env, '
                    .'(2) LLM server has the IRR/360 routes deployed, '
                    .'(3) run php artisan xfusion:llm-probe on this server.';
            }
            $this->lastError = is_string($detail) ? $detail : json_encode($detail);
        } else {
            $this->lastError = $e->getMessage();
        }

        Log::warning('[xfusion-llm] irr ai call failed', ['review_id' => $reviewId, 'path' => $path, 'error' => $this->lastError]);
    }

    private function client()
    {
        return app(XfusionLlmHttpClient::class)->client();
    }
}
