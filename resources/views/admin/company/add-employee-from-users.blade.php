<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <div class="mb-4">
                <a href="{{ route('company.show', $id) }}" class="btn btn-secondary mb-3">
                    ← Back to Company
                </a>
            </div>
            
            <livewire:add-employee-from-users :companyId="$id"/>
        </div>
    </div>
</x-admin-layout>
