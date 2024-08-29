<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class CourseScheduleGenerate extends \App\Models\CourseScheduleGenerate implements View
{
    protected $table= 'course_schedule_generates';

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query() : static::query()
            ->whereHas('courseList',function ($q) use ($query) {
                $q->where('url', 'like', "%$query%")
                    ->orWhere('course_title', 'like', "%$query%")
                    ->orWhere('page_title', 'like', "%$query%");
            });
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
            ['label' => 'Parent title', ],
            ['label' => 'Course title', ],
            ['label' => 'Link', 'sort' => 'link'],
            ['label' => 'week','text-align'=>'center', ],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $link = route('course-schedule-generate-edit',$data->id);
        $url = $data->courseList->url;
        return [
            ['type' => 'index'],
            ['type' => 'string', 'data' => $data->courseListParent->course_title." - ".$data->courseListParent->page_title],
            ['type' => 'string', 'data' => $data->courseList->course_title." - ".$data->courseList->page_title],
//            ['type' => 'raw_html', 'data' => "<a href='$url' class='px-2 py-1 rounded-md bg-primary text-white' target='_blank'>LINK</a>"],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
<script >
function myFunction(link) {
    navigator.clipboard.writeText(link);
}
</script>
<button onclick='myFunction(`$url`)'  wire:click='toastAlert(`success`,`Link has been copied`)'  class='btn btn-primary text-nowrap'>Copy Link</button>"],
            ['type' => 'string','text-align'=>'center', 'data' => $data->week],
            ['type' => 'raw_html','text-align'=>'center', 'data' => "
<div class='flex gap-1'>
<a href='$link' class='btn btn-primary'>Edit</a>
<button href='#' wire:click='deleteItem($data->id)' class='btn btn-error'>delete</button>
</div>"],

        ];
    }
}
