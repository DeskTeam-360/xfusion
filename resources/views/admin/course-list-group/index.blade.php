<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('course-group.create') }}" class="btn btn-primary">Create course group</a>
            <livewire:table.master name="CourseListGroup"/>
        </div>
    </div>
</x-admin-layout>
