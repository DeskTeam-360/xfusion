@props(['title'=>'','placeholder'=>'','type'=>'text','class'=>'','model'=>'','accept'=>''
,'step'=>'any', 'required'=>false, 'ignore'=>false
])
@php($id = "component-".rand(0,1000))
<div class="{{ $class }}" @if($ignore) wire:ignore @endif >
    <label for="{{ $id }}" class="form-label mb-2 " style="width: 100%">
        {!! $title !!}@if($required)<span class="text-red-600">*</span>   @endif
    </label>
    <br>

    @if($type != 'password')
        <input type="{{ $type }}" class="py-2.5 px-4 form-control"

               id="{{ $id }}" aria-describedby="hs-input-helper-text" wire:model="{{ $model }}"
               accept="{{ $accept }}" step="{{ $step }}" @if($required) required @endif
        >
    @else
        @php($id = "component".rand(0,1000))

        <input type="{{ $type }}" style="margin-bottom: 10px;" class="py-2.5 px-4 form-control"

               id="{{ $id }}" aria-describedby="hs-input-helper-text" wire:model="{{ $model }}"
               accept="{{ $accept }}" step="{{ $step }}" @if($required) required @endif
        >
        <input type="checkbox" style="margin-right: 10px;" onclick="{{ $id }}showPassword()">Show Password

        <script>
            function {{ $id }}showPassword() {
                var x = document.getElementById("{{ $id }}");
                console.log(x);
                if (x.type === "password") {
                    x.type = "text";
                } else {
                    x.type = "password";
                }
            }
        </script>
    @endif

    <div> @error($model) <span class="error">{{ ($message) }}</span> @enderror </div>
    <br>
</div>


