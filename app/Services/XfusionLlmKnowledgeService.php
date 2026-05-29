<?php

namespace App\Services;

use App\Models\XfusionKnowledge;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XfusionLlmKnowledgeService
{
    public function isConfigured(): bool
    {
        return config('xfusion-llm.api_url') !== ''
            && config('xfusion-llm.sync_enabled');
    }

    /**
     * @return array{ok: bool, message: string, chunks_added?: int, chunks_deleted?: int}
     */
    public function upsertPost(XfusionKnowledge $post, string $category): array
    {
        if (! $this->isConfigured()) {
            $reason = config('xfusion-llm.api_url') === ''
                ? 'API URL empty — set XFUSION_LLM_API_URL in Laravel .env (e.g. http://127.0.0.1:8000).'
                : 'Sync disabled — set XFUSION_LLM_SYNC_ENABLED=true in Laravel .env.';
            $this->markSync($post, 'skipped', $reason);

            return ['ok' => true, 'message' => 'Saved locally; LLM sync skipped. '.$reason];
        }

        $content = $this->plainContent((string) $post->post_content);

        if ($content === '') {
            $this->markSync($post, 'skipped', 'Content empty — nothing to index');

            return ['ok' => true, 'message' => 'Post saved; no text content to index.'];
        }

        if ($category === '') {
            $this->markSync($post, 'failed', 'Category is required for LLM indexing');

            return ['ok' => false, 'message' => 'Category is required for LLM sync.'];
        }

        try {
            $response = $this->client()
                ->post('/api/v1/knowledge/upsert', [
                    'wordpress_post_id' => (int) $post->ID,
                    'category' => $category,
                    'content' => $content,
                ])
                ->throw();

            $body = $response->json();
            $chunksAdded = (int) ($body['chunks_added'] ?? 0);

            $post->setMeta(XfusionKnowledge::META_CHUNKS_ADDED, (string) $chunksAdded);
            $this->markSync($post, 'synced');

            return [
                'ok' => true,
                'message' => (string) ($body['message'] ?? 'Indexed in vector store.'),
                'chunks_added' => $chunksAdded,
                'chunks_deleted' => (int) ($body['chunks_deleted'] ?? 0),
            ];
        } catch (RequestException $e) {
            $detail = $e->response?->json('detail') ?? $e->response?->body() ?? $e->getMessage();
            $msg = is_array($detail) ? json_encode($detail) : (string) $detail;
            $this->markSync($post, 'failed', $msg);
            Log::warning('[xfusion-llm] upsert failed', ['post_id' => $post->ID, 'error' => $msg]);

            return ['ok' => false, 'message' => $msg];
        } catch (\Throwable $e) {
            $this->markSync($post, 'failed', $e->getMessage());
            Log::warning('[xfusion-llm] upsert failed', ['post_id' => $post->ID, 'error' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteFromVector(int $postId): void
    {
        if (! $this->isConfigured() || $postId <= 0) {
            return;
        }

        try {
            $this->client()
                ->delete("/api/v1/knowledge/delete/{$postId}")
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('[xfusion-llm] delete failed', ['post_id' => $postId, 'error' => $e->getMessage()]);
        }
    }

    public function plainContent(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function markSync(XfusionKnowledge $post, string $status, ?string $error = null): void
    {
        $post->setMeta(XfusionKnowledge::META_SYNC_STATUS, $status);
        $post->setMeta(XfusionKnowledge::META_SYNCED_AT, now()->toIso8601String());

        if ($error !== null) {
            $post->setMeta(XfusionKnowledge::META_SYNC_ERROR, $error);
        } elseif ($status === 'synced') {
            $post->setMeta(XfusionKnowledge::META_SYNC_ERROR, '');
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
