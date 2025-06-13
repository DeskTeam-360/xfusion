<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class User extends \App\Models\User implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        $params = $params['param1'];
        if ($params == null) {
            return empty($query) ? static::query() : static::query()->where('user_nicename', 'like', "%$query%")->orWhereHas('meta', function ($q2) use ($query) {
                $q2->where('meta_value', 'like', "%$query%");
            });
        } else {
            return empty($query) ? static::
            query()->whereHas('companyEmployee', function ($q) use ($params) {
                $q->where('company_id', '=', $params);
            })->where('user_nicename', 'like', "%$query%") :

                static::query()->whereHas('companyEmployee', function ($q) use ($params) {
                $q->where('company_id', '=', $params);
            })->where('user_nicename', 'like', "%$query%");
        }

    }

    public static function tableView(): array
    {
        return ['searchable' => true,];
    }

    public static function tableField(): array
    {
        $roles = Auth::user()->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $roleUser = '';

        foreach ($roles as $r) {
            $roleUser = array_key_first(unserialize($r['meta_value']));
        }
        if ($roleUser == "administrator") {
            return [
                ['label' => '#', 'sort' => 'id', 'width' => '7%'],
                ['label' => 'Name', 'sort' => 'user_nicename'],
                ['label' => 'Keap'],
                ['label' => 'Role'],
                ['label' => 'Access',],
                ['label' => 'Action'],
                ];
        } else {
            return [['label' => '#', 'sort' => 'id', 'width' => '7%'], ['label' => 'Name', 'sort' => 'user_nicename'], ['label' => 'Company'], ['label' => 'Access'], ['label' => 'Role'], ['label' => 'Action'],];
        }

    }

    public static function tableData($data = null): array
    {
        $roles = Auth::user()->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $roleUser = '';

        foreach ($roles as $r) {
            $roleUser = array_key_first(unserialize($r['meta_value']));
        }

        $role = $data->meta->where('meta_key', '=', 'user_role')->first()->meta_value??'';

        $route = "";
//        $route = route('user.connect-keap', $data->ID);
        $keap = "<div href='$route' class='p-1 rounded btn-error text-nowrap text-xs'>Not connect with keap</div>";
        $routeAccess = route('user.tag-list', $data->ID);

        $keaps = $data->meta->where('meta_key', '=', 'keap_contact_id')->first()->meta_value??'';
        if ($keaps){
            $keap = "<div class='p-1 rounded btn btn-primary text-nowrap text-xs'>Connect with keap</div>";
        }


        $companies = $data->meta->where('meta_key', '=', 'company');
        $company = 'Non Company';

        $activity = $data->meta->where('meta_key', '=', '_sfwd-course_progress')->first();

        $button4 = '';
        if ($activity != null) {
            $link4 = route('user.course', [$data->ID]);
            $button4 = "<span><a href='$link4' class='btn btn-success text-nowrap'>Activity Check</a></span>";
        }

        $link2 = route('user.show', $data->ID);

        $companyId = null;
        foreach ($companies as $r) {
            $c = \App\Models\Company::find($r['meta_value']);
            if ($c != null) {
                $companyId = $c->id;
                $company = $c->title;
            } else {
                $company = 'Company has been delete';
            }
        }

        if ($roleUser == "administrator") {
            $link = route('user.edit', $data->ID);

        } else {
            $link = route('company.edit-employee', [$companyId, $data->ID]);
        }

//        <div style='text-wrap: nowrap'>$userAccess</div>
        return [
            ['type' => 'string', 'data' => $data->ID],
            ['type' => 'raw_html', 'data' => "<div>$data->user_login <br><div style='font-size: 10px'>$data->email <br>$company</div></div>"],
            ['type' => 'raw_html', 'data' => "<div style=' font-weight: bold'>$keap</div>"],
            ['type' => 'raw_html', 'data' => "<div style=' font-weight: bold'>$role</div>"],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
                <div class='flex gap-1'>
                    <span><a href='$routeAccess' class='btn btn-success'>Access</a></span>
                    <span><a href='$link' class='btn btn-primary'>Edit</a></span>
                    $button4
                    <span><a href='$link2' class='btn btn-secondary text-nowrap'>Reset Password</a></span>
                    <span><a href='#' wire:click='deleteItem($data->ID)' class='btn btn-error text-nowrap'>Delete</a></span>
                </div>"
            ],
        ];
    }
}
