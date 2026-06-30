<x-admin-layout>
    <x-admin.page
        title="Company group — new"
        description="Select a company, then save title and description. You will land on edit to add members."
        padded
    >
        <x-slot:actions>
            <a href="{{ route('company-group.index') }}" class="btn-outline-primary inline-flex items-center gap-1.5">
                <i class="ti ti-arrow-left text-base" aria-hidden="true"></i>
                Back to list
            </a>
        </x-slot:actions>

        <livewire:form.company-group/>
    </x-admin.page>
</x-admin-layout>
