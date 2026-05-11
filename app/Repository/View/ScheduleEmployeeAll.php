<?php

namespace App\Repository\View;

use App\Models\ScheduleExecution;
use App\Models\User;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Legacy company schedule list (empty; no schedule storage).
 */
class ScheduleEmployeeAll extends ScheduleExecution implements View
{
    public static function tableSearch($params = null): Builder
    {
        return static::query();
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
        ];
    }

    public static function tableData($data = null): array
    {
        $link = $data->link ?? '';

        return [
            ['type' => 'index', 'data' => $data->id ?? 0],
            ['type' => 'string', 'data' => isset($data->user_id) ? (User::find($data->user_id)?->user_nicename ?? '-') : '-'],
            ['type' => 'string', 'text-align' => 'center', 'data' => $data->title ?? '-'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
<script >
function myFunction(link) {
    navigator.clipboard.writeText(link);
}
</script>
<button onclick='myFunction(`$link`)'  wire:click='toastAlert(`success`,`Link has been copied`)'  class='btn btn-primary text-nowrap'>Copy Link</button>"],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $data->schedule_access ?? 'Not schedule'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $data->schedule_deadline ?? 'Not schedule'],
        ];
    }
}
