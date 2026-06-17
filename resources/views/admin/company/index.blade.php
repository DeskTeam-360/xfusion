<x-admin-layout>
    <div class="container full-container py-5 px-5">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Companies</h1>
                <p class="mt-1 text-sm text-muted dark:text-darklink">Manage company profiles, employees, and dashboards.</p>
            </div>
            <a href="{{ route('company.create') }}" class="btn btn-primary shrink-0">
                <i class="ti ti-plus me-1"></i> Create new company
            </a>
        </div>
        <livewire:table.master name="Company"/>
    </div>
</x-admin-layout>
