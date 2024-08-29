<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <livewire:form.user action="create" :company-id="$id"/>
{{--            <a href="{{ route('user.create') }}" class="btn btn-primary">Add Employee</a>--}}
{{--            <livewire:table.master name="CompanyEmployee" :param1="$id"/>--}}
        </div>
    </div>
</x-admin-layout>
