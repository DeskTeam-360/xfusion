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
    public $complete;
    public $inComplete;


    public function mount()
    {
        $this->user = Auth::user();
        $company = $this->user->meta->where('meta_key', '=', 'company')->first();
        $this->companyId = $company['meta_value'];
        $this->getData();
    }

    public function getData()
    {
        $this->userEmployee = User::whereHas('meta', function ($q) {
            $q->where('meta_key', config('app.wp_prefix', 'wp_') . 'capabilities')
                ->where('meta_value', 'like', '%subscriber%');
        })->whereHas('meta', function ($q) {
            $q->where('meta_key', 'company')
                ->where('meta_value', $this->companyId);
        })->get();

        foreach ($this->userEmployee as $c){
            $link = \App\Models\ScheduleExecution::where('user_id',$c->ID)->get()->pluck('link')->toArray();
            $courseComplete = \App\Models\WpGfEntry::where('created_by',$c->ID)->where('status','active')->whereIn('source_url',$link)->count();
            $course = \App\Models\ScheduleExecution::where('user_id',$c->ID)->count();
            if ($courseComplete==$course){
                $this->complete+=1;
            }else{
                $this->inComplete+=1;
            }
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
