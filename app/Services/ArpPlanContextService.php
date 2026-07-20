<?php

namespace App\Services;

use App\Models\Arp;
use App\Models\ArpReadinessPriority;
use App\Models\ArpStrategicPriority;
use App\Models\WpGfEntry;
use App\Models\WpGfEntryMeta;

/**
 * Assembles ARP wizard Steps 1–5 for AI Readiness Review generation.
 */
class ArpPlanContextService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Arp $arp): array
    {
        $arp->loadMissing(['company:id,title', 'companyGroup:id,title']);

        return [
            'arp' => [
                'id' => $arp->id,
                'title' => $arp->title,
                'year' => $arp->year,
                'status' => $arp->status,
                'company_name' => $arp->companyGroup?->title ?? $arp->company?->title,
                'mission' => $arp->mission,
                'vision' => $arp->vision,
            ],
            'foundation' => $this->loadGfStep($arp->id, 'foundation'),
            'future_state' => $this->loadGfStep($arp->id, 'future_state'),
            'readiness_priorities' => ArpReadinessPriority::query()
                ->where('arp_id', $arp->id)
                ->orderBy('priority_rank')
                ->get()
                ->map(fn (ArpReadinessPriority $p) => $p->only([
                    'name', 'cor_capability', 'primary_driver', 'secondary_driver',
                    'priority_level', 'description', 'business_rationale',
                    'executive_owner_user_id', 'expected_impact', 'priority_rank',
                ]))
                ->values()
                ->all(),
            'strategic_priorities' => ArpStrategicPriority::query()
                ->where('arp_id', $arp->id)
                ->with('readinessPriority:id,name')
                ->orderBy('priority_rank')
                ->get()
                ->map(function (ArpStrategicPriority $p) {
                    $row = $p->only([
                        'title', 'description', 'owner_user_id', 'target_date',
                        'success_measures', 'org_kpi', 'readiness_indicator',
                        'related_groups', 'status', 'priority_rank',
                    ]);
                    $row['related_readiness'] = $p->readinessPriority?->name;

                    return $row;
                })
                ->values()
                ->all(),
            'learning' => $this->loadGfStep($arp->id, 'learning'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function loadGfStep(int $arpId, string $step): array
    {
        $config = config("arp-gf.{$step}");
        if (! is_array($config) || (int) ($config['form_id'] ?? 0) < 1) {
            return [];
        }

        $arpFieldId = (int) ($config['hidden']['arp_id'] ?? 0);
        if ($arpFieldId < 1) {
            return [];
        }

        $entryId = WpGfEntryMeta::query()
            ->where('form_id', (int) $config['form_id'])
            ->where('meta_key', (string) $arpFieldId)
            ->where('meta_value', (string) $arpId)
            ->orderByDesc('entry_id')
            ->value('entry_id');

        if (! $entryId) {
            return [];
        }

        $entry = WpGfEntry::query()
            ->where('id', $entryId)
            ->where('status', 'active')
            ->first();

        if ($entry === null) {
            return [];
        }

        $meta = WpGfEntryMeta::query()
            ->where('entry_id', $entryId)
            ->pluck('meta_value', 'meta_key');

        $out = [];
        foreach ($config['fields'] as $slug => $fieldId) {
            $value = $meta->get((string) $fieldId);
            if ($value !== null && trim((string) $value) !== '') {
                $out[$slug] = trim((string) $value);
            }
        }

        return $out;
    }
}
