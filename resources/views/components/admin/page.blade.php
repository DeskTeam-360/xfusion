@props(['title', 'description' => null])

<div class="container full-container py-5 px-5">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold text-dark dark:text-white">{{ $title }}</h1>
            @if($description)
                <p class="mt-1 max-w-3xl text-sm text-muted dark:text-darklink">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap gap-2 shrink-0">{{ $actions }}</div>
        @endisset
    </div>

    {{ $slot }}

    <div class="mb-5" aria-hidden="true"></div>
</div>
