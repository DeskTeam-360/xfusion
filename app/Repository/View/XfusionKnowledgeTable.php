<?php

namespace App\Repository\View;

use App\Models\XfusionKnowledge;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;

class XfusionKnowledgeTable extends XfusionKnowledge implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = trim((string) ($params['query'] ?? ''));

        $q = static::query()->with('postMeta');

        if ($query === '') {
            return $q;
        }

        return $q->where(function (Builder $w) use ($query): void {
            $w->where('post_title', 'like', "%{$query}%")
                ->orWhere('post_content', 'like', "%{$query}%")
                ->orWhereHas('postMeta', function (Builder $m) use ($query): void {
                    $m->where('meta_key', XfusionKnowledge::META_CATEGORY)
                        ->where('meta_value', 'like', "%{$query}%");
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
            ['label' => '#', 'sort' => 'ID', 'width' => '7%'],
            ['label' => 'Title', 'sort' => 'post_title'],
            ['label' => 'Category', 'sort' => 'post_title'],
            ['label' => 'Status'],
            ['label' => 'LLM sync'],
            ['label' => 'Actions', 'text-align' => 'center'],
        ];
    }

    public static function tableData($data = null): array
    {
        $linkEdit = route('xfusion-knowledge.edit', $data->ID);
        $category = $data->getMeta(XfusionKnowledge::META_CATEGORY) ?: '—';
        $sync = $data->getMeta(XfusionKnowledge::META_SYNC_STATUS) ?: 'pending';
        $syncError = $data->getMeta(XfusionKnowledge::META_SYNC_ERROR);

        $syncBadge = match ($sync) {
            'synced' => "<span class='badge bg-success/15 text-success'>Synced</span>",
            'failed' => "<span class='badge bg-error/15 text-error' title='".e((string) $syncError)."'>Failed</span>",
            'skipped' => "<span class='badge bg-warning/15 text-warning'>Skipped</span>",
            default => "<span class='badge bg-gray-200 text-dark dark:bg-darkgray dark:text-darklink'>Pending</span>",
        };

        $statusLabel = $data->post_status === 'publish'
            ? "<span class='text-success'>Published</span>"
            : "<span class='text-muted'>".e($data->post_status).'</span>';

        return [
            ['type' => 'string', 'data' => $data->ID],
            ['type' => 'string', 'data' => $data->post_title],
            ['type' => 'string', 'data' => $category],
            ['type' => 'raw_html', 'data' => $statusLabel],
            ['type' => 'raw_html', 'data' => $syncBadge],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
<div class='flex flex-wrap justify-center gap-1'>
<a href='{$linkEdit}' class='btn btn-primary'>Edit</a>
<button type='button' wire:click='deleteItem({$data->ID})' class='btn btn-error'>Delete</button>
</div>"],
        ];
    }
}
