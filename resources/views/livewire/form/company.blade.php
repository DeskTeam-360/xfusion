<form wire:submit="{{ $action }}">

    <div class="form-group">
{{--        <label for="title">{{ __('Name company') }}</label>@error('title') <span style="margin-left: 10px; color: indianred;" >{{ $message }}</span> @enderror--}}
        <x-input model="title" id="title" required="true" title="Company Name"/>
    </div>

    <div class="form-group">
        <x-input model="logo_url" type="file" accept="image/jpeg,image/png,image/jpg,image/gif" ignore="{{true}}" id="logo_url"  title="Company logo"/>
        <small class="text-muted">You can resize your logo on <a href="https://www.iloveimg.com/resize-image#resize-options,pixels" target="_blank" class="text-blue-600 hover:text-blue-800">iLoveIMG</a></small>
        @error('logo_url') <span style="margin-left: 10px; color: indianred;" >{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
{{--        <label for="title">{{ __('Website company') }}</label>@error('company_url') <span style="margin-left: 10px; color: indianred;" >{{ $message }}</span> @enderror--}}
        <x-input model="company_url" id="company_url" title="Company Website"/>
    </div>

{{--    <div class="form-group">--}}
{{--        <label for="qrcode_url">{{ __('Company QR code') }}</label>@error('qrcode_url') <span style="margin-left: 10px; color: indianred;" >{{ $message }}</span> @enderror--}}
{{--        <x-input model="qrcode_url" type="file" accept="image/png" ignore="{{true}}" id="qrcode_url"/>--}}
{{--    </div>--}}

    <div class="form-group">
{{--        <label for="user_id">{{ __('Company leader') }}</label>@error('user_id') <span style="margin-left: 10px; color: indianred;" >{{ $message }}</span> @enderror--}}
        <x-select model="user_id" :options="$usersOption" id="user_id" required="true" title="Company leader"/>

    </div>

    <hr>
    <h5>Organizational Context&trade;</h5>
    <p class="text-muted" style="margin-top:-.5rem">Shown read-only in the 1-on-1 wizard's Step 1 evidence card. A group's own values (if set) override these company-level defaults.</p>

    <div class="form-group">
        <x-input model="role" id="role" title="Role"/>
    </div>
    <div class="form-group">
        <x-input model="team" id="team" title="Team"/>
    </div>
    <div class="form-group">
        <label for="organizational_goals">{{ __('Organizational Goals') }}</label>@error('organizational_goals') <span style="margin-left: 10px; color: indianred;">{{ $message }}</span> @enderror
        <textarea wire:model="organizational_goals" id="organizational_goals" class="form-control" rows="3"></textarea>
    </div>
    <div class="form-group">
        <label for="readiness_priorities">{{ __('Readiness Priorities') }}</label>@error('readiness_priorities') <span style="margin-left: 10px; color: indianred;">{{ $message }}</span> @enderror
        <textarea wire:model="readiness_priorities" id="readiness_priorities" class="form-control" rows="3"></textarea>
    </div>

    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>

</form>
