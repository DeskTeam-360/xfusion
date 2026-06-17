@props(['title', 'description' => null, 'padded' => false])

<div class="container full-container py-5 px-5">
    <div class="admin-page-card">
        <div class="card-body p-0">
            <div class="flex flex-col gap-4 border-b border-border p-5 dark:border-darkborder sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-semibold text-dark dark:text-white">{{ $title }}</h1>
                    @if($description)
                        <p class="mt-1 max-w-3xl text-sm text-muted dark:text-darklink">{{ $description }}</p>
                    @endif
                </div>
                @isset($actions)
                    <div class="admin-page-actions flex flex-wrap items-center gap-2 self-start shrink-0">{{ $actions }}</div>
                @endisset
            </div>

            @isset($toolbar)
                <div class="border-b border-border px-5 py-4 dark:border-darkborder">
                    {{ $toolbar }}
                </div>
            @endisset

            <div @class(['admin-page-content', 'p-5 pt-4' => $padded])>
                {{ $slot }}
            </div>
        </div>
    </div>

    <div class="mb-5" aria-hidden="true"></div>
</div>
