<form wire:submit.prevent="{{ $action }}">
    <x-input title="Username" model="username" required="true"/>
    <x-input title="Email" model="email" required="true"/>
    <x-input title="First Name" model="first_name"/>
    @if($action=="create")
        <x-input title="Last Name" model="last_name"/>
    @endif
    <x-input title="Website" model="website"/>
    @if($action=="create")
        <x-input title="Password" model="password" type="password"/>
        <x-input title="Re-type password" model="rePassword" type="password"/>
    @endif

    @if($action=="create")
        <div>
            <label for="role" class="form-label mb-2">
                Role <span class="text-red-600">*</span>
            </label>
            <select wire:model="role" class="form-control">
                @if($companyId==null)
                    <option value="contributor">Contributor</option>
                    <option value="editor">Company/Editor</option>
                    {{--                <option value="administrator">Administrator</option>--}}
                @else
                    <option value="subscriber">Employee/Subscriber</option>
                @endif


            </select>
            <div> @error('role') <span class="error">{{ $message }}</span> @enderror </div>
            <br>
        </div>
    @endif

    <input type="submit" class="btn" value="Submit">

</form>
