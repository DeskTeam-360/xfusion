@php
    $spinnerSvg = '<svg class="size-4 animate-spin shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    $fields = $this->visibleFields();
    $connectedCount = count($fieldIds);
    $totalCount = $this->totalFieldsCount();
@endphp

<div class="text-dark dark:text-darklink">
    <p wire:loading wire:target="save" class="flex items-center gap-2 text-sm font-medium text-dark dark:text-white" role="status">
        {!! $spinnerSvg !!}
        <span>Saving…</span>
    </p>

    <fieldset wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="save" class="min-w-0 border-0 p-0 transition-opacity">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="mb-1 text-sm text-dark/70 dark:text-darklink">
                    Group: <strong class="text-dark dark:text-white">{{ $groupTitle }}</strong>
                </p>
                <p class="text-sm text-dark/70 dark:text-darklink">
                    Form: <strong class="text-dark dark:text-white">{{ $formTitle }}</strong> (ID {{ $formId }})
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="self-center text-xs text-dark/60 dark:text-darklink">{{ $connectedCount }}/{{ $totalCount }} fields</span>
                <button type="button"
                        wire:click="toggleExpanded"
                        wire:loading.attr="disabled"
                        wire:target="toggleExpanded"
                        class="btn-outline-primary shrink-0">
                    @if($expanded)
                        Show connected only
                    @else
                        Browse all fields
                    @endif
                </button>
            </div>
        </div>

        @if(count($fields) === 0)
            <p class="text-sm text-dark/75 dark:text-darklink">
                @if($expanded)
                    No input fields found in this form meta.
                @else
                    No fields connected yet. Use "Browse all fields" to pick questions.
                @endif
            </p>
        @else
            <div class="max-h-[32rem] space-y-2 overflow-y-auto [color-scheme:light] dark:[color-scheme:dark]">
                @foreach($fields as $f)
                    @php
                        $_id = (int) $f['id'];
                        $isChecked = $this->fieldIsChecked($_id);
                        $fieldWeight = $this->fieldWeight($_id);
                    @endphp
                    <div wire:key="fld-{{ $_id }}"
                         x-data="{ connected: @js($isChecked) }"
                         class="flex items-center gap-3 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-darkborder/40">
                        <input type="checkbox"
                               wire:key="fld-cb-{{ $_id }}"
                               wire:change="setFieldChecked({{ $_id }}, $event.target.checked)"
                               x-on:change="connected = $event.target.checked"
                               :checked="connected"
                               class="size-[1.125rem] shrink-0 cursor-pointer appearance-auto rounded border-2 border-gray-600 bg-white accent-blue-600 shadow-sm outline-none ring-offset-2 focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-gray-300 dark:bg-darkgray dark:accent-teal-400 dark:shadow-inner dark:focus-visible:ring-teal-400"/>
                        <span class="min-w-0 flex-1 text-sm text-dark dark:text-white">
                            <strong class="font-medium">{{ $f['label'] }}</strong>
                            @if(($f['type'] ?? '') !== '')
                                <span class="text-xs text-dark/60 dark:text-darklink">({{ $f['type'] }})</span>
                            @endif
                            <span class="text-xs text-dark/60 dark:text-darklink"> · field #{{ $_id }}</span>
                        </span>
                        <div x-show="connected"
                             x-cloak
                             class="flex shrink-0 items-center gap-2">
                            <label for="csgf-weight-{{ $_id }}" class="text-[10px] font-semibold uppercase tracking-wide text-dark/50 dark:text-darklink">Weight</label>
                            <input id="csgf-weight-{{ $_id }}"
                                   type="number"
                                   step="0.01"
                                   min="0.01"
                                   wire:key="fld-wt-{{ $_id }}"
                                   wire:blur="setFieldWeight({{ $_id }}, $event.target.value)"
                                   value="{{ $fieldWeight ?? 1 }}"
                                   inputmode="decimal"
                                   class="form-control w-[5.5rem] rounded border border-border bg-white px-2 py-1 text-end text-sm tabular-nums text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"/>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-6">
            <button type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    x-on:click="document.querySelectorAll('[id^=csgf-weight-]').forEach(function (el) { el.blur(); })"
                    class="btn btn-primary inline-flex items-center justify-center gap-2">
                <span wire:loading wire:target="save" aria-hidden="true">{!! $spinnerSvg !!}</span>
                <span wire:loading.remove wire:target="save">Save fields</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </fieldset>
</div>
