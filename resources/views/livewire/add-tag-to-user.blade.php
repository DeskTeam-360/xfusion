<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Access to User</h3>
    
    @if(count($availableTags) > 0)
        <form wire:submit.prevent="addTags">
            <div class="mb-4">
                <x-select2 
                    title="" 
                    model="tags" 
                    :options="$availableTags"
                    :isSelected="$tags"
                    class="w-full"
                />
                @error('tags')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex justify-end">
                <button 
                    type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200"
                    wire:loading.attr="disabled"
                    wire:target="addTags"
                >
                    <span wire:loading.remove wire:target="addTags">Add Selected Access</span>
                    <span wire:loading wire:target="addTags">Adding Access...</span>
                </button>
            </div>
        </form>
    @else
        <div class="text-center py-8">
            <div class="text-gray-500 mb-2">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-1">No Available Access</h3>
            <p class="text-gray-500">This user already has all available access assigned.</p>
            <div class="mt-4">
                <a href="{{ route('tag.create') }}" class="text-blue-600 hover:text-blue-500 font-medium">
                    Create New Access
                </a>
            </div>
        </div>
    @endif
</div>
