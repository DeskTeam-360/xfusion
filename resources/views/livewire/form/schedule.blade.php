<form wire:submit="{{ $action }}">
    <x-input type="text" title="Course Title" model="title" />
    <x-select title="Course title" model="link" :options="$optionCourse" required="true"/>
    <x-select title="Employee Name" model="user_id" :options="$usersOption" required="true"/>
    <div class="grid grid-cols-2 gap-3">
        <x-input type="date" model="schedule_access_date" title="Can access"/>
        <x-input type="time" model="schedule_access_time" title="&nbsp"/>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <x-input type="date" model="schedule_deadline_date" title="Last access"/>
        <x-input type="time" model="schedule_deadline_time" title="&nbsp"/>
    </div>
    <input type="submit" class="btn" value="Submit">
</form>
