<form wire:submit="{{ $action }}">
    <x-select title="Course title" model="courseTitle" :options="$optionCourseTitle" required="true"/>
    <x-select title="Course tag" model="courseTag" :options="$optionCourseTag"/>
    <x-select title="Gravity Form" model="gfFormId" :options="$optionWpGfForm"/>
    <x-input title="Page title" model="pageTitle" required="true"/>

    <x-input title="Url" model="url" required="true"/>
    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
