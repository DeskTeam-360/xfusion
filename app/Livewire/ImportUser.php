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
        dd($this->file,);
    }

    public function updatedFile()
    {
        $this->validate(['file' => 'required|file|mimes:csv,txt|max:2048',],);

        $this->parseCsv();
    }

    public function parseCsv()
    {
        $path = $this->file->getRealPath();
        $data = array_map('str_getcsv', file($path,),);
        $this->rows = $data;

        $headers = array_map('trim', $data[0],); // Baris pertama = header

        $this->users = collect(array_slice($data, 1,),)->map(function ($row,) use ($headers,) {
            return array_combine(array_map(fn($h,) => strtolower(str_replace(' ', '_', $h,),), $headers,), $row,);
        },)->toArray();
    }

    public function import()
    {
        foreach ($this->users as $row) {
            $this->create($row,);
        }
        $this->dispatch('swal:alert', data: [
            'icon'  => 'success',
            'title' => 'Successfully added user',
        ],);
        $this->redirect(route('user.index',),);

    }


    public function create($row,)
    {
//        dd($row,);
        $hasher = new PasswordHash(8, true,); // Sama seperti di WordPress
        $passwordHash = $hasher->HashPassword($row['password'],);

        $user = User::create([
            'user_login'          => $row['username'],
            'user_pass'           => $passwordHash,
            'user_nicename'       => $row['first_name'],
            'user_email'          => $row['email'],
            'user_url'            => 'http://' . $row['first_name'],
            'user_registered'     => Carbon::now()->toDateTimeString(),
            'user_activation_key' => '',
            'user_status'         => 0,
            'display_name'        => $row['first_name'] . ' ' . $row['last_name'],
        ],);

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
        $userMeta['wp_capabilities'] = serialize([$wpRole => true],);

        $ur = UserRole::where('title', $row['role'],)->first();
//        dd($ur,$row['role']);
        if ($ur) {
            $userMeta['user_role'] = $ur->title;
            $userMeta['user_access'] = $ur->accesses;
            $userMeta['access_tags'] = implode(';', json_decode($ur->tag_starter,),);
            $userMeta['access_tags'] = $userMeta['access_tags'] . ';1960';
        } else {
            throw new Exception('User role not found',);
        }

        if ($row['skip_revitalize(0|1)'] == 1) {
            if (str_contains($ur->accesses, '"sustain"',)) {
                $userMeta['access_tags'] = $userMeta['access_tags'] . ';322';
            }
            if (str_contains($ur->accesses, '"transform"',)) {
                $userMeta['access_tags'] = $userMeta['access_tags'] . ';1012';
            }
        }


        $contact = Keap::contact()->createOrUpdate([
            'given_name'      => $row['first_name'],
            'family_name'     => $row['last_name'],
            'email_addresses' => [
                [
                    'email' => $row['email'],
                    'field' => 'EMAIL1',
                ],
            ],
            'phone_numbers'   => [
                [
                    'number' => $row['cell'],
                    'field'  => 'PHONE1',
                    // atau 'MOBILE' tergantung kebutuhan
                ],
            ],
            'custom_fields'   => [
                [
                    'id'      => '96',
                    'content' => $row['email'],
                ],
                [
                    'id'      => '98',
                    'content' => $row['password'],
                ],
            ],
        ],);

        if ($row['keap_integration_(0|1)']==1) {
            $userMeta['keap_contact_id'] = $contact['id'];
            $userMeta['keap_status'] = true;
            $userMeta['access_tags'] = $userMeta['access_tags'] . ';1958';
        } else {
            $userMeta['keap_status'] = false;
        }

        if ($this->keapMailSend) {
            $userMeta['access_tags'] = $userMeta['access_tags'] . ';1942';
        }

        if ($contact['id']) {
            Keap::contact()->tag($contact['id'], explode(';', $userMeta['access_tags']),);
        }


        $userMeta['wp_user_level'] = 0;
        $userMeta['dismissed_wp_pointers'] = '';


        foreach ($userMeta as $key => $meta) {
            WpUserMeta::create([
                'meta_key'   => $key,
                'user_id'    => $user->ID,
                'meta_value' => $meta,
            ],);
        }


    }


    public function render()
    {
        return view('livewire.import-user',);
    }
}
