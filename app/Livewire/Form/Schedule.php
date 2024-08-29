<?php

namespace App\Livewire\Form;

use App\Models\ScheduleExecution;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Schedule extends Component
{
    public $action;

    public $companyId;

    #[Validate('required|max:255')]
    public $user_id;

    #[Validate('required|max:255')]
    public $title;

    #[Validate('required')]
    public $link;
    #[Validate('nullable')]
    public $schedule_access_time;
    #[Validate('nullable')]
    public $schedule_access_date;

    #[Validate('nullable')]
    public $schedule_deadline_time;
    #[Validate('nullable')]
    public $schedule_deadline_date;

    public $usersOption;
    public $optionCourse;

    public function mount()
    {
        $this->schedule_deadline_date = Carbon::now()->addDays(7)->format('Y-m-d');
        $this->schedule_access_date = Carbon::now()->format('Y-m-d');
        $this->schedule_deadline_time = '00:00';
        $this->schedule_access_time = '00:00';

        $this->usersOption = [];
        foreach (\App\Models\User::get() as $item) {
            $companies = $item->meta->where('meta_key', '=', 'company');
            foreach ($companies as $r) {
                if ($r['meta_value'] == $this->companyId) {
                    $this->usersOption[] = ['value' => $item->ID, 'title' => $item->user_email];
                }
            }
        }

        $this->optionCourse = [];
//        'url','course_title','page_title'
        foreach (\App\Models\CourseList::get() as $cl) {
            $this->optionCourse [] = ['value' => $cl->url, 'title' => $cl->course_title.' - '.$cl->page_title];
        }
    }

    public function create()
    {

//        dd($this->schedule_access_date != '' ? $this->schedule_access_date . ' ' . $this->schedule_access_time : null);
        $url = substr($this->link, -3);
        $number = intval(preg_replace('/[^\w\s]/', '', $url));
        $this->validate();
        $this->resetErrorBag();
        $se =ScheduleExecution::create([
            'link' => $this->link,
            'company_id' => $this->companyId,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'season_id' => ($number <= 15) ? 1 : 2,
            'status' => 0,
            'schedule_access' => $this->schedule_access_date != '' ? $this->schedule_access_date . ' ' . $this->schedule_access_time : null,
            'schedule_deadline' => $this->schedule_deadline_date != '' ? $this->schedule_deadline_date . ' ' . $this->schedule_deadline_time : null,
        ]);

        $this->redirect(route('company.schedule', $this->companyId));
    }


    public function render()
    {
        return view('livewire.form.schedule');
    }
}
