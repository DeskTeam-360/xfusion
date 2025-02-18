<form wire:submit="{{ $action }}">
    <x-input title="Title" model="title" required="true"/>
    <x-input title="Sub Title" model="subTitle" required="true"/>
    <x-select title="Type" model="type" :options="$optionCourseType" required="true"/>
    <x-select2 title="Course list" model="courseLists" :options="$optionCourseTitle" required="true"/>
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
