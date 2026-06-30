<?php

namespace App\Livewire\Form;

use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\CompanyGroup as CompanyGroupModel;
use App\Models\CompanyGroupDetail;
use App\Models\User;
use Livewire\Component;

class CompanyGroup extends Component
{
    public ?string $dataId = null;

    public ?int $companyId = null;

    public string $title = '';

    public ?string $description = null;

    /**
     * @var list<array{detail_id: int|null, user_id: int, status: string, label: string}>
     */
    public array $members = [];

    /** @var list<array{id: int, label: string, email: string}> */
    public array $userSearchResults = [];

    public function mount(?string $dataId = null): void
    {
        $this->dataId = $dataId !== null && $dataId !== '' ? $dataId : null;

        if ($this->dataId !== null) {
            $group = CompanyGroupModel::with('details.user')->findOrFail((int) $this->dataId);
            $this->companyId = (int) $group->company_id;
            $this->title = (string) $group->title;
            $this->description = $group->description;
            $this->hydrateMembersFromGroup($group);
        }
    }

    private function hydrateMembersFromGroup(CompanyGroupModel $group): void
    {
        $this->members = [];

        foreach ($group->details as $detail) {
            $this->members[] = [
                'detail_id' => (int) $detail->id,
                'user_id' => (int) $detail->user_id,
                'status' => (string) $detail->status,
                'label' => $this->userLabel($detail->user),
            ];
        }
    }

    private function userLabel(?User $user): string
    {
        if ($user === null) {
            return 'Unknown user';
        }

        $name = trim((string) ($user->display_name ?: $user->user_nicename ?: ''));
        $email = trim((string) ($user->user_email ?? ''));

        if ($name !== '' && $email !== '') {
            return "{$name} ({$email})";
        }

        return $name !== '' ? $name : ($email !== '' ? $email : "User #{$user->id}");
    }

    /** @return list<array{id: int, title: string}> */
    public function companyOptions(): array
    {
        return Company::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(static fn ($c) => ['id' => (int) $c->id, 'title' => (string) $c->title])
            ->all();
    }

    public function searchCompanyUsers(string $query): void
    {
        $this->userSearchResults = [];

        if ($this->companyId === null || (int) $this->companyId < 1) {
            return;
        }

        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return;
        }

        $needle = addcslashes($query, '%_\\');
        $existingIds = array_fill_keys(array_column($this->members, 'user_id'), true);

        $employeeUserIds = CompanyEmployee::query()
            ->where('company_id', (int) $this->companyId)
            ->pluck('user_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if ($employeeUserIds === []) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $employeeUserIds)
            ->where(static function ($w) use ($needle) {
                $w->where('display_name', 'like', "%{$needle}%")
                    ->orWhere('user_nicename', 'like', "%{$needle}%")
                    ->orWhere('user_email', 'like', "%{$needle}%");
            })
            ->orderBy('display_name')
            ->limit(25)
            ->get(['id', 'display_name', 'user_nicename', 'user_email']);

        foreach ($users as $user) {
            $uid = (int) $user->id;
            if (isset($existingIds[$uid])) {
                continue;
            }

            $this->userSearchResults[] = [
                'id' => $uid,
                'label' => $this->userLabel($user),
                'email' => (string) ($user->user_email ?? ''),
            ];
        }
    }

    public function addMember(int $userId): void
    {
        $userId = abs($userId);
        if ($userId < 1 || $this->companyId === null) {
            return;
        }

        foreach ($this->members as $member) {
            if ((int) $member['user_id'] === $userId) {
                return;
            }
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            return;
        }

        $isEmployee = CompanyEmployee::query()
            ->where('company_id', (int) $this->companyId)
            ->where('user_id', $userId)
            ->exists();

        if (! $isEmployee) {
            return;
        }

        $this->members[] = [
            'detail_id' => null,
            'user_id' => $userId,
            'status' => CompanyGroupModel::STATUS_MEMBER,
            'label' => $this->userLabel($user),
        ];

        $this->userSearchResults = [];
    }

    public function removeMember(int $index): void
    {
        if (! isset($this->members[$index])) {
            return;
        }

        unset($this->members[$index]);
        $this->members = array_values($this->members);
    }

    public function setMemberStatus(int $index, string $status): void
    {
        if (! isset($this->members[$index])) {
            return;
        }

        $status = strtolower(trim($status));
        if (! in_array($status, CompanyGroupDetail::validStatuses(), true)) {
            return;
        }

        $this->members[$index]['status'] = $status;
    }

    public function saveNew(): void
    {
        $this->validate([
            'companyId' => 'required|integer|exists:wp_companies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group = CompanyGroupModel::create([
            'company_id' => (int) $this->companyId,
            'title' => trim($this->title),
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);

        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Group created. You can add members on the next screen.',
        ]);

        $this->redirect(route('company-group.edit', ['company_group' => $group->id]));
    }

    public function saveExisting(): void
    {
        if ($this->dataId === null) {
            return;
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'members' => 'present|array',
            'members.*.user_id' => 'required|integer|min:1',
            'members.*.status' => 'required|in:member,leader',
        ]);

        $leaderCount = collect($this->members)->where('status', CompanyGroupModel::STATUS_LEADER)->count();
        if ($leaderCount > 1) {
            $this->addError('members', 'Only one leader is allowed per group.');

            return;
        }

        $group = CompanyGroupModel::findOrFail((int) $this->dataId);
        $group->update([
            'title' => trim($this->title),
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);

        $existing = CompanyGroupDetail::query()
            ->where('company_group_id', $group->id)
            ->get()
            ->keyBy('id');

        $keptDetailIds = [];

        foreach ($this->members as $member) {
            $userId = (int) $member['user_id'];
            $status = (string) $member['status'];
            $detailId = isset($member['detail_id']) ? (int) $member['detail_id'] : null;

            if ($detailId !== null && $existing->has($detailId)) {
                $detail = $existing->get($detailId);
                if ($detail->status !== $status) {
                    $detail->update(['status' => $status]);
                }
                $keptDetailIds[] = $detailId;

                continue;
            }

            try {
                $created = CompanyGroupDetail::create([
                    'company_group_id' => $group->id,
                    'user_id' => $userId,
                    'status' => $status,
                ]);
                $keptDetailIds[] = (int) $created->id;
            } catch (\Illuminate\Database\QueryException) {
                // duplicate user in group
            }
        }

        CompanyGroupDetail::query()
            ->where('company_group_id', $group->id)
            ->whereNotIn('id', $keptDetailIds === [] ? [0] : $keptDetailIds)
            ->delete();

        $group->load('details.user');
        $this->hydrateMembersFromGroup($group);

        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Saved.',
        ]);
    }

    public function render()
    {
        return view('livewire.form.company-group', [
            'companies' => $this->dataId === null ? $this->companyOptions() : [],
            'companyTitle' => $this->companyId !== null
                ? (Company::query()->find((int) $this->companyId)?->title ?? "Company #{$this->companyId}")
                : null,
        ]);
    }
}
