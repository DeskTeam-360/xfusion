<?php

namespace App\Repository\View;

use App\Models\Company;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserProgressOnly extends \App\Models\User implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'] ?? '';

        $scoped = static::query()->whereHas('meta', function ($q2) {
            $q2->where('meta_key', '=', '_sfwd-course_progress')
                ->whereNotNull('meta_value')
                ->where('meta_value', '!=', '');
        });

        if ($query === '' || $query === null) {
            return $scoped;
        }

        return $scoped->where(function ($qb) use ($query) {
            $qb->where('user_nicename', 'like', "%{$query}%")
                ->orWhere('user_email', 'like', "%{$query}%")
                ->orWhereHas('meta', function ($q2) use ($query) {
                    $q2->where('meta_value', 'like', "%{$query}%");
                });
        });
    }

    public static function tableView(): array
    {
        return ['searchable' => true];
    }

    public static function tableField(): array
    {
        if (self::authWpCapabilityFirst() === 'administrator') {
            return [
                ['label' => 'Profile', 'sort' => 'user_nicename'],
                ['label' => 'Status'],
                ['label' => 'Actions'],
            ];
        }

        return [
            ['label' => '#', 'sort' => 'ID', 'width' => '7%'],
            ['label' => 'Name', 'sort' => 'user_nicename'],
            ['label' => 'Company'],
            ['label' => 'Access'],
            ['label' => 'Role'],
            ['label' => 'Actions'],
        ];
    }

    public static function tableData($data = null): array
    {
        if (self::authWpCapabilityFirst() === 'administrator') {
            return self::buildAdministratorRow($data);
        }

        return User::tableData($data);
    }

    private static function authWpCapabilityFirst(): string
    {
        $rows = Auth::user()->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        foreach ($rows as $r) {
            $mv = $r['meta_value'] ?? '';
            $un = @unserialize(is_string($mv) ? $mv : '');
            if (is_array($un) && $un !== []) {
                return (string) array_key_first($un);
            }
        }

        return '';
    }

    private static function metaScalar(object $metaCollection, string $key): string
    {
        $row = $metaCollection->where('meta_key', '=', $key)->first();
        if ($row === null) {
            return '';
        }
        $v = $row->meta_value ?? '';
        if (is_array($v)) {
            $v = $v[0] ?? '';
        }

        return trim((string) $v);
    }

    private static function buildAdministratorRow($data): array
    {
        $fn = self::metaScalar($data->meta, 'first_name');
        $ln = self::metaScalar($data->meta, 'last_name');
        $fullName = trim("$fn $ln");
        $role = self::metaScalar($data->meta, 'user_role');

        $keaps = self::metaScalar($data->meta, 'keap_contact_id');
        $keapStatusRaw = self::metaScalar($data->meta, 'keap_status');
        $keapStatus = filter_var($keapStatusRaw, FILTER_VALIDATE_BOOLEAN) || $keapStatusRaw === '1';

        $keap = "<div class='text-nowrap text-xs text-danger' style='color: red'>Not connected to Keap</div>";
        if ($keaps !== '' && $keapStatus) {
            $keap = "<div class='text-nowrap text-xs text-success' style='color: green;'>Connected to Keap</div>";
        }

        $company = 'Non Company';
        foreach ($data->meta->where('meta_key', '=', 'company') as $r) {
            $c = Company::find($r['meta_value']);
            $company = $c !== null ? $c->title : 'Company has been delete';
        }

        $toolbar = User::actionToolbarHtml($data, route('user.edit', $data->ID));

        return [
            ['type' => 'raw_html', 'data' => '<div>
                <span class="text-xl">' . e($fullName) . '</span> <br>
                <font color=\'#ffd700\'>' . e($data->user_login) . '</font> <br>
                ' . e($data->user_email) . '
                </div>'],
            ['type' => 'raw_html', 'data' => '<div>
                <span class="text-xl">' . e($company) . '</span> <br>
                <span class="text-xs">' . e($role ?: '—') . '</span> <br>
                ' . $keap . '
                </div>'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $toolbar],
        ];
    }
}
