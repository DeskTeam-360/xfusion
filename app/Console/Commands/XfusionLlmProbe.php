<?php

namespace App\Console\Commands;

use App\Services\XfusionLlmHttpClient;
use Illuminate\Console\Command;

class XfusionLlmProbe extends Command
{
    protected $signature = 'xfusion:llm-probe';

    protected $description = 'Test Laravel → Xfusion-llm auth on one-on-1 and ARP endpoints';

    public function handle(XfusionLlmHttpClient $client): int
    {
        $this->line('LLM base URL: '.$client->apiUrl());
        $this->line('API key set: '.($client->apiKey() !== '' ? 'yes ('.strlen($client->apiKey()).' chars)' : 'NO'));

        if (! $client->isConfigured()) {
            $this->error('Set XFUSION_LLM_API_URL and XFUSION_LLM_API_KEY in .env, then php artisan config:clear');

            return self::FAILURE;
        }

        // Empty-object fields must be sent as stdClass, not []: PHP can't
        // distinguish an empty array from an empty dict, and json_encode([])
        // produces JSON `[]`, which FastAPI/Pydantic rejects for a Dict field
        // (expects `{}`). This tripped up earlier probe runs with 422s that
        // looked like connectivity failures but weren't.
        $emptyObject = new \stdClass();

        $checks = [
            'one-on-one meeting-brief' => [
                '/api/v1/one-on-one/meeting-brief',
                ['conversation_id' => 1, 'leader_user_id' => 1, 'employee_user_id' => 2],
            ],
            'arp readiness-review' => [
                '/api/v1/arp/readiness-review',
                ['arp_id' => 1, 'plan_context' => $emptyObject],
            ],
            'qbr assessment' => [
                '/api/v1/qbr/assessment',
                ['qbr_id' => 1, 'evidence' => $emptyObject],
            ],
            'qbr synthesis' => [
                '/api/v1/qbr/synthesis',
                ['qbr_id' => 1, 'context' => $emptyObject],
            ],
            'irr development-assessment' => [
                '/api/v1/360/development-assessment',
                ['review_id' => 1, 'evidence' => $emptyObject, 'readiness_indicators' => $emptyObject],
            ],
            'irr development-synthesis' => [
                '/api/v1/360/development-synthesis',
                ['review_id' => 1, 'context' => $emptyObject],
            ],
            'arr annual-assessment' => [
                '/api/v1/arr/annual-assessment',
                ['arr_id' => 1, 'evidence' => $emptyObject, 'readiness_indicators' => $emptyObject],
            ],
            'arr strategic-renewal-synthesis' => [
                '/api/v1/arr/strategic-renewal-synthesis',
                ['arr_id' => 1, 'context' => $emptyObject],
            ],
        ];

        $allOk = true;

        foreach ($checks as $label => [$path, $payload]) {
            $result = $client->probePost($path, $payload);
            $status = $result['status'];
            $detail = mb_substr($result['detail'], 0, 200);

            if ($result['ok']) {
                $this->info("[OK] {$label} — HTTP {$status}");
            } else {
                $allOk = false;
                $this->error("[FAIL] {$label} — HTTP {$status}");
                $this->line("  URL: {$result['url']}");
                $this->line("  Detail: {$detail}");

                if ($status === 401) {
                    $this->warn('  → Token mismatch: XFUSION_LLM_API_KEY must equal API_KEY in xfusion-llm .env');
                } elseif ($status === 404) {
                    $this->warn('  → Route not found: git pull + restart xfusion-llm on the LLM server');
                }
            }
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
