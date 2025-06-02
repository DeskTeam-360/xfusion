<?php

namespace App\Livewire\Form;

use App\Models\CampaignLog;
use App\Models\CompanyEmployee;
use App\Models\Tag;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;

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

    #[Validate('required')]
    public $status;

    public $tags;

    public function mount()
    {
        if ($this->dataId) {
            $mounted = \App\Models\Campaign::find($this->dataId);
            $this->users = explode(", ", $mounted->users);
            $this->tags = explode(", ", $mounted->tags);
            $this->status = $mounted->status;

            list($time_schedule, $clock) = explode(' ', $mounted->time_schedule);
            $this->time_schedule = $time_schedule;
            $this->clock = $clock;
        } else {
            $this->time_schedule = Carbon::now()->format('Y-m-d');
            $this->clock = '00:00';
        }

        $this->statusOptions = [
            ['value' => 'send', 'title' => 'Send'],
            ['value' => 'scheduled', 'title' => 'Scheduled'],
        ];

        $this->tagOptions = [];
        $keap_tags = Keap::tag()->list(['category' => 44]);

        foreach ($keap_tags as $cmp) {
            $this->tagOptions [] = ['value' => $cmp['id'], 'title' => $cmp['name']];
        }

        $this->userOptions = [];
        foreach (\App\Models\User::whereHas('meta', function ($q) {
            $q->where('meta_key', 'keap_contact_id');
        })->get() as $user) {
            foreach ($user->meta->where('meta_key', 'keap_contact_id') as $k) {
                $this->userOptions [] = ['value' => $k->meta_value, 'title' => $user->user_email];
            }
        }

        $companyCount = \App\Models\User::whereHas('meta', function ($q) {
            $q->where('meta_key', 'wp_capabilities')
                ->where('meta_value', 'like', "%editor%");
        })->whereHas('meta', function ($q) {
            $q->where('meta_key', 'keap_contact_id');
        })->count();

        $contributorCount = \App\Models\User::whereHas('meta', function ($q) {
            $q->where('meta_key', 'wp_capabilities')
                ->where('meta_value', 'like', "%contributor%");
        })->whereHas('meta', function ($q) {
            $q->where('meta_key', 'keap_contact_id');
        })->count();


        $this->companyOptions = [];
        $this->companyOptions[] = ['value' => 'editor', 'title' => "Company ($companyCount contact)"];
        $this->companyOptions[] = ['value' => 'contributor', 'title' => "Contributor  ($contributorCount contact)"];

        $this->created_by_group = '';

    }

    public function create()
    {
        $this->validate();
        $this->resetErrorBag();

        $array_data = [
            'tags' => implode(";", $this->tags),
            'time_schedule' => $this->time_schedule != '' ? $this->time_schedule . ' ' . $this->clock : null,
            'status' => $this->status
        ];


        $group = \App\Models\User::whereHas('meta', function ($q) {
            $q->where('meta_key', 'wp_capabilities')
                ->where('meta_value', 'like', "%$this->companies%");
        })->whereHas('meta', function ($q) {
            $q->where('meta_key', 'keap_contact_id');
        })->get();

        $this->companies = [];

        foreach ($group as $user) {
            foreach ($user->meta->where('meta_key', 'keap_contact_id') as $k) {
                $this->companies[] = $k->meta_value;
            }
            foreach ($this->tags as $tag) {
                $keapTag = $user->meta->where('meta_key', 'keap_tags')->first();
                if ($keapTag != null) {
                    $keapTag->update(['meta_value' => $keapTag->meta_value . ";$tag"]);
                } else {
                    WpUserMeta::create(['user_id' => $user->ID, 'meta_key' => 'keap_tags', 'meta_value' => $tag]);
                }
            }
        }

        if ($this->for == 'user') {
            $array_data['users'] = implode(";", $this->users);
            $array_data['created_by_group'] = 'no';

            foreach ($this->users as $user) {
                $wum = WpUserMeta::where('meta_key', 'keap_contact_id')->where('meta_value', $user)->first();
                if ($wum != null) {
                    $userID= $wum->user_id;
                    $user = \App\Models\User::find($userID);

                    $keapTag = $user->meta->where('meta_key', 'keap_tags')->first();
                    if ($keapTag != null) {
                        $keapTag->update(['meta_value' => $keapTag->meta_value . ";$tag"]);
                    } else {
                        WpUserMeta::create(['user_id' => $user->ID, 'meta_key' => 'keap_tags', 'meta_value' => $tag]);
                    }

                }
            }

        } elseif ($this->for == 'group') {
            $array_data['users'] = implode(";", $this->companies);
            $array_data['created_by_group'] = 'yes';
        } elseif ($this->for == 'independent') {

            $keap_id = null;
            foreach (\App\Models\User::find($this->userId)->meta->where('meta_key', 'keap_contact_id') as $k) {
                $keap_id = $k->meta_value;
            }

            $array_data['users'] = $keap_id;
            $array_data['created_by_group'] = 'no';
        }

        \App\Models\Campaign::create($array_data);

        if ($this->status == "send") {
            foreach ($this->tags as $tag) {

                $k = Keap::tag()->applyToContacts(
                    $tag,
                    explode(';',$array_data['users'])
                );

                foreach ($k as $key => $note) {
                    CampaignLog::create([
                        'tag_id'=>$tag,
                        'user_id'=>$key,
                        'status'=>$note
                    ]);
                }
            }


            Artisan::queue('app:get-tag');

//            $users = \App\Models\User::whereHas('meta',function ($q){
//                $q->where('meta_key', '=', 'keap_contact_id');
//            })->get();
//            foreach ($users as $user){
//                $tag = [];
//                $wpUserMeta = WpUserMeta::where('user_id','=',$user->ID)->where('meta_key','=','keap_tags')->first();
//                $keapId = WpUserMeta::where('user_id','=',$user->ID)->where('meta_key','=','keap_contact_id')->first()->meta_value;
//                $tagKeaps = Keap::contact()->tags($keapId);
//                foreach ($tagKeaps as $tk){
//                    $tag[]=$tk['tag']['id'];
//                }
//                $tag = implode(';',$tag);
//                if ($wpUserMeta!=null){
//                    WpUserMeta::find($wpUserMeta->umeta_id)->update(['meta_value'=>$tag]);
//                }else{
//                    WpUserMeta::create([
//                        'user_id'=>$user->ID,
//                        'meta_key'=>'keap_tags',
//                        'meta_value'=>$tag
//                    ]);
//                }
//            }
        }

        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'Successfully added campaign',
        ]);

        $this->redirect(route('campaign.index'));
    }

    public function update()
    {
        $this->validate();
        $this->resetErrorBag();
        $campaign = \App\Models\Campaign::find($this->dataId)->update([
            'tags' => implode(", ", $this->tags),
            'users' => implode(", ", $this->users),
            'time_schedule' => $this->time_schedule != '' ? $this->time_schedule . ' ' . $this->clock : null,
            'status' => $this->status,
        ]);
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'successfully changed the campaign',
        ]);
        $this->redirect(route('campaign.index'));
    }

    public function render()
    {
        return view('livewire.form.campaign');
    }
}
