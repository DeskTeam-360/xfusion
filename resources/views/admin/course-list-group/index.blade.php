<x-admin-layout>
    <x-admin.page title="Course Groups" description="Group course lists for reporting and organization.">
        <x-slot:actions>
            <a href="{{ route('course-group.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Create course group
            </a>
        </x-slot:actions>

        <livewire:table.master name="CourseListGroup"/>
    </x-admin.page>
</x-admin-layout>
