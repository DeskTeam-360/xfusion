@props(['repository'])
<div class="mt-3 @isset($repository['class']) {{ $repository['class'] }} @endisset">
    @isset($repository['title'])
        <label class="block text-sm text-black dark:text-white mb-1" for="data{{ $repository['model'] }}">
            {{ $repository['title'] }}
        </label>
    @endisset
    <input
        id="data{{ $repository['model'] }}"
        type="{{ $repository['type'] }}"
        @isset($repository['placeholder']) placeholder="{{ $repository['placeholder'] }}" @endisset
        @isset($repository['required']) @if($repository['required']) required @endif @endisset
        @isset($repository['step']) step="{{ $repository['step'] }}" @else step="any" @endisset
        @isset($repository['accept']) accept="{{ $repository['accept'] }}" @endisset

        wire:model="{{'form.'.$repository['model']}}"
        name="{{ $repository['model'] }}"
        class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark  focus:dark:border-white"
    >
    @if($repository['type']=="file")
        <div wire:loading wire:target="{{ 'form.'.$repository['model'] }}">
            Proses upload
        </div>
    @endif

    @error('form.'.$repository['model']) <span class="text-danger">{{ $message }}</span> @enderror
</div>

