<form wire:submit="{{ $action }}">
    <x-input title="Name company" model="title" required="true"/>
    <x-input title="Company logo" model="logo_url" type="file" accept="image/png" ignore="{{true}}"/>
    <x-input title="Company qrcode" model="qrcode_url" type="file" accept="image/png" ignore="{{true}}"/>
    <x-select title="Company leader" model="user_id" :options="$usersOption" required="true"/>
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
