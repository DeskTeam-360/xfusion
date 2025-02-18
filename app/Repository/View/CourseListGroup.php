<?php

namespace App\Repository\View;

use App\Models\Tag;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class CourseListGroup extends \App\Models\CourseGroup implements View
{
    protected $table ='course_groups';

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query()
            : static::query()
            ->where('title', 'like', "%$query%")
            ->orWhere('sub_title', 'like', "%$query%");
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
            ['label' => 'Group title', 'sort' => 'title'],
            ['label' => 'Group sub title', 'sort' => 'sub_title'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $link = route('course-group.edit',$data->id);

        return [
            ['type' => 'string','data'=>$data->id],
            ['type' => 'string', 'data' => $data->title],
            ['type' => 'string', 'data' => $data->sub_title],

            ['type' => 'raw_html','text-align'=>'center', 'data' => "
<div class='flex gap-1'>
<button href='#' wire:click='deleteItem($data->id)' class='btn btn-error'>Delete</button>
<a href='$link' class='btn btn-primary'>Edit</a>
</div>"],

        ];
    }
}
