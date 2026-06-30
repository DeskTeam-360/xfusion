<?php

namespace App\Livewire\Form;

use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\OneOnOne as OneOnOneModel;
use App\Models\OneOnOneConversation;
use App\Models\User;
use Livewire\Component;

class OneOnOne extends Component
{
    public ?string $dataId = null;

    public ?int $companyId = null;

    public ?int $leaderUserId = null;

    public ?int $employeeUserId = null;

    /** @var list<array{id: int, label: string}> */
    public array $companyEmployeeOptions = [];

    public ?string $newScheduledAt = null;

    public function mount(?string $dataId = null): void
    {
        $this->dataId = $dataId !== null && $dataId !== '' ? $dataId : null;

        if ($this->dataId !== null) {
            $pair = OneOnOneModel::findOrFail((int) $this->dataId);
            $this->companyId = (int) $pair->company_id;
            $this->leaderUserId = (int) $pair->leader_user_id;
            $this->employeeUserId = (int) $pair->employee_user_id;
            $this->loadCompanyEmployees();
        }
    }

    public function updatedCompanyId(): void
    {
        $this->loadCompanyEmployees();
        $this->leaderUserId = null;
        $this->employeeUserId = null;
    }

    private function loadCompanyEmployees(): void
    {
        $this->companyEmployeeOptions = [];

        if ($this->companyId === null) {
            return;
        }

        $userIds = CompanyEmployee::query()
            ->where('company_id', $this->companyId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($userIds === []) {
            return;
        }

        $this->companyEmployeeOptions = User::query()
            ->whereIn('ID', $userIds)
            ->orderBy('display_name')
            ->get(['ID', 'display_name', 'user_nicename'])
            ->map(fn ($u) => ['id' => (int) $u->ID, 'label' => $u->display_name ?: $u->user_nicename])
            ->all();
    }

    /** @return list<array{id: int, title: string}> */
    public function companyOptions(): array
    {
        return Company::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn ($c) => ['id' => (int) $c->id, 'title' => (string) $c->title])
            ->all();
    }

    public function save(): void
    {
        $this->validate([
            'companyId' => 'required|integer|exists:wp_companies,id',
            'leaderUserId' => 'required|integer|min:1|different:employeeUserId',
            'employeeUserId' => 'required|integer|min:1',
        ]);

        if ($this->dataId === null) {
            $pair = OneOnOneModel::create([
                'company_id' => $this->companyId,
                'leader_user_id' => $this->leaderUserId,
                'employee_user_id' => $this->employeeUserId,
                'status' => OneOnOneModel::STATUS_ACTIVE,
            ]);

            $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => '1-on-1 pair created.']);
            $this->redirect(route('one-on-one.edit', ['one_on_one' => $pair->id]));

            return;
        }

        $pair = OneOnOneModel::findOrFail((int) $this->dataId);
        $pair->update([
            'company_id' => $this->companyId,
            'leader_user_id' => $this->leaderUserId,
            'employee_user_id' => $this->employeeUserId,
        ]);

        $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => 'Saved.']);
    }

    public function scheduleConversation(): void
    {
        if ($this->dataId === null) {
            return;
        }

        $this->validate(['newScheduledAt' => 'required|date']);

        OneOnOneConversation::create([
            'one_on_one_id' => (int) $this->dataId,
            'scheduled_at' => $this->newScheduledAt,
            'status' => OneOnOneConversation::STATUS_SCHEDULED,
        ]);

        $this->newScheduledAt = null;

        $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => 'Conversation scheduled.']);
    }

    public function cancelConversation(int $conversationId): void
    {
        OneOnOneConversation::where('id', $conversationId)
            ->where('one_on_one_id', (int) $this->dataId)
            ->update(['status' => OneOnOneConversation::STATUS_CANCELLED]);
    }

    public function render()
    {
        $pair = $this->dataId !== null ? OneOnOneModel::find((int) $this->dataId) : null;

        return view('livewire.form.one-on-one', [
            'companies' => $this->companyOptions(),
            'conversations' => $pair?->conversations()->with('synthesis')->get() ?? collect(),
        ]);
    }
}
