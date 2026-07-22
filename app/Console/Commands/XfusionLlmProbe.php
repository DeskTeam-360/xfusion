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

        $checks = [
            'one-on-one meeting-brief' => [
                '/api/v1/one-on-one/meeting-brief',
                ['conversation_id' => 1, 'leader_user_id' => 1, 'employee_user_id' => 2],
            ],
            'arp readiness-review' => [
                '/api/v1/arp/readiness-review',
                ['arp_id' => 1, 'plan_context' => []],
            ],
            'qbr assessment' => [
                '/api/v1/qbr/assessment',
                ['qbr_id' => 1, 'evidence' => []],
            ],
            'qbr synthesis' => [
                '/api/v1/qbr/synthesis',
                ['qbr_id' => 1, 'context' => []],
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
