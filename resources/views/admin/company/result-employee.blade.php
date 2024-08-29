<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            @if($id)
                <a href="{{ route('company.schedule-create',$id) }}" class="btn btn-primary">Create Schedule</a>
            @endif

            <livewire:table.master name="WpGfEntry" :param1="$userID"/>
        </div>
    </div>
</x-admin-layout>
