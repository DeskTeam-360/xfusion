<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
{{--            <a href="{{ route('user.create') }}" class="btn btn-primary">Create new user</a>--}}
            <livewire:table.master name="UserRole"/>
        </div>
        <div class="mb-5"></div>
    </div>
</x-admin-layout>
