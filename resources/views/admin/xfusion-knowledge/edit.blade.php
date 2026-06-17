<x-admin-layout>
    <div class="container full-container py-5">
        <div class="mb-6 px-5 flex flex-wrap gap-3">
            <a href="{{ route('xfusion-knowledge.index') }}" class="btn-sm btn">Back to list</a>
        </div>
        <div class="px-5">
            <h1 class="mb-2 text-2xl font-semibold text-dark dark:text-white">Edit LLM knowledge</h1>
            <p class="mb-6 text-sm text-dark/75 dark:text-darklink">WordPress post ID: <strong>#{{ $id }}</strong></p>
            <livewire:form.xfusion-knowledge-form :data-id="$id"/>
        </div>
    </div>
</x-admin-layout>
