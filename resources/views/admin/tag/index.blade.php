<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('tag.create') }}" class="btn btn-primary">Create tag</a>
            <livewire:table.master name="Tag"/>
        </div>
    </div>
</x-admin-layout>
