<form wire:submit="{{ $action }}">
    <x-select title="Course title" model="courseTitle" :options="$optionCourseTitle" required="true"/>
    <x-input title="Page title" model="pageTitle" required="true"/>
    <x-input title="Url" model="url" required="true"/>

    <x-select title="Gravity Form" model="gfFormId" :options="$optionWpGfForm"/>
    <x-select title="Course tag require" model="courseTag" :options="$optionCourseTag"/>
    <x-select title="Course tag parent" model="courseTagParent" :options="$optionCourseTag"/>


    @if($courseTagParent!=null)
        <x-input title="Delay between this course with parent" model="delay" type="number"/>
    @endif

    <x-select title="Direct input from Gravity Form if user finish course" model="courseTagParent" :options="$optionCourseTag"/>

    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
