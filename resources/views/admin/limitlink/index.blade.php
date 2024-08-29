<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('course-title.create') }}" class="btn btn-primary">Create course list</a>
            <livewire:table.master name="CourseList"/>
        </div>
    </div>
</x-admin-layout>
