<div class="w-full max-w-4xl">
    <form wire:submit.prevent="search" class="flex flex-col gap-4 sm:flex-row sm:items-end">
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
        <p class="mt-3 text-sm text-gray-500">Enter at least 2 characters.</p>
    @endif

    @if($searched && strlen(trim($q)) >= 2 && count($results) === 0)
        <p class="mt-6 text-gray-600">No matching topics.</p>
    @endif

    @if(count($results) > 0)
        <div class="mt-6 space-y-4">
            @foreach($results as $row)
                <div class="rounded-lg border border-gray-800/20 p-4 shadow-sm">
                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer" class="text-lg font-semibold text-primary hover:underline">
                        {{ $row['title'] }}
                    </a>
                    <p class="mt-1 text-xs text-gray-400 break-all">{{ $row['url'] }}</p>
                    @if(!empty($row['snippets']))
                        @foreach($row['snippets'] as $snippet)
                            <p class="mt-2 text-sm text-gray-600">{!! $snippet !!}</p>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
