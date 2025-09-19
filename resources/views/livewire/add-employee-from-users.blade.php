<div>
    <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Add Existing Users to Company</h3>
        <p class="text-gray-600 mb-4">Select users who are not currently affiliated with any company to add them to this company.</p>
    </div>

    <!-- Search functionality -->
    <div class="mb-4">
        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
        <input 
            type="text" 
            id="search"
            wire:model.live="searchTerm" 
            placeholder="Search by name or email..."
            class="form-control"
        >
    </div>

    @if(count($availableUsers) > 0)
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Available Users ({{ count($availableUsers) }} found)</label>
            
            <!-- Select2 Component -->
            <x-select2 
                title="Select Users" 
                model="selectedUsers" 
                :options="$availableUsers"
                :isSelected="$selectedUsers"
                class="w-full"
            />
        </div>

        <div class="mb-4">
            <p class="text-sm text-gray-600">
                <strong>Selected Users:</strong> 
                @if(count($selectedUsers) > 0)
                    {{ count($selectedUsers) }} user(s) selected
                @else
                    No users selected
                @endif
            </p>
        </div>

        <div class="flex gap-3">
            <button 
                wire:click="addSelectedUsers" 
                class="btn btn-primary"
                @if(empty($selectedUsers)) disabled @endif
            >
                Add Selected Users to Company
            </button>
            
            <button 
                wire:click="$set('selectedUsers', [])" 
                class="btn btn-secondary"
                @if(empty($selectedUsers)) disabled @endif
            >
                Clear Selection
            </button>
        </div>
    @else
        <div class="text-center py-8">
            <div class="text-gray-500 mb-4">
                @if($searchTerm)
                    <p>No users found matching "{{ $searchTerm }}" who are not affiliated with any company.</p>
                @else
                    <p>No users available to add. All users are already affiliated with companies.</p>
                @endif
            </div>
            @if($searchTerm)
                <button wire:click="$set('searchTerm', '')" class="btn btn-secondary">
                    Clear Search
                </button>
            @endif
        </div>
    @endif

</div>
