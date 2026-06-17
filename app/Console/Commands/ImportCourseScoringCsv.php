<?php

namespace App\Console\Commands;

use App\Livewire\Form\CourseScoringGroup;
use App\Models\CourseScoringGroup as CourseScoringGroupModel;
use App\Models\CourseScoringGroupDetail;
use App\Models\WpGfForm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportCourseScoringCsv extends Command
{
    protected $signature = 'course-scoring:import-csv
                            {--path= : Path to CSV (default: public/FUSION_COR_Primary_Scale_Mapping.xlsx - Scaled Mapping.csv)}
                            {--dry-run : Parse and report without writing to the database}
                            {--force : Replace existing data without confirmation (use on server)}
                            {--skip-replace : Do not delete existing groups/details before import}
                            {--list-fields= : List GF input fields for a form_id (debug), then exit}';

    protected $description = 'Replace course scoring groups from COR Primary Scale Mapping CSV (weights per group column).';

    /** CSV column indexes (0-based). Columns 0,3,14,15 ignored per spec. */
    private const COL_COURSE_TITLE = 1;

    private const COL_QUESTION = 2;

    private const COL_WEIGHT_START = 4;

    private const COL_WEIGHT_END = 13;

    public function handle(): int
    {
        $listFormId = $this->option('list-fields');
        if ($listFormId !== null && $listFormId !== '') {
            return $this->listFormFields((int) $listFormId);
        }

        $path = $this->option('path')
            ?: base_path('public/FUSION_COR_Primary_Scale_Mapping.xlsx - Scaled Mapping.csv');

        if (! is_readable($path)) {
            $this->error("CSV not found or not readable: {$path}");

            return self::FAILURE;
        }

        if (! Schema::hasColumn('wp_course_scoring_group_details', 'weight')) {
            $this->error('Column weight missing on wp_course_scoring_group_details. Run database/sql/wp_course_scoring_group_details_add_weight.sql first.');

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error("Could not open: {$path}");

            return self::FAILURE;
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if ($header === false || count($header) < 14) {
            fclose($handle);
            $this->error('Invalid or empty CSV header.');

            return self::FAILURE;
        }

        $groupTitles = array_map(
            static fn (string $h): string => trim($h),
            array_slice($header, self::COL_WEIGHT_START, self::COL_WEIGHT_END - self::COL_WEIGHT_START + 1)
        );

        if (count($groupTitles) !== 10) {
            fclose($handle);
            $this->error('Expected 10 weight columns (Get Real … Execution). Found: '.count($groupTitles));

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $formByTitle = $this->buildFormTitleIndex();
        $stats = [
            'rows' => 0,
            'details' => 0,
            'skipped_form' => 0,
            'null_form_id' => 0,
            'skipped_field' => 0,
            'null_field_id' => 0,
            'skipped_weight' => 0,
            'duplicate_key' => 0,
        ];
        $errors = [];
        $pendingDetails = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }

            ++$stats['rows'];

            $courseRaw = trim((string) ($row[self::COL_COURSE_TITLE] ?? ''));
            $question = trim((string) ($row[self::COL_QUESTION] ?? ''));

            if ($courseRaw === '') {
                ++$stats['skipped_field'];
                $errors[] = "Row {$stats['rows']}: empty course title.";

                continue;
            }

            $formTitle = $this->normalizeCourseTitle($courseRaw);
            $formId = $this->resolveFormId($formTitle, $formByTitle);

            if ($formId === null) {
                ++$stats['null_form_id'];
                $errors[] = "Row {$stats['rows']}: GF form not found; importing weights with form_id NULL. Title: [{$formTitle}]";
            }

            $fieldId = null;
            if ($question !== '' && $formId !== null) {
                $fieldId = CourseScoringGroup::gfResolveFieldIdByQuestion($formId, $question);
            }

            if ($fieldId === null && $question !== '') {
                ++$stats['null_field_id'];
                if ($formId !== null) {
                    $snippet = mb_substr($question, 0, 100);
                    $errors[] = "Row {$stats['rows']}: field not found on form #{$formId}; importing weights with field_id NULL. Question: {$snippet}".(mb_strlen($question) > 100 ? '…' : '');
                }
            }

            $formKey = $formId !== null ? (string) $formId : 'form:'.md5($formTitle);
            $detailKeySuffix = $fieldId !== null
                ? (string) $fieldId
                : 'null:'.md5($formTitle.'|'.$question);

            foreach ($groupTitles as $offset => $groupTitle) {
                $colIndex = self::COL_WEIGHT_START + $offset;
                $weightRaw = trim((string) ($row[$colIndex] ?? ''));
                if ($weightRaw === '') {
                    ++$stats['skipped_weight'];

                    continue;
                }

                $weight = $this->parseWeight($weightRaw);
                if ($weight === null) {
                    ++$stats['skipped_weight'];
                    $errors[] = "Row {$stats['rows']}, group [{$groupTitle}]: invalid weight [{$weightRaw}].";

                    continue;
                }

                $key = "{$groupTitle}|{$formKey}|{$detailKeySuffix}";
                if (isset($pendingDetails[$key])) {
                    ++$stats['duplicate_key'];
                }

                $pendingDetails[$key] = [
                    'group_title' => $groupTitle,
                    'form_id' => $formId,
                    'field_id' => $fieldId,
                    'weight' => $weight,
                ];
            }
        }

        fclose($handle);

        $stats['details'] = count($pendingDetails);

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if ($errors !== []) {
            $this->warn('Issues ('.count($errors).'):');
            foreach (array_slice($errors, 0, 30) as $line) {
                $this->line('  '.$line);
            }
            if (count($errors) > 30) {
                $this->line('  … '.(count($errors) - 30).' more');
            }
        }

        if ($dryRun) {
            $this->info('Dry run — no database changes.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Replace ALL existing course scoring groups and import '.$stats['details'].' details?')) {
            $this->info('Aborted. Use --force to skip this prompt.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($pendingDetails, $groupTitles): void {
            if (! $this->option('skip-replace')) {
                CourseScoringGroupDetail::query()->delete();
                CourseScoringGroupModel::query()->delete();
            }

            $groupIds = [];
            foreach ($groupTitles as $title) {
                $group = CourseScoringGroupModel::query()->firstOrCreate(
                    ['title' => $title],
                    ['description' => null]
                );
                $groupIds[$title] = (int) $group->id;
            }

            foreach ($pendingDetails as $detail) {
                CourseScoringGroupDetail::create([
                    'course_scoring_group_id' => $groupIds[$detail['group_title']],
                    'form_id' => $detail['form_id'],
                    'field_id' => $detail['field_id'],
                    'weight' => $detail['weight'],
                ]);
            }
        });

        $this->info('Import complete.');

        return self::SUCCESS;
    }

    /**
     * Strip leading "[digits] - " and normalize mojibake/quotes (CSV column 2).
     */
    public static function normalizeCourseTitle(string $raw): string
    {
        $stripped = trim((string) preg_replace('/^\d+\s*-\s*/', '', $raw));

        return CourseScoringGroup::normalizeFormTitleText($stripped);
    }

    /**
     * @return array<string, int> lookup key (normalized / alias) => form id
     */
    private function buildFormTitleIndex(): array
    {
        $index = [];

        WpGfForm::query()
            ->where('is_trash', 0)
            ->orderBy('id')
            ->get(['id', 'title'])
            ->each(function (WpGfForm $form) use (&$index): void {
                $title = trim((string) $form->title);
                if ($title === '') {
                    return;
                }

                $id = (int) $form->id;
                $keys = [
                    $title,
                    CourseScoringGroup::normalizeFormTitleText($title),
                    CourseScoringGroup::normalizeFormTitleAlias($title),
                ];

                foreach ($keys as $key) {
                    if ($key !== '' && ! isset($index[$key])) {
                        $index[$key] = $id;
                    }
                }
            });

        return $index;
    }

    private function resolveFormId(string $normalizedTitle, array $formByTitle): ?int
    {
        $candidates = array_values(array_unique(array_filter([
            $normalizedTitle,
            CourseScoringGroup::trimFormTitleEdges($normalizedTitle),
            CourseScoringGroup::normalizeFormTitleAlias($normalizedTitle),
        ])));

        if (str_starts_with($normalizedTitle, 'No Orders - ')) {
            $without = trim(substr($normalizedTitle, strlen('No Orders - ')));
            if ($without !== '') {
                $candidates[] = $without;
                $candidates[] = 'No Orders - '.$without;
            }
        }

        foreach ($candidates as $title) {
            if ($title === '') {
                continue;
            }

            if (isset($formByTitle[$title])) {
                return $formByTitle[$title];
            }

            foreach ($formByTitle as $key => $formId) {
                if (strcasecmp($key, $title) === 0) {
                    return $formId;
                }
            }
        }

        $bestId = null;
        $bestLen = 0;
        foreach ($formByTitle as $key => $formId) {
            if (strlen($key) < 12) {
                continue;
            }
            if (str_contains($normalizedTitle, $key) || str_contains($key, $normalizedTitle)) {
                $len = min(strlen($key), strlen($normalizedTitle));
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $bestId = $formId;
                }
            }
        }

        return $bestLen >= 15 ? $bestId : null;
    }

    private function listFormFields(int $formId): int
    {
        if ($formId < 1) {
            $this->error('Invalid form_id.');

            return self::FAILURE;
        }

        $form = WpGfForm::find($formId);
        $this->info('Form #'.$formId.($form ? ': '.(string) $form->title : ''));

        $rows = [];
        foreach (CourseScoringGroup::gfInputFieldsForFormId($formId) as $field) {
            $rows[] = [
                $field['id'],
                $field['type'],
                $field['label'],
                $field['admin_label'] ?? '',
            ];
        }

        if ($rows === []) {
            $this->warn('No input fields found in display_meta.');

            return self::FAILURE;
        }

        $this->table(['id', 'type', 'label', 'adminLabel'], $rows);
        $this->line('Scoring UI (admin) lists all GF input fields — count: '.count(CourseScoringGroup::gfFieldsForFormId($formId)));

        return self::SUCCESS;
    }

    private function parseWeight(string $raw): ?string
    {
        $raw = str_replace(',', '.', trim($raw));
        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return number_format((float) $raw, 2, '.', '');
    }
}
