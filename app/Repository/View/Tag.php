<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Tag extends \App\Models\Tag implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query()
            : static::query()->where('title', 'like', "%$query%")
                ->orWhere('description', 'like', "%$query%");
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
            ['label' => '#', 'sort' => 'id'],
            ['label' => 'Name'],
            ['label' => 'Description'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $link = route('tag.edit',$data->id);

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $data->name],
            ['type' => 'string', 'data' => $data->description],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
            <div class='flex gap-1 m-3'>
<span><a href='$link' class='btn btn-primary'>Edit</a></span>
</div>
            "],
        ];
    }
}
