<x-admin-layout>
    <x-admin.page
        title="1-on-1 — new pair"
        description="Select a company, then pick the leader and the employee for this 1-on-1 relationship."
        padded
    >
        <x-slot:actions>
            <a href="{{ route('one-on-one.index') }}" class="btn-outline-primary inline-flex items-center gap-1.5">
                <i class="ti ti-arrow-left text-base" aria-hidden="true"></i>
                Back to list
            </a>
        </x-slot:actions>

        <livewire:form.one-on-one/>
    </x-admin.page>
</x-admin-layout>
