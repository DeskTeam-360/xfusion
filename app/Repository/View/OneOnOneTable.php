<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class OneOnOneTable extends \App\Models\OneOnOne implements View
{
    protected $table = 'wp_fusion_one_on_ones';

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'] ?? '';

        $q = static::query()
            ->with(['company:id,title', 'leader:ID,display_name,user_nicename', 'employee:ID,display_name,user_nicename'])
            ->withCount('conversations');

        if ($query === '' || $query === null) {
            return $q;
        }

        return $q->where(function ($w) use ($query) {
            $w->whereHas('company', fn ($c) => $c->where('title', 'like', "%{$query}%"))
                ->orWhereHas('leader', fn ($u) => $u->where('display_name', 'like', "%{$query}%"))
                ->orWhereHas('employee', fn ($u) => $u->where('display_name', 'like', "%{$query}%"));
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
            ['label' => 'Company', 'sort' => 'company_id'],
            ['label' => 'Leader', 'sort' => 'leader_user_id'],
            ['label' => 'Employee', 'sort' => 'employee_user_id'],
            ['label' => 'Conversations', 'text-align' => 'center', 'width' => '11%'],
            ['label' => 'Status', 'text-align' => 'center', 'width' => '10%'],
            ['label' => 'Actions', 'text-align' => 'center'],
        ];
    }

    public static function tableData($data = null): array
    {
        $linkEdit = route('one-on-one.edit', $data->id);
        $companyTitle = $data->company?->title ?? '—';
        $leaderName = $data->leader ? ($data->leader->display_name ?: $data->leader->user_nicename) : '—';
        $employeeName = $data->employee ? ($data->employee->display_name ?: $data->employee->user_nicename) : '—';
        $convCount = (int) ($data->conversations_count ?? 0);
        $statusBadge = $data->status === \App\Models\OneOnOne::STATUS_ACTIVE
            ? "<span class='badge bg-success'>Active</span>"
            : "<span class='badge bg-secondary'>Inactive</span>";

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $companyTitle],
            ['type' => 'string', 'data' => $leaderName],
            ['type' => 'string', 'data' => $employeeName],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "<span class='tabular-nums font-medium'>{$convCount}</span>"],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $statusBadge],
            ['type' => 'raw_html', 'text-align' => 'center', 'class' => 'admin-table__cell-actions', 'data' => "
<div class='admin-table-actions justify-center'>
<a href='{$linkEdit}' class='btn btn-primary'>Manage</a>
<button type='button' wire:click='deleteItem({$data->id})' class='btn btn-error'>Delete</button>
</div>"],
        ];
    }
}
