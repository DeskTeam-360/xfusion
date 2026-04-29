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

            {{-- Pivot readable --}}
            <div class="text-center mt-4">
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

            <div class="overflow-auto " style="width: 100%; padding: 10px">
                @if($field_target!=[] and $table==1)
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

                    @if($activityFooterStats !== [])
                    <div class="mt-8 grid gap-8 lg:grid-cols-2">
                        {{-- 5.1 Participating users vs non-participating (no activity completed) --}}
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-600">
                            <h3 class="mb-3 text-sm font-bold">Participation vs non-participation (all users)</h3>
                            @php
                                $pPie = $chartUserParticipationPie;
                                $pPct = max(0, min(100, (float) ($pPie['pct'] ?? 0)));
                                $npPct = max(0, min(100, 100 - $pPct));
                            @endphp
                            <div class="flex flex-wrap items-center gap-6">
                                <div
                                    class="shrink-0 rounded-full border-4 border-white shadow"
                                    style="width:140px;height:140px;background:conic-gradient(#2563eb 0% {{ $pPct }}%, #cbd5e1 {{ $pPct }}% 100%);"
                                    title="Participating {{ $pPie['participating'] ?? 0 }}, non-participating {{ $pPie['non_participating'] ?? 0 }}"
                                ></div>
                                <div class="text-sm text-left space-y-1">
                                    <div><span class="inline-block h-3 w-3 rounded-sm bg-blue-600 align-middle mr-2"></span> Participating (completed ≥1 activity): <strong>{{ $pPie['participating'] ?? 0 }}</strong></div>
                                    <div><span class="inline-block h-3 w-3 rounded-sm bg-slate-300 align-middle mr-2"></span> Non-participating (completed 0): <strong>{{ $pPie['non_participating'] ?? 0 }}</strong></div>
                                    <div class="text-gray-600 dark:text-gray-400">Share of chart: {{ number_format($pPct, 1) }}% / {{ number_format($npPct, 1) }}%</div>
                                </div>
                            </div>
                        </div>

                        {{-- 5.2 Same pie, by work type --}}
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-600">
                            <h3 class="mb-3 text-sm font-bold">Participation vs non-participation by work type</h3>
                            <div class="flex flex-wrap gap-6">
                                @forelse($chartUserParticipationPieByWorkType as $wtPie)
                                    @php
                                        $wPct = max(0, min(100, (float) ($wtPie['pct'] ?? 0)));
                                        $wRest = max(0, min(100, 100 - $wPct));
                                    @endphp
                                    <div class="text-center">
                                        <div
                                            class="mx-auto rounded-full border-2 border-white shadow"
                                            style="width:100px;height:100px;background:conic-gradient(#0d9488 0% {{ $wPct }}%, #cbd5e1 {{ $wPct }}% 100%);"
                                            title="{{ $wtPie['label'] }}: {{ $wtPie['participating'] }} / {{ $wtPie['non_participating'] }}"
                                        ></div>
                                        <div class="mt-2 max-w-[140px] text-xs font-medium truncate" title="{{ $wtPie['label'] }}">{{ $wtPie['label'] }}</div>
                                        <div class="text-xs text-gray-600">P: {{ $wtPie['participating'] }} · NP: {{ $wtPie['non_participating'] }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($wPct, 0) }}% / {{ number_format($wRest, 0) }}%</div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No users in selection.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- 5.3 Horizontal participation (thin rule, not thick bars); title once; labels left only --}}
                    <div class="mt-8 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-600 dark:bg-dark">
                        <h3 class="mb-4 border-b border-gray-200 pb-2 text-left text-sm font-bold text-gray-900 dark:border-gray-600 dark:text-light">Participation count by activity</h3>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($chartParticipationBar as $bar)
                                <div class="flex items-center gap-4 py-2.5">
                                    <div
                                        class="min-w-0 max-w-[min(42vw,22rem)] shrink-0 text-left text-xs leading-snug text-gray-800 dark:text-gray-200 sm:text-sm"
                                        title="{{ $bar['full_label'] ?? $bar['axis_label'] }}"
                                    >{{ $bar['axis_label'] }}</div>
                                    <div class="relative min-h-[6px] min-w-0 flex-1">
                                        <div class="h-[6px] w-full rounded-full bg-gray-100 dark:bg-gray-700"></div>
                                        <div
                                            class="absolute left-0 top-0 h-[6px] rounded-full transition-[width] duration-300"
                                            style="width: {{ number_format($bar['width_pct'], 1) }}%; min-width: {{ $bar['count'] > 0 ? '4px' : '0' }}; background-color: {{ $bar['color'] }};"
                                        ></div>
                                    </div>
                                    <div class="w-9 shrink-0 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-light">{{ $bar['count'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <br><br>
                @endif
            </div>

        </div>
        <br>
    </div>
</div>
