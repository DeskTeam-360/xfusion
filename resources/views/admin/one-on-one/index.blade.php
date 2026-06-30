<x-admin-layout>
    <x-admin.page
        title="1-on-1 Alignment Capture"
        description="Manage leader–employee pairs and their monthly conversation history."
    >
        <x-slot:actions>
            <a href="{{ route('one-on-one.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Pair leader & employee
            </a>
        </x-slot:actions>

        <livewire:table.master name="OneOnOneTable"/>
    </x-admin.page>
</x-admin-layout>
