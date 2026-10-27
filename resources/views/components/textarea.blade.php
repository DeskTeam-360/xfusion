@props(['title'=>'','placeholder'=>'','class'=>'','model'=>'','rows'=>4,'required'=>false,'help'=>''])
@php($id = 'f-'.\Illuminate\Support\Str::slug((string) $model, '-'))
<div class="mb-3 {{ $class }}">
    <label for="{{ $id }}" class="form-label" style="width: 100%">
        {!! $title !!}@if($required)<span class="text-red-600"> *</span>@endif
    </label>

    <textarea id="{{ $id }}" wire:model="{{ $model }}" rows="{{ $rows }}" class="py-2.5 px-4 form-control"
              style="min-height:7rem;resize:vertical"
              @if($placeholder !== '') placeholder="{{ $placeholder }}" @endif
              @if($help !== '') aria-describedby="{{ $id }}-help" @endif
              @if($required) required @endif></textarea>

    @if($help !== '')
        <div id="{{ $id }}-help" style="margin-top:.35rem;font-size:.85rem;line-height:1.4;color:#374151">{!! $help !!}</div>
    @endif

    @error($model)
        <div style="margin-top:.35rem;font-size:.875rem;color:#dc2626" role="alert">{{ $message }}</div>
    @enderror
</div>
