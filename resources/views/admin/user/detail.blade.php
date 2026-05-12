<x-admin-layout>
    <div class="container full-container py-5">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <h1 class="text-2xl font-semibold">User detail</h1>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('user.index') }}" class="btn btn-ghost btn-sm">Back to list</a>
                <a href="{{ route('user.course', $user) }}" class="btn btn-outline btn-sm">Activity check</a>
            </div>
        </div>

        <livewire:user-detail :user="$user" />
    </div>
</x-admin-layout>
