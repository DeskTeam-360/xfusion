<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
{{--            <a href="{{ route('company.schedule-create',$id) }}" class="btn btn-primary">Create Schedule</a>--}}
            <livewire:table.master name="ScheduleEmployeeAll" :param1="$id"/>
        </div>
    </div>
</x-admin-layout>
