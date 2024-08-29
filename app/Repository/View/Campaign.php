<?php

namespace App\Repository\View;

use App\Models\Tag;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use KeapGeek\Keap\Facades\Keap;

class Campaign extends \App\Models\Campaign implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query() : static::query()->where('title', 'like', "%$query%");
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
//            ['label' => '#', 'sort' => 'id', 'width' => '7%'],
            ['label' => 'Users'],
            ['label' => 'Tags'],
            ['label' => 'Status', 'text-align'=>'center'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $exp_users = explode(", ", $data->users);
        $user_names = [];
        foreach ($exp_users as $user) {
            try {
                User::find($user)->user_nicename;
                $user_names[] = User::find($user)->user_nicename;
            } catch (\Exception) {

            }
        }
        $listItems = '';
        foreach ($user_names as $userName) {
            $listItems .= '<li> - ' . htmlspecialchars($userName) . '</li>';
        }

        $exp_tags = explode(", ", $data->tags);
        $tag_names = [];
        foreach ($exp_tags as $tag) {
//            $tag_names[] = Tag::find($tag)->title;
            $tag_names[] = Keap::tag()->find($tag)['name'];
        }
        $listItems2 = '';
        foreach ($tag_names as $tag) {
            $listItems2 .= '<li> - ' . htmlspecialchars($tag) . '</li>';
        }

        $link = route('campaign.edit',$data->id);
        $result = [
            ['type' => 'raw_html', 'data' =>  '<ul>' . $listItems . '</ul>',],
            ['type' => 'raw_html', 'data' =>  '<ul>' . $listItems2 . '</ul>',],
            ['type' => 'string', 'data' => $data->status],
        ];

        if ($data->status == 'send') {
            $result[] = [
                'type' => 'raw_html',
                'text-align' => 'center',
                'data' => "<div style='color: #888; padding: 5px; border-radius: 4px; text-align: left; font-size: 16px; pointer-events: none; opacity: 0.6; '>
                                Delivered
                            </div>"
            ];
        } else {
            $result[] = [
                'type' => 'raw_html',
                'text-align' => 'center',
                'data' => "
                        <div class='flex gap-1'>
                            <span><a href='$link' class='btn btn-primary'>Edit</a></span>
                            <span><a href='#' wire:click='deleteItem($data->id)' class='btn btn-error'>Delete</a></span>
                        </div>"
            ];
        }

        return $result;

    }
}
