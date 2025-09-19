<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserRole;
use App\Models\WpUserMeta;
use App\Models\CompanyEmployee;
use Carbon\Carbon;
use Exception;
use Hautelook\Phpass\PasswordHash;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportUserCompany extends Component
{
    public $file;
    public $rows = [];
    public $users = [];
    public $keapMailSend;
    public $companyId;

    use WithFileUploads;

    public $duplicateEmail;

    public function mount($companyId)
    {
        $this->companyId = $companyId;
    }

    public function checkCsv()
    {
        dd($this->file);
    }

    public function updatedFile()
    {
        $this->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $this->parseCsv();
    }

    public function parseCsv()
    {
        $path = $this->file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        // Filter out blank rows from the display data
        $this->rows = [];
        foreach ($data as $row) {
            // Check if row has any content
            $hasContent = false;
            foreach ($row as $cell) {
                if (!empty(trim($cell))) {
                    $hasContent = true;
                    break;
                }
            }

            // Only add rows with content
            if ($hasContent) {
                $this->rows[] = $row;
            }
        }

        $headers = array_map('trim', $data[0]); // First row = header

        $this->users = [];
        $dataRows = array_slice($data, 1);

        foreach ($dataRows as $row) {
            // Check if row has any content
            $hasContent = false;
            foreach ($row as $cell) {
                if (!empty(trim($cell))) {
                    $hasContent = true;
                    break;
                }
            }

            // Skip blank rows
            if (!$hasContent) {
                continue;
            }

            // Ensure row has same number of elements as headers by padding with empty strings
            $paddedRow = array_pad($row, count($headers), '');

            // Create associative array with headers as keys
            $userData = [];
            foreach ($headers as $index => $header) {
                $key = strtolower(str_replace(' ', '_', $header));
                $userData[$key] = $paddedRow[$index] ?? '';
            }

            $this->users[] = $userData;
        }
    }

    public function import()
    {
        foreach ($this->users as $row) {
            $this->create($row);
        }
        $this->dispatch('swal:alert', data: [
            'icon'  => 'success',
            'title' => 'Successfully added users to company',
        ]);
        $this->redirect(route('company.show', $this->companyId));
    }

    public function create($row)
    {
        $hasher = new PasswordHash(8, true); // Same as WordPress
        $passwordHash = $hasher->HashPassword($row['password']);

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
        ]);

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
        if ($ur) {
            $userMeta['user_role'] = $ur->title;
            $userMeta['user_access'] = $ur->accesses;
            $userMeta['access_tags'] = implode(';', json_decode($ur->tag_starter));
            $userMeta['access_tags'] = $userMeta['access_tags'] . ';1960';
        } else {
            throw new Exception('User role not found');
        }

        if (isset($row['skip_revitalize(0|1)']) && $row['skip_revitalize(0|1)'] == 1) {
            if (str_contains($ur->accesses, '"sustain"')) {
                $userMeta['access_tags'] = $userMeta['access_tags'] . ';322';
            }
            if (str_contains($ur->accesses, '"transform"')) {
                $userMeta['access_tags'] = $userMeta['access_tags'] . ';1012';
            }
        }

        // Add company association
        $userMeta['company'] = $this->companyId;
        CompanyEmployee::create([
            'user_id' => $user->ID,
            'company_id' => $this->companyId
        ]);

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
                    'number' => $row['cell'] ?? '',
                    'field'  => 'PHONE1',
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
        ]);

        if (isset($row['keap_integration_(0|1)']) && $row['keap_integration_(0|1)'] == 1) {
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
            Keap::contact()->tag($contact['id'], explode(';', $userMeta['access_tags']));
        }

        $userMeta['wp_user_level'] = 0;
        $userMeta['dismissed_wp_pointers'] = '';

        foreach ($userMeta as $key => $meta) {
            WpUserMeta::create([
                'meta_key'   => $key,
                'user_id'    => $user->ID,
                'meta_value' => $meta,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.import-user-company');
    }
}
