<x-admin-layout>
    <x-admin.page title="Edit company group" padded>
        <x-slot:actions>
            <a href="{{ route('company-group.index') }}" class="btn-outline-primary inline-flex items-center gap-1.5">
                <i class="ti ti-arrow-left text-base" aria-hidden="true"></i>
                Back to list
            </a>
        </x-slot:actions>

        <livewire:form.company-group :data-id="$id"/>
    </x-admin.page>
</x-admin-layout>
