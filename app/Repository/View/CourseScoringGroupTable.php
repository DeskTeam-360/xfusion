<?php

namespace App\Repository\View;

use App\Models\CourseScoringGroupDetail;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class CourseScoringGroupTable extends \App\Models\CourseScoringGroup implements View
{
    protected $table = 'wp_course_scoring_groups';

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'] ?? '';
        $groupsTable = (new static)->getTable();
        $detailsTable = (new CourseScoringGroupDetail)->getTable();

        $q = static::query()
            ->select("{$groupsTable}.*")
            ->selectSub(
                static::detailsCountSubquery($detailsTable, $groupsTable, 'forms'),
                'forms_count'
            )
            ->selectSub(
                static::detailsCountSubquery($detailsTable, $groupsTable, 'questions'),
                'questions_count'
            );

        return $query === '' || $query === null
            ? $q
            : $q->where(static function ($w) use ($query) {
                $w->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
    }

    /**
     * Per-group counts from wp_course_scoring_group_details (scoped by group id).
     */
    private static function detailsCountSubquery(
        string $detailsTable,
        string $groupsTable,
        string $type
    ): Builder {
        $sub = CourseScoringGroupDetail::query()
            ->from("{$detailsTable} as csgd")
            ->whereColumn('csgd.course_scoring_group_id', "{$groupsTable}.id");

        if ($type === 'forms') {
            return $sub
                ->whereNotNull('csgd.form_id')
                ->where('csgd.form_id', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('csgd.weight')->orWhere('csgd.weight', '>', 0);
                })
                ->where('csgd.field_id', '>', 0)
                ->selectRaw('COUNT(DISTINCT csgd.form_id)');
        }

        return $sub
            ->whereNotNull('csgd.field_id')
            ->where('csgd.field_id', '>', 0)
            ->where(function ($q) {
                $q->whereNull('csgd.weight')->orWhere('csgd.weight', '>', 0);
            })
            ->selectRaw('COUNT(*)');
    }

    public static function tableView(): array
    {
        return ['searchable' => true];
    }

    public static function tableField(): array
    {
        return [
            ['label' => '#', 'sort' => 'id', 'width' => '7%'],
            ['label' => 'Title', 'sort' => 'title'],
            ['label' => 'Description', 'sort' => 'description'],
            ['label' => 'Forms', 'text-align' => 'center', 'width' => '8%'],
            ['label' => 'Questions', 'text-align' => 'center', 'width' => '10%'],
            ['label' => 'Actions', 'text-align' => 'center'],
        ];
    }

    public static function tableData($data = null): array
    {
        $linkEdit = route('course-scoring-group.edit', $data->id);

        $desc = $data->description ?? '';
        $descShort = mb_strlen((string) $desc) > 80 ? mb_substr((string) $desc, 0, 80) . '…' : $desc;

        $formsCount = static::resolveFormsCount($data);
        $questionsCount = static::resolveQuestionsCount($data);

        $formsLabel = $formsCount === 1 ? 'form' : 'forms';
        $questionsLabel = $questionsCount === 1 ? 'question' : 'questions';

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $data->title],
            ['type' => 'string', 'data' => $descShort ?: '—'],
            [
                'type' => 'raw_html',
                'text-align' => 'center',
                'data' => "<span class='tabular-nums font-medium text-dark dark:text-white' title='{$formsCount} connected Gravity Form(s)'>{$formsCount}</span>"
                    . "<span class='ms-1 text-xs text-muted dark:text-darklink'>{$formsLabel}</span>",
            ],
            [
                'type' => 'raw_html',
                'text-align' => 'center',
                'data' => "<span class='tabular-nums font-medium text-dark dark:text-white' title='{$questionsCount} connected field(s)'>{$questionsCount}</span>"
                    . "<span class='ms-1 text-xs text-muted dark:text-darklink'>{$questionsLabel}</span>",
            ],
            ['type' => 'raw_html', 'text-align' => 'center', 'class' => 'admin-table__cell-actions', 'data' => "
<div class='admin-table-actions justify-center'>
<a href='{$linkEdit}' class='btn btn-primary'>Edit</a>
<button type='button' wire:click='deleteItem({$data->id})' class='btn btn-error'>Delete</button>
</div>"],
        ];
    }

    private static function resolveFormsCount(object $data): int
    {
        if (isset($data->forms_count)) {
            return (int) $data->forms_count;
        }

        return (int) CourseScoringGroupDetail::query()
            ->where('course_scoring_group_id', (int) $data->getKey())
            ->connected()
            ->distinct()
            ->count('form_id');
    }

    private static function resolveQuestionsCount(object $data): int
    {
        if (isset($data->questions_count)) {
            return (int) $data->questions_count;
        }

        return (int) CourseScoringGroupDetail::query()
            ->where('course_scoring_group_id', (int) $data->getKey())
            ->connected()
            ->count();
    }
}
