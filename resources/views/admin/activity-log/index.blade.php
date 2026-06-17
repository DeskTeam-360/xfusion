<x-admin-layout>
    <x-admin.page
        title="Activity Log"
        description="Recent system activity and user events."
    >
        <div class="card">
            <div class="card-body p-6">
                <livewire:table.master name="WpViewAllLog"/>
            </div>
        </div>
    </x-admin.page>
</x-admin-layout>
