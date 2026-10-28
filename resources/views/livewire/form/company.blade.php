<form wire:submit="{{ $action }}">

    <x-input model="title" required="true" title="Company Name"/>

    <x-input model="slug" title="Company custom URL"
             :placeholder="\Illuminate\Support\Str::slug($title ?: 'company-name')"
             :help="'Leave empty to use the company name. Used in the website links, e.g. <code>/'.e($slug ?: \Illuminate\Support\Str::slug($title ?: 'company-name')).'/annual-readiness-plan/</code>'"/>

    <x-input model="logo_url" type="file" accept="image/jpeg,image/png,image/jpg,image/gif" ignore="{{true}}" title="Company logo"
             help='Must be 140×60 to 160×80 pixels (recommended 150×70). Resize on <a href="https://www.iloveimg.com/resize-image#resize-options,pixels" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800">iLoveIMG</a>.'/>

    <x-input model="company_url" title="Company Website"/>

    <x-select model="user_id" :options="$usersOption" required="true" title="Company leader"/>
    @if($leaderWarning)
        <div class="alert alert-warning" role="alert" style="margin:-.5rem 0 1rem">{{ $leaderWarning }}</div>
    @endif

    <x-form-section title="Organizational Context&trade;"
                    description="Shown read-only in the 1-on-1 wizard's Step 1 evidence card. A group's own values (if set) override these company-level defaults."/>

    <x-input model="role" title="Role"/>
    <x-input model="team" title="Team"/>
    <x-textarea model="organizational_goals" title="Organizational Goals" rows="4"/>
    <x-textarea model="readiness_priorities" title="Readiness Priorities" rows="4"/>

    <div style="position:sticky;bottom:0;z-index:5;margin-top:1rem;padding:.75rem 0;background:#fff;border-top:1px solid #e5e7eb">
        @if ($errors->any())
            <div role="alert" style="margin-bottom:.75rem;padding:.65rem .9rem;border-radius:.375rem;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:.875rem">
                <ul style="margin:0;padding-left:1.1rem">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="{{ $action }}">Submit</span>
            <span wire:loading wire:target="{{ $action }}">Saving…</span>
        </button>
    </div>

</form>
