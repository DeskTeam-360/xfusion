<x-admin-layout>
    @php($check = \App\Models\Company::whereKey($companyId)->limit(1)->exists())
    <div class="container full-container py-5">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 mb-4">
            <div class="text-3xl">Company dashboard</div>
            @if($check)
                <a href="{{ route('company.show-detail', $companyId) }}" class="btn btn-primary shrink-0">
                    Company detail
                </a>
            @endif
        </div>
        <div class="px-5 py-5">
            @unless($check)
                <p class="text-error">Company not found.</p>
            @else
                <livewire:dashboard-company/>
            @endunless
        </div>
    </div>
</x-admin-layout>
