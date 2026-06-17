<x-admin-layout>
    <x-admin.page
        title="User Progress Only"
        description="Users with LearnDash course progress — same tools as Users, filtered to accounts with activity data."
    >
        <x-slot:actions>
            <a href="{{ route('user.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-user-plus me-1"></i> Create new user
            </a>
            <a href="{{ route('to-import-user') }}" class="btn btn-light-success text-nowrap">
                <i class="ti ti-file-import me-1"></i> Import CSV
            </a>
            <button type="button" onclick="refreshAllUsers()" class="btn btn-light-warning text-nowrap">
                <i class="ti ti-refresh me-1"></i> Refresh all users
            </button>
        </x-slot:actions>

        @include('admin.user.partials.keap-export-and-refresh')

        <livewire:table.master name="UserProgressOnly"/>
    </x-admin.page>
</x-admin-layout>
