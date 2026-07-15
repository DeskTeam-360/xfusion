<?php

namespace App\Services;

use App\Models\CompanyGroup;
use App\Models\CompanyGroupDetail;
use App\Models\OneOnOne;
use App\Models\OneOnOneConversation;
use App\Models\User;

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

    public function findGroupForPair(int $leaderUserId, int $employeeUserId): ?CompanyGroup
    {
        $groupIds = CompanyGroupDetail::query()
            ->where('user_id', $leaderUserId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->pluck('company_group_id');

        if ($groupIds->isEmpty()) {
            return null;
        }

        $matchId = CompanyGroupDetail::query()
            ->whereIn('company_group_id', $groupIds)
            ->where('user_id', $employeeUserId)
            ->where('status', CompanyGroup::STATUS_MEMBER)
            ->value('company_group_id');

        return $matchId ? CompanyGroup::query()->find((int) $matchId) : null;
    }

    public function isMemberOfLeaderGroup(int $leaderUserId, int $employeeUserId, int $groupId): bool
    {
        $leaderInGroup = CompanyGroupDetail::query()
            ->where('company_group_id', $groupId)
            ->where('user_id', $leaderUserId)
            ->where('status', CompanyGroup::STATUS_LEADER)
            ->exists();

        if (! $leaderInGroup) {
            return false;
        }

        return CompanyGroupDetail::query()
            ->where('company_group_id', $groupId)
            ->where('user_id', $employeeUserId)
            ->where('status', CompanyGroup::STATUS_MEMBER)
            ->exists();
    }

    /**
     * Company groups the user belongs to, with role and pairing context.
     *
     * @return list<array<string, mixed>>
     */
    public function groupsForUser(int $userId): array
    {
        $memberships = CompanyGroupDetail::query()
            ->where('user_id', $userId)
            ->with(['companyGroup.company', 'companyGroup.details'])
            ->get();

        $groups = [];

        foreach ($memberships as $membership) {
            $group = $membership->companyGroup;
            if ($group === null) {
                continue;
            }

            $role = $membership->status === CompanyGroup::STATUS_LEADER ? 'leader' : 'member';
            $leaderDetail = $group->details->firstWhere('status', CompanyGroup::STATUS_LEADER);
            $leaderUserId = $leaderDetail ? (int) $leaderDetail->user_id : 0;

            $leaderPayload = null;
            if ($leaderUserId > 0) {
                $leaderUser = User::query()->find($leaderUserId, ['ID', 'display_name', 'user_nicename']);
                if ($leaderUser !== null) {
                    $leaderPayload = [
                        'id' => (int) $leaderUser->ID,
                        'name' => $leaderUser->display_name ?: $leaderUser->user_nicename,
                    ];
                }
            }

            $entry = [
                'id' => (int) $group->id,
                'title' => (string) $group->title,
                'company' => (string) ($group->company?->title ?? ''),
                'role' => $role,
                'leader' => $leaderPayload,
                'members' => [],
                'pair_id' => null,
                'counterpart' => null,
            ];

            if ($role === 'leader') {
                $memberIds = $group->details
                    ->where('status', CompanyGroup::STATUS_MEMBER)
                    ->pluck('user_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0 && $id !== $userId)
                    ->values()
                    ->all();

                $employees = $memberIds === []
                    ? collect()
                    : User::query()->whereIn('ID', $memberIds)->orderBy('display_name')->get(['ID', 'display_name', 'user_nicename']);

                $pairs = $memberIds === []
                    ? collect()
                    : OneOnOne::query()
                        ->where('leader_user_id', $userId)
                        ->whereIn('employee_user_id', $memberIds)
                        ->where('status', OneOnOne::STATUS_ACTIVE)
                        ->get(['id', 'employee_user_id'])
                        ->keyBy('employee_user_id');

                foreach ($employees as $employee) {
                    $entry['members'][] = [
                        'employee' => [
                            'id' => (int) $employee->ID,
                            'name' => $employee->display_name ?: $employee->user_nicename,
                        ],
                        'pair_id' => isset($pairs[$employee->ID]) ? (int) $pairs[$employee->ID]->id : null,
                    ];
                }
            } elseif ($leaderUserId > 0) {
                $pair = OneOnOne::query()
                    ->where('leader_user_id', $leaderUserId)
                    ->where('employee_user_id', $userId)
                    ->where('status', OneOnOne::STATUS_ACTIVE)
                    ->first(['id']);

                $entry['pair_id'] = $pair ? (int) $pair->id : null;
                $entry['counterpart'] = $leaderPayload;
            }

            $groups[] = $entry;
        }

        usort($groups, static fn (array $a, array $b) => strcasecmp((string) $a['title'], (string) $b['title']));

        return $groups;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function meetingsForUser(int $userId): array
    {
        $pairs = OneOnOne::query()
            ->where('status', OneOnOne::STATUS_ACTIVE)
            ->where(function ($q) use ($userId) {
                $q->where('leader_user_id', $userId)->orWhere('employee_user_id', $userId);
            })
            ->with(['leader:ID,display_name,user_nicename', 'employee:ID,display_name,user_nicename'])
            ->get();

        if ($pairs->isEmpty()) {
            return [];
        }

        $pairIds = $pairs->pluck('id')->all();
        $conversations = OneOnOneConversation::query()
            ->whereIn('one_on_one_id', $pairIds)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->get(['id', 'one_on_one_id', 'scheduled_at', 'meeting_link', 'status']);

        $pairMap = $pairs->keyBy('id');
        $meetings = [];

        foreach ($conversations as $conversation) {
            $pair = $pairMap->get($conversation->one_on_one_id);
            if ($pair === null) {
                continue;
            }

            $role = $pair->leader_user_id === $userId ? 'leader' : 'employee';
            $leaderName = $pair->leader ? ($pair->leader->display_name ?: $pair->leader->user_nicename) : '—';
            $employeeName = $pair->employee ? ($pair->employee->display_name ?: $pair->employee->user_nicename) : '—';
            $counterpartName = $role === 'leader' ? $employeeName : $leaderName;
            $group = $this->findGroupForPair((int) $pair->leader_user_id, (int) $pair->employee_user_id);

            $meetings[] = [
                'id' => (int) $conversation->id,
                'pair_id' => (int) $pair->id,
                'scheduled_at' => $conversation->scheduled_at,
                'meeting_link' => $conversation->meeting_link,
                'status' => (string) $conversation->status,
                'user_role' => $role,
                'leader' => $pair->leader ? ['id' => (int) $pair->leader->ID, 'name' => $leaderName] : null,
                'employee' => $pair->employee ? ['id' => (int) $pair->employee->ID, 'name' => $employeeName] : null,
                'counterpart_name' => $counterpartName,
                'group' => $group ? [
                    'id' => (int) $group->id,
                    'title' => (string) $group->title,
                ] : null,
            ];
        }

        return $meetings;
    }
}
