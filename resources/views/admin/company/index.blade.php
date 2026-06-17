<x-admin-layout>
    <x-admin.page
        title="Companies"
        description="Manage company profiles, employees, and dashboards."
    >
        <x-slot:actions>
            <a href="{{ route('company.create') }}" class="btn btn-primary shrink-0">
                <i class="ti ti-plus me-1"></i> Create new company
            </a>
        </x-slot:actions>

        <livewire:table.master name="Company"/>
    </x-admin.page>
</x-admin-layout>
