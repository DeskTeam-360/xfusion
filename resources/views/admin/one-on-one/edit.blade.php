<x-admin-layout>
    <x-admin.page title="Manage 1-on-1 pair" padded>
        <x-slot:actions>
            <a href="{{ route('one-on-one.index') }}" class="btn-outline-primary inline-flex items-center gap-1.5">
                <i class="ti ti-arrow-left text-base" aria-hidden="true"></i>
                Back to list
            </a>
        </x-slot:actions>

        <livewire:form.one-on-one :data-id="$id"/>
    </x-admin.page>
</x-admin-layout>
