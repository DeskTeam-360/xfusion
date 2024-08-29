<?php

namespace App\Livewire\Form;

use App\Models\CompanyEmployee;
use App\Models\Tag;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Campaign extends Component
{
    public $action;
    public $for;
    public $data;
    public $dataId;
    public $tagOptions;
    public $statusOptions;
    public $userOptions;
    public $companyOptions;

    public $created_by_group;

//    #[Validate('required')]
    public $users;

//    #[Validate('required')]
    public $companies;

    public $time_schedule;
    public $clock;

    #[Validate('required')]
    public $status;

    public $tags;

    public function mount()
    {
        if ($this->dataId) {
            $mounted = \App\Models\Campaign::find($this->dataId);
//            dd($mounted->tags);
            $this->users = explode(", ", $mounted->users);
            $this->tags = explode(", ", $mounted->tags);
//            dd($this->tags);
            $this->status = $mounted->status;

            list($time_schedule, $clock) = explode(' ', $mounted->time_schedule);
            $this->time_schedule = $time_schedule;
            $this->clock = $clock;
        } else {
            $this->time_schedule = Carbon::now()->format('Y-m-d');
            $this->clock = '00:00';
        }

        $this->statusOptions = [
            ['value' => 'send','title'=>'Send'],
            ['value' => 'scheduled','title'=>'Scheduled'],
            ['value' => 'failed','title'=>'Failed'],
        ];

        $this->tagOptions = [];
        $keap_tags = Keap::tag()->list();
//        dd($data);
//        foreach (\App\Models\Tag::get() as $cmp) {
//            $this->tagOptions [] = ['value' => $cmp->id, 'title' => $cmp->title];
//        }
        foreach ($keap_tags as $cmp) {
//            dd($cmp['id']);
            $this->tagOptions [] = ['value' => $cmp['id'], 'title' => $cmp['name']];
        }

//        dd($this->tagOptions);
        $this->userOptions = [];
        foreach (\App\Models\User::get() as $user) {
            $this->userOptions [] = ['value' => $user->ID, 'title' => $user->user_email];
        }

        $this->companyOptions = [];
        $this->companyOptions[]=['value' => 'editor', 'title' => 'Company'];
        $this->companyOptions[]=['value' => 'contributor', 'title' => 'Contributor'];
//        foreach (\App\Models\Company::get() as $company) {
////            dd($company->id);
//            $this->companyOptions [] = ['value' => $company->id, 'title' => $company->title];
//        }

        $this->created_by_group = '';
//        if ($this->dataId!=null){
//            $data = \App\Models\CourseList::find($this->dataId);
//            $this->url=$data->url;
//            $this->pageTitle=$data->page_title;
//            $this->courseTitle=$data->course_title;
//        }
    }

    public function create()
    {
//        dd($this->created_by_group);
//        dd($this->status,$this->companies);
        $this->validate();
        $this->resetErrorBag();
//        if ()
//        dd(($this->for == 'user') ? 'no' : ($this->for == 'company' ? 'yes' : ''));

//        $array_data = [];
        $array_data = [
            'tags' => implode(", ", $this->tags),
            'time_schedule' => $this->time_schedule != '' ? $this->time_schedule . ' ' . $this->clock : null,
            'status' => $this->status
        ];
//        dd($this->companies);

        $this->companies = WpUserMeta::where('meta_key', 'wp_capabilities')->where('meta_value', 'like', "%$this->companies%")->get()->pluck('user_id')->toArray();


        if ($this->for == 'user') {
            $array_data['users'] = implode(", ", $this->users);
            $array_data['created_by_group'] = 'no';
        } elseif ($this->for == 'group') {
            $array_data['users'] = implode(", ", $this->companies);
            $array_data['created_by_group'] = 'yes';
        }
//        dd($array_data);

        \App\Models\Campaign::create($array_data);

        $this->redirect(route('campaign.index'));
    }

    public function update() {

        $this->validate();
        $this->resetErrorBag();
        $campaign =\App\Models\Campaign::find($this->dataId)->update([
            'tags' => implode(", ", $this->tags),
            'users' => implode(", ", $this->users),
            'time_schedule' => $this->time_schedule != '' ? $this->time_schedule . ' ' . $this->clock : null,
            'status' => $this->status,
        ]);

        $this->redirect(route('campaign.index'));
    }

    public function render()
    {
        return view('livewire.form.campaign');
    }
}
