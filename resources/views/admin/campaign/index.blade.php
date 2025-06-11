<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('campaign.create') }}" class="btn btn-primary">Create new campaign</a>
{{--            <a href="{{ route('create_company') }}" class="btn btn-primary">Create Group campaign</a>--}}
            <livewire:table.master name="Campaign"/>
        </div>
    </div>
</x-admin-layout>
