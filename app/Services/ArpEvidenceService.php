<?php

namespace App\Services;

use App\Models\Arp;
use App\Models\ArpAiAssessment;
use App\Models\ArpReadinessPriority;
use App\Models\ArpStrategicPriority;
use App\Models\ArpVersion;
use App\Models\FusionEvidenceLog;

class ArpEvidenceService
{
    public function logPublished(Arp $arp, ArpVersion $version, int $userId): FusionEvidenceLog
    {
        return FusionEvidenceLog::create([
            'source_type' => FusionEvidenceLog::SOURCE_ARP,
            'source_id' => $version->id,
            'event_type' => FusionEvidenceLog::EVENT_ARP_PUBLISHED,
            'user_id' => $userId,
            'evidence_date' => now()->toDateString(),
            'metadata' => [
                'arp_id' => $arp->id,
                'company_group_id' => $arp->company_group_id,
                'year' => $arp->year,
                'version' => (string) $version->version,
                'readiness_count' => ArpReadinessPriority::query()->where('arp_id', $arp->id)->count(),
                'strategic_count' => ArpStrategicPriority::query()->where('arp_id', $arp->id)->count(),
            ],
        ]);
    }

    public function logArchived(Arp $arp, ArpVersion $version, int $userId): FusionEvidenceLog
    {
        return FusionEvidenceLog::create([
            'source_type' => FusionEvidenceLog::SOURCE_ARP,
            'source_id' => $version->id,
            'event_type' => FusionEvidenceLog::EVENT_ARP_ARCHIVED,
            'user_id' => $userId,
            'evidence_date' => now()->toDateString(),
            'metadata' => [
                'arp_id' => $arp->id,
                'company_group_id' => $arp->company_group_id,
                'year' => $arp->year,
                'version' => (string) $version->version,
            ],
        ]);
    }

    public function logAiReadinessReview(Arp $arp, ArpAiAssessment $assessment, int $userId): FusionEvidenceLog
    {
        $payload = is_array($assessment->assessment) ? $assessment->assessment : [];

        return FusionEvidenceLog::create([
            'source_type' => FusionEvidenceLog::SOURCE_ARP,
            'source_id' => $assessment->id,
            'event_type' => FusionEvidenceLog::EVENT_AI_READINESS_REVIEW,
            'user_id' => $userId,
            'evidence_date' => now()->toDateString(),
            'metadata' => [
                'arp_id' => $arp->id,
                'company_group_id' => $arp->company_group_id,
                'year' => $arp->year,
                'insight_model' => $assessment->insight_model,
                'tokens_used' => (int) $assessment->tokens_used,
                'overall_score' => $payload['overall_readiness']['score'] ?? null,
            ],
        ]);
    }
}
