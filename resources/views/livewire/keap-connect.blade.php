<div class="w-12/12 grid grid-cols-12">
    <div class="grid grid-cols-12 col-span-6">
        <h2 class="col-span-12 text-xl">Admin site data</h2>
        <br>
        <div class="col-span-12 grid grid-cols-12">
            <div class="col-span-2">Email</div>
            <div class="col-span-1">:</div>
            <div class="col-span-4">
                {{ $user->email }}
            </div>
        </div>
        <br>
        <div class="col-span-12 grid grid-cols-12">
            <div class="col-span-2">First Name</div>
            <div class="col-span-1">:</div>
            <div class="col-span-4">
                {{ $firstName }}
            </div>
        </div>
        <br>
        <div class="col-span-12 grid grid-cols-12">
            <div class="col-span-2">Last Name</div>
            <div class="col-span-1">:</div>
            <div class="col-span-4">
                {{ $lastName }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 col-span-6">
        @forelse($keap as $index=>$k)
        <h2 class="col-span-12 text-xl">Keap Data</h2>
        <br>
        <div class="col-span-12 grid grid-cols-12">
            <div class="col-span-2">Email</div>
            <div class="col-span-1">:</div>
            <div class="col-span-4">
                {{ $user->email }}
            </div>
        </div>
        <br>
        <div class="col-span-12 grid grid-cols-12">
            <div class="col-span-2">First Name</div>
            <div class="col-span-1">:</div>
            <div class="col-span-4">
                {{ $k['given_name'] }}
            </div>
        </div>
        <br>
        <div class="col-span-12 grid grid-cols-12">
            <div class="col-span-2">Last Name</div>
            <div class="col-span-1">:</div>
            <div class="col-span-4">
                {{ $k['family_name'] }}
            </div>
        </div>
            @break
        @empty
            <h2 class="col-span-12 text-xl">No Keap Data</h2>
        @endforelse
    </div>

    <div class="col-span-12 mt-8">
        <button href="" class="btn btn-primary mb-1" wire:click="connect">Connect data</button>
        <p class="text-xs">Connecting to existing data does not change your data for security reasons.</p>
    </div>
</div>
