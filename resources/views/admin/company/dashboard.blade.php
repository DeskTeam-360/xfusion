<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-light">
                Company dashboard
            </h1>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                Export and participation views for this company’s employees.
            </p>
            <livewire:export-result
                wire:key="company-export-{{ $id }}"
                :locked-company-id="$id"
                :is-company-dashboard="true"
            />
        </div>
    </div>
</x-admin-layout>
