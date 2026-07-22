<?php

namespace App\Services;

use App\Models\Qbr;
use App\Models\QbrAiAssessment;
use App\Models\QbrAiSynthesis;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Calls Xfusion-llm for Step 3 (AI Organizational Assessment) and Step 6
 * (AI Organizational Synthesis). Mirrors ArpAiService's client setup and
 * error handling exactly; unlike ARP, both generate methods return null on
 * failure so the controller can fall back to a deterministic composer
 * (QbrAssessmentFromEvidenceService / QbrSynthesisFromContextService) —
 * same pattern as OneOnOneController::generateBrief/generateSynthesis.
 */
class QbrAiService
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

    public function latestAssessment(Qbr $qbr): ?QbrAiAssessment
    {
        return QbrAiAssessment::query()->where('qbr_id', $qbr->id)->orderByDesc('id')->first();
    }

    public function latestSynthesis(Qbr $qbr): ?QbrAiSynthesis
    {
        return QbrAiSynthesis::query()->where('qbr_id', $qbr->id)->orderByDesc('id')->first();
    }

    /** Step 3 — AI Organizational Assessment™. Always appends a new row. */
    public function generateAssessment(Qbr $qbr, array $evidenceSnapshot): ?QbrAiAssessment
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL / XFUSION_LLM_API_KEY are not configured in Laravel .env.';

            return null;
        }

        try {
            $body = $this->client()
                ->post('/api/v1/qbr/assessment', [
                    'qbr_id' => $qbr->id,
                    'evidence' => $evidenceSnapshot,
                ])
                ->throw()
                ->json();

            $assessmentPayload = $body['assessment'] ?? $body;
            if (! is_array($assessmentPayload)) {
                $this->lastError = 'LLM returned an invalid assessment payload.';

                return null;
            }

            $previous = $this->latestAssessment($qbr);

            return QbrAiAssessment::create([
                'qbr_id' => $qbr->id,
                'assessment' => $assessmentPayload,
                'leadership_context' => $previous?->leadership_context,
                'agreement_rating' => $previous?->agreement_rating,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            $this->recordFailure($e, '/api/v1/qbr/assessment', $qbr->id);

            return null;
        }
    }

    /** Step 6 — AI Organizational Synthesis™. Always appends a new row. */
    public function generateSynthesis(Qbr $qbr, array $synthesisContext): ?QbrAiSynthesis
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL / XFUSION_LLM_API_KEY are not configured in Laravel .env.';

            return null;
        }

        try {
            $body = $this->client()
                ->post('/api/v1/qbr/synthesis', [
                    'qbr_id' => $qbr->id,
                    'context' => $synthesisContext,
                ])
                ->throw()
                ->json();

            $synthesisPayload = $body['synthesis'] ?? $body;
            if (! is_array($synthesisPayload)) {
                $this->lastError = 'LLM returned an invalid synthesis payload.';

                return null;
            }

            return QbrAiSynthesis::create([
                'qbr_id' => $qbr->id,
                'synthesis' => $synthesisPayload,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            $this->recordFailure($e, '/api/v1/qbr/synthesis', $qbr->id);

            return null;
        }
    }

    public function saveLeadershipContext(Qbr $qbr, ?string $context, ?string $agreementRating): QbrAiAssessment
    {
        $latest = $this->latestAssessment($qbr);
        if ($latest === null) {
            return QbrAiAssessment::create([
                'qbr_id' => $qbr->id,
                'assessment' => [],
                'leadership_context' => $context,
                'agreement_rating' => $agreementRating,
            ]);
        }

        $latest->update(array_filter([
            'leadership_context' => $context,
            'agreement_rating' => $agreementRating,
        ], fn ($v) => $v !== null));

        return $latest->fresh();
    }

    private function recordFailure(\Throwable $e, string $path, int $qbrId): void
    {
        if ($e instanceof RequestException) {
            $detail = $e->response?->json('detail') ?? $e->response?->body() ?? $e->getMessage();
            $status = (int) ($e->response?->status() ?? 0);
            $llmUrl = app(XfusionLlmHttpClient::class)->apiUrl();

            if ($status === 401 || str_contains((string) $detail, 'Bearer token')) {
                $detail = "LLM returned HTTP {$status} from {$llmUrl}{$path}. "
                    .'Check: (1) XFUSION_LLM_API_KEY matches API_KEY in xfusion-llm .env, '
                    .'(2) LLM server has the QBR routes deployed, '
                    .'(3) run php artisan xfusion:llm-probe on this server.';
            }
            $this->lastError = is_string($detail) ? $detail : json_encode($detail);
        } else {
            $this->lastError = $e->getMessage();
        }

        Log::warning('[xfusion-llm] qbr call failed', ['qbr_id' => $qbrId, 'path' => $path, 'error' => $this->lastError]);
    }

    private function client()
    {
        return app(XfusionLlmHttpClient::class)->client();
    }
}
