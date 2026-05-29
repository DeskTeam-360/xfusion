<x-admin-layout>
    <div class="container full-container py-5">
        <div class="mb-6 px-5">
            <a href="{{ route('xfusion-knowledge.index') }}" class="btn-sm btn">Kembali</a>
        </div>
        <div class="px-5">
            <h1 class="mb-6 text-2xl font-semibold text-dark dark:text-white">Tambah knowledge LLM</h1>
            <livewire:form.xfusion-knowledge-form/>
        </div>
    </div>
</x-admin-layout>
