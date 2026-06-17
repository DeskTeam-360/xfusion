<x-admin-layout>
    @php($check = \App\Models\Company::whereKey($companyId)->limit(1)->exists())
    <x-admin.page title="Company dashboard" description="Overview of employees, course progress, and company metrics.">
        @if($check)
            <x-slot:actions>
                <a href="{{ route('company.show-detail', $companyId) }}" class="btn btn-primary shrink-0">
                    <i class="ti ti-building-community me-1"></i> Company detail
                </a>
            </x-slot:actions>
        @endif

        @unless($check)
            <p class="text-error">Company not found.</p>
        @else
            <livewire:dashboard-company/>
        @endunless
    </x-admin.page>
</x-admin-layout>
