<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('user.create') }}" class="btn btn-primary">Create new user</a>
            <a href="{{ route('to-import-user') }}" class="btn btn-secondary">Import CSV</a>
            <a href="{{ route('export-user') }}" class="btn btn-secondary">Export to CSV</a>
            <a href="{{ route('template-download') }}" class="btn btn-success">Download template</a>
            <livewire:table.master name="User"/>
        </div>
        <div class="mb-5"></div>
    </div>
</x-admin-layout>
