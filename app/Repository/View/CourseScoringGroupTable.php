<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class CourseScoringGroupTable extends \App\Models\CourseScoringGroup implements View
{
    protected $table = 'wp_course_scoring_groups';

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'] ?? '';

        $q = static::query();

        return $query === '' || $query === null
            ? $q
            : $q->where(static function ($w) use ($query) {
                $w->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
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
            ['label' => 'Actions', 'text-align' => 'center'],
        ];
    }

    public static function tableData($data = null): array
    {
        $linkEdit = route('course-scoring-group.edit', $data->id);

        $desc = $data->description ?? '';
        $descShort = mb_strlen((string) $desc) > 80 ? mb_substr((string) $desc, 0, 80) . '…' : $desc;

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $data->title],
            ['type' => 'string', 'data' => $descShort ?: '—'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
<div class='flex flex-wrap justify-center gap-1'>
<a href='{$linkEdit}' class='btn btn-primary'>Edit</a>
<button type='button' wire:click='deleteItem({$data->id})' class='btn btn-error'>Delete</button>
</div>"],
        ];
    }
}
