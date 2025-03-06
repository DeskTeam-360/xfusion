<?php

namespace App\Repository\View;

use App\Models\Tag;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class CourseList extends \App\Models\CourseList implements View
{

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query()
            : static::query()
            ->where('url', 'like', "%$query%")
            ->orWhere('course_title', 'like', "%$query%")
            ->orWhere('page_title', 'like', "%$query%");
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
            ['label' => 'Course title', 'sort' => 'course_title'],
            ['label' => 'Page title', 'sort' => 'page_title'],
            ['label' => 'Require Tag', 'sort' => 'keap_tag'],
            ['label' => 'Repeat', 'sort' => 'repeat_entry'],
            ['label' => 'Link', 'sort' => 'url'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $link = route('course-title.edit',$data->id);
        $tag='';
        if ($data->keap_tag!=null){
            $tag = Tag::find($data->keap_tag)->name??"Tag has been deleted";
        }
        return [
            ['type' => 'string','data'=>$data->id],
            ['type' => 'string', 'data' => $data->course_title],
            ['type' => 'string', 'data' => $data->page_title],
            ['type' => 'string', 'data' => $tag],
            ['type' => 'string', 'data' => ($data->repeat_entry==1)?'Yes':'No'],
            ['type' => 'raw_html', 'data' => "<b>Main link</b> : <a href='$data->url'>$data->url</a> <br><b> Next Page Link : </b> <a href='$data->url_next'>$data->url_next</a>"],
            ['type' => 'raw_html','text-align'=>'center', 'data' => "
<div class='flex gap-1'>
<button href='#' wire:click='deleteItem($data->id)' class='btn btn-error'>Delete</button>
<a href='$link' class='btn btn-primary'>Edit</a>
</div>"],

        ];
    }
}
