<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

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

        $courseGroup = \App\Models\CourseGroup::find(substr(url()->current(), -1));
//        dd($courseGroup);

        $formIds = [];
        foreach ($courseGroup->courseGroupDetails as $cgd) {
            $formIds[] = $cgd->courseList->wp_gf_form_id;
        }
//        dd($formIds);

        $data = User::whereHas('WpGfEntry', function ($q) use ($formIds) {
            $q->whereIn('form_id', $formIds);
        })->pluck('ID');
        dd($data->id);

        $scheduleExec = \DB::table('wp_gf_entry')->where('created_by', $data->id)->count() >= \App\Models\CourseGroupBackup::where('season_id', $d)->count() ? 'Done' : 'On Progress';
        $date = \App\Models\ScheduleExecution::where('user_id', $data->id)->first()['schedule_access'];
        $link = route('company.edit',$data->id);

        return [
            ['type' => 'string','data'=>$data->id],
            ['type' => 'string', 'data' =>  \App\Models\User::find($data->user_id)->user_nicename],
            ['type' => 'string', 'data' => $scheduleExec],
            ['type' => 'string', 'data' => $date],
            ['type' => 'raw_html','text-align'=>'center', 'data' => "
                <div class='flex gap-1'>
                    <span><a href='$link' class='btn btn-primary'>Course</a></span>
                </div>
            "],
        ];
    }
}
