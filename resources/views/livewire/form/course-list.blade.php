<form wire:submit="{{ $action }}">
    <x-select title="Course title" model="courseTitle" :options="$optionCourseTitle" required="true"/>
    <x-input title="Page title" model="pageTitle" required="true"/>
    <x-input title="Url" model="url" required="true"/>
    <x-input title="Next Url" model="urlNext"/>


    <x-select title="Gravity Form" model="gfFormId" :options="$optionWpGfForm"/>
{{--    <x-select title="Course tag require" model="courseTag" :options="$optionCourseTag"/>--}}
{{--    <x-select title="Course tag next" model="courseTagNext" :options="$optionCourseTag"/>--}}



    <div class="mt-3" wire:ignore>
        <label for="{{'courseTag'}}"
               class="block text-sm font-bold dark:text-light">
            Course tag require
        </label>
        <select id="{{'courseTag'}}"
                class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white select2"
                multiple=""
                name="courseTag"
                style="padding:0  100px" wire:model="{{'courseTag'}}">
            @for($i=0;$i<count($optionCourseTag) ;$i++)
                <option value="{{$optionCourseTag[$i]['value']}}"
                        style="padding: 0 25px"
                    {{ ($optionCourseTag[$i]['value']==$courseTag)?'selected':''}}
                >
                    {{$optionCourseTag[$i]['title']}}
                </option>
            @endfor
        </select>
        <script>
            document.addEventListener('livewire:init', function () {
                let data;
                $('#courseTag').select2({
                    maximumSelectionLength: 1
                })
                $('#courseTag').on('change', function (e) {

                    data = $('#{{'courseTag'}}').select2("val");
                    // console.log(data[0])
                    @this.set('{{'courseTag'}}', data);
                })
            });
        </script>
    </div>

    <div class="mt-3" wire:ignore>
        <label for="{{'courseTagNext'}}"
               class="block text-sm font-bold dark:text-light">
            Course tag next
        </label>
        <select id="{{'courseTagNext'}}"
                class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white select2"
                multiple=""
                name="courseTagNext"
                style="padding:0  100px" wire:model="{{'courseTagNext'}}">
            @for($i=0;$i<count($optionCourseTag) ;$i++)
                <option value="{{$optionCourseTag[$i]['value']}}"
                        style="padding: 0 25px"
                    {{ ($optionCourseTag[$i]['value']==$courseTagNext)?'selected':''}}
                >
                    {{$optionCourseTag[$i]['title']}}
                </option>
            @endfor
        </select>
        <script>
            document.addEventListener('livewire:init', function () {
                let data;
                $('#courseTagNext').select2({
                    maximumSelectionLength: 1
                })
                $('#courseTagNext').on('change', function (e) {
                    data = $('#{{'courseTagNext'}}').select2("val");
                    @this.
                    set('{{'courseTagNext'}}', data);
                })
            });
        </script>
    </div>


    @if($courseTagNext!=null)
        <x-input title="Delay between this course with parent" model="delay" type="number"/>
    @endif

    <div style="width: 100px">
        <x-input type="checkbox" title="Repeat Entry" model="repeatEntry"/>
    </div>

{{--    <x-select title="Direct input from Gravity Form if user finish course" model="courseTagParent" :options="$optionCourseTag"/>--}}

    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
