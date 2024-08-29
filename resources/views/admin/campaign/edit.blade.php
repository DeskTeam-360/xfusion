<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
{{--            @php dd($id); @endphp--}}
            <livewire:form.campaign action="update" :data-id="$id" for="user"/>
        </div>
    </div>
</x-admin-layout>
