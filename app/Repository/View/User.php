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
            return empty($query) ? static::query()
                : static::query()->where('user_nicename', 'like', "%$query%")
                    ->orWhereHas('meta', function ($q2) use ($query) {
                        $q2->where('meta_value', 'like', "%$query%");
                    });
        } else {
            return empty($query) ? static::query()
                ->orWhereHas('companyEmployee', function ($q) use ($params) {
                    $q->where('company_id', '=', $params);
                })
                ->where('user_nicename', 'like', "%$query%") :
                static::query()
                    ->whereHas('companyEmployee', function ($q) use ($params) {
                        $q->where('company_id', '=', $params);
                    })
                    ->orWhere('user_nicename', 'like', "%$query%");
        }

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
            ['label' => '#', 'sort' => 'id', 'width' => '7%'],
            ['label' => 'Name', 'sort' => 'user_nicename'],
            ['label' => 'Company'],
            ['label' => 'Role', 'sort' => 'role'],
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


        $roles = $data->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';

        foreach ($roles as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
//        dd($data->ID);


        $companies = $data->meta->where('meta_key', '=', 'company');
        $company = '-';


//        $link = route('user.edit', $data->ID);
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
            ['type' => 'raw_html', 'data' =>
                "<div>$data->user_login <br><div style='font-size: 10px'>$data->email</div></div>"
            ],
            ['type' => 'string', 'data' => $company],
            ['type' => 'string', 'data' => $role],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
<div class='flex gap-1'>
<span><a href='$link' class='btn btn-primary'>Edit</a></span>
<span><a href='#' wire:click='deleteItem($data->ID)' class='btn btn-error text-nowrap'>Delete</a></span>
<span><a href='$link2' class='btn btn-secondary text-nowrap'>Reset Password</a></span>
<span><a href='$link3' class='btn btn-info text-nowrap'>Show Schedule</a></span>
</div>"],
        ];
    }
}
