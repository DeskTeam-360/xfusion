<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserRole;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use Exception;
use Hautelook\Phpass\PasswordHash;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportUser extends Component
{
    public $file;
    public $rows = [];
    public $users = [];
    public $keapMailSend;

    use WithFileUploads;

    public function checkCsv()
    {
        dd($this->file);
    }

    public function updatedFile()
    {
        $this->validate(['file' => 'required|file|mimes:csv,txt|max:2048',]);

        $this->parseCsv();
    }

    public function parseCsv()
    {
        $path = $this->file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $this->rows = $data;

        $headers = array_map('trim', $data[0]); // Baris pertama = header

        $this->users = collect(array_slice($data, 1))->map(function ($row) use ($headers) {
            return array_combine(array_map(fn($h) => strtolower(str_replace(' ', '_', $h)), $headers), $row);
        })->toArray();
    }

    public function import()
    {
        foreach ($this->users as $row) {
            $this->create($row);
        }
        $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => 'Successfully added user',]);
        $this->redirect(route('user.index'));

    }


    public function create($row)
    {

        $hasher = new PasswordHash(8, true); // Sama seperti di WordPress
        $passwordHash = $hasher->HashPassword($row['password']);

        $user = User::create(['user_login' => $row['username'], 'user_pass' => $passwordHash, 'user_nicename' => $row['first_name'], 'user_email' => $row['email'], 'user_url' => 'http://' . $row['first_name'], 'user_registered' => Carbon::now()->toDateTimeString(), 'user_activation_key' => '', 'user_status' => 0, 'display_name' => $row['first_name'] . ' ' . $row['last_name'],]);

        $wpRole = 'subscriber';

        $userMeta['nickname'] = $row['first_name'];
        $userMeta['first_name'] = $row['first_name'];
        $userMeta['last_name'] = $row['last_name'];
        $userMeta['description'] = '';
        $userMeta['rich_editing'] = true;
        $userMeta['syntax_highlighting'] = true;
        $userMeta['comment_shortcuts'] = false;
        $userMeta['admin_color'] = 'fresh';
        $userMeta['use_ssl'] = 0;
        $userMeta['show_admin_bar_front'] = true;
        $userMeta['locale'] = '';
        $userMeta['wp_capabilities'] = serialize([$wpRole => true]);

        $ur = UserRole::where('title', $row['role'])->first();
//        dd($ur,$row['role']);
        if ($ur) {
            $userMeta['user_role'] = $ur->title;
            $userMeta['user_access'] = $ur->accesses;
            $userMeta['access_tags'] = implode(';', json_decode($ur->tag_starter));
        } else {
            throw new Exception('User role not found');
        }

        $contact = Keap::contact()->createOrUpdate(['given_name' => $row['first_name'], 'family_name' => $row['last_name'], 'email_addresses' => [['email' => $row['email'], 'field' => 'EMAIL1',],], 'phone_numbers' => [['number' => $row['cell'], 'field' => 'PHONE1', // atau 'MOBILE' tergantung kebutuhan
        ],], 'custom_fields' => [['id' => '96', 'content' => $row['email']], ['id' => '98', 'content' => $row['password']],],]);
//        $userMeta['keap_contact_id'] = $contact['id'];

        if (str_contains($ur->accesses, 'keap')) {
//            $contact = Keap::contact()->createOrUpdate(['given_name' => $this->first_name, 'family_name' => $this->last_name, 'email_addresses' => [['email' => $this->email, 'field' => 'EMAIL1',],],]);
            $userMeta['keap_contact_id'] = $contact['id'];
            $userMeta['keap_status'] = true;
            Keap::contact()->tag($contact['id'], json_decode($ur->tag_starter));
        } else {
            $userMeta['keap_status'] = false;
        }

        if ($this->keapMailSend) {
            Keap::contact()->tag($contact['id'], [1942]);
        }

        $userMeta['wp_user_level'] = 0;
        $userMeta['dismissed_wp_pointers'] = '';

//        if ($this->companyId != null) {
//            $userMeta['company'] = $this->companyId;
//            CompanyEmployee::create(['user_id' => $user->ID, 'company_id' => $this->companyId]);
//        }

        foreach ($userMeta as $key => $meta) {
            WpUserMeta::create(['meta_key' => $key, 'user_id' => $user->ID, 'meta_value' => $meta]);
        }

//        $this->dispatch('swal:alert', data: ['icon' => 'success', 'title' => 'Successfully added user',]);
//        if ($this->companyId != null) {
//            $this->redirect(route('company.show', $this->companyId));
//        } else {
//            $this->redirect(route('user.index'));
//        }
    }


    public function render()
    {
        return view('livewire.import-user');
    }
}
