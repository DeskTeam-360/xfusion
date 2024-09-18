<form wire:submit.prevent="update">
    <h2 style="font-size: 24px">Reset password {{ $user->user_nicename }}</h2>
    <br><br>
    <x-input title="Password" model="password" type="password"/>
    <x-input title="Password" model="rePassword" type="password"/>
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
