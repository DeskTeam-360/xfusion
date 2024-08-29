<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('course-schedule-generate-create') }}" class="btn btn-primary">Create Schedule Generate</a>
            <livewire:table.master name="CourseScheduleGenerate"/>
        </div>
    </div>
</x-admin-layout>
