<x-admin-layout>
    <div class="container full-container py-5">
        <div class="mb-6 px-5 flex flex-wrap gap-3">
            <a href="{{ route('course-scoring-group.index') }}" class="btn-sm">Back to list</a>
        </div>
        <div class="px-5">
            <h1 class="mb-6 text-2xl font-semibold text-dark dark:text-white">Edit course scoring group</h1>
            <livewire:form.course-scoring-group :data-id="$id"/>
        </div>
    </div>
</x-admin-layout>
