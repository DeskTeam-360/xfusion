<div class="space-y-5">
    <div>
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Title <span class="text-error">*</span></label>
        <input wire:model="title" type="text"
               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
               required/>
        @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Category <span class="text-error">*</span></label>
        <input wire:model="category" type="text" list="xfusion-knowledge-categories"
               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
               placeholder="Must match AI exam category"/>
        <datalist id="xfusion-knowledge-categories">
            @foreach($categoryOptions as $opt)
                <option value="{{ $opt }}"></option>
            @endforeach
        </datalist>
        <p class="mt-1 text-xs text-muted">Pick from the list or enter a custom category. Name must match the category in the exam evaluation payload.</p>
        @error('category') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Publication status</label>
        <select wire:model="post_status"
                class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white">
            <option value="publish">Published</option>
            <option value="draft">Draft</option>
            <option value="pending">Pending</option>
            <option value="private">Private</option>
        </select>
        @error('post_status') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Knowledge content <span class="text-error">*</span></label>
        <textarea wire:model="content" rows="14"
                  class="form-control w-full rounded border border-border bg-white px-3 py-2 font-mono text-sm text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
                  placeholder="Policy, SOP, or company guidelines…"></textarea>
        <p class="mt-1 text-xs text-muted">Simple HTML is allowed; text will be cleaned before indexing to the vector store.</p>
        @error('content') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
    </div>
</div>
