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
            ->selectSub(
                CompanyGroupDetail::query()
                    ->from("{$detailsTable} as cgd")
                    ->whereColumn('cgd.company_group_id', "{$groupsTable}.id")
                    ->selectRaw('COUNT(*)'),
                'members_count'
            )
            ->selectSub(
                CompanyGroupDetail::query()
                    ->from("{$detailsTable} as cgd")
                    ->whereColumn('cgd.company_group_id', "{$groupsTable}.id")
                    ->where('cgd.status', \App\Models\CompanyGroup::STATUS_LEADER)
                    ->selectRaw('COUNT(*)'),
                'leaders_count'
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
            ['label' => 'Description', 'sort' => 'description'],
            ['label' => 'Members', 'text-align' => 'center', 'width' => '9%'],
            ['label' => 'Leaders', 'text-align' => 'center', 'width' => '9%'],
            ['label' => 'Actions', 'text-align' => 'center'],
        ];
    }

    public static function tableData($data = null): array
    {
        $linkEdit = route('company-group.edit', $data->id);

        $desc = $data->description ?? '';
        $descShort = mb_strlen((string) $desc) > 80 ? mb_substr((string) $desc, 0, 80).'…' : $desc;

        $membersCount = (int) ($data->members_count ?? 0);
        $leadersCount = (int) ($data->leaders_count ?? 0);
        $companyTitle = $data->company?->title ?? '—';

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $companyTitle],
            ['type' => 'string', 'data' => $data->title],
            ['type' => 'string', 'data' => $descShort ?: '—'],
            [
                'type' => 'raw_html',
                'text-align' => 'center',
                'data' => "<span class='tabular-nums font-medium text-dark dark:text-white'>{$membersCount}</span>",
            ],
            [
                'type' => 'raw_html',
                'text-align' => 'center',
                'data' => "<span class='tabular-nums font-medium text-dark dark:text-white'>{$leadersCount}</span>",
            ],
            ['type' => 'raw_html', 'text-align' => 'center', 'class' => 'admin-table__cell-actions', 'data' => "
<div class='admin-table-actions justify-center'>
<a href='{$linkEdit}' class='btn btn-primary'>Edit</a>
<button type='button' wire:click='deleteItem({$data->id})' class='btn btn-error'>Delete</button>
</div>"],
        ];
    }
}
