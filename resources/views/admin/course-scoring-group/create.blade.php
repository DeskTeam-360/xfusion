<x-admin-layout>
    <div class="container full-container py-5">
        <div class="mb-6 px-5">
            <a href="{{ route('course-scoring-group.index') }}" class="btn-sm">Back</a>
        </div>
        <div class="px-5">
            <h1 class="mb-6 text-2xl font-semibold dark:text-white">Course scoring — new group</h1>
            <p class="mb-6 max-w-prose text-sm text-muted dark:text-darklink">
                First save title and description. You will land on edit to search Gravity Forms and choose fields (repeat “+ Form” for more forms).
            </p>
            <livewire:form.course-scoring-group/>
        </div>
    </div>
</x-admin-layout>
