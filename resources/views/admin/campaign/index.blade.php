<x-admin-layout>
    <x-admin.page title="Campaigns" description="Create and manage email campaigns via Keap.">
        <x-slot:actions>
            <a href="{{ route('campaign.create') }}" class="btn btn-primary text-nowrap">
                <i class="ti ti-plus me-1"></i> Create new campaign
            </a>
        </x-slot:actions>

        <livewire:table.master name="Campaign"/>
    </x-admin.page>
</x-admin-layout>
