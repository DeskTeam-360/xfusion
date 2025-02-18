<form wire:submit="{{ $action }}">
    <x-input title="Title" model="title" required="true"/>
    <x-input title="Sub Title" model="subTitle" required="true"/>
    <x-select title="Type" model="type" :options="$optionCourseType" required="true"/>
{{--    <x-select2 title="Course list" model="courseLists" :options="$optionCourseTitle" required="true"/>--}}

{{--    @props(['repository'])--}}
    <div class="mt-3" wire:ignore>
        <label for="{{'datacourseLists'}}"
               class="block text-sm font-bold dark:text-light">
            Course List
        </label>
        <select id="{{'datacourseLists'}}"
                class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white select2"
                multiple=""

                name="courseLists"
                style="padding:0  100px" wire:model="{{'form.courseLists'}}">
            @for($i=0;$i<count($optionCourseTitle) ;$i++)
                <option value="{{$optionCourseTitle[$i]['value']}}"
                        style="padding: 0 25px"
                        {{ in_array($optionCourseTitle[$i]['value'],$courseLists)?'selected':''}}
                >
                    {{$optionCourseTitle[$i]['title']}} . {{$optionCourseTitle[$i]['value']}}
                </option>
            @endfor
        </select>
        <script>
            document.addEventListener('livewire:init', function () {
                let data;
                $('#datacourseLists').select2();
                $('#datacourseLists').on('change', function (e) {
                    data = $('#{{'datacourseLists'}}').select2("val");
                    @this.set('{{'courseLists'}}', data);
                })
            });

        </script>

    </div>



    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
