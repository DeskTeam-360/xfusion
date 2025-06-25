<?php

namespace App\Repository\View;

use App\Repository\View;
use Corcel\Model\Attachment;
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
//                ['label' => '#', 'sort' => 'id', 'width' => '7%'],
                ['label' => 'Profile', 'sort' => 'user_nicename'],
//                ['label' => 'Keap'],
                ['label' => 'Status'],
//                ['label' => 'Access',],
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

        $fn = $data->meta->where('meta_key', '=', 'first_name')->first()->meta_value??'';
        $ln = $data->meta->where('meta_key', '=', 'last_name')->first()->meta_value??'';

        $fullName = "$fn $ln";
        $role = $data->meta->where('meta_key', '=', 'user_role')->first()->meta_value??'';

        $routeAccess = route('user.tag-list', $data->ID);

        $keaps = $data->meta->where('meta_key', '=', 'keap_contact_id')->first()->meta_value??'';
        $keapStatus = $data->meta->where('meta_key', '=', 'keap_status')->first()->meta_value??'';

        $keap = "<div class='text-nowrap text-xs text-danger' style='color: red'>Not connect with keap</div>";

        if ($keaps and $keapStatus == true) {
            $keap = "<div class='text-nowrap text-xs text-success' style='color: green;'>Connect with keap</div>";
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

        return [
            ['type' => 'raw_html', 'data' => "<div>
                <span class='text-xl'>$fullName</span> <br>
                <font color='#ffd700'>$data->user_login</font> <br>
                $data->user_email
                </div>"],
            ['type' => 'raw_html', 'data' => "<div>
                <span class='text-xl'>$company</span> <br>
                <span class='text-xs'>$role</span> <br>
                $keap

                </div>"],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => "
                <div class='flex gap-1'>
                    <span><a href='$routeAccess' class='btn'>Access</a></span>
                    <span><a href='$link' class='btn btn-primary'>Edit</a></span>
                    $button4
                    <span><a href='$link2' class='btn btn-secondary text-nowrap'>Reset Password</a></span>
                    <span><a href='#' wire:click='deleteItem($data->ID)' class='btn btn-error text-nowrap'>Delete</a></span>
                </div>"
            ],
        ];
    }
}
