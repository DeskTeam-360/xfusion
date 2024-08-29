<form wire:submit="{{ $action }}">
    <x-select title="Course title" model="courseTitle" :options="$optionCourseTitle" required="true"/>
    <x-input title="Page title" model="pageTitle" required="true"/>
    <x-input title="Url" model="url" required="true"/>
    <input type="submit" class="btn" value="Submit">
</form>
