<form wire:submit="{{ $action }}">
    <x-input type="text" title="Name" model="name" />
    <x-input type="text" title="Description" model="description" />
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
