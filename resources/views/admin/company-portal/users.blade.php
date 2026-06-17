<x-admin-layout>
    <x-admin.page
        title="Company users"
        description="Search and manage users linked to your company only."
    >
        <x-slot:actions>
            <a href="{{ route('company.portal.users.create') }}" class="btn btn-primary shrink-0">
                <i class="ti ti-user-plus me-1"></i> Add user
            </a>
        </x-slot:actions>

        <livewire:table.master wire:key="company-portal-users-{{ $companyId }}" name="User" :param1="$companyId"/>
    </x-admin.page>
</x-admin-layout>
