<div class="w-full">
    <form wire:submit.prevent="search" class="flex w-full max-w-3xl flex-col gap-4 sm:flex-row sm:items-end">
        <br> <br>
        <div class="flex-1">
            <input
                id="lms-topic-q"
                type="search"
                wire:model="q"
                placeholder="Search LMS Topics using keywords..."
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                autocomplete="off"
            />
        </div>
        <input
            type="submit"
            class="rounded-lg bg-primary px-6 py-2 font-medium text-white hover:opacity-90"
        value="Search"
    />
    </form>
    @if($q !== '' && strlen(trim($q)) < 2)
        <p class="mt-3 text-sm text-muted dark:text-darklink">Enter at least 2 characters.</p>
    @endif

    @if(! $searched)
        <div class="mt-8 rounded-lg border border-dashed border-border bg-lightgray/40 px-6 py-10 text-center dark:border-darkborder dark:bg-darkgray/30">
            <i class="ti ti-search mb-3 block text-4xl text-muted dark:text-darklink" aria-hidden="true"></i>
            <p class="text-base font-medium text-dark dark:text-white">No search yet</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted dark:text-darklink">
                Enter keywords above and click <strong class="font-semibold text-dark dark:text-white">Search</strong> to find published LearnDash topics.
            </p>
        </div>
    @elseif(strlen(trim($q)) >= 2 && count($results) === 0)
        <div class="mt-8 rounded-lg border border-border bg-lightgray/40 px-6 py-10 text-center dark:border-darkborder dark:bg-darkgray/30">
            <i class="ti ti-file-off mb-3 block text-4xl text-muted dark:text-darklink" aria-hidden="true"></i>
            <p class="text-base font-medium text-dark dark:text-white">No matching topics</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted dark:text-darklink">
                No published topics matched &ldquo;{{ trim($q) }}&rdquo;. Try different keywords or fewer words.
            </p>
        </div>
    @endif

    @if(count($results) > 0)
        <p class="mt-6 text-sm text-muted dark:text-darklink">
            {{ count($results) }} topic{{ count($results) === 1 ? '' : 's' }} found
        </p>
        <div class="mt-4 w-full space-y-4">
            @foreach($results as $row)
                <div class="w-full rounded-lg border border-border bg-white p-4 shadow-sm dark:border-darkborder dark:bg-darkgray/20">
                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer" class="text-lg font-semibold text-primary hover:underline">
                        {{ $row['title'] }}
                    </a>
                    <p class="mt-1 break-all text-xs text-muted dark:text-darklink">{{ $row['url'] }}</p>
                    @if(!empty($row['snippets']))
                        @foreach($row['snippets'] as $snippet)
                            <p class="mt-2 text-sm text-dark/80 dark:text-darklink">{!! $snippet !!}</p>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
