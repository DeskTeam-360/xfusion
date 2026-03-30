<x-admin-layout>
    <div class="container full-container py-5 px-5">
        <h1 class="mb-6 text-2xl font-semibold">Pencarian topic LMS</h1>
        <p class="mb-6 max-w-2xl text-gray-600">
            Mencari LearnDash topic (<code class="rounded bg-gray-100 px-1">sfwd-topic</code>) memakai meta
            <code class="rounded bg-gray-100 px-1">_search_index</code> yang dibangun di WordPress.
            Set <code class="rounded bg-gray-100 px-1">WORDPRESS_URL</code> di <code class="rounded bg-gray-100 px-1">.env</code> ke URL publik site WordPress Anda bila berbeda dari admin Laravel.
        </p>
        <livewire:lms-topic-search />
    </div>
</x-admin-layout>
