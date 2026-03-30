<x-admin-layout>
    <div class="container full-container py-5 px-5">
        <h1 class="mb-6 text-2xl font-semibold">LMS topic search</h1>
        <p class="mb-6 max-w-2xl text-gray-600">
            Search LearnDash topics (<code class="rounded bg-gray-100 px-1">sfwd-topic</code>) using the
            <code class="rounded bg-gray-100 px-1">_search_index</code> post meta built in WordPress.
            Topic links are always built from this app’s host (not from <code class="rounded bg-gray-100 px-1">guid</code> in the database, which can keep an old domain): drop the <code class="rounded bg-gray-100 px-1">admin.</code> prefix (e.g. <code class="rounded bg-gray-100 px-1">admin.sandbox.example.com</code> → <code class="rounded bg-gray-100 px-1">sandbox.example.com</code>), then append the topic path and slug. Set <code class="rounded bg-gray-100 px-1">WORDPRESS_URL</code> in <code class="rounded bg-gray-100 px-1">.env</code> only if the public WordPress site is not on that derived URL.
        </p>
        <livewire:lms-topic-search />
    </div>
</x-admin-layout>
