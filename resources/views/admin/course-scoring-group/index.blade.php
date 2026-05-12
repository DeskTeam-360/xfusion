<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full px-5">
            <a href="{{ route('course-scoring-group.create') }}" class="btn btn-primary mb-4">Create course scoring group</a>
            <livewire:table.master name="CourseScoringGroupTable"/>
        </div>
    </div>
</x-admin-layout>
