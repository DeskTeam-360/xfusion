<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full px-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-dark dark:text-white">LLM Knowledge</h1>
                    <p class="mt-1 max-w-prose text-sm text-dark/75 dark:text-darklink">
                        Kelola pengetahuan perusahaan (<code class="text-xs">post_type: xfusion_knowledge</code> di WordPress).
                        Perubahan disinkronkan ke XFusion-llm (ChromaDB) untuk evaluasi ujian AI.
                    </p>
                </div>
                <a href="{{ route('xfusion-knowledge.create') }}" class="btn btn-primary">Tambah knowledge</a>
            </div>
            <livewire:table.master name="XfusionKnowledgeTable" sort-field="ID"/>
        </div>
    </div>
</x-admin-layout>
