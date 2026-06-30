<x-admin-layout>
    <x-admin.page
        title="Company groups"
        description="Organize employees into groups within a company. Assign member or leader roles."
    >
        <x-slot:actions>
            <a href="{{ route('company-group.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Create group
            </a>
        </x-slot:actions>

        <livewire:table.master name="CompanyGroupTable"/>
    </x-admin.page>
</x-admin-layout>
