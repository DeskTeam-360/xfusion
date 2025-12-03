<div class="col-span-12">
    <div class="overflow-auto " style="width: 100%">
        <div>
            <x-input title="Title file export" model="title"/>

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
            <div class="text-center">

                <hr style="display: inline-block; width: 40%; margin: 0 10px;"> Human readable <hr style="display: inline-block; width: 40%; margin: 0 10px;">
            </div>
            <div class="mt-3 ">

<x-input title="Header format" model="headerFormat"/>
<div><b>[course_title]</b> for full course title</div>
<div><b>[clean_course_title]</b> for clean course title</div>
<div><b>[question]</b> for full question</div>
<div><b>[clean_question]</b> for clean question</div>
</div>
<br>


            
<button class="btn btn-success" 
        wire:click="getData" 
        wire:loading.attr="disabled" 
        wire:target="getData">
    Show Data
</button>

<button wire:click="exportCsv" 
        class="btn btn-primary"
        wire:loading.attr="disabled"
        wire:target="exportCsv">
    Download CSV
</button>
<!-- Spinner hanya tampil saat getData loading -->
<div class="spinner-border text-primary" 
     role="status" 
     wire:loading 
     wire:target="getData">
    <span class="visually-hidden">Loading...</span>
</div>



<div class="spinner-border text-primary" 
     role="status" 
     wire:loading 
     wire:target="exportCsv">
    <span class="visually-hidden">Loading...</span>
</div>


<div class="overflow-auto " style="width: 100%; padding: 10px">
        @if($field_target!=[] and $table==1)
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

<div class="text-center mt-3">

<hr style="display: inline-block; width: 40%; margin: 0 10px;"> Pivot readable <hr style="display: inline-block; width: 40%; margin: 0 10px;">
</div>

<div class="mt-3">
<label for="headerFormatPivot1">Course Title Format</label>
<select wire:model.live="headerFormatPivot1" class="form-control">
    @foreach($headerFormatPivotOption as $option)
        <option value="{{$option}}">{{$option}}</option>
        @endforeach
    </select>
</div>
<div class="mt-3">
    <label for="headerFormatPivot2">Question Format</label>
    <select wire:model.live="headerFormatPivot2" class="form-control">
        @foreach($headerFormatPivotOption as $option)
            <option value="{{$option}}">{{$option}}</option>
        @endforeach
    </select>
</div>
<div class="mt-3">
    <button class="btn btn-success" 
            wire:click="getData2" 
            wire:loading.attr="disabled" 
            wire:target="getData2">
        Show Data
    </button>

    <button wire:click="exportCsv2" 
            class="btn btn-success"
            wire:loading.attr="disabled"
            wire:target="exportCsv2">
        Download CSV
    </button>

    <!-- Spinner hanya tampil saat getData loading -->
    <div class="spinner-border text-primary" 
        role="status" 
        wire:loading 
        wire:target="getData2">
        <span class="visually-hidden">Loading...</span>
    </div>



    <div class="spinner-border text-success" 
        role="status" 
        wire:loading 
        wire:target="exportCsv2">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
            

        </div>
        <br>
    </div>
   


    <div class="overflow-auto " style="width: 100%; padding: 10px">
        @if($field_target!=[] and $table==2)
            <table
                class="border-collapse border-wishka-400 w-full text-sm text-center rounded table-auto">
                <thead class=" text-md text-uppercase uppercase dark:bg-dark  text-bold">
         
                <tr class="border border-gray-200 border-collapse">
                    <td class="text-nowrap border py-4 px-6 font-bold">Name</td>
                    <td class="text-nowrap border py-4 px-6 font-bold">Course Title</td>
                    <td class="text-nowrap border py-4 px-6 font-bold">Question</td>
                    <td class="text-nowrap border py-4 px-6 font-bold">Answer</td>
                </tr>
                </thead>
                <tbody>
                @foreach($userLists as $user)
                    @foreach ($this->field_target as $form_id => $field)
                        @isset($field['title'])
                            @foreach($field['title'] as $k=>$f)
                                @php    
                                if ($headerFormatPivot1 == 'Clean') {
                                    $clean_course_title = $this->getCleanHeaderFormat($field['form_title']);
                                } else {
                                    $clean_course_title = $field['form_title'];
                                }
                                if ($headerFormatPivot2 == 'Clean') {
                                    $clean_question = $this->getCleanHeaderFormat($f);
                                } else {
                                    $clean_question = $f;
                                }
                                @endphp
                                <tr class="border border-gray-200 ">
                                    <td class="py-4 px-6 border font-bold">{{ $user->user_nicename }}</td>
                                    <td class="border ">
                                        {{ $clean_course_title }}
                                    </td>
                                    <td class="border ">
                                        {{ $clean_question }}
                                    </td>
                                    <td class="border ">
                                        {{ $results[$user->ID][$form_id]['data'][$k]??'-' }}
                                    </td>
                                </tr>
                            @endforeach
                            
                        @endisset
                    @endforeach
                @endforeach
                    
                </tbody>
            </table>
            @endif
        </div>

</div>
