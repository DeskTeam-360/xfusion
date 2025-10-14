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
    public $errorMessage = '';
    public $successMessage = '';
    public $importErrors = [];
    public $importSuccess = [];

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
    
    protected $listeners = ["updatedFile" => "updatedFile"];


    public function updatedFile()
    {
        // Clear previous messages
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->importErrors = [];
        $this->importSuccess = [];
        
        try {
            $this->validate([
                'file' => 'required|file|mimes:csv,txt|max:10048'
            ], [
                'file.required' => 'Please select a CSV file to upload.',
                'file.file' => 'The uploaded file is not valid.',
                'file.mimes' => 'The file must be a CSV or TXT file.',
                'file.max' => 'The file size must not exceed 2MB.'
            ]);
            
            $this->parseCsv();
            $this->successMessage = 'CSV file uploaded and parsed successfully. Review the data below before importing.';
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = 'Validation Error: ' . implode(', ', $e->validator->errors()->all());
        } catch (\Exception $e) {
            $this->errorMessage = 'Error processing file: ' . $e->getMessage();
        }
    }

    public function parseCsv()
    {
        try {
            $path = $this->file->getRealPath();
            
            if (!file_exists($path)) {
                throw new \Exception('File not found or could not be accessed.');
            }
            
            $fileContent = file($path);
            if ($fileContent === false) {
                throw new \Exception('Could not read the file content.');
            }
            
            if (empty($fileContent)) {
                throw new \Exception('The CSV file is empty.');
            }
            
            $data = array_map('str_getcsv', $fileContent);
            
            if (empty($data)) {
                throw new \Exception('No data found in the CSV file.');
            }

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

            if (empty($this->rows)) {
                throw new \Exception('No valid data rows found in the CSV file.');
            }

            $headers = array_map('trim', $data[0]); // First row = header
            
            if (empty($headers)) {
                throw new \Exception('No headers found in the CSV file.');
            }

            $this->users = [];
            $dataRows = array_slice($data, 1);

            foreach ($dataRows as $index => $row) {
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
                foreach ($headers as $headerIndex => $header) {
                    $key = strtolower(str_replace(' ', '_', $header));
                    $userData[$key] = $paddedRow[$headerIndex] ?? '';
                }

                $this->users[] = $userData;
            }
            
        } catch (\Exception $e) {
            throw new \Exception('CSV parsing failed: ' . $e->getMessage());
        }
    }

    public function import()
    {
        // Clear previous messages
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->importErrors = [];
        $this->importSuccess = [];
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($this->users as $index => $row) {
            try {
                $this->create($row);
                $this->importSuccess[] = "Row " . ($index + 2) . ": Successfully created user " . ($row['email'] ?? 'Unknown');
                $successCount++;
            } catch (\Exception $e) {
                $this->importErrors[] = "Row " . ($index + 2) . ": " . $e->getMessage() . " (Email: " . ($row['email'] ?? 'Unknown') . ")";
                $errorCount++;
            }
        }
        
        if ($errorCount > 0 && $successCount > 0) {
            $this->errorMessage = "Import completed with errors. {$successCount} users created successfully, {$errorCount} failed.";
        } elseif ($errorCount > 0) {
            $this->errorMessage = "Import failed. All {$errorCount} users failed to import.";
        } else {
            $this->successMessage = "Import successful! All {$successCount} users have been added to the company.";
        }
        
        // Show appropriate alert
        if ($errorCount > 0) {
            $this->dispatch('swal:alert', data: [
                'icon'  => 'warning',
                'title' => 'Import completed with errors',
                'text'  => "{$successCount} users created successfully, {$errorCount} failed. Check the error details below.",
            ]);
        } else {
            $this->dispatch('swal:alert', data: [
                'icon'  => 'success',
                'title' => 'Successfully added users to company',
                'text'  => "All {$successCount} users have been imported successfully.",
            ]);
        }
        
        // Only redirect if all imports were successful
        if ($errorCount === 0) {
            $this->redirect(route('company.show', $this->companyId));
        }
    }

    public function create($row)
    {
        try {
            // Validate required fields
            $requiredFields = ['username', 'password', 'first_name', 'last_name', 'email', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($row[$field])) {
                    throw new \Exception("Required field '{$field}' is missing or empty");
                }
            }

            // Check if user already exists
            $existingUser = User::where('user_email', $row['email'])->first();
            if ($existingUser) {
                throw new \Exception("User with email '{$row['email']}' already exists");
            }

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

            if (!$user) {
                throw new \Exception("Failed to create user record");
            }

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
                throw new \Exception("User role '{$row['role']}' not found");
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
            $companyEmployee = CompanyEmployee::create([
                'user_id' => $user->ID,
                'company_id' => $this->companyId
            ]);

            if (!$companyEmployee) {
                throw new \Exception("Failed to create company employee association");
            }

            // Handle Keap integration with error handling
            $contact = null;
            try {
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
            } catch (\Exception $e) {
                // Log Keap error but don't fail the entire import
                \Log::warning("Keap API error for user {$row['email']}: " . $e->getMessage());
            }

            if (isset($row['keap_integration_(0|1)']) && $row['keap_integration_(0|1)'] == 1) {
                if ($contact && isset($contact['id'])) {
                    $userMeta['keap_contact_id'] = $contact['id'];
                    $userMeta['keap_status'] = true;
                    $userMeta['access_tags'] = $userMeta['access_tags'] . ';1958';
                    
                    // Try to tag the contact
                    try {
                        Keap::contact()->tag($contact['id'], explode(';', $userMeta['access_tags']));
                    } catch (\Exception $e) {
                        \Log::warning("Failed to tag Keap contact for user {$row['email']}: " . $e->getMessage());
                    }
                } else {
                    $userMeta['keap_status'] = false;
                }
            } else {
                $userMeta['keap_status'] = false;
            }

            if ($this->keapMailSend) {
                $userMeta['access_tags'] = $userMeta['access_tags'] . ';1942';
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

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.import-user-company');
    }
}
