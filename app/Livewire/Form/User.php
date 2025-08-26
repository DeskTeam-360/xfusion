<?php

namespace App\Livewire\Form;

use App\Models\CompanyEmployee;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use Hautelook\Phpass\PasswordHash;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;

class User extends Component
{

    public $action;
    public $companyId;
    public $dataId;
    #[Validate('required|max:255')]
    public $username;
    #[Validate('required|max:255|email|unique:wp_users,user_email')]
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

    public $company_id=null;

    public $userMeta;
    public $keap;
    public $keapMailSend;
    public $keapIntegration;
    public $skipRevitalize;

    public $optionAccess;
    public $optionCompany;


    public function mount()
    {
        $this->optionAccess = [];

        foreach (\App\Models\UserRole::get() as $role) {
            $this->optionAccess[$role->id] = $role->title;
        }
        $this->optionCompany = [];

        foreach (\App\Models\Company::get() as $company) {
            $this->optionCompany[$company->id] = $company->title;
        }

        if ($this->dataId != null) {
            $data = \App\Models\User::find($this->dataId,);
            $this->username = $data->user_login;
            $this->first_name = $data->user_nicename;
            $this->last_name = $data->last_name;
            $this->password = $data->password;

            $this->rePassword = $data->password;

            $this->email = $data->user_email;
            $this->website = $data->user_url;

            $role = $data->meta()->where('meta_key', 'user_role',)->first()->meta_value ?? '';
            if ($role) {
                $this->role = \App\Models\UserRole::where('title', $role,)->first()->id;
            }

        }
    }

    public function update()
    {
        $user = \App\Models\User::find($this->dataId,);
        $user->update([
            'user_nicename'   => $this->username,
            'user_email'      => $this->email,
            'user_url'        => $this->website ?? 'http://' . $this->first_name,
            'user_registered' => Carbon::now()->toDateTimeString(),
            'user_status'     => 0,
            'display_name'    => $this->first_name . ' ' . $this->last_name,
        ],);

        $ur = \App\Models\UserRole::find($this->role,);

        $currentTag = json_decode($ur->tag_starter,);

        if ($this->skipRevitalize) {
            if (str_contains($ur->accesses, '"sustain"',)) {
                $currentTag = array_merge($currentTag, [322],);
            }
            if (str_contains($ur->accesses, '"transform"',)) {
                $currentTag = array_merge($currentTag, [1012],);
            }
        }
        $currentTag = array_merge($currentTag, [1960],);

        $contact = $this->updateContact();
        $contactId = $contact['id'];

        if ($this->company_id){
            $this->userMeta['company'] = $this->company_id;
            CompanyEmployee::where('user_id', $this->dataId)->delete();
            \App\Repository\View\CompanyEmployee::create([
                'user_id' => $user->ID,
                'company_id' => $this->company_id
            ]);
        }

        $this->updateOrCreateMeta('last_name', $this->last_name,);
        $this->updateOrCreateMeta('first_name', $this->first_name,);
        $this->updateOrCreateMeta('user_access', $ur->accesses,);
        $this->updateOrCreateMeta('access_tags', implode(';', $currentTag,),);
        $this->updateOrCreateMeta('user_role', $ur->title,);
        Keap::contact()->tag($contactId, $currentTag,);

        if ($this->keapIntegration) {
            Keap::contact()->tag($contactId, [1958],);
            $this->updateOrCreateMeta('keap_contact_id', $contactId,);
            $this->updateOrCreateMeta('keap_status', true,);
        } else {
            $this->updateOrCreateMeta('keap_status', false,);
        }


        if ($this->keapMailSend) {
            $this->keapMailSend($contactId,);
        }


        $this->dispatch('swal:alert', data: [
            'icon'  => 'success',
            'title' => 'successfully changed the user',
        ],);
        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId,),);
        } else {
            $this->redirect(route('user.index',),);
        }
    }

    public function updateContact()
    {
        try {
            if ($this->action == 'create') {
                $contact = Keap::contact()->createOrUpdate([
                    'given_name'      => $this->first_name,
                    'family_name'     => $this->last_name,
                    'email_addresses' => [
                        [
                            'email' => $this->email,
                            'field' => 'EMAIL1',
                        ],
                    ],
                    'custom_fields'   => [
                        [
                            'id'      => '96',
                            'content' => $this->email,
                        ],
                        [
                            'id'      => '98',
                            'content' => $this->password,
                        ],
                    ],
                ]);
            } elseif ($this->action == 'update') {
                $contact = Keap::contact()->createOrUpdate([
                    'given_name'      => $this->first_name,
                    'family_name'     => $this->last_name,
                    'email_addresses' => [
                        [
                            'email' => $this->email,
                            'field' => 'EMAIL1',
                        ],
                    ],
                    'custom_fields'   => [
                        [
                            'id'      => '96',
                            'content' => $this->email,
                        ],
                    ],
                ]);
            }

            return $contact;
        } catch (\Exception $e) {
            \Log::error('Error updating Keap contact: ' . $e->getMessage());
            return null;
        }
    }


    public function updateOrCreateMeta($key, $value,)
    {
        WpUserMeta::updateOrCreate([
            'user_id'  => $this->dataId,
            'meta_key' => $key,
        ], ['meta_value' => $value],);
    }

    public function keapMailSend($contactId,)
    {
        Keap::contact()->tag($contactId, [1942],);
    }

    public function create()
    {

        $this->validate();

        $hasher = new PasswordHash(8, true,); // Sama seperti di WordPress
        $passwordHash = $hasher->HashPassword($this->password,);

        $user = \App\Models\User::create([
            'user_login'          => $this->username,
            'user_pass'           => $passwordHash,
            'user_nicename'       => $this->first_name,
            'user_email'          => $this->email,
            'user_url'            => $this->website ?? 'http://' . $this->first_name,
            'user_registered'     => Carbon::now()->toDateTimeString(),
            'user_activation_key' => '',
            'user_status'         => 0,
            'display_name'        => $this->first_name . ' ' . $this->last_name,
        ],);

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
        $this->userMeta['wp_capabilities'] = serialize([$wpRole => true],);

        $ur = \App\Models\UserRole::find($this->role,);
        if ($ur) {
            $this->userMeta['user_role'] = $ur->title;
            $this->userMeta['user_access'] = $ur->accesses;
            $this->userMeta['access_tags'] = implode(';', json_decode($ur->tag_starter,),);
            $this->userMeta['access_tags'] = $this->userMeta['access_tags'] . ';1960';
        }

        if ($this->company_id){
            $this->userMeta['company'] = $this->company_id;

            \App\Repository\View\CompanyEmployee::create([
                'user_id' => $user->ID,
                'company_id' => $this->company_id
            ]);
        }


        if ($this->skipRevitalize) {
            if (str_contains($ur->accesses, '"sustain"',)) {
                $this->userMeta['access_tags'] = $this->userMeta['access_tags'] . ';322;1840;1864;1870;1888';
            }
            if (str_contains($ur->accesses, '"transform"',)) {
                $this->userMeta['access_tags'] = $this->userMeta['access_tags'] . ';1012;1840;1864;1870;1888';
            }
        }

        $contact = $this->updateContact();

        if (isset($contact['id'])) {
            Keap::contact()->tag($contact['id'], explode(';', $this->userMeta['access_tags'],),);

            if ($this->keapIntegration) {
                $this->userMeta['keap_contact_id'] = $contact['id'];
                $this->userMeta['keap_status'] = true;
                Keap::contact()->tag($contact['id'], [1958],);
            } else {
                $this->userMeta['keap_status'] = false;
            }

            if ($this->keapMailSend) {
                $this->keapMailSend($contact['id'],);
            }
        }

        $this->userMeta['wp_user_level'] = 0;
        $this->userMeta['dismissed_wp_pointers'] = '';

        if ($this->companyId != null) {
            $this->userMeta['company'] = $this->companyId;
            CompanyEmployee::create([
                'user_id'    => $user->ID,
                'company_id' => $this->companyId,
            ],);
        }

        foreach ($this->userMeta as $key => $meta) {
            WpUserMeta::create([
                'meta_key'   => $key,
                'user_id'    => $user->ID,
                'meta_value' => $meta,
            ],);
        }

        $this->dispatch('swal:alert', data: [
            'icon'  => 'success',
            'title' => 'Successfully added user',
        ],);
        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId,),);
        } else {
            $this->redirect(route('user.index',),);
        }
    }

    public function render()
    {
        return view('livewire.form.user',);
    }
}
