@props(['repository'])

<div class="mt-3 @isset($repository['class']) {{ $repository['class'] }} @endisset">
    @if($repository['title']!=null)
        <label class="block text-sm text-black dark:text-white mb-1" for="data{{ $repository['model'] }}">
            {{ $repository['title'] }}
        </label>
    @endif
    <select
        id="data{{ $repository['model'] }}"
        wire:model="form.{{ $repository['model'] }}"
        name="{{ $repository['model'] }}"
        @isset($repository['disabled']) disabled @endisset
        @isset($repository['required']) @if($repository['required']) required @endif @endisset
        class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white">
{{--        <option></option>--}}
        @for($i=0;$i<count($repository['options']) ;$i++)
            <option value="{{$repository['options'][$i]['value']}}"
                    style="padding: 0 25px">
                {{$repository['options'][$i]['title']}}
            </option>
        @endfor
{{--        @error( $repository['model'] ) <span class="error">{{ $message }}</span> @enderror--}}
        @error('form.'.$repository['model']) <span class="text-danger">{{ $message }}</span> @enderror
    </select>
</div>
