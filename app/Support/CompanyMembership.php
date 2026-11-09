<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use Illuminate\Support\Collection;

/**
 * Which companies a user is "inside" — the basis for who may SEE an
 * ARP / QBR / ARR list. Seeing is broader than editing: editing stays with
 * the owner (company leader for ARP and ARR, group leader for QBR).
 *
 * A user is inside a company when they are its owner (companies.user_id),
 * one of its employees (company_employees), or a member of any of its groups.
 */
class CompanyMembership
{
    /** @return Collection<int, int> */
    public static function companyIdsFor(int $userId): Collection
    {
        if ($userId < 1) {
            return collect();
        }

        $owned = Company::query()->where('user_id', $userId)->pluck('id');

        $employee = CompanyEmployee::query()->where('user_id', $userId)->pluck('company_id');

        $viaGroups = CompanyGroup::query()
            ->whereIn('id', CompanyGroupDetail::query()->where('user_id', $userId)->pluck('company_group_id'))
            ->pluck('company_id');

        return $owned->merge($employee)->merge($viaGroups)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
