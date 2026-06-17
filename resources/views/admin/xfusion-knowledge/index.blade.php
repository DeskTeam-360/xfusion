<x-admin-layout>
    <x-admin.page
        title="LLM Knowledge"
        description="Kelola pengetahuan perusahaan (post_type: xfusion_knowledge di WordPress). Perubahan disinkronkan ke XFusion-llm untuk evaluasi ujian AI."
    >
        <x-slot:actions>
            <a href="{{ route('xfusion-knowledge.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Tambah knowledge
            </a>
        </x-slot:actions>

        <livewire:table.master name="XfusionKnowledgeTable" sort-field="ID"/>
    </x-admin.page>
</x-admin-layout>
