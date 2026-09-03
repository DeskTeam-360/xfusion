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
        $arp->loadMissing(['company:id,title']);

        return [
            'arp' => [
                'id' => $arp->id,
                'title' => $arp->title,
                'year' => $arp->year,
                'status' => $arp->status,
                'company_name' => $arp->company?->title,
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
                    'executive_owner_user_ids', 'expected_impact', 'priority_rank',
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
                        'title', 'description', 'owner_user_ids', 'target_date',
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

    /** @var list<string> Foundation fields with a required (*) marker in step-1-foundation.php. */
    private const FOUNDATION_REQUIRED = ['mission', 'vision', 'organizational_description', 'business_environment', 'executive_narrative'];

    /**
     * Steps 1-5 required-field gate for Step 6 (AI Readiness Review™).
     * Steps 1-5 themselves stay freely navigable (they're just draft saves),
     * but generating the AI review needs real input to analyze - this is
     * the one hard stop, checked server-side so it can never be bypassed by
     * skipping straight to Step 6.
     *
     * @return list<string> Human-readable missing items; empty = ready.
     */
    public function readyForAiReviewIssues(Arp $arp): array
    {
        $issues = [];

        $foundation = $this->foundationValues($arp);
        foreach (self::FOUNDATION_REQUIRED as $field) {
            if (trim((string) ($foundation[$field] ?? '')) === '') {
                $issues[] = 'Step 1 (Organizational Foundation™): '.ucwords(str_replace('_', ' ', $field)).' is required.';
            }
        }

        $future = $this->futureStateValues($arp);
        if (trim((string) ($future['future_state_narrative'] ?? '')) === '') {
            $issues[] = 'Step 2 (Future State™): Future State Narrative is required.';
        }

        $readiness = ArpReadinessPriority::query()->where('arp_id', $arp->id)->orderBy('priority_rank')->get();
        if ($readiness->isEmpty()) {
            $issues[] = 'Step 3 (Organizational Readiness™): add at least one readiness priority.';
        } else {
            foreach ($readiness as $index => $p) {
                $n = $index + 1;
                if (trim((string) $p->name) === '') {
                    $issues[] = "Step 3, Priority {$n}: Priority Name is required.";
                }
                if (trim((string) $p->cor_capability) === '') {
                    $issues[] = "Step 3, Priority {$n}: COR Capability™ is required.";
                }
                if (trim((string) $p->primary_driver) === '') {
                    $issues[] = "Step 3, Priority {$n}: Primary Behavioral Driver™ is required.";
                }
                if (trim((string) $p->priority_level) === '') {
                    $issues[] = "Step 3, Priority {$n}: Priority Level is required.";
                }
                if (empty($p->executive_owner_user_ids)) {
                    $issues[] = "Step 3, Priority {$n}: at least one Executive Owner is required.";
                }
            }
        }

        $strategic = ArpStrategicPriority::query()->where('arp_id', $arp->id)->orderBy('priority_rank')->get();
        if ($strategic->isEmpty()) {
            $issues[] = 'Step 4 (Strategic Priorities™): add at least one strategic priority.';
        } else {
            foreach ($strategic as $index => $p) {
                $n = $index + 1;
                if (trim((string) $p->title) === '') {
                    $issues[] = "Step 4, Priority {$n}: Title is required.";
                }
                if ($p->readiness_priority_id === null) {
                    $issues[] = "Step 4, Priority {$n}: Related Readiness Priority is required.";
                }
                if ($p->target_date === null) {
                    $issues[] = "Step 4, Priority {$n}: Target Completion Date is required.";
                }
                if (trim((string) $p->success_measures) === '') {
                    $issues[] = "Step 4, Priority {$n}: Success Measures is required.";
                }
                if (empty($p->owner_user_ids)) {
                    $issues[] = "Step 4, Priority {$n}: at least one Executive Owner is required.";
                }
            }
        }

        return $issues;
    }
}
