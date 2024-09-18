<?php

namespace App\Repository\View;

use App\Models\WpUserMeta;
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
            ['label' => 'User have', 'text-align'=>'center'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $link = route('tag.show',$data->id);
        $c = WpUserMeta::where('meta_key','keap_tags')->where('meta_value','like',"%$data->id%")->get()->count();

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $data->name],
            ['type' => 'string', 'data' => $data->description],
            ['type' => 'string', 'text-align'=>'center','data' => $c],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
            <div class='flex gap-1'><span><a href='$link' class='btn btn-primary'>Show</a></span></div>
            "],
        ];
    }
}
