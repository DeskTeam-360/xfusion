<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <a href="{{ route('company.portal.users') }}" class="btn-sm shrink-0">Back to users</a>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-light mb-4">Add user</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">The new account will be linked to your company as an employee.</p>
            <livewire:form.user action="create" :company-id="$companyId"/>
        </div>
    </div>
</x-admin-layout>
