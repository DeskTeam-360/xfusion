<?php

namespace App\Repository\View;

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
            return User::administratorTableFields();
        }

        return [
            ['label' => '#', 'sort' => 'ID', 'width' => '7%'],
            ['label' => 'Name', 'sort' => 'user_nicename'],
            ['label' => 'Company'],
            ['label' => 'Access'],
            ['label' => 'Role'],
            ['label' => 'Actions', 'class' => 'admin-table__col-actions'],
        ];
    }

    public static function tableData($data = null): array
    {
        if (self::authWpCapabilityFirst() === 'administrator') {
            return User::administratorTableRow($data);
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
}
