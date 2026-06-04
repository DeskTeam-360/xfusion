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

        $header = fgetcsv($handle);
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
            'skipped_field' => 0,
            'skipped_weight' => 0,
            'duplicate_key' => 0,
        ];
        $errors = [];
        $pendingDetails = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }

            ++$stats['rows'];

            $courseRaw = trim((string) ($row[self::COL_COURSE_TITLE] ?? ''));
            $question = trim((string) ($row[self::COL_QUESTION] ?? ''));

            if ($courseRaw === '' || $question === '') {
                ++$stats['skipped_field'];
                $errors[] = "Row {$stats['rows']}: empty course title or question.";

                continue;
            }

            $formTitle = $this->normalizeCourseTitle($courseRaw);
            $formId = $formByTitle[$formTitle] ?? null;

            if ($formId === null) {
                ++$stats['skipped_form'];
                $errors[] = "Row {$stats['rows']}: GF form not found for title [{$formTitle}] (from [{$courseRaw}]).";

                continue;
            }

            $fieldId = CourseScoringGroup::gfResolveFieldIdByQuestion($formId, $question);
            if ($fieldId === null) {
                ++$stats['skipped_field'];
                $errors[] = "Row {$stats['rows']}: field not found on form #{$formId} for question: ".mb_substr($question, 0, 120);

                continue;
            }

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

                $key = "{$groupTitle}|{$formId}|{$fieldId}";
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

            return $stats['skipped_form'] > 0 || $stats['skipped_field'] > 0 ? self::FAILURE : self::SUCCESS;
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

        return $stats['skipped_form'] > 0 || $stats['skipped_field'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Strip leading "[digits] - " from course title (CSV column 2).
     */
    public static function normalizeCourseTitle(string $raw): string
    {
        return trim((string) preg_replace('/^\d+\s*-\s*/', '', $raw));
    }

    /**
     * @return array<string, int> normalized title => form id
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
                if ($title !== '' && ! isset($index[$title])) {
                    $index[$title] = (int) $form->id;
                }
            });

        return $index;
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
        $this->line('Scoring UI (admin) only lists: radio, number — count: '.count(CourseScoringGroup::gfFieldsForFormId($formId)));

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
