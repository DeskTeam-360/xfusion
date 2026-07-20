<?php

namespace App\Services;

use App\Models\Arp;

/**
 * Assembles ARP wizard Steps 1–5 for AI Readiness Review generation.
 *
 * @deprecated Prefer ArpPlanService — kept as thin wrapper for ArpAiService.
 */
class ArpPlanContextService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Arp $arp): array
    {
        return app(ArpPlanService::class)->aiPlanContext($arp);
    }
}
