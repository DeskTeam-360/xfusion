<form wire:submit="{{ $action }}">

    <div class="form-group">
{{--        <label for="title">{{ __('Name company') }}</label>@error('title') <span style="margin-left: 10px; color: indianred;" >{{ $message }}</span> @enderror--}}
        <x-input model="title" id="title" required="true" title="Company Name"/>
    </div>

    <div class="form-group">
{{--        <label for="logo_url">{{ __('Company logo') }}</label>@error('logo_url') <span style="margin-left: 10px; color: indianred;" >{{ $message }}</span> @enderror--}}
        <x-input model="logo_url" type="file" accept="image/png" ignore="{{true}}" id="logo_url"  title="Company logo"/>

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

    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>

</form>
