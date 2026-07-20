<?php

namespace App\Services;

use App\Models\Arp;
use App\Models\ArpAiAssessment;
use App\Models\ArpFutureState;
use App\Models\ArpLearning;
use App\Models\ArpReadinessPriority;
use App\Models\ArpStrategicPriority;

/**
 * Canonical ARP plan read/write — Laravel is the system of record.
 */
class ArpPlanService
{
    /** @var list<string> */
    public const FOUNDATION_FIELDS = [
        'mission',
        'vision',
        'core_values',
        'organizational_description',
        'business_environment',
        'executive_narrative',
    ];

    /**
     * Full wizard payload for Steps 1, 2, 5 (load draft).
     *
     * @return array{foundation: array<string, string>, future_state: array<string, string>, learning: array<string, string>}
     */
    public function wizardDraftPayload(Arp $arp): array
    {
        return [
            'foundation' => $this->foundationValues($arp),
            'future_state' => $this->futureStateValues($arp),
            'learning' => $this->learningValues($arp),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function foundationValues(Arp $arp): array
    {
        $arp->refresh();
        $out = [];
        foreach (self::FOUNDATION_FIELDS as $field) {
            $out[$field] = (string) ($arp->{$field} ?? '');
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveFoundation(Arp $arp, array $values): Arp
    {
        $payload = [];
        foreach (self::FOUNDATION_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $payload[$field] = (string) $values[$field];
            }
        }

        if ($payload !== []) {
            $arp->update($payload);
        }

        return $arp->fresh();
    }

    /**
     * @return array<string, string>
     */
    public function futureStateValues(Arp $arp): array
    {
        $row = ArpFutureState::query()->where('arp_id', $arp->id)->first();

        return $row ? $row->toWizardValues() : array_fill_keys(array_keys(ArpFutureState::uiToColumnMap()), '');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveFutureState(Arp $arp, array $values): ArpFutureState
    {
        $payload = ['arp_id' => $arp->id];
        foreach (ArpFutureState::uiToColumnMap() as $slug => $column) {
            if (array_key_exists($slug, $values)) {
                $payload[$column] = (string) $values[$slug];
            }
        }

        return ArpFutureState::query()->updateOrCreate(
            ['arp_id' => $arp->id],
            $payload
        );
    }

    /**
     * @return array<string, string>
     */
    public function learningValues(Arp $arp): array
    {
        $rows = ArpLearning::query()->where('arp_id', $arp->id)->get()->keyBy('type');
        $out = [];
        foreach (ArpLearning::uiToTypeMap() as $slug => $type) {
            $out[$slug] = (string) ($rows->get($type)?->description ?? '');
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveLearning(Arp $arp, array $values): void
    {
        ArpLearning::query()->where('arp_id', $arp->id)->delete();

        foreach (ArpLearning::uiToTypeMap() as $slug => $type) {
            if (! array_key_exists($slug, $values)) {
                continue;
            }
            $text = trim((string) $values[$slug]);
            if ($text === '') {
                continue;
            }
            ArpLearning::create([
                'arp_id' => $arp->id,
                'type' => $type,
                'description' => $text,
            ]);
        }
    }

    /**
     * Plan context for AI — same shape as ArpPlanContextService previously built from GF.
     *
     * @return array<string, mixed>
     */
    public function aiPlanContext(Arp $arp): array
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
            'foundation' => $this->foundationValues($arp),
            'future_state' => $this->futureStateValues($arp),
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
            'learning' => $this->learningValues($arp),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function computeStepProgress(Arp $arp): array
    {
        $foundationDone = collect($this->foundationValues($arp))
            ->contains(fn ($v) => trim((string) $v) !== '');

        $future = $this->futureStateValues($arp);
        $futureDone = trim((string) ($future['future_state_narrative'] ?? '')) !== '';

        $readinessDone = ArpReadinessPriority::query()->where('arp_id', $arp->id)->exists();
        $strategicDone = ArpStrategicPriority::query()->where('arp_id', $arp->id)->exists();

        $learningDone = collect($this->learningValues($arp))
            ->contains(fn ($v) => trim((string) $v) !== '');

        $latestAi = ArpAiAssessment::query()
            ->where('arp_id', $arp->id)
            ->orderByDesc('id')
            ->first();
        $aiDone = $latestAi !== null
            && is_array($latestAi->assessment)
            && $latestAi->assessment !== [];

        $publishDone = $arp->status === Arp::STATUS_ACTIVE && $arp->published_at !== null;

        return [
            'foundation' => $foundationDone,
            'future_state' => $futureDone,
            'readiness' => $readinessDone,
            'strategic' => $strategicDone,
            'learning' => $learningDone,
            'ai_review' => $aiDone,
            'publish' => $publishDone,
        ];
    }

    public function refreshStepProgress(Arp $arp): Arp
    {
        $fresh = $arp->fresh() ?? $arp;
        $fresh->update(['step_progress' => $this->computeStepProgress($fresh)]);

        return $fresh->fresh();
    }
}
