<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('company.add-employee',$id) }}" class="btn btn-primary">Add Employee</a>
            <a href="{{ route('export-user-company',$id) }}" class="btn btn-secondary">Export to CSV</a>
            <livewire:table.master name="User" :param1="$id"/>
        </div>
    </div>
</x-admin-layout>
