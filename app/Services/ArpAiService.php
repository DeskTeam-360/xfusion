<?php

namespace App\Services;

use App\Models\Arp;
use App\Models\ArpAiAssessment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArpAiService
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

    public function latestAssessment(Arp $arp): ?ArpAiAssessment
    {
        return ArpAiAssessment::query()
            ->where('arp_id', $arp->id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Generate AI Readiness Review from Steps 1–5 context. Always appends a new row.
     */
    public function generateReadinessReview(Arp $arp): ?ArpAiAssessment
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'XFUSION_LLM_API_URL is not configured.';

            return null;
        }

        $planContext = app(ArpPlanContextService::class)->build($arp);

        try {
            $response = $this->client()
                ->post('/api/v1/arp/readiness-review', [
                    'arp_id' => $arp->id,
                    'plan_context' => $planContext,
                ])
                ->throw();

            $body = $response->json();
            $assessmentPayload = $body['assessment'] ?? $body;
            if (! is_array($assessmentPayload)) {
                $this->lastError = 'LLM returned an invalid assessment payload.';

                return null;
            }

            $normalized = app(ArpReadinessReviewNormalizer::class)->normalize($assessmentPayload);

            $previous = $this->latestAssessment($arp);

            return ArpAiAssessment::create([
                'arp_id' => $arp->id,
                'assessment' => $normalized,
                'leadership_context' => $previous?->leadership_context,
                'insight_model' => $body['model'] ?? null,
                'tokens_used' => (int) ($body['tokens_used'] ?? 0),
                'cost_usd' => (float) ($body['cost_usd'] ?? 0),
            ]);
        } catch (RequestException $e) {
            $this->lastError = $e->response?->json('detail') ?? $e->response?->body() ?? $e->getMessage();
            Log::warning('[xfusion-llm] arp readiness-review failed', [
                'arp_id' => $arp->id,
                'error' => $this->lastError,
            ]);

            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::warning('[xfusion-llm] arp readiness-review failed', [
                'arp_id' => $arp->id,
                'error' => $this->lastError,
            ]);

            return null;
        }
    }

    public function saveLeadershipContext(Arp $arp, string $context): ArpAiAssessment
    {
        $latest = $this->latestAssessment($arp);
        if ($latest === null) {
            return ArpAiAssessment::create([
                'arp_id' => $arp->id,
                'assessment' => [],
                'leadership_context' => $context,
            ]);
        }

        $latest->update(['leadership_context' => $context]);

        return $latest->fresh();
    }

    private function client()
    {
        $base = rtrim((string) config('xfusion-llm.api_url'), '/');
        $token = (string) config('xfusion-llm.api_key');

        $pending = Http::baseUrl($base)
            ->timeout((int) config('xfusion-llm.timeout_seconds', 60))
            ->acceptJson();

        if ($token !== '') {
            $pending = $pending->withToken($token);
        }

        return $pending;
    }
}
