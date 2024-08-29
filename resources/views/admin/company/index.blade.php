<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('company.create') }}" class="btn btn-primary">Create new company</a>
            <livewire:table.master name="Company"/>
        </div>
    </div>
</x-admin-layout>
