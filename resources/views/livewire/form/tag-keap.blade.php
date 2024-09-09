<form wire:submit="{{ $action }}">
    <x-input type="text" title="Name" model="name" />
    <x-input type="text" title="Description" model="description" />
    <input type="submit" class="btn" value="Submit">
</form>
