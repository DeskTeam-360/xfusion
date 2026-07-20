<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Shared HTTP client for Laravel → Xfusion-llm (FastAPI).
 */
class XfusionLlmHttpClient
{
    public function apiUrl(): string
    {
        return rtrim((string) config('xfusion-llm.api_url'), '/');
    }

    public function apiKey(): string
    {
        return trim((string) config('xfusion-llm.api_key'));
    }

    public function isConfigured(): bool
    {
        return $this->apiUrl() !== '' && $this->apiKey() !== '';
    }

    public function client(): PendingRequest
    {
        $request = Http::baseUrl($this->apiUrl())
            ->timeout((int) config('xfusion-llm.timeout_seconds', 60))
            ->acceptJson();

        $key = $this->apiKey();
        if ($key !== '') {
            $request = $request->withToken($key);
        }

        return $request;
    }

    /**
     * Probe a LLM POST endpoint (minimal payload) for connectivity/auth checks.
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status: int, detail: string, url: string}
     */
    public function probePost(string $path, array $payload): array
    {
        $path = '/'.ltrim($path, '/');
        $url = $this->apiUrl().$path;

        if ($this->apiUrl() === '') {
            return [
                'ok' => false,
                'status' => 0,
                'detail' => 'XFUSION_LLM_API_URL is empty.',
                'url' => $url,
            ];
        }

        try {
            /** @var Response $response */
            $response = $this->client()->post($path, $payload);
            $status = $response->status();
            $detail = $response->json('detail') ?? $response->body();

            return [
                'ok' => $status >= 200 && $status < 300,
                'status' => $status,
                'detail' => is_string($detail) ? $detail : json_encode($detail),
                'url' => $url,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'detail' => $e->getMessage(),
                'url' => $url,
            ];
        }
    }
}
