<x-admin-layout>
    <x-admin.page title="Course List" description="Map course titles, forms, and Keap tags for the LMS flow.">
        <x-slot:actions>
            <a href="{{ route('course-title.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Create course list
            </a>
        </x-slot:actions>

        <livewire:table.master name="CourseList"/>
    </x-admin.page>
</x-admin-layout>
