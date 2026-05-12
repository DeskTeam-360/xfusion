<div class="text-dark dark:text-darklink">
    @if($dataId === null)
        {{-- Step 1: buat grup dulu --}}
        <form wire:submit.prevent="saveNew" class="max-w-xl space-y-4">
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
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Create group</button>
        </form>
    @else
        {{-- Edit: judul + daftar Form GF berulang --}}
        <form wire:submit.prevent="saveExisting" class="space-y-8">
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

            <div class="space-y-6">
                <h3 class="text-lg font-semibold text-dark dark:text-white mb-4">Gravity Forms &amp; fields</h3>

                @foreach($blocks as $index => $block)
                    @php
                        /** @var int $index */
                        $gfFields = \App\Livewire\Form\CourseScoringGroup::gfFieldsForFormId(isset($block['form_id']) ? (int)$block['form_id'] : null);
                        $picked = isset($block['form_id']) && $block['form_id'] !== null;
                    @endphp
                    <div wire:key="csg-block-{{ $index }}-{{ $picked ? 'yes' : 'no' }}-{{ md5(($block['search'] ?? '')) }}" class="rounded-lg border border-border bg-gray-50/40 p-5 dark:bg-transparent dark:border-darkborder mb-4">
                        <div class="mb-4 flex flex-wrap items-center gap-3">
                            <span class="text-sm font-medium text-dark/70 dark:text-darklink uppercase tracking-wide">Form block {{ $index + 1 }}</span>
                            <button wire:click="removeFormBlock({{ $index }})" type="button"
                                    class="btn btn-error btn-outline btn-xs">Remove block
                            </button>
                        </div>

                        <label class="mb-2 block text-sm font-bold text-dark dark:text-light">Find form</label>
                        <div class="flex flex-wrap gap-2">
                            <input type="text" wire:model.live.debounce.400ms="blocks.{{ $index }}.search"
                                   placeholder="Judul Gravity Form…"
                                   class="form-control min-w-[200px] flex-1 rounded border border-border bg-white px-3 py-2 text-dark placeholder:text-muted dark:bg-darkgray dark:border-darkborder dark:text-white dark:placeholder:text-darklink"/>
                            <button type="button" wire:click.prevent="searchForms({{ $index }})"
                                    class="btn btn-secondary shrink-0">Search
                            </button>
                            @if($picked)
                                <button type="button" wire:click.prevent="clearForm({{ $index }})"
                                        class="btn btn-outline shrink-0">Change form
                                </button>
                            @endif
                        </div>

                        @if(!empty($blockFormPickResults[$index] ?? []) && !$picked)
                            <ul class="mt-3 divide-y divide-border rounded border border-border bg-white dark:border-darkborder dark:bg-darkgray/30">
                                @foreach(($blockFormPickResults[$index] ?? []) as $row)
                                    <li>
                                        <button type="button" wire:key="gf-p-{{ $index }}-{{ $row['id'] }}"
                                                wire:click="pickForm({{ $index }}, {{ $row['id'] }})"
                                                class="w-full px-3 py-2 text-start text-sm text-dark hover:bg-primary/10 dark:text-white dark:hover:bg-darkborder/40">
                                            <strong class="font-semibold">{{ $row['title'] }}</strong>
                                            <span class="ms-2 text-xs text-dark/60 dark:text-darklink">ID {{ $row['id'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($picked)
                            <div class="mt-4">
                                <p class="mb-2 text-sm text-dark/75 dark:text-darklink">
                                    Selected: <strong class="text-dark dark:text-white">{{ $block['search'] }}</strong>
                                    &nbsp;(form ID {{ $block['form_id'] }})
                                </p>
                                <p class="mb-2 text-sm font-semibold text-dark dark:text-white">Questions / fields untuk scoring</p>
                                @if(count($gfFields) === 0)
                                    <p class="text-sm text-dark/75 dark:text-darklink">Meta form tidak ada field (cek <code class="rounded bg-gray-100 px-1 py-0.5 text-xs text-dark dark:bg-darkborder dark:text-light">gf_form_meta.display_meta</code>).</p>
                                @else
                                    <div class="max-h-60 space-y-2 overflow-y-auto rounded border border-border bg-white p-3 [color-scheme:light] dark:[color-scheme:dark] dark:bg-darkgray/30 dark:border-darkborder">
                                        @foreach($gfFields as $f)
                                            @php($_id = (int)$f['id'])
                                            <label wire:key="fld-{{ $index }}-{{ $_id }}" class="flex cursor-pointer gap-3 rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-darkborder/40">
                                                <input type="checkbox"
                                                       wire:key="fld-cb-{{ $index }}-{{ $_id }}"
                                                       wire:change="setFieldChecked({{ $index }}, {{ $_id }}, $event.target.checked)"
                                                       @checked($this->fieldIsChecked($index, $_id))
                                                       class="mt-1 size-[1.125rem] shrink-0 cursor-pointer appearance-auto rounded border-2 border-gray-600 bg-white accent-blue-600 shadow-sm outline-none ring-offset-2 focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-gray-300 dark:bg-darkgray dark:accent-teal-400 dark:shadow-inner dark:focus-visible:ring-teal-400"/>
                                                <span class="text-sm text-dark dark:text-white">
                                                    <strong class="font-medium">{{ $f['label'] }}</strong>
                                                    @if(($f['type'] ?? '') !== '')
                                                        <span class="text-xs text-dark/60 dark:text-darklink">({{ $f['type'] }})</span>
                                                    @endif
                                                    <span class="text-xs text-dark/60 dark:text-darklink"> · field #{{ $_id }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="addFormBlock" class="btn btn-primary">
                    + Form
                </button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save</button>
            </div>
        </form>
    @endif
</div>
