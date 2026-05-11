<x-admin-layout>
    <div class="container full-container py-5">
        <div class="px-5 text-3xl mb-4">Company dashboard</div>
        @php($check = \App\Models\Company::whereKey($companyId)->limit(1)->exists())
        <div class="px-5 py-5">
            @unless($check)
                <p class="text-error">Company not found.</p>
            @else
                <livewire:dashboard-company/>
            @endunless
        </div>
    </div>
</x-admin-layout>
