<div class="col-span-12">
    <div class="overflow-auto " style="width: 100%">
        <div>
            <x-input title="Title file export" model="title"/>
            <div class="mt-3">

            <x-input title="Header format" model="headerFormat"/>
            <div><b>[course_title]</b> for full course title</div>
            <div><b>[clean_course_title]</b> for clean course title</div>
            <div><b>[question]</b> for full question</div>
            <div><b>[clean_question]</b> for clean question</div>
            </div>
            {{--            <x-select title="Type" model="typeUser" :options="$optionTypeUser" required="true"/>--}}
            <div class="mt-3" wire:ignore>
                <label for="{{'dataUsers'}}"
                       class="block text-sm font-bold dark:text-light">
                    Users
                </label>
                <select id="{{'dataUsers'}}"
                        class=" appearance-none border-1 border border-gray-100 rounded w-full
                        text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100
                        dark:bg-dark
                        dark:text-light
                        bg-white
                        focus:dark:border-white select2"
                        multiple=""
                        name="users"
                        style="padding:0  100px" wire:model="{{'users'}}">
                    @foreach($optionUsers as $option)
                        <option value="{{$option['value']}}"
                                style="padding: 0 25px">
                            {{$option['title']}}
                        </option>
                    @endforeach
                </select>
                <script>
                    document.addEventListener('livewire:init', function () {
                        let data;
                        $('#dataUsers').select2();
                        $('#dataUsers').on('change', function (e) {
                            data = $('#{{'dataUsers'}}').select2("val");
                            @this.
                            set('{{'users'}}', data);
                        })
                    });
                </script>

            </div>


            <div class="mt-3" wire:ignore>
                <label for="{{'dataCompanies'}}"
                       class="block text-sm font-bold dark:text-light">
                    Company
                </label>
                <select id="{{'dataCompanies'}}"
                        class=" appearance-none border-1 border border-gray-100 rounded w-full
                        text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100
                        dark:bg-dark
                        dark:text-light
                        bg-white
                        focus:dark:border-white select2"
                        multiple=""
                        name="users"
                        style="padding:0  100px" wire:model="{{'form.courseLists'}}">
                    @foreach($optionCompanies as $option)
                        <option value="{{$option['value']}}"
                                style="padding: 0 25px">
                            {{$option['title']}}
                        </option>
                    @endforeach
                </select>

                <script>
                    document.addEventListener('livewire:init', function () {
                        let data;
                        $('#dataCompanies').select2();
                        $('#dataCompanies').on('change', function (e) {
                            data = $('#{{'dataCompanies'}}').select2("val");
                            @this.
                            set('{{'companies'}}', data);
                        })
                    });

                </script>
            </div>


            <div class="mt-3" wire:ignore>
                <label for="{{'courseGroupLists'}}"
                       class="block text-sm font-bold dark:text-light">
                    Course Group
                </label>
                <select id="{{'courseGroupLists'}}"
                        class=" appearance-none border-1 border border-gray-100 rounded w-full
                        text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100
                        dark:bg-dark
                        dark:text-light
                        bg-white
                        focus:dark:border-white select2"
                        multiple=""
                        name="users"
                        style="padding:0  100px" wire:model="{{'courseGroupLists'}}">
                    @foreach($optionCourseGroupLists as $option)
                        <option value="{{$option['value']}}"
                                style="padding: 0 25px">
                            {{$option['title']}}
                        </option>
                    @endforeach
                </select>

                
            </div>


            <div class="mt-3" wire:ignore>
                <label for="{{'datacourseLists'}}"
                       class="block text-sm font-bold dark:text-light">
                    Course List
                    <span class="text-sm text-gray-500 cursor-pointer float-right" onclick="clearCourseLists()">Clear</span>
                    </label>
                    <script>
                        function clearCourseLists() {
                            console.log('clear');
                            @this.set('courseLists', []);
                            $('#datacourseLists').val([]).trigger('change');
                        }
                    </script>
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

    $('#datacourseLists').on('change', function () {
        data = $('#datacourseLists').select2("val");
        @this.set('courseLists', data);
    });
});


document.addEventListener('livewire:init', function () {
    let data;
    $('#courseGroupLists').select2();

    $('#courseGroupLists').on('change', function () {
        data = $('#courseGroupLists').select2("val");

        @this.set('courseGroupLists', data);

        data.forEach(d => {
            const data2 = Object.values(@this.get('optionCourseLists2')[d] || {});
            const data3 = @this.get('courseLists') || [];

            // merge + remove duplicates
            const unique = [...new Set([...data3, ...data2])];

            // set ke Livewire
            @this.set('courseLists', unique);

            // set ke Select2
            $('#datacourseLists').val(unique).trigger('change');
        });
    });
});

                </script>

            </div>

            <div class="mt-3" wire:ignore>
                <label for="{{'fields'}}"
                       class="block text-sm font-bold dark:text-light">
                    Field Export
                    <span class="text-sm text-gray-500 cursor-pointer float-right" onclick="clearFields()">Clear</span> 
                    <span class="text-sm text-gray-500 mx-2 float-right"> | </span>
                    <span class="text-sm text-gray-500 cursor-pointer float-right" onclick="addAllFields()">Add All</span>
                </label>
                <script>
                    function clearFields() {
                        @this.set('fields', []);
                        $('#fields').val([]).trigger('change');
                    }
                </script>
                <script>
                    function addAllFields() {
                        @this.set('fields', @json($optionFields));
                        $('#fields').val(@json($optionFields)).trigger('change');
                    }
                </script>
                <select id="{{'fields'}}"
                        class=" appearance-none border-1 border border-gray-100 rounded w-full
                        text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100
                        dark:bg-dark
                        dark:text-light
                        bg-white
                        focus:dark:border-white select2"
                        multiple=""
                        name="users"
                        style="padding:0  100px" wire:model="{{'fields'}}">
                    @foreach($optionFields as $option)
                        <option value="{{$option}}" style="padding: 0 25px" {{ in_array($option,$fields) }}>
                            {{$option}}
                        </option>
                    @endforeach
                </select>
                <script>
                    document.addEventListener('livewire:init', function () {
                        let data;
                        $('#fields').select2();
                        $('#fields').on('change', function (e) {
                            data = $('#{{'fields'}}').select2("val");
                            @this.
                            set('{{'fields'}}', data);
                        })
                    });
                </script>
            </div>

            
            <button class="btn btn-success" wire:click="getData">Show Data</button>
            <button wire:click="exportCsv"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Download CSV
            </button>


        </div>
        <br>
    </div>
    <div class="overflow-auto " style="width: 100%; padding: 10px">
        @if($field_target!=[])
            <table
                class="border-collapse border-wishka-400 w-full text-sm text-center rounded table-auto">
                <thead class=" text-md text-uppercase uppercase dark:bg-dark  text-bold">
         
                <tr class="border border-gray-200 border-collapse">
                    <td class="text-nowrap border py-4 px-6 font-bold">Name</td>
                    @foreach($field_target as $field)
                        @isset($field['title'])
                            @foreach($field['title'] as $k=>$f)
                                <td class="text-nowrap border py-4 px-6 font-bold">{{ $this->getHeaderFormat($field['form_title'], $f) }}</td>
                            @endforeach
                        @endisset
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach($userLists as $user)
                    <tr class="border border-gray-200 ">
                        <td class="py-4 px-6 border font-bold">{{ $user->user_nicename }}</td>
                        @foreach($field_target as $form_id=>$field)
                            @isset($field['title'])
                                @foreach($field['title'] as $k=>$f)
                                    <td class="border ">
                                        {{ $results[$user->ID][$form_id]['data'][$k]??'-' }}
                                    </td>
                                @endforeach
                            @endisset
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
            <br><br>


        @endif

    </div>
</div>
