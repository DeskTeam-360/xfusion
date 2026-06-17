<x-admin-layout>
    <x-admin.page
        title="LLM Knowledge"
        description="Manage company knowledge (post_type: xfusion_knowledge in WordPress). Changes sync to XFusion-llm for AI exam evaluation."
    >
        <x-slot:actions>
            <a href="{{ route('xfusion-knowledge.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Add knowledge
            </a>
        </x-slot:actions>

        <livewire:table.master name="XfusionKnowledgeTable" sort-field="ID"/>
    </x-admin.page>
</x-admin-layout>
