<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class CompanyEmployee2 extends \App\Models\CompanyEmployee implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        $param = $params['param1'];
//        dd(static::query()->where('company_id','=',$param));
        return empty($query) ?
            static::query()->where('company_id', '=', $param) :
            static::query()->where('company_id', '=', $param)
                ->whereHas('user', function ($q) use ($query) {
                $q->where('user_nicename', 'like', "%$query%");
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
            ['label' => 'Employee Name', 'sort' => 'user_id'],
            // ['label' => 'Course completed count', 'text-align' => 'center'],
            ['label' => 'Course progress', 'text-align' => 'left'],
            ['label' => 'User information', 'text-align' => 'left'],
            
            
        ];
    }

    public static function tableData($data = null): array
    {
        $user = \App\Models\User::find($data->user_id);
        $courseCompletedCount = \App\Models\WpGfEntry::where('created_by', $data->user_id)->where('status', 'Active')->count();
        // $courseProgress = $courseCompletedCount . '/' . \App\Models\Co::where('user_id', $data->user_id)->count();
        $lastCourseCompletedAt = \App\Models\WpGfEntry::where('created_by', $data->user_id)->where('status', 'Active')->orderBy('id', 'desc')->first();
        $lastCourseCompletedAtTitle = '';
        $lastCourseCompletedAtDate = '';
        if($lastCourseCompletedAt){
            $lastCourseCompletedAtTitle = $lastCourseCompletedAt->wpGfForm->title;
            $lastCourseCompletedAtDate =  \Carbon\Carbon::parse($lastCourseCompletedAt->date_created)->format('F d, Y');
        } else {
            $lastCourseCompletedAtTitle = '-';
            $lastCourseCompletedAtDate = '-';
        }

        $courseCompletedCountTotal = 0;
        $courseGroupCountTotal = 0;
        $courseProgress = [];
        foreach(CourseGroup::orderBy('title', 'asc')->orderBy('sub_title', 'asc')->get() as $courseGroup){
            $formIds = $courseGroup->courseGroupDetails->pluck('course_list_id')->toArray();
            $progress = \App\Models\WpGfEntry::where('created_by', $data->user_id)
            ->where('status', 'Active')->whereIn('form_id', $formIds)->count();
            $courseProgress[] = '<div style="display:flex;justify-content:space-between;">'
    . '<span><strong>' . $courseGroup->title . ' - ' . $courseGroup->sub_title . '</strong></span>'
    . ' <span>' . $progress . '/' . $courseGroup->courseGroupDetails->count() . ' (' . round($progress/$courseGroup->courseGroupDetails->count()*100) . '%)</span>'
    . '</div>';
 
            $courseCompletedCountTotal += $progress;
            $courseGroupCountTotal += $courseGroup->courseGroupDetails->count();
}   

        $courseProgress = implode(' ', $courseProgress);
        $courseCompletedCount = $courseCompletedCountTotal . '/' . $courseGroupCountTotal;
        $courseProgress .= '<hr>';
        $courseProgress .= "<div style='display:flex;justify-content:space-between;'><span><b>Total</b></span> <span>". $courseCompletedCount . ' (' . round($courseCompletedCountTotal/$courseGroupCountTotal*100) . '%)</span></div>  </br>';
        // dd($courseProgress);
        $lastLoginAt = \App\Models\WpViewAllLog::where('user_id', $data->user_id)->where('note', 'login action from wordfence')->orderBy('id', 'desc')->first(); 
        if($lastLoginAt){
            $lastLoginAt = "<span>" . \Carbon\Carbon::parse($lastLoginAt->log_time)->format('F d, Y') . "</span>";
        } else {
            $lastLoginAt = '-';
        }   
        $userInfo = "Last login: $lastLoginAt <br> Last course: $lastCourseCompletedAtTitle <br> Last course completed at: $lastCourseCompletedAtDate";

        $activityCheckButton = '';
        if ($courseCompletedCountTotal != 0) {
            $link4 = route('user.course', [$data->user_id]);
            $activityCheckButton = "<span><a href='$link4' class='btn btn-success text-nowrap'>Activity Check</a></span>";
        }

        $userInfo .= "<br><br> $activityCheckButton";

        return [
            ['type' => 'index', 'data' => $data->id],
            ['type' => 'string', 'data' => $user->user_nicename],
            ['type' => 'raw_html', 'text-align' => 'left', 'data' => $courseProgress],
            // ['type' => 'raw_html', 'text-align' => 'center', 'data' => $lastCourseCompletedAt],
            ['type' => 'raw_html', 'text-align' => 'left', 'data' => $userInfo],
        ];
    }
}
