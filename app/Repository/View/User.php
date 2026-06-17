<?php

namespace App\Repository\View;

use App\Models\Company;
use App\Repository\View;
use App\Support\CompanyAdmin;
use App\Support\UserAccessCoder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use KeapGeek\Keap\Facades\Keap;

class User extends \App\Models\User implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'] ?? '';
        $companyId = $params['param1'] ?? null;

        if ($companyId === null || $companyId === '') {
            return $query === '' || $query === null
                ? static::query()
                : static::query()->where('user_nicename', 'like', "%{$query}%")->orWhereHas('meta', function ($q2) use ($query) {
                    $q2->where('meta_value', 'like', "%{$query}%");
                });
        }

        $scoped = static::query()->with('meta')->whereHas('companyEmployee', function ($q) use ($companyId) {
            $q->where('company_id', '=', $companyId);
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
        return ['searchable' => true,];
    }

    public static function tableField(): array
    {
        if (CompanyAdmin::isCompanyAdminPortalUser(Auth::user())) {
            return [
                ['label' => '#', 'sort' => 'ID', 'width' => '3.25rem'],
                ['label' => 'Employee'],
                ['label' => 'Email', 'sort' => 'user_email'],
                ['label' => 'Role'],
                ['label' => 'Access'],
                ['label' => 'Actions', 'text-align' => 'center'],
            ];
        }

        $roleUser = self::authWpCapabilityFirst();
        if ($roleUser === 'administrator') {
            return [
                ['label' => 'Profile', 'sort' => 'user_nicename'],
                ['label' => 'Status'],
                ['label' => 'Action'],
            ];
        }

        return [
            ['label' => '#', 'sort' => 'ID', 'width' => '7%'],
            ['label' => 'Name', 'sort' => 'user_nicename'],
            ['label' => 'Company'],
            ['label' => 'Access'],
            ['label' => 'Role'],
            ['label' => 'Actions', 'text-align' => 'center'],
        ];
    }

    public function keapMailSend($contactId)
    {
        Keap::contact()->tag($contactId, [1942]);
    }

    /** @phpstan-return list<array<string, mixed>> */
    public static function tableData($data = null): array
    {
        $roleUser = self::authWpCapabilityFirst();

        if ($roleUser === 'administrator') {
            return self::buildAdministratorRow($data);
        }

        if (CompanyAdmin::isCompanyAdminPortalUser(Auth::user())) {
            return self::buildCompanyPortalRow($data);
        }

        return self::buildEditorRow($data);
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
        $v = $row->meta_value ?? (($row instanceof \Illuminate\Database\Eloquent\Model) ? $row->getAttribute('meta_value') : '');
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

        $keap = "<div class='text-nowrap text-xs text-danger' style='color: red'>Not connect with keap</div>";

        if ($keaps !== '' && $keapStatus === true) {
            $keap = "<div class='text-nowrap text-xs text-success' style='color: green;'>Connect with keap</div>";
        }

        $companies = $data->meta->where('meta_key', '=', 'company');
        $company = 'Non Company';
        foreach ($companies as $r) {
            $c = Company::find($r['meta_value']);
            if ($c != null) {
                $company = $c->title;
            } else {
                $company = 'Company has been delete';
            }
        }

        $toolbar = self::actionToolbarHtml($data, route('user.edit', $data->ID));

        return [
            ['type' => 'raw_html', 'data' => "<div>
                <span class='text-xl'>" . e($fullName) . '</span> <br>
                <font color=\'#ffd700\'>' . e($data->user_login) . '</font> <br>
                ' . e($data->user_email) . "
                </div>"],
            ['type' => 'raw_html', 'data' => "<div>
                <span class='text-xl'>" . e($company) . '</span> <br>
                <span class="text-xs">' . e($role ?: '—') . '</span> <br>
                ' . $keap . '
                </div>'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $toolbar],
        ];
    }

    private static function buildCompanyPortalRow($data): array
    {
        $fn = self::metaScalar($data->meta, 'first_name');
        $ln = self::metaScalar($data->meta, 'last_name');
        $fullName = trim("$fn $ln") ?: ($data->user_nicename ?? $data->user_login ?? '');

        $role = self::metaScalar($data->meta, 'user_role');

        $portalCid = CompanyAdmin::portalCompanyMetaId(Auth::user()) ?? '';
        $linkEdit = $portalCid !== '' ? route('company.edit-employee', [$portalCid, $data->ID]) : '#';

        $accessHtml = UserAccessCoder::badgesHtmlFromUser($data, 10);

        $toolbar = self::actionToolbarHtml($data, $linkEdit);

        return [
            ['type' => 'index'],
            ['type' => 'raw_html', 'data' => '<div><span class="font-semibold">' . e($fullName) . '</span><br>'
                . '<span class="text-xs text-muted">' . e($data->user_login ?? '') . '</span></div>'],
            ['type' => 'string', 'data' => $data->user_email ?? ''],
            ['type' => 'string', 'data' => $role !== '' ? $role : '—'],
            ['type' => 'raw_html', 'data' => $accessHtml],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $toolbar],
        ];
    }

    private static function buildEditorRow($data): array
    {
        $fn = self::metaScalar($data->meta, 'first_name');
        $ln = self::metaScalar($data->meta, 'last_name');
        $fullName = trim("$fn $ln") ?: ($data->user_nicename ?? '');
        $role = self::metaScalar($data->meta, 'user_role');

        $companies = $data->meta->where('meta_key', '=', 'company');
        $company = 'Non Company';
        $companyId = null;

        foreach ($companies as $r) {
            $c = Company::find($r['meta_value']);
            if ($c != null) {
                $companyId = $c->id;
                $company = $c->title;
            } else {
                $company = 'Company has been delete';
            }
        }

        $linkEdit = route('company.edit-employee', [$companyId ?? 0, $data->ID]);

        $accessHtml = UserAccessCoder::badgesHtmlFromUser($data, 8);

        $toolbar = self::actionToolbarHtml($data, $linkEdit);

        return [
            ['type' => 'index'],
            ['type' => 'string', 'data' => $fullName ?: '—'],
            ['type' => 'string', 'data' => $company],
            ['type' => 'raw_html', 'data' => $accessHtml],
            ['type' => 'string', 'data' => $role !== '' ? $role : '—'],
            ['type' => 'raw_html', 'text-align' => 'center', 'data' => $toolbar],
        ];
    }

    /**
     * Full action toolbar (same as legacy admin Users table): Access, Edit (custom URL), Activity, Keap mail, Reset password, Delete, passwords.
     */
    public static function actionToolbarHtml($data, string $editHref): string
    {
        $routeAccess = route('user.tag-list', $data->ID);

        $keaps = self::metaScalar($data->meta, 'keap_contact_id');
        $keapStatusRaw = self::metaScalar($data->meta, 'keap_status');
        $keapStatus = filter_var($keapStatusRaw, FILTER_VALIDATE_BOOLEAN) || $keapStatusRaw === '1';

        $activity = $data->meta->where('meta_key', '=', '_sfwd-course_progress')->first();

        $password = $data->meta->where('meta_key', '=', 'plain_password')->first();
        $passwordButton = '';
        $exportPasswordButton = '';
        if ($password != null) {
            $passwordVal = is_object($password) ? ($password->meta_value ?? '') : ($password['meta_value'] ?? '');
            $passwordValJs = htmlspecialchars((string) $passwordVal, ENT_QUOTES, 'UTF-8');
            $passwordButton = "<span><a href='#' onclick='showPassword(\"$passwordValJs\")' class='btn btn-light-info text-nowrap'>Show Password</a></span>";
            $exportPasswordLink = route('export-password-to-keap');
            $exportPasswordButton = "<span><a href='$exportPasswordLink' class='btn btn-light-warning text-nowrap'>Export Password to Keap</a></span>";
        }

        $button4 = '';
        if ($activity != null) {
            $link4 = route('user.course', [$data->ID]);
            $button4 = "<span><a href='$link4' class='btn btn-light-success text-nowrap'>Activity Check</a></span>";
        }

        $keapMailButton = '';
        if ($keaps !== '' && $keapStatus === true) {
            $keapMailLink = route('user.keap-mail-send', $keaps);
            $keapMailButton = "<span><a href='$keapMailLink' class='btn btn-light-warning text-nowrap'>Send Keap Mail</a></span>";
        }

        $linkReset = route('user.show', $data->ID);
        $linkDetail = route('user.detail', $data->ID);

        $editAttr = '';
        $editHrefSafe = htmlspecialchars($editHref, ENT_QUOTES, 'UTF-8');
        if ($editHref === '#' || $editHref === '') {
            $editAttr = " href='#' class='btn btn-primary pointer-events-none opacity-50'";
        }

        return "
                <div class='flex flex-wrap gap-2 justify-start'>
                    <span><a href='" . htmlspecialchars($routeAccess, ENT_QUOTES, 'UTF-8') . "' class='btn btn-light-secondary text-nowrap'>Access</a></span>
                    <span><a href='" . htmlspecialchars($linkDetail, ENT_QUOTES, 'UTF-8') . "' class='btn btn-light-info text-nowrap'>Detail</a></span>
                    <span><a "
            . ($editAttr !== '' ? $editAttr : "href='$editHrefSafe' class='btn btn-primary text-nowrap'")
            . ">Edit</a></span>
                    $button4
                    $keapMailButton
                    <span><a href='$linkReset' class='btn btn-secondary text-nowrap'>Reset Password</a></span>
                    <span><a href='#' wire:click='deleteItem($data->ID)' class='btn btn-error text-nowrap'>Delete</a></span>
                    $passwordButton
                    $exportPasswordButton
                </div>";
    }
}
