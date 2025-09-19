<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\CompanyEmployee;
use App\Models\WpUserMeta;
use Livewire\Component;

class AddEmployeeFromUsers extends Component
{
    public $companyId;
    public $selectedUsers = [];
    public $availableUsers = [];
    public $searchTerm = '';

    public function mount($companyId)
    {
        $this->companyId = $companyId;
        $this->loadAvailableUsers();
    }

    public function loadAvailableUsers()
    {
        // Get users who are not affiliated with any company
        $this->availableUsers = User::whereDoesntHave('companyEmployee')
            ->where(function($query) {
                $query->where('user_login', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('user_email', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('display_name', 'like', '%' . $this->searchTerm . '%');
            })
            ->orderBy('display_name')
            ->get()
            ->map(function($user) {
                return [
                    'value' => $user->ID,
                    'title' => $user->display_name . ' (' . $user->user_email . ')',
                    'user' => $user
                ];
            })
            ->toArray();
    }

    public function updatedSearchTerm()
    {
        $this->loadAvailableUsers();
    }

    public function addSelectedUsers()
    {
        if (empty($this->selectedUsers)) {
            $this->dispatch('swal:alert', data: [
                'icon'  => 'warning',
                'title' => 'Please select at least one user',
            ]);
            return;
        }

        $addedCount = 0;
        foreach ($this->selectedUsers as $userId) {
            // Check if user is already associated with this company
            $existingEmployee = CompanyEmployee::where('user_id', $userId)
                ->where('company_id', $this->companyId)
                ->first();

            if (!$existingEmployee) {
                // Create company employee relationship
                CompanyEmployee::create([
                    'user_id' => $userId,
                    'company_id' => $this->companyId
                ]);

                // Update user meta to include company
                $this->updateOrCreateMeta($userId, 'company', $this->companyId);
                $addedCount++;
            }
        }

        if ($addedCount > 0) {
            $this->dispatch('swal:alert', data: [
                'icon'  => 'success',
                'title' => "Successfully added {$addedCount} user(s) to the company",
            ]);
            
            // Clear selection and reload available users
            $this->selectedUsers = [];
            $this->loadAvailableUsers();
        } else {
            $this->dispatch('swal:alert', data: [
                'icon'  => 'info',
                'title' => 'No new users were added (they may already be associated with this company)',
            ]);
        }
    }

    private function updateOrCreateMeta($userId, $key, $value)
    {
        WpUserMeta::updateOrCreate(
            [
                'user_id' => $userId,
                'meta_key' => $key
            ],
            [
                'meta_value' => $value
            ]
        );
    }

    public function render()
    {
        return view('livewire.add-employee-from-users');
    }
}
