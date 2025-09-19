@props(['title'=>'','placeholder'=>'','type'=>'text','class'=>'','model'=>'','accept'=>'','step'=>'any', 'required'=>false, 'ignore'=>false])
@php($id = "component-".rand(0,1000))
<div class="{{ $class }}" @if($ignore) wire:ignore @endif >
    <label for="{{ $id }}" class="form-label mb-2 " style="width: 100%">
        {!! $title !!}@if($required)<span class="text-red-600">*</span>   @endif
    </label>

    @if($type != 'password')
        <input type="{{ $type }}" class="py-2.5 px-4 form-control"
               id="{{ $id }}" aria-describedby="hs-input-helper-text" wire:model="{{ $model }}"
               accept="{{ $accept }}" step="{{ $step }}" @if($required) required @endif>
    @else
        @php($id = "component".rand(0,1000))

        <div class="relative">
            <input type="{{ $type }}" class="py-2.5 px-4 form-control pr-10"
                   id="{{ $id }}" aria-describedby="hs-input-helper-text" wire:model="{{ $model }}"
                   accept="{{ $accept }}" step="{{ $step }}" @if($required) required @endif>
            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" onclick="{{ $id }}showPassword()">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
            </button>
        </div>

        <script>
            function {{ $id }}showPassword() {
                var x = document.getElementById("{{ $id }}");
                var button = event.target.closest('button');
                var svg = button.querySelector('svg');
                
                if (x.type === "password") {
                    x.type = "text";
                    svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>';
                } else {
                    x.type = "password";
                    svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
                }
            }
        </script>
    @endif

    <div> @error($model) <span class="error">{{ $message }}</span> @enderror </div>
</div>
