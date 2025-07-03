<form wire:submit.prevent="{{ $action }}">
    @if($action=="create")
        <x-input title="Username" model="username" required="true"/>
        <x-input title="Email" model="email" required="true"/>
    @endif
    @if($action=="update")
        <x-input title="Nickname" model="username" required="true"/>
    @endif
    <x-input title="First Name" model="first_name" required="true"/>
    <x-input title="Last Name" model="last_name" required="true"/>
    <div class="mb-2">
        <label for="role" class="form-label mb-2">
            Role <span class="text-red-600">*</span>
        </label>
        <select wire:model="role" class="form-control" required>
            <option value=""></option>
            @foreach($optionAccess as $key=>$value)
                <option value="{{ $key }}">{{ $value }}</option>
            @endforeach
        </select>
    </div>

    <x-input title="Website" model="website"/>
    @if($action=="create")
        <x-input title="Password" model="password" type="password" required="true"/>
        <x-input title="Re-type password" model="rePassword" type="password" required="true"/>
    @endif


    <div class="mb-2 mt-2">
        <label for="keap-integration" class="flex align-items-center">
            <input type="checkbox" id="keap-integration" style="margin-right: 10px;" wire:model="keapIntegration">
            Keap integration ?
        </label>
    </div>

    <div class="mb-2 mt-2">
        <label for="skip-revitalize" class="flex align-items-center">
            <input type="checkbox" id="skip-revitalize" style="margin-right: 10px;" wire:model="skipRevitalize">
            Skip revitalize ?
        </label>
    </div>

    <div class="mb-2 mt-2">
        <label for="keap-mail-send" class="flex align-items-center">
            <input type="checkbox" id="keap-mail-send" style="margin-right: 10px;" wire:model="keapMailSend">
            Send email and password access ?
        </label>
    </div>

    <br>
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>

</form>
