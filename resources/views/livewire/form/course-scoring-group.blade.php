<div>
    @if($dataId === null)
        {{-- Step 1: buat grup dulu --}}
        <form wire:submit.prevent="saveNew" class="max-w-xl space-y-4">
            <div>
                <label class="mb-1 block text-sm font-bold dark:text-light">Title <span class="text-error">*</span></label>
                <input wire:model="title" type="text"
                       class="w-full rounded border border-border bg-transparent px-3 py-2 dark:border-darkborder dark:text-white"
                       required/>
                @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-bold dark:text-light">Description</label>
                <textarea wire:model="description" rows="4"
                          class="w-full rounded border border-border bg-transparent px-3 py-2 dark:border-darkborder dark:text-white"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Create group</button>
        </form>
    @else
        {{-- Edit: judul + daftar Form GF berulang --}}
        <form wire:submit.prevent="saveExisting" class="space-y-8">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold dark:text-light">Title <span class="text-error">*</span></label>
                    <input wire:model="title" type="text"
                           class="w-full rounded border border-border bg-transparent px-3 py-2 dark:border-darkborder dark:text-white"/>
                    @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-bold dark:text-light">Description</label>
                    <textarea wire:model="description" rows="3"
                              class="w-full rounded border border-border bg-transparent px-3 py-2 dark:border-darkborder dark:text-white"></textarea>
                </div>
            </div>

            <div class="space-y-6">
                <h3 class="text-lg font-semibold dark:text-white">Gravity Forms &amp; fields</h3>

                @foreach($blocks as $index => $block)
                    @php
                        /** @var int $index */
                        $gfFields = \App\Livewire\Form\CourseScoringGroup::gfFieldsForFormId(isset($block['form_id']) ? (int)$block['form_id'] : null);
                        $picked = isset($block['form_id']) && $block['form_id'] !== null;
                    @endphp
                    <div wire:key="csg-block-{{ $index }}-{{ $picked ? 'yes' : 'no' }}-{{ md5(($block['search'] ?? '')) }}" class="rounded-lg border border-border p-5 dark:border-darkborder">
                        <div class="mb-4 flex flex-wrap items-center gap-3">
                            <span class="text-sm font-medium text-muted dark:text-darklink uppercase tracking-wide">Form block {{ $index + 1 }}</span>
                            <button wire:click="removeFormBlock({{ $index }})" type="button"
                                    class="btn btn-error btn-outline btn-xs">Remove block
                            </button>
                        </div>

                        <label class="mb-2 block text-sm font-bold dark:text-light">Find form</label>
                        <div class="flex flex-wrap gap-2">
                            <input type="text" wire:model.live.debounce.400ms="blocks.{{ $index }}.search"
                                   placeholder="Judul Gravity Form…"
                                   class="min-w-[200px] flex-1 rounded border border-border bg-transparent px-3 py-2 dark:border-darkborder dark:text-white"/>
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
                            <ul class="mt-3 divide-y divide-border rounded border border-border bg-gray-50/50 dark:border-darkborder dark:bg-darkgray/30">
                                @foreach(($blockFormPickResults[$index] ?? []) as $row)
                                    <li>
                                        <button type="button" wire:key="gf-p-{{ $index }}-{{ $row['id'] }}"
                                                wire:click="pickForm({{ $index }}, {{ $row['id'] }})"
                                                class="w-full px-3 py-2 text-start text-sm hover:bg-primary/10 dark:hover:bg-darkborder/40">
                                            <strong>{{ $row['title'] }}</strong>
                                            <span class="ms-2 text-xs text-muted">ID {{ $row['id'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($picked)
                            <div class="mt-4">
                                <p class="mb-2 text-sm text-muted dark:text-darklink">
                                    Selected: <strong>{{ $block['search'] }}</strong>
                                    &nbsp;(form ID {{ $block['form_id'] }})
                                </p>
                                <p class="mb-2 text-sm font-semibold dark:text-white">Questions / fields untuk scoring</p>
                                @if(count($gfFields) === 0)
                                    <p class="text-sm text-muted">Meta form tidak ada field (cek <code class="text-xs">gf_form_meta.display_meta</code>).</p>
                                @else
                                    <div class="max-h-60 space-y-2 overflow-y-auto rounded border border-border p-3 dark:border-darkborder">
                                        @foreach($gfFields as $f)
                                            @php($_id = (int)$f['id'])
                                            <label wire:key="fld-{{ $index }}-{{ $_id }}" class="flex cursor-pointer gap-3 rounded px-2 py-1 hover:bg-gray-50 dark:hover:bg-darkborder/40">
                                                <input type="checkbox"
                                                       wire:click.prevent="toggleField({{ $index }}, {{ $_id }})"
                                                       @checked($this->fieldIsChecked($index, $_id))
                                                       class="mt-1"/>
                                                <span class="text-sm dark:text-white">
                                                    <strong>{{ $f['label'] }}</strong>
                                                    @if(($f['type'] ?? '') !== '')
                                                        <span class="text-xs text-muted">({{ $f['type'] }})</span>
                                                    @endif
                                                    <span class="text-xs text-muted"> · field #{{ $_id }}</span>
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
                <button type="button" wire:click="addFormBlock" class="btn btn-outline btn-primary">
                    + Form
                </button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save</button>
            </div>
        </form>
    @endif
</div>
