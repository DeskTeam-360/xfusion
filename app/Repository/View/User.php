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
                ['label' => 'Actions', 'text-align' => 'center', 'class' => 'admin-table__col-actions'],
            ];
        }

        $roleUser = self::authWpCapabilityFirst();
        if ($roleUser === 'administrator') {
            return self::administratorTableFields();
        }

        return [
            ['label' => '#', 'sort' => 'ID', 'width' => '7%'],
            ['label' => 'Name', 'sort' => 'user_nicename'],
            ['label' => 'Company'],
            ['label' => 'Access'],
            ['label' => 'Role'],
            ['label' => 'Actions', 'text-align' => 'center', 'class' => 'admin-table__col-actions'],
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
            return self::administratorTableRow($data);
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

    /** @return list<array<string, mixed>> */
    public static function administratorTableFields(): array
    {
        return [
            ['label' => '#', 'sort' => 'ID', 'width' => '4rem'],
            ['label' => 'Employee', 'sort' => 'user_nicename', 'width' => '16%'],
            ['label' => 'Email', 'sort' => 'user_email', 'width' => '18%'],
            ['label' => 'Company', 'width' => '14%'],
            ['label' => 'Role & Keap', 'width' => '16%'],
            ['label' => 'Actions', 'width' => '32%', 'class' => 'admin-table__col-actions'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function administratorTableRow($data): array
    {
        $fn = self::metaScalar($data->meta, 'first_name');
        $ln = self::metaScalar($data->meta, 'last_name');
        $fullName = trim("$fn $ln") ?: ($data->user_nicename ?? $data->user_login ?? '—');
        $role = self::metaScalar($data->meta, 'user_role');

        $keaps = self::metaScalar($data->meta, 'keap_contact_id');
        $keapStatusRaw = self::metaScalar($data->meta, 'keap_status');
        $keapStatus = filter_var($keapStatusRaw, FILTER_VALIDATE_BOOLEAN) || $keapStatusRaw === '1';

        $keapClass = 'text-error';
        $keapLabel = 'Not connected to Keap';
        if ($keaps !== '' && $keapStatus === true) {
            $keapClass = 'text-success';
            $keapLabel = 'Connected to Keap';
        }

        $company = 'Non Company';
        foreach ($data->meta->where('meta_key', '=', 'company') as $r) {
            $c = Company::find($r['meta_value']);
            $company = $c !== null ? $c->title : 'Company has been deleted';
        }

        $employeeHtml = '<div class="admin-table-user">'
            . '<div class="font-semibold leading-snug text-dark dark:text-white">' . e($fullName) . '</div>'
            . '<div class="text-xs text-warning">' . e($data->user_login ?? '') . '</div>'
            . '</div>';

        $statusHtml = '<div class="space-y-1 text-sm">'
            . '<div class="font-medium text-dark dark:text-white">' . e($role ?: '—') . '</div>'
            . '<div class="text-xs ' . $keapClass . '">' . e($keapLabel) . '</div>'
            . '</div>';

        $toolbar = self::actionToolbarHtml($data, route('user.edit', $data->ID));

        return [
            ['type' => 'string', 'data' => $data->ID],
            ['type' => 'raw_html', 'data' => $employeeHtml],
            ['type' => 'string', 'data' => $data->user_email ?? '—'],
            ['type' => 'string', 'data' => $company],
            ['type' => 'raw_html', 'data' => $statusHtml],
            ['type' => 'raw_html', 'class' => 'admin-table__cell-actions', 'data' => $toolbar],
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
            ['type' => 'raw_html', 'class' => 'admin-table__cell-actions', 'text-align' => 'center', 'data' => $toolbar],
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
            ['type' => 'raw_html', 'class' => 'admin-table__cell-actions', 'text-align' => 'center', 'data' => $toolbar],
        ];
    }

    /**
     * Full action toolbar
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
            $passwordButton = "<a href='#' onclick='showPassword(\"$passwordValJs\")' class='btn btn-light-info text-nowrap'>Show Password</a>";
            $exportPasswordLink = route('export-password-to-keap');
            $exportPasswordButton = "<a href='$exportPasswordLink' class='btn btn-light-warning text-nowrap'>Export Password to Keap</a>";
        }

        $button4 = '';
        if ($activity != null) {
            $link4 = route('user.course', [$data->ID]);
            $button4 = "<a href='$link4' class='btn btn-light-success text-nowrap'>Activity Check</a>";
        }

        $keapMailButton = '';
        if ($keaps !== '' && $keapStatus === true) {
            $keapMailLink = route('user.keap-mail-send', $keaps);
            $keapMailButton = "<a href='$keapMailLink' class='btn btn-light-warning text-nowrap'>Send Keap Mail</a>";
        }

        $linkReset = route('user.show', $data->ID);
        $linkDetail = route('user.detail', $data->ID);

        $editAttr = '';
        $editHrefSafe = htmlspecialchars($editHref, ENT_QUOTES, 'UTF-8');
        if ($editHref === '#' || $editHref === '') {
            $editAttr = " href='#' class='btn btn-primary pointer-events-none opacity-50'";
        }

                return "
                <div class='admin-table-actions'>
                    <a href='" . htmlspecialchars($routeAccess, ENT_QUOTES, 'UTF-8') . "' class='btn btn-light-secondary text-nowrap'>Access</a>
                    <a href='" . htmlspecialchars($linkDetail, ENT_QUOTES, 'UTF-8') . "' class='btn btn-light-info text-nowrap'>Detail</a>
                    <a "
            . ($editAttr !== '' ? $editAttr : "href='$editHrefSafe' class='btn btn-primary text-nowrap'")
            . ">Edit</a>
                    $button4
                    $keapMailButton
                    <a href='$linkReset' class='btn btn-secondary text-nowrap'>Reset Password</a>
                    <a href='#' wire:click='deleteItem($data->ID)' class='btn btn-error text-nowrap'>Delete</a>
                    $passwordButton
                    $exportPasswordButton
                </div>";
    }
}
