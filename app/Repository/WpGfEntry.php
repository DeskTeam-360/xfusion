<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class WpGfEntry extends \App\Models\WpGfEntry implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        $params = $params['param1'];

        return empty($query) ? static::query()
            ->whereHas('wpGfForm', function ($q2) use ($query) {
                $q2->where('title', 'like', "%$query%")->whereIn('id', [2, 3, 4, 7, 8, 9, 10, 13, 11, 14, 15, 16, 17, 18, 19]);
            })->whereHas('user', function ($q2) use ($params) {
                $q2->where('ID', '=', $params);
            })
            : static::query()
                ->whereHas('wpGfForm', function ($q2) use ($query) {
                    $q2->where('title', 'like', "%$query%")->whereIn('id', [2, 3, 4, 7, 8, 9, 10, 13, 11, 14, 15, 16, 17, 18, 19]);
                })->whereHas('user', function ($q2) use ($params) {
                    $q2->where('ID', '=', $params);
                })->where(function ($q) use ($query) {
                    $q->whereHas('user', function ($q2) use ($query) {
                        $q2->where('user_nicename', 'like', "%$query%");
                    });
                })->orderByDesc('id');

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
            ['label' => 'Attempt', 'sort' => 'date_created'],
            ['label' => 'Page title'],
            ['label' => 'Name',],
//            ['label' => 'Company'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $roles = Auth::user()->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $roleUser = '';

        foreach ($roles as $r) {
            $roleUser = array_key_first(unserialize($r['meta_value']));
        }


//        $roles = $data->user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
//        $role = '';

//        foreach ($roles as $r) {
//            $role = array_key_first(unserialize($r['meta_value']));
//        }


//        $companies = $data->user->meta->where('meta_key', '=', 'company');
//        $company = '-';


//        $link = route('user.edit', $data->ID);
//        $link2 = route('user.show', $data->ID);


//        $companyId = null;
//        foreach ($companies as $r) {
//            $c = \App\Models\Company::find($r['meta_value']);
//            if ($c != null) {
//                $companyId = $c->id;
//                $company = $c->title;
//            } else {
//                $company = 'Company has been delete';
//            }
//        }

//
//        if ($roleUser == "administrator") {
//            $link = route('user.edit', $data->ID);
//            $link3 = route('schedule-user-administrator', [$data->ID]);
//        } else {
//            $link = route('company.edit-employee', [$companyId, $data->ID]);
//            $link3 = route('company.schedule-user', [$companyId, $data->ID]);
//        }
        $user = $data->user;
        $userLogin = '';
        $userEmail = '';
        if ($user != null) {
            $userLogin = $user->user_login;
            $userEmail = $user->email;
        }


//        const url = data[dataKey][contentKey]['source_url']
//                            const urlObj = new URL(url);
//                            urlObj.search = '';
//                            urlObj.hash = '';
//                            content+=`<td><a href="${urlObj.toString()}/?dataId=${data[dataKey][contentKey]['id']}" target="_blank">Link</a></td>`

//        $url="broken link";
//        if (parse_url($data->source_url)['host']=="teamsetup-2.deskteam360.com"){
        $url = 'https://' . parse_url($data->source_url)['host'] . parse_url($data->source_url)['path'] . '/?dataId=' . $data->id;
        $url = "<a href='$url' target='_blank' class='btn bg-blue-300'>Look result</a>";
//        }

        $form = $data->wpGfForm->title;


        return [
            ['type' => 'string', 'data' => $data->date_created],
            ['type' => 'string', 'data' => $form],
            ['type' => 'raw_html', 'data' =>
                "<div>$userLogin <br><div style='font-size: 10px'>$userEmail</div></div>"
            ],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
            <div class='flex gap-1'>
$url
</div>
            "],
        ];
    }
}
