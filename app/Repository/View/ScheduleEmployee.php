<?php

namespace App\Repository\View;

use App\Models\ScheduleExecution;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class ScheduleEmployee extends ScheduleExecution implements View
{
    protected $table = 'schedule_executions';

//schedule_executions
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        $param = $params['param1'];
        $param2 = $params['param2'];


        if ($param != null) {
            if ($param2 != null) {
                return empty($query) ?
                    static::query()->where('company_id', '=', $param)->where('user_id', '=', $param2) :
                    static::query()->where('company_id', '=', $param)->where('user_id', '=', $param2)->where(function ($q) use ($query) {
                        $q->whereHas('user', function ($q) use ($query) {
                            $q->where('user_nicename', 'like', "%$query%");
                        })
                            ->orWhere('schedule_access', 'like', "%$query%")
                            ->orWhere('schedule_deadline', 'like', "%$query%");
                    });
            } else {
                return empty($query) ?
                    static::query()->where('company_id', '=', $param) :
                    static::query()->where('company_id', '=', $param)->where(function ($q) use ($query) {
                        $q->whereHas('user', function ($q) use ($query) {
                            $q->where('user_nicename', 'like', "%$query%");
                        })
                            ->orWhere('schedule_access', 'like', "%$query%")
                            ->orWhere('schedule_deadline', 'like', "%$query%");
                    });
            }

        } else {
            return empty($query) ?
                static::query()->where('user_id', '=', $param2) :
                static::query()->where('user_id', '=', $param2)->where(function ($q) use ($query) {
                    $q->whereHas('user', function ($q) use ($query) {
                        $q->where('user_nicename', 'like', "%$query%");
                    })
                        ->orWhere('schedule_access', 'like', "%$query%")
                        ->orWhere('schedule_deadline', 'like', "%$query%");
                });
        }
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
            ['label' => 'Title', 'text-align' => 'center'],
            ['label' => 'Link', 'text-align' => 'center'],
            ['label' => 'Start accessible', 'text-align' => 'center'],
            ['label' => 'Accessible until', 'text-align' => 'center'],
            ['label' => 'Result', 'text-align' => 'center'],
        ];
    }

    public static function tableData($data = null): array
    {

        $link = $data->link;

        $result = "No result";
        $gfEntry = \App\Models\WpGfEntry::where('source_url', $link)
            ->where('created_by', $data->user_id)
            ->where('status','active')
            ->orderByDesc('id')
            ->first();

        if ($gfEntry != null) {
            $url = $link . '?dataId=' . $gfEntry->id;
            $result = "<a href='$url' target='_blank' class='btn bg-blue-300 text-nowrap' >Look result</a>
<a href='#' wire:click='trashItem($gfEntry->id)' class='btn bg-error text-nowrap' >Delete Record</a>
";
        }


        return [
            ['type' => 'index', 'data' => $data->id],
            ['type' => 'string', 'data' => \App\Models\User::find($data->user_id)->user_nicename],
            ['type' => 'string', 'text-align' => 'center', 'data' => $data->title],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
<script >
function myFunction(link) {
    navigator.clipboard.writeText(link);
}
</script>
<button onclick='myFunction(`$link`)'  wire:click='toastAlert(`success`,`Link has been copied`)'  class='btn btn-primary text-nowrap'>Copy Link</button>"],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $data->schedule_access ?? 'Not schedule'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $data->schedule_deadline ?? 'Not schedule'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "<div class='flex gap-1'>
$result
</div>"],
        ];
    }
}
