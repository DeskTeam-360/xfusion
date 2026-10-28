@props(['title'=>'','placeholder'=>'','type'=>'text','class'=>'','model'=>'','accept'=>'','step'=>'any', 'required'=>false, 'ignore'=>false, 'help'=>''])
{{-- Stable id (derived from the model path). A random id changed on every Livewire re-render and could collide between inputs. --}}
@php($id = 'f-'.\Illuminate\Support\Str::slug((string) $model, '-'))
{{-- wire:ignore keeps a chosen file name after re-render, but it also freezes every child — including @error — so the message must sit outside it. --}}
<div class="mb-3 {{ $class }}">
    <div @if($ignore) wire:ignore @endif>
    <label for="{{ $id }}" class="form-label" style="width: 100%">
        {!! $title !!}@if($required)<span class="text-red-600"> *</span>@endif
    </label>

    @if($type != 'password')
        <input type="{{ $type }}" class="py-2.5 px-4 form-control"
               id="{{ $id }}" wire:model="{{ $model }}"
               @if($placeholder !== '') placeholder="{{ $placeholder }}" @endif
               @if($accept !== '') accept="{{ $accept }}" @endif
               @if($type === 'number') step="{{ $step }}" @endif
               @if($help !== '') aria-describedby="{{ $id }}-help" @endif
               @if($required) required @endif>
    @else
        <div class="relative" x-data="{ show: false }">
            <input :type="show ? 'text' : 'password'" class="py-2.5 px-4 form-control pr-10"
                   id="{{ $id }}" wire:model="{{ $model }}"
                   @if($placeholder !== '') placeholder="{{ $placeholder }}" @endif
                   @if($help !== '') aria-describedby="{{ $id }}-help" @endif
                   @if($required) required @endif>
            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" @click="show = !show" aria-label="Show or hide password">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!show">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="show" x-cloak>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                </svg>
            </button>
        </div>
    @endif

    @if($help !== '')
        <div id="{{ $id }}-help" style="margin-top:.35rem;font-size:.85rem;line-height:1.4;color:#374151">{!! $help !!}</div>
    @endif
    </div>

    @error($model)
        <div style="margin-top:.35rem;font-size:.875rem;color:#dc2626" role="alert">{{ $message }}</div>
    @enderror
</div>
