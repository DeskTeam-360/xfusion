<x-admin-layout>
    <x-admin.page title="Course Scoring" description="Configure scoring groups and question weights for course evaluations.">
        <x-slot:actions>
            <a href="{{ route('course-scoring-group.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Create scoring group
            </a>
        </x-slot:actions>

        <livewire:table.master name="CourseScoringGroupTable"/>
    </x-admin.page>
</x-admin-layout>
