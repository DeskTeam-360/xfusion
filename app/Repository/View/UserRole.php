<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserRole extends \App\Models\UserRole implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query() : static::query()->where('title', 'like', "%$query%")->orWhere('accesses', 'like', "%$query%");
    }

    public static function tableView(): array
    {
        return ['searchable' => true,];
    }

    public static function tableField(): array
    {
        return [
            ['label' => '#', 'sort' => 'id', 'width' => '7%'],
            ['label' => 'Title', 'sort' => 'user_nicename'],
            ['label' => 'Access'],
            ['label' => 'Tag Starter'],
        ];
    }

    public static function tableData($data = null): array
    {
        $access = "";
        foreach (json_decode($data->accesses) as $a) {
            $access.="<li>$a</li>";
        }
        $tags = "";
        foreach (json_decode($data->tag_starter) as $a) {
            $title = \App\Models\Tag::find($a)->name;
            $tags.="<li>$title</li>";
        }

        return [
            ['type' => 'string', 'data' => $data->id],
            ['type' => 'string', 'data' => $data->title],
            ['type' => 'raw_html', 'data' => "<ul style='list-style: disc'>$access</ul>"],
            ['type' => 'raw_html', 'data' => "<ul style='list-style: disc'>$tags</ul>"],
        ];
    }
}
