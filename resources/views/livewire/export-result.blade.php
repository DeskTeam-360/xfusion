<div class="col-span-12">
    <div class="overflow-auto " style="width: 100%">
        <div>
            @if(!$isCompanyDashboard)
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

            const unique = [...new Set([...data3, ...data2])];

            @this.set('courseLists', unique);

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
            @else
            {{-- Company Group filter --}}
            <div class="mb-5">
                <label for="dashboard-company-group" class="form-label mb-2 block text-sm font-bold dark:text-light">
                    Company Group
                </label>
                <select
                    id="dashboard-company-group"
                    class="form-control w-full rounded border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-dark dark:text-light"
                    wire:model.live="companyGroupId"
                >
                    <option value="">All groups</option>
                    @foreach($companyGroupOptions as $cg)
                        <option value="{{ $cg['id'] }}">{{ $cg['title'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- RPM Gauges per Course Scoring Group --}}
            @if(count($gauges) > 0)
            <div class="mb-8">
                <h2 class="mb-4 text-base font-bold text-dark dark:text-white">Course Scoring Overview</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach($gauges as $gauge)
                    @php
                        $needleDeg  = $gauge['needle_deg'];
                        $avg        = $gauge['average'];
                        $zoneColor  = $gauge['zone_color'];
                        $zoneLabel  = $gauge['zone_label'];
                        $title      = $gauge['title'];
                        $partCount  = $gauge['participant_count'];
                        $noData     = $gauge['no_data_count'];
                    @endphp
                    <div class="flex flex-col items-center rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm dark:border-gray-700 dark:bg-darkgray">
                        {{-- SVG Gauge --}}
                        <svg viewBox="0 0 220 145" class="w-full max-w-[10rem]" role="img" aria-label="{{ $title }} gauge">
                            {{-- Red zone: 0→3 --}}
                            <path fill="none" stroke="#dc2626" stroke-width="10" stroke-linecap="round"
                                  d="M 35.000 110.000 A 75 75 0 0 1 133.176 38.670"/>
                            {{-- Amber zone: 3→4.5 --}}
                            <path fill="none" stroke="#ca8a04" stroke-width="10" stroke-linecap="round"
                                  d="M 133.176 38.670 A 75 75 0 0 1 181.330 86.824"/>
                            {{-- Green zone: 4.5→5 --}}
                            <path fill="none" stroke="#16a34a" stroke-width="10" stroke-linecap="round"
                                  d="M 181.330 86.824 A 75 75 0 0 1 185.000 110.000"/>
                            {{-- Tick marks 1–4 (inner) --}}
                            <line x1="68.0" y1="79.4" x2="77.8" y2="85.2" stroke="#9ca3af" stroke-opacity=".45" stroke-width="2" stroke-linecap="round"/>
                            <text x="68" y="71" text-anchor="middle" fill="#9ca3af" font-size="10">1</text>
                            <line x1="93.9" y1="60.6" x2="100.4" y2="70.1" stroke="#9ca3af" stroke-opacity=".45" stroke-width="2" stroke-linecap="round"/>
                            <text x="88" y="52" text-anchor="middle" fill="#9ca3af" font-size="10">2</text>
                            <line x1="126.1" y1="60.6" x2="119.6" y2="70.1" stroke="#9ca3af" stroke-opacity=".45" stroke-width="2" stroke-linecap="round"/>
                            <text x="132" y="52" text-anchor="middle" fill="#9ca3af" font-size="10">3</text>
                            <line x1="152.1" y1="79.4" x2="142.2" y2="85.2" stroke="#9ca3af" stroke-opacity=".45" stroke-width="2" stroke-linecap="round"/>
                            <text x="152" y="71" text-anchor="middle" fill="#9ca3af" font-size="10">4</text>
                            {{-- Min label (left end = 0) --}}
                            <text x="22" y="127" text-anchor="middle" fill="#dc2626" font-size="11" font-weight="700">Min</text>
                            <text x="22" y="139" text-anchor="middle" fill="#6b7280" font-size="10">0</text>
                            {{-- Max label (right end = 5) --}}
                            <text x="198" y="127" text-anchor="middle" fill="#16a34a" font-size="11" font-weight="700">Max</text>
                            <text x="198" y="139" text-anchor="middle" fill="#6b7280" font-size="10">5</text>
                            {{-- Needle --}}
                            <g transform="rotate({{ $needleDeg }} 110 110)">
                                <line x1="110" y1="112" x2="110" y2="36" fill="none" stroke="#1f2937" stroke-width="4" stroke-linecap="round"/>
                            </g>
                            <circle cx="110" cy="110" r="7" fill="#1f2937"/>
                            <circle cx="110" cy="110" r="4" fill="#ffffff"/>
                        </svg>
                        {{-- Labels --}}
                        <h3 class="mt-1 min-h-[2.5rem] text-xs font-semibold leading-tight text-dark dark:text-white" title="{{ $title }}">{{ $title }}</h3>
                        <p class="text-lg font-bold tabular-nums text-dark dark:text-white">
                            {{ $avg !== null ? number_format($avg, 2) : '—' }}
                        </p>
                        <p class="text-xs font-semibold" style="color:{{ $zoneColor }}">{{ $zoneLabel }}</p>
                        <p class="mt-1 text-[11px] text-gray-400 tabular-nums">
                            {{ $partCount }} participant{{ $partCount !== 1 ? 's' : '' }}{{ $noData > 0 ? ', '.$noData.' no data' : '' }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <div class="mb-4">
                <label for="dashboard-course-group" class="form-label mb-2 block text-sm font-bold dark:text-light">
                    Course group
                </label>
                <select
                    id="dashboard-course-group"
                    class="form-control w-full rounded border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-dark dark:text-light"
                    wire:model.live="dashboardCourseGroupId"
                >
                    <option value="">— Select course group —</option>
                    @foreach($optionCourseGroupLists as $option)
                        <option value="{{ $option['value'] }}">{{ $option['title'] }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Fields load automatically for company employees. Select which Gravity Forms field types to include below. Charts (pie / bar) show only when this course group has charts enabled <em>and</em> every included column is a radio field—e.g. uncheck text/textarea to show charts; mixed types hide charts but keep the table.
                </p>
                <div class="mt-3" wire:ignore>
                    <label for="company-dashboard-fields" class="mb-1 block text-sm font-bold dark:text-light">
                        Field types to include
                        <span class="text-sm text-gray-500 cursor-pointer float-right" onclick="clearCompanyDashboardFields()">Clear</span>
                        <span class="text-sm text-gray-500 mx-2 float-right"> | </span>
                        <span class="text-sm text-gray-500 cursor-pointer float-right" onclick="addAllCompanyDashboardFields()">Add All</span>
                    </label>
                    <script>
                        function clearCompanyDashboardFields() {
                            @this.set('fields', []);
                            $('#company-dashboard-fields').val([]).trigger('change');
                        }
                        function addAllCompanyDashboardFields() {
                            @this.set('fields', @json($optionFields));
                            $('#company-dashboard-fields').val(@json($optionFields)).trigger('change');
                        }
                    </script>
                    <select
                        id="company-dashboard-fields"
                        class="appearance-none border-1 border border-gray-100 rounded w-full
                        text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100
                        dark:bg-dark dark:text-light bg-white focus:dark:border-white select2"
                        multiple
                        name="company_dashboard_fields"
                        style="padding:0 100px"
                        wire:model="fields"
                    >
                        @foreach($optionFields as $option)
                            <option
                                value="{{ $option }}"
                                style="padding: 0 25px"
                                {{ is_array($fields) && in_array($option, $fields, true) ? 'selected' : '' }}
                            >{{ $option }}</option>
                        @endforeach
                    </select>
                    <script>
                        document.addEventListener('livewire:init', function () {
                            $('#company-dashboard-fields').select2();
                            $('#company-dashboard-fields').on('change', function () {
                                var data = $('#company-dashboard-fields').select2("val");
                                @this.set('fields', data || []);
                            });
                        });
                    </script>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Tip: choose <strong>only radio</strong> to enable charts (when the course group allows charts).
                    </p>
                </div>
                <div wire:loading class="mt-2 flex items-center gap-2 text-sm text-primary">
                    <span class="spinner-border spinner-border-sm inline-block align-middle" role="status"></span>
                    <span>Loading report and charts…</span>
                </div>
            </div>
            @endif

            {{-- Pivot readable --}}
            <div class="text-center mt-4">
                <hr style="display: inline-block; width: 40%; margin: 0 10px;"> Pivot readable <hr style="display: inline-block; width: 40%; margin: 0 10px;">
            </div>
            
            <div class="flex gap-2">
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

            <div class="overflow-auto " style="width: 100%; padding: 10px">
                @if($field_target!=[] and $table==2)
                    <table
                        class="border-collapse border-wishka-400 w-full text-sm text-center rounded table-auto">
                        <thead class=" text-md text-uppercase uppercase dark:bg-dark  text-bold">
                        <tr class="border border-gray-200 border-collapse">
                            <td class="text-nowrap border py-4 px-6 font-bold">Name</td>
                            <td class="text-nowrap border py-4 px-6 font-bold">Course</td>
                            <td class="text-nowrap border py-4 px-6 font-bold">Lesson</td>
                            <td class="text-nowrap border py-4 px-6 font-bold">Topic</td>
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
                                            <td class="border py-4 px-6 ">
                                                {{ $field['ld_course_title'] }}
                                            </td>
                                            <td class="border py-4 px-6 ">
                                                {{ $field['ld_lesson_title'] }}
                                            </td>
                                            <td class="border text-left py-4 px-6 ">
                                            {{ $field['sort_order'] }}. {{ $field['ld_topic_title'] }}
                                            </td>
                                            <td class="border text-left py-4 px-6 ">
                                                {{ $clean_question }}
                                            </td>
                                            <td class="border text-left py-4 px-6 ">
                                                {{ $this->displayAnswer($user->ID, $form_id, $k) }}
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

            {{-- Human readable --}}
            <div class="text-center mt-5">
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

            <div class="overflow-auto " style="width: 100%; margin-top: 20px">
                @if($field_target!=[] and $table==1)
                    @if($humanReadableChartsEnabled && $activityFooterStats !== [])
                    <div wire:key="export-charts-{{ $dashboardCourseGroupId }}-{{ count($activityFooterStats) }}"
                         class="mb-8 grid gap-8 lg:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-600">
                            <h3 class="mb-2 text-sm font-bold">Participation vs non-participation (all users)</h3>
                            <div id="export-result-pie-overall" class="min-h-[280px] w-full"></div>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-600">
                            <h3 class="mb-2 text-sm font-bold">Participation vs non-participation by work type</h3>
                            <div class="flex flex-wrap justify-start gap-4">
                                @forelse($chartUserParticipationPieByWorkType as $idx => $wtPie)
                                    <div class="w-[220px] shrink-0 rounded border border-gray-100 p-2 dark:border-gray-700">
                                        <div id="export-result-wt-chart-{{ $idx }}" class="min-h-[200px] w-full"></div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No users in selection.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div wire:key="export-bar-{{ $dashboardCourseGroupId }}"
                         class="mb-8 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-600 dark:bg-dark">
                        <h3 class="mb-2 border-b border-gray-200 pb-2 text-left text-sm font-bold text-gray-900 dark:border-gray-600 dark:text-light">Participation count by activity</h3>
                        <div id="export-result-bar-horizontal" class="w-full min-h-[200px]"></div>
                    </div>
                    @endif

                    <table
                        class="border-collapse border-wishka-400 w-full text-sm text-center rounded table-auto">
                        <thead class=" text-md text-uppercase uppercase dark:bg-dark  text-bold">
                        <tr class="border border-gray-200 border-collapse">
                            <td class="text-nowrap border py-4 px-6 font-bold">Name</td>
                            <td class="text-nowrap border py-4 px-6 font-bold">Work Type</td>
                            @foreach($field_target as $field)
                                @isset($field['title'])
                                    @foreach($field['title'] as $k=>$f)
                                        <td class="text-nowrap border py-4 px-6 font-bold">{{ $this->getHeaderFormat($field['form_title'], $f) }}</td>
                                    @endforeach
                                @endisset
                            @endforeach
                            <td class="text-nowrap border py-4 px-6 font-bold bg-slate-50 dark:bg-slate-800">Activities Complete</td>
                            <td class="text-nowrap border py-4 px-6 font-bold bg-slate-50 dark:bg-slate-800">% Complete</td>
                            <td class="text-nowrap border py-4 px-6 font-bold bg-slate-50 dark:bg-slate-800">Average Score</td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($userLists as $user)
                            @php
                                $uStats = $userRowStats[$user->ID] ?? ['complete' => 0, 'pct' => null, 'avg_score' => null];
                                $wt = $workTypeByUser[$user->ID] ?? '';
                            @endphp
                            <tr class="border border-gray-200 ">
                                <td class="py-4 px-6 border font-bold">{{ $user->user_nicename }}</td>
                                <td class="py-4 px-6 border">{{ $wt !== '' ? $wt : '-' }}</td>
                                @foreach($field_target as $form_id=>$field)
                                    @isset($field['title'])
                                        @foreach($field['title'] as $k=>$f)
                                            <td class="border ">
                                                {{ $this->displayAnswer($user->ID, $form_id, $k) }}
                                            </td>
                                        @endforeach
                                    @endisset
                                @endforeach
                                <td class="border bg-slate-50 dark:bg-slate-800 font-medium">{{ $uStats['complete'] }}</td>
                                <td class="border bg-slate-50 dark:bg-slate-800 font-medium">{{ $uStats['pct'] !== null ? $uStats['pct'].'%' : '-' }}</td>
                                <td class="border bg-slate-50 dark:bg-slate-800 font-medium">{{ $uStats['avg_score'] !== null ? $uStats['avg_score'] : '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        @if($activityFooterStats !== [])
                        <tfoot>
                            <tr class="border border-gray-200 bg-amber-50/80 dark:bg-amber-900/20 font-semibold">
                                <td colspan="2" class="text-left border py-3 px-4">Participation Count</td>
                                @foreach($activityFooterStats as $fs)
                                    <td class="border py-3 px-2">{{ $fs['participation_count'] }}</td>
                                @endforeach
                                <td colspan="3" class="border"></td>
                            </tr>
                            <tr class="border border-gray-200 bg-amber-50/60 dark:bg-amber-900/15 font-semibold">
                                <td colspan="2" class="text-left border py-3 px-4">Activity participation %</td>
                                @foreach($activityFooterStats as $fs)
                                    <td class="border py-3 px-2">{{ $fs['participation_rate'] !== null ? $fs['participation_rate'].'%' : '-' }}</td>
                                @endforeach
                                <td colspan="3" class="border"></td>
                            </tr>
                            <tr class="border border-gray-200 bg-amber-50/40 dark:bg-amber-900/10 font-semibold">
                                <td colspan="2" class="text-left border py-3 px-4">Avg Activity Assessment</td>
                                @foreach($activityFooterStats as $fs)
                                    <td class="border py-3 px-2">{{ $fs['avg_assessment'] !== null ? $fs['avg_assessment'] : '-' }}</td>
                                @endforeach
                                <td colspan="3" class="border"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>

                    <br><br>
                @endif
            </div>

        </div>
        <br>
    </div>
</div>

<script>
(function () {
    function bindChartsListener() {
        if (window.__exportResultChartsBound) {
            return;
        }
        if (typeof Livewire === 'undefined' || typeof Livewire.on !== 'function') {
            return;
        }
        window.__exportResultChartsBound = true;
        Livewire.on('export-result-charts-updated', function (e) {
            var payload = parsePayload(e);
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    renderExportResultCharts(payload);
                });
            });
        });
    }

    document.addEventListener('livewire:init', bindChartsListener);
    bindChartsListener();

    function destroyExportResultCharts() {
        (window.__exportResultApexCharts || []).forEach(function (c) {
            try { c.destroy(); } catch (e) {}
        });
        window.__exportResultApexCharts = [];
    }

    function registerChart(chart) {
        window.__exportResultApexCharts = window.__exportResultApexCharts || [];
        window.__exportResultApexCharts.push(chart);
    }

    function parsePayload(e) {
        var p = e;
        if (e && e.detail && e.detail.pie !== undefined) {
            p = e.detail;
        }
        return {
            pie: p.pie || { participating: 0, non_participating: 0, pct: 0 },
            pieByWt: Array.isArray(p.pieByWt) ? p.pieByWt : [],
            bar: Array.isArray(p.bar) ? p.bar : [],
        };
    }

    function renderExportResultCharts(payload) {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        destroyExportResultCharts();

        var pie = payload.pie;
        var elPie = document.querySelector('#export-result-pie-overall');
        if (elPie && (pie.participating > 0 || pie.non_participating > 0)) {
            var chartPie = new ApexCharts(elPie, {
                series: [pie.participating, pie.non_participating],
                chart: {
                    type: 'pie',
                    height: 280,
                    fontFamily: 'inherit',
                    foreColor: '#94a3b8',
                    toolbar: { show: false },
                },
                labels: ['Participating', 'Non-participating'],
                colors: ['#2563eb', '#cbd5e1'],
                legend: { position: 'bottom' },
                plotOptions: { pie: { size: '68%' } },
                dataLabels: { enabled: true },
                tooltip: { theme: 'dark' },
            });
            chartPie.render();
            registerChart(chartPie);
        }

        payload.pieByWt.forEach(function (row, idx) {
            var el = document.querySelector('#export-result-wt-chart-' + idx);
            if (!el) {
                return;
            }
            var p = row.participating || 0;
            var np = row.non_participating || 0;
            if (p === 0 && np === 0) {
                return;
            }
            var c = new ApexCharts(el, {
                series: [p, np],
                chart: {
                    type: 'pie',
                    height: 200,
                    fontFamily: 'inherit',
                    foreColor: '#94a3b8',
                    toolbar: { show: false },
                },
                labels: ['Participating', 'Non-participating'],
                colors: ['#0d9488', '#cbd5e1'],
                title: {
                    text: row.label || '',
                    align: 'center',
                    style: { fontSize: '11px', fontWeight: 600 },
                },
                legend: { show: true, position: 'bottom', fontSize: '10px' },
                plotOptions: { pie: { size: '62%' } },
                dataLabels: { enabled: true },
                tooltip: { theme: 'dark' },
            });
            c.render();
            registerChart(c);
        });

        var elBar = document.querySelector('#export-result-bar-horizontal');
        if (elBar && payload.bar.length > 0) {
            var counts = payload.bar.map(function (b) { return b.count; });
            var categories = payload.bar.map(function (b) { return b.axis_label || ''; });
            var colors = payload.bar.map(function (b) { return b.color || '#6366f1'; });
            var h = Math.max(260, payload.bar.length * 34 + 80);
            var chartBar = new ApexCharts(elBar, {
                series: [{ name: 'Participants (n)', data: counts }],
                chart: {
                    type: 'bar',
                    height: h,
                    fontFamily: 'inherit',
                    foreColor: '#94a3b8',
                    toolbar: { show: false },
                },
                colors: colors,
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '72%',
                        distributed: true,
                        // 'end' = di ujung kanan batang (sering putih & “bablas” ke luar). 'center' = tetap di area warna.
                        dataLabels: {
                            position: 'end',
                            hideOverflowingLabels: false,
                        },
                    },
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return val != null ? String(val) : ''; },
                    offsetX: 0,
                    offsetY: 0,
                    textAnchor: 'start',
                    // Warna angka: array panjang = jumlah batang (wajib untuk distributed), jangan biarkan default putih.
                    style: {
                        fontSize: '12px',
                        fontWeight: 700,
                        colors: counts.map(function () { return '#0f172a'; }),
                    },
                    dropShadow: {
                        enabled: true,
                        top: 0,
                        left: 0,
                        blur: 1,
                        color: '#ffffff',
                        opacity: 0.85,
                    },
                },
                stroke: { width: 1, colors: ['#ffffff'] },
                xaxis: {
                    categories: categories,
                    labels: {
                        style: { fontSize: '11px' },
                        maxHeight: 140,
                        trim: true,
                    },
                },
                yaxis: {
                    labels: { style: { fontSize: '11px' } },
                },
                grid: {
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                },
                legend: { show: false },
                tooltip: { theme: 'dark' },
            });
            chartBar.render();
            registerChart(chartBar);
        }
    }

})();
</script>
