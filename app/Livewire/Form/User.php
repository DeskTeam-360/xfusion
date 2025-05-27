<?php

namespace App\Livewire\Form;

use App\Models\CompanyEmployee;
use App\Models\ScheduleExecution;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use GuzzleHttp\Client;
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

//    public $optionAccess;
//    public $accessSelected=[];

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


        $client = Http::post('https://hooks.zapier.com/hooks/catch/941497/2hr769d/', [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'website' => $this->website,
            'password' => WpPassword::make($this->password),
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
//        sleep(2);
//        $this->getDataKeap();
//
//        WpUserMeta::create([
//            'meta_key' => 'keap_contact_id',
//            'user_id' => $user->ID,
//            'meta_value' => $this->keap
//        ]);
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'Successfully added user',
        ]);
        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId));
        } else {
            $this->redirect(route('user.index'));
        }
    }

    public function getDataKeap()
    {
        $this->keap = Keap::contact()->list([
            'email' => $this->email
        ]);

    }

    public function update()
    {
//        if ($this->accessSelected){
//            $wum = WpUserMeta::where('meta_key', 'user_access')->where('user_id', $this->dataId)->first();
//            if ($wum) {
//                $wum->delete();
//            }
//            WpUserMeta::create([
//                'meta_key' => 'user_access',
//                'user_id' => $this->dataId,
//                'meta_value' => json_encode($this->accessSelected)
//            ]);
//        }

//        dd($this->accessSelected);

        $user = \App\Models\User::find($this->dataId)->update([
            'user_nicename' => $this->username,
            'user_email' => $this->email,
            'user_url' => $this->website ?? 'http://' . $this->first_name,
            'user_registered' => Carbon::now()->toDateTimeString(),
            'user_status' => 0,
            'display_name' => $this->first_name . ' ' . $this->last_name,
        ]);

        $fn = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'first_name')->first();
        $ln = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'last_name')->first();
//        $keapId = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'keap_contact_id')->first();

        if ($ln != null) {
            $ln->update([
                'meta_value' => $this->last_name
            ]);
        } else {
            WpUserMeta::create([
                'user_id' => $this->dataId,
                'meta_key' => 'last_name',
                'meta_value' => $this->last_name
            ]);
        }

        if ($fn != null) {
            $fn->update([
                'meta_value' => $this->first_name
            ]);
        } else {
            WpUserMeta::create([
                'user_id' => $this->dataId,
                'meta_key' => 'first_name',
                'meta_value' => $this->first_name
            ]);
        }
        $client = Http::post('https://hooks.zapier.com/hooks/catch/941497/2hr769d/', [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'website' => $this->website,
        ]);

        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'successfully changed the user',
        ]);
        if ($this->companyId != null) {
            $this->redirect(route('company.show', $this->companyId));
        } else {
            $this->redirect(route('user.index'));
        }
    }

    public function mount()
    {
//        $this->optionAccess =[
//            ['value'=>'revitalize','title'=>'Revitalize'],
//            ['value'=>'revitalize-facilitation','title'=>'Revitalize facilitation'],
//
//            ['value'=>'transform','title'=>'Transform'],
//            ['value'=>'transform-resource','title'=>'Transform resource'],
//            ['value'=>'transform-tools','title'=>'Transform tools'],
//
//            ['value'=>'sustain','title'=>'Sustain'],
//            ['value'=>'sustain-resource','title'=>'Sustain resource'],
//            ['value'=>'sustain-tools','title'=>'Sustain tools'],
//            ['value'=>'individual-reports','title'=>'Individual reports'],
//        ];
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
//            $as = $data->meta->where('meta_key','=','user_access')->first();
//            if ($as != null) {
//                $this->accessSelected = json_decode($as->meta_value);
//            }
//            dd($this->accessSelected);
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
