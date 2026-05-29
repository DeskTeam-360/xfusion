<div class="space-y-5">
    <div>
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Judul <span class="text-error">*</span></label>
        <input wire:model="title" type="text"
               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
               required/>
        @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Kategori <span class="text-error">*</span></label>
        <input wire:model="category" type="text" list="xfusion-knowledge-categories"
               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
               placeholder="Harus cocok dengan kategori ujian AI"/>
        <datalist id="xfusion-knowledge-categories">
            @foreach($categoryOptions as $opt)
                <option value="{{ $opt }}"></option>
            @endforeach
        </datalist>
        <p class="mt-1 text-xs text-muted">Pilih dari daftar atau ketik kategori custom. Nama harus sama dengan kategori di payload evaluasi ujian.</p>
        @error('category') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Status publikasi</label>
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
        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Isi pengetahuan <span class="text-error">*</span></label>
        <textarea wire:model="content" rows="14"
                  class="form-control w-full rounded border border-border bg-white px-3 py-2 font-mono text-sm text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
                  placeholder="Kebijakan, SOP, atau panduan perusahaan…"></textarea>
        <p class="mt-1 text-xs text-muted">HTML sederhana boleh; teks akan dibersihkan sebelum di-index ke vector store.</p>
        @error('content') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
    </div>
</div>
