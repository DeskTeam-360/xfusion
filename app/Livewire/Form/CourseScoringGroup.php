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
    /** Gravity Forms field types selectable for scoring (matches {@see gfFieldsForFormId}). */
    private const GF_SCORING_FIELD_TYPES = ['radio', 'number'];

    /** Non-input GF types skipped when resolving CSV question → field_id. */
    private const GF_INPUT_SKIP_TYPES = [
        'html', 'section', 'page', 'submit', 'captcha', 'honeypot', 'password',
    ];

    /** Set from edit route only; empty on create screen. */
    public ?string $dataId = null;

    public string $title = '';

    public ?string $description = null;

    /**
     * @var list<array{form_id: int|null, search: string, field_ids: list<int>}>
     */
    public array $blocks = [];

    /**
     * Legacy property kept for hydrating old Livewire snapshots; search UI no longer writes here.
     *
     * @var array<int, list<array{id: int, title: string}>>
     */
    public array $blockFormPickResults = [];

    /** @var list<array{id: int, title: string}>|null Loaded once per request for client-side picker filter. */
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
        /** @var list<int> $ids */
        $ids = array_values(array_unique(array_map(
            static fn (array $f): int => (int) $f['id'],
            self::gfFieldsForFormId($formId)
        )));
        $this->blocks[$index]['field_ids'] = $ids;
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

    /** @return list<array{id: int, label: string, type: string}> Gravity Forms fields with type radio or number */
    public static function gfFieldsForFormId(?int $formId): array
    {
        return array_values(array_filter(
            self::gfInputFieldsForFormId($formId),
            static fn (array $f): bool => in_array($f['type'], self::GF_SCORING_FIELD_TYPES, true)
        ));
    }

    /**
     * All Gravity Forms input fields (any type except structural) — used for CSV import label matching.
     *
     * @return list<array{id: int, label: string, admin_label: string, type: string}>
     */
    public static function gfInputFieldsForFormId(?int $formId): array
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

            $type = isset($field->type) ? strtolower((string) $field->type) : '';
            if ($type === '' || in_array($type, self::GF_INPUT_SKIP_TYPES, true)) {
                continue;
            }

            $label = isset($field->label) ? trim((string) $field->label) : '';
            $adminLabel = isset($field->adminLabel) ? trim((string) $field->adminLabel) : '';

            $out[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : ('Field #'.$id),
                'admin_label' => $adminLabel,
                'type' => $type,
            ];
        }

        return $out;
    }

    /**
     * Match CSV "Question" text to a GF field id (all input types, not only radio/number).
     */
    public static function gfResolveFieldIdByQuestion(?int $formId, string $question): ?int
    {
        if ($formId === null || $formId < 1) {
            return null;
        }

        $needle = self::normalizeFieldLabelText($question);
        if ($needle === '') {
            return null;
        }

        foreach (self::gfInputFieldsForFormId($formId) as $field) {
            foreach (self::fieldLabelCandidates($field) as $candidate) {
                if (self::normalizeFieldLabelText($candidate) === $needle) {
                    return (int) $field['id'];
                }
            }
        }

        foreach (self::gfInputFieldsForFormId($formId) as $field) {
            foreach (self::fieldLabelCandidates($field) as $candidate) {
                if (strcasecmp(self::normalizeFieldLabelText($candidate), $needle) === 0) {
                    return (int) $field['id'];
                }
            }
        }

        $suffixId = self::gfResolveFieldIdByLabelSuffix($formId, $needle);
        if ($suffixId !== null) {
            return $suffixId;
        }

        return self::gfResolveFieldIdByPrefixMatch($formId, $needle);
    }

    /**
     * CSV labels like "SWOT – Strengths" / "SWOT â€" Strengths" when GF label is only "Strengths".
     */
    public static function gfResolveFieldIdByLabelSuffix(int $formId, string $question): ?int
    {
        $needle = self::normalizeFieldLabelText($question);
        $suffix = self::extractCategoryFieldSuffix($needle);
        if ($suffix === null) {
            return null;
        }

        foreach (self::gfInputFieldsForFormId($formId) as $field) {
            foreach (self::fieldLabelCandidates($field) as $candidate) {
                $label = self::normalizeFieldLabelText($candidate);
                if ($label === $suffix || strcasecmp($label, $suffix) === 0) {
                    return (int) $field['id'];
                }
                if (str_ends_with($label, '-'.$suffix) || str_ends_with($label, ' '.$suffix)) {
                    return (int) $field['id'];
                }
            }
        }

        return null;
    }

    /**
     * @return string|null Sub-label after SWOT/PESTLE prefix (e.g. "Strengths", "Political").
     */
    public static function extractCategoryFieldSuffix(string $normalized): ?string
    {
        if ($normalized === '') {
            return null;
        }

        if (preg_match('/[-]([^-]+)$/u', $normalized, $matches)) {
            $suffix = trim((string) $matches[1]);

            return $suffix !== '' && strlen($suffix) >= 2 ? $suffix : null;
        }

        if (preg_match('/^(?:SWOT|PESTLE)\s*["\x{2013}\x{2014}\-]\s*(.+)$/iu', $normalized, $matches)) {
            $suffix = trim((string) $matches[1]);

            return $suffix !== '' ? $suffix : null;
        }

        return null;
    }

    /**
     * Long labels: CSV/GF may differ slightly (dash type, truncation). Match if one starts with the other.
     */
    public static function gfResolveFieldIdByPrefixMatch(int $formId, string $needle, int $minOverlap = 48): ?int
    {
        $bestId = null;
        $bestOverlap = 0;

        foreach (self::gfInputFieldsForFormId($formId) as $field) {
            foreach (self::fieldLabelCandidates($field) as $candidate) {
                $hay = self::normalizeFieldLabelText($candidate);
                if ($hay === '') {
                    continue;
                }

                $overlap = 0;
                if (str_starts_with($hay, $needle)) {
                    $overlap = strlen($needle);
                } elseif (str_starts_with($needle, $hay)) {
                    $overlap = strlen($hay);
                }

                if ($overlap >= $minOverlap && $overlap > $bestOverlap) {
                    $bestOverlap = $overlap;
                    $bestId = (int) $field['id'];
                }
            }
        }

        return $bestId;
    }

    /**
     * @param  array{label: string, admin_label?: string}  $field
     * @return list<string>
     */
    private static function fieldLabelCandidates(array $field): array
    {
        $candidates = [];
        foreach (['label', 'admin_label'] as $key) {
            $text = trim((string) ($field[$key] ?? ''));
            if ($text !== '' && ! in_array($text, $candidates, true)) {
                $candidates[] = $text;
            }
        }

        return $candidates;
    }

    public static function normalizeFieldLabelText(string $text): string
    {
        $text = self::repairUtf8Mojibake($text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = self::normalizeQuoteCharacters($text);
        $text = str_replace(["\xc2\xa0", "\u{00A0}"], ' ', $text);
        // CSV "SWOT â€" Strengths" is often mojibake quote, not en-dash → treat as subsection separator.
        $text = preg_replace('/\b(SWOT|PESTLE)\s*["\']\s*/iu', '$1 - ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
        $text = self::normalizeDashCharacters($text);
        // CSV export sometimes ends with ".." while GF has a single period.
        $text = preg_replace('/\.+$/u', '.', $text) ?? $text;

        return $text;
    }

    /**
     * Unify em/en dash and CSV mojibake (â€") so "script—challenge" matches across sources.
     */
    public static function normalizeDashCharacters(string $text): string
    {
        $text = self::stripInvisibleCharacters($text);
        $text = preg_replace('/â€[\x{0093}\x{0094}\x{0096}\x{0097}\x{009C}\x{009D}]/u', '-', $text) ?? $text;
        $text = preg_replace('/[\x{2013}\x{2014}\x{2015}\x{2212}]/u', '-', $text) ?? $text;
        $text = preg_replace('/\s*-\s*/', '-', $text) ?? $text;

        return $text;
    }

    /**
     * Normalize GF / CSV form titles for lookup (mojibake, quotes). Keeps spaced " - " separators.
     */
    public static function normalizeFormTitleText(string $text): string
    {
        $text = self::repairUtf8Mojibake($text);
        $text = self::stripInvisibleCharacters($text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = self::normalizeQuoteCharacters($text);
        $text = str_replace(["\xc2\xa0", "\u{00A0}"], ' ', $text);
        $text = self::replaceMojibakeDashesInTitle($text);
        $text = preg_replace('/[\x{2013}\x{2014}\x{2015}\x{2212}]/u', ' - ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        return self::trimFormTitleEdges($text);
    }

    /**
     * Zero-width space (â€‹) and similar — must not become trailing " - ".
     */
    public static function stripInvisibleCharacters(string $text): string
    {
        $text = str_replace(['â€‹', 'â€Œ', 'â€Ž', 'â€', 'ï»¿'], '', $text);

        return preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $text) ?? $text;
    }

    /**
     * Only dash-like â€X triplets → " - ", not zero-width bytes.
     */
    public static function replaceMojibakeDashesInTitle(string $text): string
    {
        return preg_replace('/â€[\x{0093}\x{0094}\x{0096}\x{0097}\x{009C}\x{009D}]/u', ' - ', $text) ?? $text;
    }

    /**
     * Remove trailing " -" artifacts after stripping invisible chars.
     */
    public static function trimFormTitleEdges(string $text): string
    {
        $text = trim($text);

        return preg_replace('/(?:\s*-\s*)+$/u', '', $text) ?? $text;
    }

    /**
     * Secondary lookup key: same title with straight quotes removed.
     */
    public static function normalizeFormTitleAlias(string $text): string
    {
        $text = self::normalizeFormTitleText($text);

        return (string) preg_replace('/["\']+/u', '', $text);
    }

    /**
     * Fix UTF-8 mojibake from Excel/CSV export (UTF-8 smart quotes read as Latin-1 bytes).
     * e.g. â€œ → “, â€ → ”, â€™ → ’
     */
    public static function repairUtf8Mojibake(string $text): string
    {
        if ($text === '' || ! str_contains($text, 'â€') && ! str_contains($text, 'Ã')) {
            return $text;
        }

        static $map = null;
        if ($map === null) {
            $map = [
                'â€œ' => "\u{201C}",
                'â€\u{009D}' => "\u{201D}",
                'â€' => "\u{201D}",
                'â€˜' => "\u{2018}",
                'â€™' => "\u{2019}",
                'â€¦' => "\u{2026}",
                'Ã©' => 'é',
                'Ã¨' => 'è',
                'Ã«' => 'ë',
                'Ã´' => 'ô',
                'Ã¢' => 'â',
                'Ã¼' => 'ü',
                'Ã±' => 'ñ',
            ];
        }

        return str_replace(array_keys($map), array_values($map), $text);
    }

    /**
     * Map curly/smart quotes to straight ASCII so CSV and GF labels match.
     */
    public static function normalizeQuoteCharacters(string $text): string
    {
        return strtr($text, [
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{201E}" => '"',
            "\u{00AB}" => '"',
            "\u{00BB}" => '"',
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{201A}" => "'",
            '«' => '"',
            '»' => '"',
            '„' => '"',
            '‚' => "'",
        ]);
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
