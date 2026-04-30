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

        <div class="mb-2">
            <label for="role" class="form-label mb-2">
                Company
            </label>
            @if($action=="create" && empty($companyId))
                <div class="mb-2">
                    <label for="create-new-company" class="flex align-items-center">
                        <input type="checkbox" id="create-new-company" style="margin-right: 10px;" wire:model.live="createNewCompany">
                        Create new company (this user will be the company leader)
                    </label>
                </div>
                @if($createNewCompany)
                    <div class="border rounded p-3 mb-2 bg-light">
                        <x-input title="New company name" model="new_company_title" required="true"/>
                        <x-input model="new_company_logo" type="file" accept="image/jpeg,image/png,image/jpg,image/gif" ignore="{{true}}" title="Company logo (optional)"/>
                        <small class="text-muted d-block mb-2">Logo dimensions: 140×60 to 160×80 px. Resize on <a href="https://www.iloveimg.com/resize-image#resize-options,pixels" target="_blank" class="text-blue-600 hover:text-blue-800">iLoveIMG</a>.</small>
                        @error('new_company_logo') <span class="text-danger">{{ $message }}</span> @enderror
                        <x-input title="Company website (optional)" model="new_company_url"/>
                    </div>
                @else
                    <select wire:model="company_id" class="form-control">
                        <option value="0"></option>
                        @foreach($optionCompany as $key=>$value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                @endif
            @else
                <select wire:model="company_id" class="form-control">
                    <option value="0"></option>
                    @foreach($optionCompany as $key=>$value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            @endif
        </div>

    <x-input title="Work Type" model="work_type"/>

    <div class="mb-2 mt-2">
        <label for="keap-integration" class="flex align-items-center">
            <input type="checkbox" id="keap-integration" style="margin-right: 10px;" wire:model="keapIntegration" {{ $keapIntegration ? 'checked' : '' }}>
            Keap integration ?
        </label>
    </div>

    @if($action=="create")
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
    @endif

    <br>
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>

</form>
