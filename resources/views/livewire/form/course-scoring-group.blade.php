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
        <form wire:submit.prevent="saveExisting" class="space-y-8"
              x-init="if (! Alpine.store('csGfForms')) { Alpine.store('csGfForms', { forms: {{ \Illuminate\Support\Js::from($formCatalog ?? []) }} }); }">
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
                            $gfFields = \App\Livewire\Form\CourseScoringGroup::gfFieldsForFormId(isset($block['form_id']) ? (int)$block['form_id'] : null);
                            $picked = isset($block['form_id']) && $block['form_id'] !== null;
                        @endphp
                        <div wire:key="csg-block-{{ $index }}-{{ $picked ? 'yes' : 'no' }}-{{ md5(($block['search'] ?? '')) }}" class="mb-4 rounded-lg border border-border bg-gray-50/40 p-5 dark:bg-transparent dark:border-darkborder">
                            <div class="mb-4 flex flex-wrap items-center gap-3">
                                <span class="text-sm font-medium uppercase tracking-wide text-dark/70 dark:text-darklink">Form block {{ $index + 1 }}</span>
                                <button wire:click="removeFormBlock({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="pickForm"
                                        type="button"
                                        class="btn btn-error btn-outline btn-xs">Remove block
                                </button>
                            </div>

                            @if($picked)
                                <div class="mb-4 flex flex-wrap gap-2">
                                    <button type="button"
                                            wire:click.prevent="clearForm({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="pickForm"
                                            class="btn shrink-0">Change form
                                    </button>
                                </div>
                            @else
                                <label class="mb-2 block text-sm font-bold text-dark dark:text-light">Find form</label>
                                <div class="relative flex min-h-[8rem] flex-wrap gap-2"
                                     wire:key="gf-find-{{ $index }}"
                                     x-data="{
                                         query: '',
                                         picking: false,
                                         maxShown: 200,
                                         get filtered() {
                                             var list = (Alpine.store('csGfForms') || {}).forms || [];
                                             var t = (this.query || '').trim().toLowerCase();
                                             if (!t.length) return [];
                                             return list.filter(function (f) {
                                                 var title = (f.title || '').toLowerCase();
                                                 return title.indexOf(t) !== -1;
                                             }).slice(0, this.maxShown);
                                         }
                                     }"
                                     wire:loading.class="opacity-70 pointer-events-none"
                                     wire:target="pickForm">
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
                                           placeholder="Search by form title (filters in-browser)…"
                                           autocomplete="off"
                                           :disabled="picking"
                                           class="form-control min-w-[200px] flex-1 rounded border border-border bg-white px-3 py-2 text-dark placeholder:text-muted disabled:opacity-60 dark:bg-darkgray dark:border-darkborder dark:text-white dark:placeholder:text-darklink"/>
                                    <p class="w-full text-xs text-dark/60 dark:text-darklink">
                                        <span class="tabular-nums"><span x-text="(Alpine.store('csGfForms').forms ?? []).length"></span></span> active form(s) loaded once; type to narrow the list — no extra server requests.
                                    </p>
                                    <template x-if="!query.trim().length">
                                        <p class="mt-1 w-full text-sm text-dark/75 dark:text-darklink">Type part of the form title to show matches.</p>
                                    </template>
                                    <template x-if="filtered.length">
                                        <ul class="mt-1 w-full divide-y divide-border rounded border border-border bg-white dark:border-darkborder dark:bg-darkgray/30">
                                            <template x-for="row in filtered" :key="'gf-p-{{ $index }}-' + row.id">
                                                <li>
                                                    <button type="button"
                                                            class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-dark hover:bg-primary/10 disabled:pointer-events-none disabled:opacity-50 dark:text-white dark:hover:bg-darkborder/40"
                                                            :disabled="picking"
                                                            x-on:click.prevent="
                                                                picking = true;
                                                                query = '';
                                                                Promise.resolve($wire.pickForm({{ $index }}, row.id)).finally(function () {
                                                                    picking = false;
                                                                });
                                                            ">
                                                        <strong class="font-semibold" x-text="row.title"></strong>
                                                        <span class="ms-auto text-xs text-dark/60 dark:text-darklink">ID <span x-text="row.id"></span></span>
                                                    </button>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                    <template x-if="filtered.length === 0 && query.trim().length">
                                        <p class="mt-2 w-full text-sm text-dark/75 dark:text-darklink">No forms match that search.</p>
                                    </template>
                                </div>
                            @endif

                            @if($picked)
                                <div class="mt-4">
                                    <p class="mb-2 text-sm text-dark/75 dark:text-darklink">
                                        Selected: <strong class="text-dark dark:text-white">{{ $block['search'] }}</strong>
                                        &nbsp;(form ID {{ $block['form_id'] }})
                                    </p>
                                    <p class="mb-2 text-sm font-semibold text-dark dark:text-white">Input fields</p>
                                    <p class="mb-3 text-xs text-dark/60 dark:text-darklink">Check a field to connect it for scoring, then set its weight (&gt; 0). Weight 0 or unchecked disconnects the field.</p>
                                    @if(count($gfFields) === 0)
                                        <p class="text-sm text-dark/75 dark:text-darklink">No input fields found in this form meta (check <code class="rounded bg-gray-100 px-1 py-0.5 text-xs text-dark dark:bg-darkborder dark:text-light">gf_form_meta.display_meta</code>).</p>
                                    @else
                                        <div class="max-h-60 space-y-2 overflow-y-auto rounded border border-border bg-white p-3 [color-scheme:light] dark:[color-scheme:dark] dark:bg-darkgray/30 dark:border-darkborder">
                                            @foreach($gfFields as $f)
                                                @php
                                                    $_id = (int) $f['id'];
                                                    $isChecked = $this->fieldIsChecked($index, $_id);
                                                    $fieldWeight = $this->fieldWeight($index, $_id);
                                                @endphp
                                                <div wire:key="fld-{{ $index }}-{{ $_id }}" class="flex items-start gap-3 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-darkborder/40">
                                                    <input type="checkbox"
                                                           wire:key="fld-cb-{{ $index }}-{{ $_id }}"
                                                           wire:change="setFieldChecked({{ $index }}, {{ $_id }}, $event.target.checked)"
                                                           @checked($isChecked)
                                                           class="mt-1 size-[1.125rem] shrink-0 cursor-pointer appearance-auto rounded border-2 border-gray-600 bg-white accent-blue-600 shadow-sm outline-none ring-offset-2 focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-gray-300 dark:bg-darkgray dark:accent-teal-400 dark:shadow-inner dark:focus-visible:ring-teal-400"/>
                                                    <span class="min-w-0 flex-1 text-sm text-dark dark:text-white">
                                                        <strong class="font-medium">{{ $f['label'] }}</strong>
                                                        @if(($f['type'] ?? '') !== '')
                                                            <span class="text-xs text-dark/60 dark:text-darklink">({{ $f['type'] }})</span>
                                                        @endif
                                                        <span class="text-xs text-dark/60 dark:text-darklink"> · field #{{ $_id }}</span>
                                                    </span>
                                                    @if($isChecked)
                                                        <div class="flex shrink-0 flex-col items-end gap-0.5">
                                                            <label for="csg-weight-{{ $index }}-{{ $_id }}" class="text-[10px] font-semibold uppercase tracking-wide text-dark/50 dark:text-darklink">Weight</label>
                                                            <input id="csg-weight-{{ $index }}-{{ $_id }}"
                                                                   type="number"
                                                                   step="0.01"
                                                                   min="0.01"
                                                                   wire:key="fld-wt-{{ $index }}-{{ $_id }}"
                                                                   wire:blur="setFieldWeight({{ $index }}, {{ $_id }}, $event.target.value)"
                                                                   value="{{ $fieldWeight ?? 1 }}"
                                                                   inputmode="decimal"
                                                                   class="form-control w-[5.5rem] rounded border border-border bg-white px-2 py-1 text-end text-sm tabular-nums text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"/>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
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
