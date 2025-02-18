@props(['repository'])
<div class="mt-3 @isset($repository['class']) {{ $repository['class'] }} @endisset" wire:ignore>
    <label for="{{'data'.$repository['model']}}"
           class="block text-sm font-bold dark:text-light">
        {{ $repository['title'] }}
    </label>
    <select id="{{'data'.$repository['model']}}"
            class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white select2"
            multiple=""
            @isset($repository['disabled']) disabled @endisset
            name="{{ $repository['model'] }}"
            style="padding:0  100px" wire:model="{{'form.'.$repository['model']}}">
        @for($i=0;$i<count($repository['options']) ;$i++)
            <option value="{{$repository['options'][$i]['value']}}"
                    style="padding: 0 25px">
                {{$repository['options'][$i]['title']}} . {{$repository['options'][$i]['value']}}
            </option>
        @endfor
    </select>
    <script>
        document.addEventListener('livewire:init', function () {
            let data;
            $('#{{'data'.$repository['model']}}').select2();
            $('#{{'data'.$repository['model']}}').on('change', function (e) {
                data = $('#{{'data'.$repository['model']}}').select2("val");
                @this.set('{{'form.'.$repository['model']}}', data);
            })
        });
    </script>
</div>
