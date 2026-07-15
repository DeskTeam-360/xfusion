<?php

namespace App\Services;

use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\OneOnOne;

/**
 * 1-on-1 pairs are derived from Company Groups (leader → members).
 * No manual pairing in admin — sync runs when groups are saved and on API read.
 */
class OneOnOneCompanyGroupSyncService
{
    /**
     * Rebuild all active 1-on-1 pairs from every company group.
     * Deactivates pairs that no longer match any group membership.
     */
    public function syncAllFromCompanyGroups(): void
    {
        /** @var array<string, int> $expected leaderId:employeeId => company_id */
        $expected = [];

        $groups = CompanyGroup::query()->with('details')->get();

        foreach ($groups as $group) {
            $leaderDetail = $group->details->firstWhere('status', CompanyGroup::STATUS_LEADER);
            if ($leaderDetail === null) {
                continue;
            }

            $leaderUserId = (int) $leaderDetail->user_id;
            if ($leaderUserId < 1) {
                continue;
            }

            foreach ($group->details->where('status', CompanyGroup::STATUS_MEMBER) as $detail) {
                $employeeUserId = (int) $detail->user_id;
                if ($employeeUserId < 1 || $employeeUserId === $leaderUserId) {
                    continue;
                }

                $expected["{$leaderUserId}:{$employeeUserId}"] = (int) $group->company_id;
            }
        }

        foreach ($expected as $key => $companyId) {
            [$leaderUserId, $employeeUserId] = array_map('intval', explode(':', $key, 2));

            OneOnOne::query()->updateOrCreate(
                [
                    'leader_user_id' => $leaderUserId,
                    'employee_user_id' => $employeeUserId,
                ],
                [
                    'company_id' => $companyId,
                    'status' => OneOnOne::STATUS_ACTIVE,
                ]
            );
        }

        OneOnOne::query()
            ->where('status', OneOnOne::STATUS_ACTIVE)
            ->get()
            ->each(function (OneOnOne $pair) use ($expected): void {
                $key = "{$pair->leader_user_id}:{$pair->employee_user_id}";
                if (! isset($expected[$key])) {
                    $pair->update(['status' => OneOnOne::STATUS_INACTIVE]);
                }
            });
    }

    /**
     * @return list<int>
     */
    public function teamMemberUserIdsForLeader(int $leaderUserId): array
    {
        $groupIds = CompanyGroupDetail::query()
            ->where('user_id', $leaderUserId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->pluck('company_group_id');

        if ($groupIds->isEmpty()) {
            return [];
        }

        return CompanyGroupDetail::query()
            ->whereIn('company_group_id', $groupIds)
            ->where('status', CompanyGroup::STATUS_MEMBER)
            ->where('user_id', '!=', $leaderUserId)
            ->pluck('user_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function leaderUserIdsForEmployee(int $employeeUserId): array
    {
        $groupIds = CompanyGroupDetail::query()
            ->where('user_id', $employeeUserId)
            ->where('status', CompanyGroup::STATUS_MEMBER)
            ->pluck('company_group_id');

        if ($groupIds->isEmpty()) {
            return [];
        }

        return CompanyGroupDetail::query()
            ->whereIn('company_group_id', $groupIds)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->pluck('user_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }
}
