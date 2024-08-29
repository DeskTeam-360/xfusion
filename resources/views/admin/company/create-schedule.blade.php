<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
{{--            <a href="{{ route('company.add-employee',$id) }}" class="btn btn-primary">Create Schedule</a>--}}
{{--            <livewire:table.master name="ScheduleEmployee" :param1="$id"/>--}}
            <livewire:form.schedule action="create" :company-id="$id"/>
        </div>
    </div>
</x-admin-layout>
