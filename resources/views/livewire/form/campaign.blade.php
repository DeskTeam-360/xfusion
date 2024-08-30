<form wire:submit="{{ $action }}">
    @if($for == "group")
        <x-select title="Group" model="companies" :options="$companyOptions" required="true"
                  :isSelected="$companies"/>
    @elseif ($for == "user")
        <x-select2 title="User" model="users" :options="$userOptions" required="true" :isSelected="$users"/>
    @endif
    <x-select title="Status" model="status" :options="$statusOptions" required="true"/>
    <x-select2 title="Tag" model="tags" :options="$tagOptions" required="true" :isSelected="$tags"/>

    @if($status!="send")
        <div class="grid grid-cols-2 gap-3">
            <x-input type="date" model="time_schedule" title="Time Schedule"/>
            <x-input type="time" model="clock" title="&nbsp" id="timeInput" step="1800"/>
        </div>
    @endif

    <input type="submit" class="btn" value="Submit">
</form>
