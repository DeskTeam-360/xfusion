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
                    ['label' => 'Company'],
                    ['label' => 'Access',],
                ['label' => 'Role'],

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

        $roleEncryption=[
            'administrator' => 'Super Admin',
            'editor' => 'Company Admin',
            'contributor' => 'Employee Company',
            'subscriber' => 'Individual purchaser',
        ];


        $roles = $data->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
//        foreach ($roles as $r) {
//            $role = $roleEncryption[array_key_first(unserialize($r['meta_value']))];
//        }

        $route = route('user.connect-keap', $data->ID);
        $keap = "<a href='$route' class='p-1 rounded btn-error text-nowrap text-xs'>Not Connect</a>";
        $campaign = "";


        $keaps = $data->meta->where('meta_key', '=', 'keap_contact_id');
        foreach ($keaps as $r) {
            $route = route('user.tag-list', $data->ID);
            $keap = "<a href='$route' class='p-1 rounded btn btn-success text-nowrap text-xs'>List Tag</a>";
            $route = route('create_independent_user', $data->ID);
            $campaign = "<span><a href='$route' class='btn btn-secondary text-xs p-1 rounded text-nowrap'>Add Tag</a></span>";
        }

        $companies = $data->meta->where('meta_key', '=', 'company');
        $company = '-';

        $activity = $data->meta->where('meta_key', '=', '_sfwd-course_progress')->first();

        $button4 = '';
        if ($activity != null) {
            $link4 = route('user.course', [$data->ID]);
            $button4 = "<span><a href='$link4' class='btn btn-success text-nowrap'>Activity Check</a></span>";
        }
        $userAccess = "";
        $access = $data->meta->where('meta_key', '=', 'user_access')->first();
        if ($access != null) {
            $ua = json_decode($access->meta_value);
            $userAccess = "<ul style='list-style: disc; margin: 0 20px'>";
            foreach ($ua as $u) {
                $userAccess .= "<li>$u</li>";
            }
            $userAccess .= "</ul>";

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
            $link3 = route('schedule-user-administrator', [$data->ID]);

        } else {
            $link = route('company.edit-employee', [$companyId, $data->ID]);
            $link3 = route('company.schedule-user', [$companyId, $data->ID]);
        }

        return [
            ['type' => 'string', 'data' => $data->ID],
            ['type' => 'raw_html', 'data' => "<div>$data->user_login <br><div style='font-size: 10px'>$data->email</div></div>"],
            ['type' => 'string', 'data' => $company],
            ['type' => 'raw_html', 'data' => "<div style='text-wrap: nowrap; font-weight: bold'>$role</div><div style='text-wrap: nowrap'>$userAccess</div>"],
            ['type' => 'raw_html', 'data' => "<div class='flex gap-1'> $keap <br> $campaign</div>"],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
                <div class='flex gap-1'>
                    <span><a href='$link' class='btn btn-primary'>Edit</a></span>
                    $button4
                    <span><a href='$link2' class='btn btn-secondary text-nowrap'>Reset Password</a></span>
                    <span><a href='#' wire:click='deleteItem($data->ID)' class='btn btn-error text-nowrap'>Delete</a></span>
                </div>"
            ],
        ];
    }
}
