<x-admin-layout>
    <x-admin.page
        title="Course scoring — new group"
        description="First save title and description. You will land on edit to search Gravity Forms and choose fields (repeat “+ Form” for more forms)."
        padded
    >
        <x-slot:actions>
            <a href="{{ route('course-scoring-group.index') }}" class="btn-outline-primary inline-flex items-center gap-1.5">
                <i class="ti ti-arrow-left text-base" aria-hidden="true"></i>
                Back to list
            </a>
        </x-slot:actions>

        <livewire:form.course-scoring-group/>
    </x-admin.page>
</x-admin-layout>
