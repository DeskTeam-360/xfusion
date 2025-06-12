<?php

namespace App\Repository\View;

use App\Models\Tag;
use App\Models\WpUserMeta;
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
            'searchable' => false,
        ];
    }

    public static function tableField(): array
    {
        return [
//            ['label' => '#', 'sort' => 'id', 'width' => '7%'],
            ['label' => 'Users'],
            ['label' => 'Tags'],
            ['label' => 'Status'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $exp_users = explode(";", $data->users);
        $user_names = [];
        foreach ($exp_users as $user) {
            try {
                $userId=WpUserMeta::where('meta_key','keap_contact_id')->where('meta_value',$user)->first()->user_id;
                $fname = WpUserMeta::where('user_id',$userId)->where('meta_key','first_name')->first()->meta_value??'';
                $lname = WpUserMeta::where('user_id',$userId)->where('meta_key','last_name')->first()->meta_value??'';
                $user_names[] = $fname.' '.$lname;
            } catch (\Exception) {
                $user_names[] = 'Non Keap user';
            }
        }
        $listItems = '';
        foreach ($user_names as $userName) {
            $listItems .= '<li> - ' . htmlspecialchars($userName) . '</li>';
        }

        $exp_tags = explode(";", $data->tags);
        $tag_names = [];
        foreach ($exp_tags as $tag) {
            try {
                $tag_names[] = Keap::tag()->find($tag)['name'];
            }catch (\Exception $e){
                $tag_names[] = "Tag ".$tag." has been deleted";
            }
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
