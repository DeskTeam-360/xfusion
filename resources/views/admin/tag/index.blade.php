<x-admin-layout>
    <x-admin.page title="Tags" description="Manage Keap tags and label mappings.">
        <x-slot:actions>
            <a href="{{ route('tag.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Create tag
            </a>
        </x-slot:actions>

        <livewire:table.master name="Tag"/>
    </x-admin.page>
</x-admin-layout>
