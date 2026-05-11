<?php

namespace App\Repository\View;

use App\Models\User;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class CourseGroup extends \App\Models\CourseGroup implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query() : static::query()->where('title', 'like', "%$query%");
    }

    public static function tableView(): array
    {
        return [
            'searchable' => true,
        ];
    }

    public static function tableField(): array
    {
        return [
            ['label' => '#', 'sort' => 'id', 'width' => '7%'],
            ['label' => 'Name', 'sort' => 'user_nicename'],
            ['label' => 'Completed course status'],
            ['label' => 'Date start course'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $row = $data instanceof User ? $data : null;
        if ($row === null && is_numeric($data)) {
            $row = User::find($data);
        }

        $name = $row?->user_nicename ?? '-';
        $id = $row?->ID ?? $row?->id ?? 0;
        $link = $id ? route('company.edit', $id) : '#';

        return [
            ['type' => 'string', 'data' => (string) $id],
            ['type' => 'string', 'data' => $name],
            ['type' => 'string', 'data' => '-'],
            ['type' => 'string', 'data' => '-'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
                <div class='flex gap-1'>
                    <span><a href='$link' class='btn btn-primary'>Course</a></span>
                </div>
            "],
        ];
    }
}
