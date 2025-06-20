<?php

namespace App\Livewire\Form;

use App\Models\CampaignLog;
use App\Models\Tag;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Campaign extends Component
{
    public $action;
    public $for;
    public $data;
    public $dataId;
    public $userId;
    public $tagOptions;
    public $statusOptions;
    public $userOptions;
    public $companyOptions;

    public $created_by_group;

    public $users;

    public $companies;

    public $time_schedule;
    public $clock;

//    #[Validate('required')]
    public $status = 'send';

    #[Validate('required')]
    public $tags = [];

    public function mount()
    {
        if ($this->dataId) {
            $mounted = \App\Models\Campaign::find($this->dataId);
            $this->users = explode(", ", $mounted->users);
            $this->tags = explode(", ", $mounted->tags);
//            $this->status = $mounted->status;

            [$time_schedule, $clock] = explode(' ', $mounted->time_schedule);
            $this->time_schedule = $time_schedule;
            $this->clock = $clock;
        } else {
            $this->time_schedule = Carbon::now()->format('Y-m-d');
            $this->clock = '00:00';
        }


        $this->tagOptions = [];
        $keap_tags = Tag::get();

        foreach ($keap_tags as $cmp) {
            $this->tagOptions [] = ['value' => $cmp['id'], 'title' => $cmp['name']];
        }

        $this->userOptions = [];
        foreach (\App\Models\User::orderBy('user_email')->get() as $user) {
            $this->userOptions [] = ['value' => $user->ID, 'title' => $user->user_email];
        }
        $this->created_by_group = '';

    }

    public function create()
    {
        $this->validate();
        $this->resetErrorBag();

        $array_data = ['tags' => implode(";", $this->tags), 'time_schedule' => $this->time_schedule != '' ? $this->time_schedule . ' ' . $this->clock : null, 'status' => 'send'];

        $array_data['users'] = [];
        $array_data['created_by_group'] = 'no';

        $tag = implode(';', $this->tags);
        foreach ($this->users as $user) {
            $userID = $user;
            $keapTag = WpUserMeta::where('user_id', $userID)->where('meta_key', 'keap_tags')->first();
            $accessTag = WpUserMeta::where('user_id', $userID)->where('meta_key', 'access_tags')->first();
            if ($keapTag != null) {
                $keapTag->update(['meta_value' => $keapTag->meta_value . ";$tag"]);
            } else {
                WpUserMeta::create(['user_id' => $userID, 'meta_key' => 'keap_tags', 'meta_value' => $tag]);
            }
            if ($accessTag != null) {
                $accessTag->update(['meta_value' => $accessTag->meta_value . ";$tag"]);
            } else {
                WpUserMeta::create(['user_id' => $userID, 'meta_key' => 'access_tags', 'meta_value' => $tag]);
            }
            $keapUser = WpUserMeta::where('user_id', $userID)->where('meta_key', 'keap_status')->where('meta_value', 'true')->first();
            if ($keapUser != null) {
                $array_data['users'][] = $keapUser->meta_value;
            }
        }
        $array_data['users'] = implode(";", $array_data['users']);

        \App\Models\Campaign::create($array_data);

        if ($array_data['users']) {
            foreach ($this->tags as $tag) {
                $k = Keap::tag()->applyToContacts($tag, explode(';', $array_data['users']));
                foreach ($k as $key => $note) {
                    CampaignLog::create(['tag_id' => $tag, 'user_id' => $key, 'status' => $note]);
                }
            }
        }


        Artisan::queue('app:get-tag');
        $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => 'Successfully added campaign',]);
        $this->redirect(route('campaign.index'));
    }


    public function render()
    {
        return view('livewire.form.campaign');
    }
}
