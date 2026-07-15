<x-admin-layout>
    <x-admin.page
        title="Company groups"
        description="Organize employees into groups within a company. Leader + members automatically enable 1-on-1 Alignment Capture in WordPress."
    >
        <x-slot:actions>
            <a href="{{ route('company-group.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Create group
            </a>
        </x-slot:actions>

        <livewire:table.master name="CompanyGroupTable"/>
    </x-admin.page>
</x-admin-layout>
