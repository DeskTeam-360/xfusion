<form wire:submit="{{ $action }}">
    <x-select title="Course title" model="course_list_id" :options="$optionCourseList" required="true"/>
    <x-select title="Course title parent" model="course_list_parent_id" :options="$optionCourseList" required="true"/>
    <x-input type="number" title="Week" model="week" />
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
