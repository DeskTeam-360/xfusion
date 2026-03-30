<x-admin-layout>
    <div class="container full-container py-5 px-5">
        <h1 class="mb-6 text-2xl font-semibold">LMS topic search</h1>
        <p class="mb-6 max-w-2xl text-gray-600">
            Search LearnDash topics (<code class="rounded bg-gray-100 px-1">sfwd-topic</code>) using the
            <code class="rounded bg-gray-100 px-1">_search_index</code> post meta built in WordPress.
            Set <code class="rounded bg-gray-100 px-1">WORDPRESS_URL</code> in <code class="rounded bg-gray-100 px-1">.env</code> to your public WordPress site URL if it differs from this Laravel admin URL.
        </p>
        <livewire:lms-topic-search />
    </div>
</x-admin-layout>
