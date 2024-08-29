@props(['title'=>'','model'=>'','required'=>false,'options'=>[],'class'=>'' ])
@php($id = "component-".rand(0,1000))
<div class="{{ $class }}">

    <label for="{{ $id }}" class="form-label mb-2">
        {{ $title }} @if($required) <span class="text-red-600">*</span> @endif
    </label>

    <select
        id="{{ $id }}"
        wire:model="{{ $model }}"
        name="{{ $title }}"
        class="py-2.5 px-4 form-control">
        <option></option>

        @for($i=0;$i<count($options) ;$i++)
            <option value="{{$options[$i]['value']}}"
                    class="p-2">
                {{$options[$i]['title']}}
            </option>
        @endfor
        <div> @error($model) <span class="error">{{ $message }}</span> @enderror </div>
    </select>
    <br><br>
</div>
