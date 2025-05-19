<form wire:submit.prevent="{{ $action }}">
    @if($action=="create")
        <x-input title="Username" model="username" required="true"/>
        <x-input title="Email" model="email" required="true"/>
    @endif
    @if($action=="update")
        <x-input title="Nickname" model="username" required="true"/>
    @endif
    <x-input title="First Name" model="first_name"/>
    <x-input title="Last Name" model="last_name"/>

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
                    <option value="administrator">Administrator</option>
                @else
                    <option value="subscriber">Employee/Subscriber</option>
                @endif


            </select>
            <div> @error('role') <span class="error">{{ $message }}</span> @enderror </div>
            <br>
        </div>
    @endif


        <div class="mt-3" wire:ignore>
            <label for="{{'optionAccess'}}"
                   class="block text-sm font-bold dark:text-light">
                Access
            </label>
            <select id="{{'optionAccess'}}"
                    class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white select2"
                    multiple=""

                    name="optionAccess"
                    style="padding:0  100px" wire:model="{{'accessSelected'}}">
                @for($i=0;$i<count($optionAccess) ;$i++)
                    <option value="{{$optionAccess[$i]['value']}}"
                            style="padding: 0 25px"
                        {{ in_array($optionAccess[$i]['value'],$accessSelected)?'selected':''}}
                    >
                        {{$optionAccess[$i]['title']}}
                    </option>
                @endfor
            </select>
            <script>
                document.addEventListener('livewire:init', function () {
                    let data;
                    $('#optionAccess').select2();
                    $('#optionAccess').on('change', function (e) {
                        data = $('#{{'optionAccess'}}').select2("val");
                        @this.set('{{'accessSelected'}}', data);
                    })
                });

            </script>

        </div>

    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>

</form>
