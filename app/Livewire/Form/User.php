<?php

namespace App\Livewire\Form;

use App\Models\CompanyEmployee;
use App\Models\ScheduleExecution;
use App\Models\WpUserMeta;
use Carbon\Carbon;
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

    public function create()
    {

        $this->validate();

        $user = \App\Models\User::create([
            'user_login' => $this->username,
            'user_pass' => WpPassword::make($this->password),
            'user_nicename' => $this->first_name,
            'user_email' => $this->email,
            'user_url' => $this->website ?? 'http://' . $this->first_name,
            'user_registered' => Carbon::now()->toDateTimeString(),
            'user_activation_key' => '',
            'user_status' => 0,
            'display_name' => $this->first_name . ' ' . $this->last_name,
        ]);


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
        $this->userMeta['wp_capabilities'] = serialize([$this->role => true]);
        $this->userMeta['wp_user_level'] = 0;
        $this->userMeta['dismissed_wp_pointers'] = '';



        if ($this->companyId != null) {
            $this->userMeta['company'] = $this->companyId;
            CompanyEmployee::create([
                'user_id' => $user->ID,
                'company_id' => $this->companyId
            ]);
        }

        foreach ($this->userMeta as $key => $meta) {
            WpUserMeta::create([
                'meta_key' => $key,
                'user_id' => $user->ID,
                'meta_value' => $meta
            ]);
        }

        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId));
        } else {
            $this->redirect(route('user.index'));
        }
    }

    public function update()
    {

        $user = \App\Models\User::find($this->dataId)->update([
            'user_login' => $this->username,
            'user_nicename' => $this->first_name,
            'user_email' => $this->email,
            'user_url' => $this->website ?? 'http://' . $this->first_name,
            'user_registered' => Carbon::now()->toDateTimeString(),
            'user_status' => 0,
            'display_name' => $this->first_name . ' ' . $this->last_name,
        ]);

        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId));
        } else {
            $this->redirect(route('user.index'));
        }
    }

    public function mount()
    {
        if ($this->companyId != null) {
            $this->role = 'subscriber';
        } else {
            $this->role = 'contributor';
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
            $roles = $data->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
            $this->role = '';
            foreach ($roles as $r) {
                $this->role = array_key_first(unserialize($r['meta_value']));
            }
        }
    }

    public function render()
    {
        return view('livewire.form.user');
    }
}
