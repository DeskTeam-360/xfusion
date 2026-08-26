<?php

namespace App\Services;

use App\Models\Arr;
use App\Models\ArrAiAssessment;
use App\Models\ArrAiSynthesis;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Calls Xfusion-llm for ARR Step 3 (AI Annual Readiness Assessment™).
 * Mirrors ArpAiService/QbrAiService/IrrAiService's client setup, error
 * handling, and prompt registry wiring.
 */
class ArrAiService
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

    public function latestAssessment(Arr $arr): ?ArrAiAssessment
    {
        return ArrAiAssessment::query()->where('arr_id', $arr->id)->orderByDesc('id')->first();
    }

    /**
     * Step 3 — AI Annual Readiness Assessment™. Always appends a new row.
     *
     * @param  array<string, mixed>  $evidenceSnapshot
     * @param  array<string, mixed>  $readinessIndicators  Pre-computed by ArrEvidenceService::computeReadinessIndicators() -
     *                                                       never trust the LLM's own numbers for these (see CLAUDE.md scoring principle).
     */
    public function generateAssessment(Arr $arr, array $evidenceSnapshot, array $readinessIndicators): ?ArrAiAssessment
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL / XFUSION_LLM_API_KEY are not configured in Laravel .env.';

            return null;
        }

        $systemPrompt = app(WordPressLlmPromptService::class)->getActivePrompt(WordPressLlmPromptService::SLUG_ARR_ASSESSMENT);

        try {
            $body = $this->client()
                ->post('/api/v1/arr/annual-assessment', array_filter([
                    'arr_id' => $arr->id,
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

            return ArrAiAssessment::create([
                'arr_id' => $arr->id,
                'assessment' => $assessmentPayload,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
                'prompt_version_id' => $systemPrompt['id'] ?? null,
                'prompt_version_label' => $systemPrompt['label'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->recordFailure($e, '/api/v1/arr/annual-assessment', (int) $arr->id);

            return null;
        }
    }

    public function latestSynthesis(Arr $arr): ?ArrAiSynthesis
    {
        return ArrAiSynthesis::query()->where('arr_id', $arr->id)->orderByDesc('id')->first();
    }

    /**
     * Step 6 — AI Strategic Renewal Synthesis™. Always appends a new row.
     *
     * @param  array<string, mixed>  $context  evidence, assessment, executive_reflection, recommendations
     */
    public function generateSynthesis(Arr $arr, array $context): ?ArrAiSynthesis
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL / XFUSION_LLM_API_KEY are not configured in Laravel .env.';

            return null;
        }

        $systemPrompt = app(WordPressLlmPromptService::class)->getActivePrompt(WordPressLlmPromptService::SLUG_ARR_SYNTHESIS);

        try {
            $body = $this->client()
                ->post('/api/v1/arr/strategic-renewal-synthesis', array_filter([
                    'arr_id' => $arr->id,
                    'context' => $context,
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

            return ArrAiSynthesis::create([
                'arr_id' => $arr->id,
                'synthesis' => $synthesisPayload,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
                'prompt_version_id' => $systemPrompt['id'] ?? null,
                'prompt_version_label' => $systemPrompt['label'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->recordFailure($e, '/api/v1/arr/strategic-renewal-synthesis', (int) $arr->id);

            return null;
        }
    }

    private function recordFailure(\Throwable $e, string $path, int $arrId): void
    {
        if ($e instanceof RequestException) {
            $detail = $e->response?->json('detail') ?? $e->response?->body() ?? $e->getMessage();
            $status = (int) ($e->response?->status() ?? 0);
            $llmUrl = app(XfusionLlmHttpClient::class)->apiUrl();

            if ($status === 401 || str_contains((string) $detail, 'Bearer token')) {
                $detail = "LLM returned HTTP {$status} from {$llmUrl}{$path}. "
                    .'Check: (1) XFUSION_LLM_API_KEY matches API_KEY in xfusion-llm .env, '
                    .'(2) LLM server has the ARR routes deployed, '
                    .'(3) run php artisan xfusion:llm-probe on this server.';
            }
            $this->lastError = is_string($detail) ? $detail : json_encode($detail);
        } else {
            $this->lastError = $e->getMessage();
        }

        Log::warning('[xfusion-llm] arr ai call failed', ['arr_id' => $arrId, 'path' => $path, 'error' => $this->lastError]);
    }

    private function client()
    {
        return app(XfusionLlmHttpClient::class)->client();
    }
}
