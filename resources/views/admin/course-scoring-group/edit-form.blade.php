<x-admin-layout>
    <x-admin.page title="Edit form fields" padded>
        <x-slot:actions>
            <a href="{{ route('course-scoring-group.edit', $id) }}" class="btn-outline-primary inline-flex items-center gap-1.5">
                <i class="ti ti-arrow-left text-base" aria-hidden="true"></i>
                Back to group
            </a>
        </x-slot:actions>

        <livewire:form.course-scoring-group-form-fields :group-id="(int) $id" :form-id="(int) $formId"/>
    </x-admin.page>
</x-admin-layout>
