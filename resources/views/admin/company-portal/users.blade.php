<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <div class="mb-4 items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-light">Company users</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Search and manage users linked to your company only.</p>
                </div>
                <a href="{{ route('company.portal.users.create') }}" class="btn btn-primary shrink-0">Tambah user</a>
            </div>
            <livewire:table.master wire:key="company-portal-users-{{ $companyId }}" name="User" :param1="$companyId"/>
        </div>
    </div>
</x-admin-layout>
