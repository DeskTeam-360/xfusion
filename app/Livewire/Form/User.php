<?php

namespace App\Livewire\Form;

use App\Models\CompanyEmployee;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;
use MikeMcLin\WpPassword\Facades\WpPassword;

class User extends Component
{

    public $action;
    public $companyId;
    public $dataId;
    #[Validate('required|max:255')]
    public $username;
    #[Validate('required|max:255|email')]
    public $email;
    #[Validate('required|max:255')]
    public $first_name;
    #[Validate('required|max:255')]
    public $last_name;
    #[Validate('max:255')]
    public $website;
    #[Validate('required|max:255')]
    public $password;
    #[Validate('required|max:255|same:password')]
    public $rePassword;

    #[Validate('required|max:255')]
    public $role;

    public $userMeta;
    public $keap;

    public $optionAccess;

//    public $accessSelected=[];


    public function mount()
    {
        $this->optionAccess = [];

        foreach (\App\Models\UserRole::get() as $role) {
            $this->optionAccess[$role->id] = $role->title;
        }

        if ($this->dataId != null) {
            $data = \App\Models\User::find($this->dataId);
            $this->username = $data->user_login;
            $this->first_name = $data->user_nicename;
            $this->last_name = $data->last_name;
            $this->password = $data->password;

            $this->rePassword = $data->password;

            $this->email = $data->user_email;
            $this->website = $data->user_url;
//            $roles = $data->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
//            $this->role = '';
//            foreach ($roles as $r) {
//                $this->role = array_key_first(unserialize($r['meta_value']));
//            }
            $role =$data->meta()->where('meta_key', 'user_role')->first()->meta_value??'';
            if ($role){
                $this->role = \App\Models\UserRole::where('title', $role)->first()->id;
            }
//            $this->role = ;
//            dd($this->role);
        }
    }

    public function create()
    {

        $this->validate();

        $user = \App\Models\User::create(['user_login' => $this->username, 'user_pass' => WpPassword::make($this->password), 'user_nicename' => $this->first_name, 'user_email' => $this->email, 'user_url' => $this->website ?? 'http://' . $this->first_name, 'user_registered' => Carbon::now()->toDateTimeString(), 'user_activation_key' => '', 'user_status' => 0, 'display_name' => $this->first_name . ' ' . $this->last_name,]);


//        $client = Http::post('https://hooks.zapier.com/hooks/catch/941497/2hr769d/', ['first_name' => $this->first_name, 'last_name' => $this->last_name, 'email' => $this->email, 'website' => $this->website, 'password' => WpPassword::make($this->password),]);
//        $wpRole = '';
        if ($this->role == '1') {
            $wpRole = 'administrator';
        } else {
            $wpRole = 'subscriber';
        }

        $this->userMeta['nickname'] = $this->first_name;
        $this->userMeta['first_name'] = $this->first_name;
        $this->userMeta['last_name'] = $this->last_name;
        $this->userMeta['description'] = '';
        $this->userMeta['rich_editing'] = true;
        $this->userMeta['syntax_highlighting'] = true;
        $this->userMeta['comment_shortcuts'] = false;
        $this->userMeta['admin_color'] = 'fresh';
        $this->userMeta['use_ssl'] = 0;
        $this->userMeta['show_admin_bar_front'] = true;
        $this->userMeta['locale'] = '';
        $this->userMeta['wp_capabilities'] = serialize([$wpRole => true]);

        $ur =  \App\Models\UserRole::find($this->role);
        if ($ur){
            $this->userMeta['user_role'] = $ur->title;
            $this->userMeta['user_access'] = $ur->accesses;
        }

        $this->userMeta['wp_user_level'] = 0;
        $this->userMeta['dismissed_wp_pointers'] = '';

        if ($this->companyId != null) {
            $this->userMeta['company'] = $this->companyId;
            CompanyEmployee::create(['user_id' => $user->ID, 'company_id' => $this->companyId]);
        }

        foreach ($this->userMeta as $key => $meta) {
            WpUserMeta::create(['meta_key' => $key, 'user_id' => $user->ID, 'meta_value' => $meta]);
        }

        $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => 'Successfully added user',]);
        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId));
        } else {
            $this->redirect(route('user.index'));
        }
    }

    public function update()
    {
        $user = \App\Models\User::find($this->dataId);
        $user->update([
            'user_nicename' => $this->username,
            'user_email' => $this->email,
            'user_url' => $this->website ?? 'http://' . $this->first_name,
            'user_registered' => Carbon::now()->toDateTimeString(),
            'user_status' => 0,
            'display_name' => $this->first_name . ' ' . $this->last_name,
            ]);

        $fn = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'first_name')->first();
        $ln = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'last_name')->first();
        $ac = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'user_access')->first();
        $ar = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'user_role')->first();


        if ($ln != null) {
            $ln->update(['meta_value' => $this->last_name]);
        } else {
            WpUserMeta::create(['user_id' => $this->dataId, 'meta_key' => 'last_name', 'meta_value' => $this->last_name]);
        }

        if ($fn != null) {
            $fn->update(['meta_value' => $this->first_name]);
        } else {
            WpUserMeta::create(['user_id' => $this->dataId, 'meta_key' => 'first_name', 'meta_value' => $this->first_name]);
        }

        $ur =  \App\Models\UserRole::find($this->role);
        if ($ac != null) {
            $ac->update(['meta_value' => $ur->accesses]);
        }else{
            WpUserMeta::create(['user_id' => $this->dataId, 'meta_key' => 'user_access', 'meta_value' => $ur->accesses]);
        }

        if ($ar != null) {
            $ar->update(['meta_value' => $ur->title]);
        }else{
            WpUserMeta::create(['user_id' => $this->dataId, 'meta_key' => 'user_role', 'meta_value' => $ur->title]);
        }

//        $client = Http::post('https://hooks.zapier.com/hooks/catch/941497/2hr769d/', ['first_name' => $this->first_name, 'last_name' => $this->last_name, 'email' => $this->email, 'website' => $this->website,]);

        $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => 'successfully changed the user',]);
        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId));
        } else {
            $this->redirect(route('user.index'));
        }
    }


    public function render()
    {
        return view('livewire.form.user');
    }
}
