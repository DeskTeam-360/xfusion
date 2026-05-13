<?php

namespace App\Livewire\Form;

use App\Models\CourseScoringGroup as CourseScoringGroupModel;
use App\Models\CourseScoringGroupDetail;
use App\Models\WpGfForm;
use App\Models\WpGfFormMeta;
use Illuminate\Support\Collection;
use Livewire\Component;

class CourseScoringGroup extends Component
{
    /** Set from edit route only; empty on create screen. */
    public ?string $dataId = null;

    public string $title = '';

    public ?string $description = null;

    /**
     * @var list<array{form_id: int|null, search: string, field_ids: list<int>}>
     */
    public array $blocks = [];

    /**
     * Tidak lagi dipakai (pencarian form pakai Alpine). Tetap ada agar hydrate snapshot Livewire lama tidak error.
     *
     * @var array<int, list<array{id: int, title: string}>>
     */
    public array $blockFormPickResults = [];

    /** @var list<array{id: int, title: string}>|null Memo: satu query DB per-request render (pakai Alpine filter, bukan LIKE tiap ketikan). */
    private ?array $formCatalogMemo = null;

    public function mount(?string $dataId = null): void
    {
        $this->dataId = $dataId !== null && $dataId !== '' ? $dataId : null;

        if ($this->dataId !== null) {
            $group = CourseScoringGroupModel::with('details')->findOrFail((int) $this->dataId);
            $this->title = (string) $group->title;
            $this->description = $group->description;
            $this->hydrateBlocksFromGroup($group);
        }
    }

    private function hydrateBlocksFromGroup(CourseScoringGroupModel $group): void
    {
        $this->blocks = [];

        /** @var Collection<string, Collection<int, CourseScoringGroupDetail>> $byForm */
        $byForm = $group->details->groupBy('form_id');

        foreach ($byForm as $formIdString => $rows) {
            $fid = (int) $formIdString;
            $form = WpGfForm::find($fid);
            $this->blocks[] = [
                'form_id' => $fid,
                'search' => $form !== null ? (string) $form->title : "Form #{$fid}",
                'field_ids' => $rows->pluck('field_id')->map(fn ($v) => (int) $v)->values()->all(),
            ];
        }

        if (count($this->blocks) === 0) {
            $this->blocks[] = $this->emptyBlock();
        }
    }

    /** @return array{form_id: int|null, search: string, field_ids: list<int>} */
    private function emptyBlock(): array
    {
        return ['form_id' => null, 'search' => '', 'field_ids' => []];
    }

    public function saveNew(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $g = CourseScoringGroupModel::create([
            'title' => trim($this->title),
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);

        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Group created. You can add Gravity Forms and fields on this page.',
        ]);

        $this->redirect(route('course-scoring-group.edit', ['course_scoring_group' => $g->id]));
    }

    public function addFormBlock(): void
    {
        $this->blocks[] = $this->emptyBlock();
    }

    public function removeFormBlock(int $index): void
    {
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);

        if (count($this->blocks) === 0) {
            $this->blocks[] = $this->emptyBlock();
        }
    }

    public function pickForm(int $index, int $formId): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $form = WpGfForm::find($formId);
        $this->blocks[$index]['form_id'] = $formId;
        $this->blocks[$index]['search'] = $form !== null ? (string) $form->title : ('Form #'.$formId);
        $this->blocks[$index]['field_ids'] = [];
    }

    public function clearForm(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $this->blocks[$index]['form_id'] = null;
        $this->blocks[$index]['search'] = '';
        $this->blocks[$index]['field_ids'] = [];
    }

    public function setFieldChecked(int $index, int $fieldId, $checked): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $fieldId = abs((int) $fieldId);
        if ($fieldId < 1) {
            return;
        }

        $on = filter_var($checked, FILTER_VALIDATE_BOOLEAN);

        $selected = &$this->blocks[$index]['field_ids'];

        if ($on) {
            if (! in_array($fieldId, $selected, true)) {
                $selected[] = $fieldId;
            }
        } else {
            $selected = array_values(array_filter($selected, static fn ($id) => (int) $id !== $fieldId));
        }

        $selected = array_values(array_unique(array_map('intval', $selected)));
    }

    public function fieldIsChecked(int $index, int $fieldId): bool
    {
        if (! isset($this->blocks[$index])) {
            return false;
        }

        return in_array($fieldId, $this->blocks[$index]['field_ids'], true);
    }

    /** @return list<array{id: int, label: string, type: string}> */
    public static function gfFieldsForFormId(?int $formId): array
    {
        if ($formId === null || $formId < 1) {
            return [];
        }

        /** @var WpGfFormMeta|null $meta */
        $meta = WpGfFormMeta::query()->where('form_id', $formId)->first();
        if ($meta === null) {
            return [];
        }

        $raw = $meta->display_meta ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string) $raw);
        if (! is_object($decoded) || ! isset($decoded->fields) || ! is_array($decoded->fields)) {
            return [];
        }

        $out = [];
        foreach ($decoded->fields as $field) {
            if (! is_object($field)) {
                continue;
            }

            $id = isset($field->id) ? (int) $field->id : 0;
            if ($id <= 0) {
                continue;
            }

            $label = isset($field->label) ? (string) $field->label : ('Field #'.$id);
            $type = isset($field->type) ? (string) $field->type : '';

            if ($type !== 'radio') {
                continue;
            }

            $out[] = ['id' => $id, 'label' => $label, 'type' => $type];
        }

        return $out;
    }

    public function saveExisting(): void
    {
        if ($this->dataId === null) {
            return;
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'blocks' => 'present|array',
        ]);

        $group = CourseScoringGroupModel::findOrFail((int) $this->dataId);
        $group->update([
            'title' => trim($this->title),
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);

        CourseScoringGroupDetail::where('course_scoring_group_id', $group->id)->delete();

        foreach ($this->blocks as $block) {
            $formId = isset($block['form_id']) ? (int) $block['form_id'] : null;
            if ($formId === null || $formId < 1) {
                continue;
            }

            $fieldIds = $block['field_ids'] ?? [];
            if ($fieldIds === []) {
                continue;
            }

            foreach ($fieldIds as $fieldId) {
                $fid = (int) $fieldId;
                if ($fid < 1) {
                    continue;
                }

                try {
                    CourseScoringGroupDetail::create([
                        'course_scoring_group_id' => $group->id,
                        'form_id' => $formId,
                        'field_id' => $fid,
                    ]);
                } catch (\Illuminate\Database\QueryException) {
                    // Duplicate (unique) skipped
                }
            }
        }

        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Saved.',
        ]);
    }

    /** @return list<array{id: int, title: string}> */
    private function loadFormCatalog(): array
    {
        return WpGfForm::query()
            ->where('is_active', 1)
            ->where('is_trash', 0)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(static function ($f) {
                return ['id' => (int) $f->id, 'title' => (string) $f->title];
            })
            ->values()
            ->all();
    }

    public function render()
    {
        if ($this->dataId !== null) {
            if ($this->formCatalogMemo === null) {
                $this->formCatalogMemo = $this->loadFormCatalog();
            }
        }

        return view('livewire.form.course-scoring-group', [
            'formCatalog' => $this->formCatalogMemo ?? [],
        ]);
    }
}
