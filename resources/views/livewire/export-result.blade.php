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

                    <div class="mt-8 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-600 dark:bg-dark">
                        <h3 class="mb-2 border-b border-gray-200 pb-2 text-left text-sm font-bold text-gray-900 dark:border-gray-600 dark:text-light">Participation count by activity</h3>
                        <div id="export-result-bar-horizontal" class="w-full min-h-[200px]"></div>
                    </div>
                    @endif

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
                        dataLabels: { position: 'end' },
                    },
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return val; },
                    
                    style: { fontSize: '11px' },
                },
                stroke: { width: 1, colors: ['#fff'] },
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
