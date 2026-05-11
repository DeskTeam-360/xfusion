<?php

namespace App\Livewire;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardCompany extends Component
{

    public $user;
    public $companyId;

    public $userEmployee;
    public ?int $complete = 0;

    public ?int $inComplete = 0;


    public function mount()
    {
        $this->user = Auth::user();
        $company = $this->user->meta->where('meta_key', '=', 'company')->first();

        if ($company === null) {
            $this->companyId = '';
            $this->userEmployee = collect();

            return;
        }

        $raw = is_object($company) ? ($company->meta_value ?? '') : ($company['meta_value'] ?? '');
        if (is_array($raw)) {
            $raw = $raw[0] ?? '';
        }
        $this->companyId = $raw !== null && $raw !== '' ? (string) $raw : '';

        if ($this->companyId === '') {
            $this->userEmployee = collect();

            return;
        }

        $this->getData();
    }

    public function getData()
    {
        $this->complete = 0;
        $this->inComplete = 0;

        if ($this->companyId === '') {
            $this->userEmployee = collect();

            return;
        }

        $this->userEmployee = User::whereHas('meta', function ($q) {
            $q->where('meta_key', config('app.wp_prefix', 'wp_') . 'capabilities')
                ->where('meta_value', 'like', '%subscriber%');
        })->whereHas('meta', function ($q) {
            $q->where('meta_key', 'company')
                ->where('meta_value', $this->companyId);
        })->get();

        foreach ($this->userEmployee as $c) {
            $this->inComplete += 1;
        }
    }

    public function getDataUserGrowh($i)
    {
        return User::whereHas('meta', function ($q) {
            $q->where('meta_key', config('app.wp_prefix', 'wp_') . 'capabilities')
                ->where('meta_value', 'like', '%subscriber%');
        })->whereHas('meta', function ($q) {
            $q->where('meta_key', 'company')
                ->where('meta_value', $this->companyId);
        })
            ->whereMonth('user_registered', Carbon::now()->subMonths(2 - $i)->month)
            ->whereYear('user_registered', Carbon::now()->subMonths(2 - $i)->year)
            ->get()->count();
    }

    public function render()
    {
        return view('livewire.dashboard-company');
    }
}
