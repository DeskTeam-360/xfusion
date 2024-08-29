<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
{{--            <livewire:form.user action="create"/>--}}
            <livewire:form.user action="update" :data-id="$id"/>
        </div>
{{--        <livewire:testing/>--}}
    </div>
</x-admin-layout>
