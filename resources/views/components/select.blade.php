@props(['title'=>'','model'=>'','required'=>false,'options'=>[],'class'=>'','help'=>''])
{{-- Stable id (see input.blade.php). Validation errors used to sit inside <select>, where browsers never show them. --}}
@php($id = 'f-'.\Illuminate\Support\Str::slug((string) $model, '-'))
<div class="mb-3 {{ $class }}">

    <label for="{{ $id }}" class="form-label mb-2">
        {{ $title }}@if($required)<span class="text-red-600"> *</span>@endif
    </label>

    <select
        id="{{ $id }}"
        wire:model.live="{{ $model }}"
        @if($required) required @endif
        @if($help !== '') aria-describedby="{{ $id }}-help" @endif
        class="py-2.5 px-4 form-control">
        <option></option>

        @foreach($options as $option)
            <option value="{{ $option['value'] }}" class="p-2">{{ $option['title'] }}</option>
        @endforeach
    </select>

    @if($help !== '')
        <div id="{{ $id }}-help" style="margin-top:.35rem;font-size:.85rem;line-height:1.4;color:#374151">{!! $help !!}</div>
    @endif

    @error($model)
        <div style="margin-top:.35rem;font-size:.875rem;color:#dc2626" role="alert">{{ $message }}</div>
    @enderror
</div>
