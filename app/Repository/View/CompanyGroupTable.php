<?php

namespace App\Repository\View;

use App\Models\CompanyGroupDetail;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class CompanyGroupTable extends \App\Models\CompanyGroup implements View
{
    protected $table = 'wp_company_groups';

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'] ?? '';
        $groupsTable = (new static)->getTable();
        $detailsTable = (new CompanyGroupDetail)->getTable();

        $q = static::query()
            ->select("{$groupsTable}.*")
            ->with('company:id,title')
            ->with(['details' => function ($detailsQuery) {
                $detailsQuery->where('status', \App\Models\CompanyGroup::STATUS_LEADER)
                    ->with('user:ID,display_name,user_nicename');
            }])
            ->selectSub(
                CompanyGroupDetail::query()
                    ->from("{$detailsTable} as cgd")
                    ->whereColumn('cgd.company_group_id', "{$groupsTable}.id")
                    ->selectRaw('COUNT(*)'),
                'members_count'
            );

        return $query === '' || $query === null
            ? $q
            : $q->where(static function ($w) use ($query) {
                $w->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('company', static function ($c) use ($query) {
                        $c->where('title', 'like', "%{$query}%");
                    });
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
            ['label' => 'Title', 'sort' => 'title'],
            ['label' => 'Members', 'text-align' => 'center', 'width' => '9%'],
            ['label' => 'Leader'],
            ['label' => 'Actions', 'text-align' => 'center'],
        ];
    }

    public static function tableData($data = null): array
    {
        $linkEdit = route('company-group.edit', $data->id);

        $membersCount = (int) ($data->members_count ?? 0);
        $companyTitle = $data->company?->title ?? '—';

        $leaderNames = $data->details
            ->map(fn ($detail) => $detail->user?->display_name ?: $detail->user?->user_nicename)
            ->filter()
            ->implode(', ');

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $companyTitle],
            ['type' => 'string', 'data' => $data->title],
            [
                'type' => 'raw_html',
                'text-align' => 'center',
                'data' => "<span class='tabular-nums font-medium text-dark dark:text-white'>{$membersCount}</span>",
            ],
            ['type' => 'string', 'data' => $leaderNames ?: '—'],
            ['type' => 'raw_html', 'text-align' => 'center', 'class' => 'admin-table__cell-actions', 'data' => "
<div class='admin-table-actions justify-center'>
<a href='{$linkEdit}' class='btn btn-primary'>Edit</a>
<button type='button' wire:click='deleteItem({$data->id})' class='btn btn-error'>Delete</button>
</div>"],
        ];
    }
}
