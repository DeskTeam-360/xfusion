@props(['repository'])
<div class="mt-3 @isset($repository['class']) {{ $repository['class'] }} @endisset">
    @isset($repository['title'])
        <label class="block text-sm text-black dark:text-white mb-1" for="data{{ $repository['model'] }}">
            {{ $repository['title'] }}
        </label>
    @endisset
    <textarea id="data{{ $repository['model'] }}" @isset($repository['placeholder']) placeholder="{{ $repository['placeholder'] }}" @endisset
    @isset($repository['required']) required @endisset
              wire:model="{{'form.'.$repository['model']}}"
              rows="5"
              @isset($repository['disabled']) disabled @endisset
              name="{{ $repository['model'] }}"
              class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white">
    </textarea>
    @error('form.'.$repository['model']) <span class="text-danger">{{ $message }}</span> @enderror
</div>
