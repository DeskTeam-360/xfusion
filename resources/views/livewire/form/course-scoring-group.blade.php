@php
    $spinnerSvg = '<svg class="size-4 animate-spin shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
@endphp

<div class="text-dark dark:text-darklink">
    @if($dataId === null)
        {{-- Step 1: create the group first --}}
        <form wire:submit.prevent="saveNew" class="max-w-xl space-y-4">
            <p wire:loading wire:target="saveNew" class="flex items-center gap-2 text-sm text-dark/80 dark:text-darklink" role="status">
                {!! $spinnerSvg !!}
                <span>Creating group…</span>
            </p>

            <fieldset wire:loading.attr="disabled"
                      wire:loading.class="opacity-60"
                      wire:target="saveNew"
                      class="min-w-0 border-0 p-0 transition-opacity">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Title <span class="text-error">*</span></label>
                        <input wire:model="title" type="text"
                               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark placeholder:text-muted dark:bg-darkgray dark:border-darkborder dark:text-white dark:placeholder:text-darklink"
                               required/>
                        @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Description</label>
                        <textarea wire:model="description" rows="4"
                                  class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark placeholder:text-muted dark:bg-darkgray dark:border-darkborder dark:text-white dark:placeholder:text-darklink"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary inline-flex items-center justify-center gap-2" wire:loading.attr="disabled" wire:target="saveNew">
                        <span wire:loading wire:target="saveNew" aria-hidden="true">{!! $spinnerSvg !!}</span>
                        <span wire:loading.remove wire:target="saveNew">Create group</span>
                        <span wire:loading wire:target="saveNew">Please wait…</span>
                    </button>
                </div>
            </fieldset>
        </form>
    @else
        {{-- Edit: title plus repeatable Gravity Form blocks --}}
        <form wire:submit.prevent="saveExisting" class="space-y-8">
            <p wire:loading wire:target="saveExisting" class="flex items-center gap-2 text-sm font-medium text-dark dark:text-white" role="status">
                {!! $spinnerSvg !!}
                <span>Saving…</span>
            </p>

            <fieldset wire:loading.attr="disabled"
                      wire:loading.class="opacity-60"
                      wire:target="saveExisting"
                      class="min-w-0 border-0 p-0 transition-opacity">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Title <span class="text-error">*</span></label>
                        <input wire:model="title" type="text"
                               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark placeholder:text-muted dark:bg-darkgray dark:border-darkborder dark:text-white dark:placeholder:text-darklink"/>
                        @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Description</label>
                        <textarea wire:model="description" rows="3"
                                  class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark placeholder:text-muted dark:bg-darkgray dark:border-darkborder dark:text-white dark:placeholder:text-darklink"></textarea>
                    </div>
                </div>

                <div class="mt-8 space-y-6">
                    <h3 class="mb-4 text-lg font-semibold text-dark dark:text-white">Gravity Forms &amp; fields</h3>

                    @foreach($blocks as $index => $block)
                        @php
                            /** @var int $index */
                            $picked = isset($block['form_id']) && $block['form_id'] !== null;
                            $connectedCount = (int) ($block['connected_count'] ?? 0);
                        @endphp
                        <div wire:key="csg-block-{{ $index }}-{{ $picked ? 'yes' : 'no' }}-{{ md5(($block['search'] ?? '')) }}" class="mb-4 rounded-lg border border-border bg-gray-50/40 p-5 dark:bg-transparent dark:border-darkborder">
                            <div class="{{ $picked ? '' : 'mb-4' }} flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <span class="text-sm font-medium uppercase tracking-wide text-dark/70 dark:text-darklink">Form block {{ $index + 1 }}</span>
                                    @if($picked)
                                        <p class="mt-1 text-sm text-dark/75 dark:text-darklink">
                                            <strong class="text-dark dark:text-white">{{ $block['search'] }}</strong>
                                            (form ID {{ $block['form_id'] }}) &middot; {{ $connectedCount }}/{{ $this->totalFieldsForForm($block['form_id']) }} connected
                                        </p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @if($picked)
                                        <a href="{{ route('course-scoring-group.edit-form', ['courseScoringGroup' => $dataId, 'formId' => $block['form_id']]) }}"
                                           class="btn btn-primary btn-xs shrink-0 inline-flex items-center gap-1">
                                            Edit fields <i class="ti ti-arrow-right text-base" aria-hidden="true"></i>
                                        </a>
                                        <button type="button"
                                                wire:click.prevent="clearForm({{ $index }})"
                                                wire:loading.attr="disabled"
                                                wire:target="pickForm,clearForm"
                                                class="btn-outline-primary shrink-0">Change form
                                        </button>
                                    @endif
                                    <button wire:click="removeFormBlock({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="pickForm,removeFormBlock"
                                            type="button"
                                            class="btn btn-error btn-outline btn-xs shrink-0">Remove block
                                    </button>
                                </div>
                            </div>

                            @if(!$picked)
                                <label class="mb-2 block text-sm font-bold text-dark dark:text-light">Find form</label>
                                <div class="relative flex min-h-[8rem] flex-wrap gap-2"
                                     wire:key="gf-find-{{ $index }}"
                                     x-data="{ query: '', picking: false }"
                                     wire:loading.class="opacity-70 pointer-events-none"
                                     wire:target="pickForm,searchPickerForms">
                                    <template x-if="picking">
                                        <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-lg border border-border bg-white/90 p-4 dark:bg-darkgray/92 dark:border-darkborder"
                                             x-transition.opacity>
                                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-dark dark:text-white">
                                                {!! $spinnerSvg !!}
                                                Selecting form…
                                            </div>
                                        </div>
                                    </template>
                                    <input type="search"
                                           x-model="query"
                                           x-on:input.debounce.400ms="$wire.searchPickerForms({{ $index }}, query)"
                                           placeholder="Type at least 2 characters to search forms…"
                                           autocomplete="off"
                                           :disabled="picking"
                                           class="form-control min-w-[200px] flex-1 rounded border border-border bg-white px-3 py-2 text-dark placeholder:text-muted disabled:opacity-60 dark:bg-darkgray dark:border-darkborder dark:text-white dark:placeholder:text-darklink"/>
                                    <p class="w-full text-xs text-dark/60 dark:text-darklink">
                                        Server search only — up to 25 matching forms per query (no full catalog loaded in the browser).
                                    </p>
                                    <template x-if="!query.trim().length">
                                        <p class="mt-1 w-full text-sm text-dark/75 dark:text-darklink">Type part of the form title to search.</p>
                                    </template>
                                    @if(isset($pickerResults[$index]) && count($pickerResults[$index]) > 0)
                                        <ul class="mt-1 w-full divide-y divide-border rounded border border-border bg-white dark:border-darkborder dark:bg-darkgray/30"
                                            wire:key="picker-results-{{ $index }}-{{ md5(json_encode($pickerResults[$index])) }}">
                                            @foreach($pickerResults[$index] as $row)
                                                <li wire:key="gf-pick-{{ $index }}-{{ $row['id'] }}">
                                                    <button type="button"
                                                            class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-dark hover:bg-primary/10 disabled:pointer-events-none disabled:opacity-50 dark:text-white dark:hover:bg-darkborder/40"
                                                            x-bind:disabled="picking"
                                                            x-on:click.prevent="
                                                                picking = true;
                                                                query = '';
                                                                Promise.resolve($wire.pickForm({{ $index }}, {{ $row['id'] }})).finally(function () {
                                                                    picking = false;
                                                                });
                                                            ">
                                                        <strong class="font-semibold">{{ $row['title'] }}</strong>
                                                        <span class="ms-auto text-xs text-dark/60 dark:text-darklink">ID {{ $row['id'] }}</span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif(isset($pickerResults[$index]))
                                        <p class="mt-2 w-full text-sm text-dark/75 dark:text-darklink">No forms match that search.</p>
                                    @endif
                                    <div wire:loading wire:target="searchPickerForms" class="w-full text-xs text-dark/60 dark:text-darklink">
                                        Searching…
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <button type="button"
                            wire:click="addFormBlock"
                            wire:loading.attr="disabled"
                            wire:target="saveExisting,pickForm"
                            class="btn btn-primary">
                        + Form
                    </button>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="saveExisting,pickForm"
                            x-on:click="document.querySelectorAll('[id^=csg-weight-]').forEach(function (el) { el.blur(); })"
                            class="btn btn-primary inline-flex items-center justify-center gap-2">
                        <span wire:loading wire:target="saveExisting" aria-hidden="true">{!! $spinnerSvg !!}</span>
                        <span wire:loading.remove wire:target="saveExisting">Save</span>
                        <span wire:loading wire:target="saveExisting">Saving…</span>
                    </button>
                </div>
            </fieldset>
        </form>
    @endif
</div>
