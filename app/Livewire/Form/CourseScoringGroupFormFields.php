<?php

namespace App\Livewire\Form;

use App\Models\CourseScoringGroup as CourseScoringGroupModel;
use App\Models\CourseScoringGroupDetail;
use App\Models\WpGfForm;
use Livewire\Component;

/**
 * Edits the connected fields/weights for a single Gravity Form within a
 * course scoring group — split out from the group edit page so that page
 * doesn't have to render every field of every attached form at once.
 */
class CourseScoringGroupFormFields extends Component
{
    public int $groupId;

    public int $formId;

    public string $groupTitle = '';

    public string $formTitle = '';

    /** @var list<int> */
    public array $fieldIds = [];

    /** @var array<int, float> */
    public array $fieldWeights = [];

    public bool $expanded = false;

    public function mount(int $groupId, int $formId): void
    {
        $this->groupId = $groupId;
        $this->formId = $formId;

        $group = CourseScoringGroupModel::findOrFail($groupId);
        $this->groupTitle = (string) $group->title;

        $form = WpGfForm::find($formId);
        $this->formTitle = $form !== null ? (string) $form->title : ('Form #'.$formId);

        $rows = CourseScoringGroupDetail::query()
            ->where('course_scoring_group_id', $groupId)
            ->where('form_id', $formId)
            ->get();

        $connected = $rows->filter(static fn (CourseScoringGroupDetail $d): bool => $d->isConnected());
        $this->fieldIds = $connected->pluck('field_id')->map(fn ($v) => (int) $v)->values()->all();

        $lookup = array_fill_keys($this->fieldIds, true);
        foreach ($rows as $detail) {
            $fieldId = (int) $detail->field_id;
            if ($fieldId < 1 || ! isset($lookup[$fieldId])) {
                continue;
            }
            $this->fieldWeights[$fieldId] = (float) ($detail->weight ?? 1);
        }

        $this->expanded = $this->fieldIds === [];
    }

    public function toggleExpanded(): void
    {
        $this->expanded = ! $this->expanded;
    }

    public function totalFieldsCount(): int
    {
        return count(CourseScoringGroup::gfFieldsForFormId($this->formId));
    }

    /** @return list<array{id: int, label: string, type: string}> */
    public function visibleFields(): array
    {
        $all = CourseScoringGroup::gfFieldsForFormId($this->formId);

        if ($this->expanded) {
            return $all;
        }

        $connected = array_fill_keys($this->fieldIds, true);
        if ($connected === []) {
            return [];
        }

        return array_values(array_filter(
            $all,
            static fn (array $f) => isset($connected[(int) $f['id']])
        ));
    }

    public function setFieldChecked(int $fieldId, $checked): void
    {
        $fieldId = abs($fieldId);
        if ($fieldId < 1) {
            return;
        }

        $on = filter_var($checked, FILTER_VALIDATE_BOOLEAN);

        if ($on) {
            if (! in_array($fieldId, $this->fieldIds, true)) {
                $this->fieldIds[] = $fieldId;
            }
            if (! isset($this->fieldWeights[$fieldId]) || $this->fieldWeights[$fieldId] <= 0) {
                $this->fieldWeights[$fieldId] = 1.0;
            }
        } else {
            $this->fieldIds = array_values(array_filter($this->fieldIds, static fn ($id) => (int) $id !== $fieldId));
            unset($this->fieldWeights[$fieldId]);
        }

        $this->fieldIds = array_values(array_unique(array_map('intval', $this->fieldIds)));

        $this->skipRender();
    }

    public function setFieldWeight(int $fieldId, $weight): void
    {
        $fieldId = abs($fieldId);
        if ($fieldId < 1) {
            return;
        }

        if (is_string($weight)) {
            $weight = trim($weight);
        }

        if ($weight === '' || $weight === null || ! is_numeric($weight)) {
            $this->setFieldChecked($fieldId, false);

            return;
        }

        $value = round((float) $weight, 2);
        if ($value <= 0) {
            $this->setFieldChecked($fieldId, false);

            return;
        }

        if (! in_array($fieldId, $this->fieldIds, true)) {
            $this->fieldIds[] = $fieldId;
            $this->fieldIds = array_values(array_unique(array_map('intval', $this->fieldIds)));
        }

        $this->fieldWeights[$fieldId] = $value;

        $this->skipRender();
    }

    public function fieldWeight(int $fieldId): ?float
    {
        $weight = $this->fieldWeights[$fieldId] ?? null;

        return $weight !== null ? (float) $weight : null;
    }

    public function fieldIsChecked(int $fieldId): bool
    {
        return in_array($fieldId, $this->fieldIds, true);
    }

    public function save(): void
    {
        $existing = CourseScoringGroupDetail::query()
            ->where('course_scoring_group_id', $this->groupId)
            ->where('form_id', $this->formId)
            ->get()
            ->keyBy(static fn (CourseScoringGroupDetail $d): int => (int) $d->field_id);

        $checkedLookup = array_fill_keys($this->fieldIds, true);

        $fieldIds = array_values(array_unique(array_map(
            static fn (array $f): int => (int) $f['id'],
            CourseScoringGroup::gfFieldsForFormId($this->formId)
        )));

        foreach ($existing as $fieldId => $detail) {
            if (! in_array($fieldId, $fieldIds, true)) {
                $fieldIds[] = $fieldId;
            }
        }

        foreach ($fieldIds as $fieldId) {
            if ($fieldId < 1) {
                continue;
            }

            $prior = $existing->get($fieldId);

            if (isset($checkedLookup[$fieldId])) {
                $weight = isset($this->fieldWeights[$fieldId]) && $this->fieldWeights[$fieldId] > 0
                    ? round($this->fieldWeights[$fieldId], 2)
                    : ($prior !== null && (float) ($prior->weight ?? 0) > 0
                        ? round((float) $prior->weight, 2)
                        : 1.0);
            } else {
                $weight = 0.0;
            }

            if ($prior !== null) {
                if ((float) ($prior->weight ?? 0) !== $weight) {
                    $prior->update(['weight' => $weight]);
                }

                continue;
            }

            if ($weight <= 0) {
                continue;
            }

            try {
                CourseScoringGroupDetail::create([
                    'course_scoring_group_id' => $this->groupId,
                    'form_id' => $this->formId,
                    'field_id' => $fieldId,
                    'weight' => $weight,
                ]);
            } catch (\Illuminate\Database\QueryException) {
                // Duplicate (unique) skipped
            }
        }

        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Saved.',
        ]);
    }

    public function render()
    {
        return view('livewire.form.course-scoring-group-form-fields');
    }
}
