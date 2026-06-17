<x-admin-layout>
    <div class="container full-container py-5 px-5">
        <h1 class="mb-2 text-2xl font-semibold">Activity Log</h1>
        <p class="mb-6 max-w-2xl text-muted dark:text-darklink">
            Recent system activity and user events.
        </p>
        <div class="card">
            <div class="card-body p-6">
                <livewire:table.master name="WpViewAllLog"/>
            </div>
        </div>
    </div>
</x-admin-layout>
