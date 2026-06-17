@php
    $spinnerSvg = '<svg class="size-4 animate-spin shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
@endphp

<div class="text-dark dark:text-darklink max-w-3xl">
    @if($dataId === null)
        <form wire:submit.prevent="saveNew" class="space-y-5">
            @include('livewire.form.partials.xfusion-knowledge-fields')
            <button type="submit" class="btn btn-primary inline-flex items-center gap-2" wire:loading.attr="disabled" wire:target="saveNew">
                <span wire:loading wire:target="saveNew">{!! $spinnerSvg !!}</span>
                <span>Save &amp; sync to LLM</span>
            </button>
        </form>
    @else
        @if($syncStatus)
            <div class="mb-5 rounded border border-border bg-lightgray/50 p-4 text-sm dark:border-darkborder dark:bg-darkgray">
                <p><strong>LLM sync status:</strong> {{ $syncStatus }}</p>
                @if($syncMessage)
                    <p class="mt-1 text-error">{{ $syncMessage }}</p>
                @endif
            </div>
        @endif

        <form wire:submit.prevent="saveExisting" class="space-y-5">
            @include('livewire.form.partials.xfusion-knowledge-fields')
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary inline-flex items-center gap-2" wire:loading.attr="disabled" wire:target="saveExisting">
                    <span wire:loading wire:target="saveExisting">{!! $spinnerSvg !!}</span>
                    <span>Save changes</span>
                </button>
                <button type="button" wire:click="resyncToLlm" class="btn btn-outline-primary inline-flex items-center gap-2" wire:loading.attr="disabled" wire:target="resyncToLlm">
                    <span wire:loading wire:target="resyncToLlm">{!! $spinnerSvg !!}</span>
                    <span>Re-sync to LLM</span>
                </button>
            </div>
        </form>
    @endif
</div>
