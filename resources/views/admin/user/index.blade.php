<x-admin-layout>
    <x-admin.page
        title="Users"
        description="Manage user accounts, access, and integrations."
    >
        <x-slot:actions>
            <a href="{{ route('user.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-user-plus me-1"></i> Create new user
            </a>
            <a href="{{ route('to-import-user') }}" class="btn btn-light-success text-nowrap">
                <i class="ti ti-file-import me-1"></i> Import CSV
            </a>
        </x-slot:actions>

        @include('admin.user.partials.keap-export-and-refresh')

        <livewire:table.master name="User"/>
    </x-admin.page>
</x-admin-layout>
