@php use App\Models\Company;use App\Models\CompanyEmployee;use App\Models\CourseList;use App\Models\CourseScoringGroup;use App\Models\User;use Carbon\Carbon; @endphp
<x-admin-layout xmlns:livewire="http://www.w3.org/1999/html">

    <div class="px-5 text-3xl font-semibold text-dark dark:text-white">
        Dashboard
    </div>
    <div class="px-5 py-5">

    

        <div class="col-span-12 grid grid-cols-12 gap-3 items-stretch">

            <div class="lg:col-span-3 md:col-span-6 sm:col-span-6 col-span-12">
                <div class="card shadow-none w-full h-full" style="min-height: 280px">
                    <div class="card-body p-6">
                        <div class="flex items-center">
                            <div class="rounded-md bg-primary w-16 h-16 flex items-center justify-center text-white">
                                <i class="ti ti-file-description text-4xl"></i>
                            </div>

                            <div class="ms-auto text-primary flex gap-1 items-center">
                                <span class="text-xs font-semibold text-primary">See details</span>
                                <i class="ti ti-trending-up text-primary text-xl"></i>
                            </div>
                        </div>
                        <div class="items-center justify-between mt-5">
                            <h3 class="text-2xl">
                                {{ User::whereHas('meta',function ($q){$q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%contributor%');})->count()+Company::count() }}
                            </h3>
                            <br>
                            <span class="font-semibold card-subtitle text-xl">
                                Total contributor & Company
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3 col-span-12 flex flex-col gap-3" style="min-height: 280px">

                <div class="card flex-1">
                    <div class="card-body flex-row py-4 flex items-center gap-2 h-full">
                        <div class="bg-primary h-10 w-10 shrink-0 flex items-center justify-center text-white" style="border-radius: 100px">
                            <i class="ti ti-users text-2xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h5 class="xl:text-xl text-base leading-normal">
                                {{ User::whereHas('meta',function ($q){$q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%contributor%');})->count() }}
                            </h5>
                            <span class="text-md flex items-center gap-1">
                                Contributor
                            </span>
                        </div>
                        <a class="ms-auto text-2xl shrink-0" style="border-radius: 40px">
                            <i class="ti ti-arrow-up-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card flex-1">
                    <div class="card-body flex-row py-4 flex items-center gap-2 h-full">
                        <div class="bg-primary h-10 w-10 shrink-0 flex items-center justify-center text-white" style="border-radius: 100px">
                            <i class="ti ti-building-community text-2xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h5 class="xl:text-xl text-base leading-normal">
                                {{ Company::count() }}
                            </h5>
                            <span class="text-lg flex items-center gap-1">
                                Company
                            </span>
                        </div>
                        <a class="ms-auto text-2xl shrink-0" style="border-radius: 40px">
                            <i class="ti ti-arrow-up-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card flex-1">
                    <div class="card-body flex-row py-4 flex items-center gap-2 h-full">
                        <div class="bg-success h-10 w-10 shrink-0 flex items-center justify-center text-white" style="border-radius: 100px">
                            <i class="ti ti-user-check text-2xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h5 class="xl:text-xl text-base leading-normal">
                                {{ User::whereHas('meta',function ($q){
                $q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%subscriber%');
                })->count() }}
                            </h5>
                            <span class="text-lg flex items-center gap-1">
                                Employee
                            </span>
                        </div>
                        <a class="ms-auto text-2xl shrink-0" style="border-radius: 40px">
                            <i class="ti ti-arrow-up-right"></i>
                        </a>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-6 md:col-span-6 sm:col-span-12 col-span-12">
                <div class="card h-full dashboard-panel-card">
                    <div class="card-body pb-8 flex flex-col h-full">
                        <h5 class="card-title">User Growth</h5>
                        <p class="card-subtitle">Every month</p>
                        <div class="-me-12 flex-1">
                            <div id="salary" class="" ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php($courseCards = [
            ['title' => 'Revitalize', 'icon' => 'ti-bulb', 'color' => 'bg-success'],
            ['title' => 'Transform', 'icon' => 'ti-arrows-exchange-2', 'color' => 'bg-primary'],
            ['title' => 'Sustain', 'icon' => 'ti-seeding', 'color' => 'bg-info'],
        ])
        <h3 class="text-xl mt-5 mb-3">Courses</h3>
        <div class="col-span-12 grid grid-cols-12 gap-3" style="margin-bottom:20px;">
            @foreach($courseCards as $courseCard)
                <div class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12">
                    <div class="card h-full">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                            <div class="{{ $courseCard['color'] }} h-10 w-10 shrink-0 flex items-center justify-center text-white" style="border-radius: 100px">
                                <i class="ti {{ $courseCard['icon'] }} text-2xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h5 class="text-3xl leading-normal">
                                    {{ CourseList::where('course_title', $courseCard['title'])->count() }}
                                </h5>
                                <span class="text-lg flex items-center gap-1">
                                    {{ $courseCard['title'] }}
                                </span>
                            </div>
                            <span class="ms-auto shrink-0 text-muted dark:text-darklink" title="Course modules">
                                <i class="ti ti-book-2 text-2xl"></i>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-span-12 grid grid-cols-5 gap-3" style="margin-bottom:16px;">
            @forelse(CourseScoringGroup::withCount('details')->orderBy('id')->get() as $group)
                <div>
                    <a href="{{ route('course-scoring-group.edit', $group->id) }}" class="card h-full block hover:shadow-md transition-shadow">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                            <div class="bg-primary h-10 w-10 shrink-0 flex items-center justify-center text-white" style="border-radius: 100px">
                                <i class="ti ti-calculator text-2xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h5 class="text-xl leading-normal truncate" title="{{ $group->title }}">{{ $group->title }}</h5>
                                <span class="text-muted">{{ $group->details_count }} field{{ $group->details_count == 1 ? '' : 's' }}</span>
                            </div>
                            <span class="ms-auto shrink-0 text-muted dark:text-darklink">
                                <i class="ti ti-chevron-right text-2xl"></i>
                            </span>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="card">
                        <div class="card-body py-10 text-center text-muted dark:text-darklink">
                            No course scoring groups yet.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <style>
            .dashboard-panel-card {
                min-height: 220px;
            }
        </style>


        <script>
            @php($series = ['Employee', 'Contributor', 'Company'])
            document.addEventListener("DOMContentLoaded", function () {
                // =====================================
                // Salary
                // =====================================
                var options = {
                    series: [
                        {
                            name: "{{ $series[0] }}",
                            data: [
                                @for($i=0; $i<3;$i++)
                                    {{ User::whereHas('meta',function ($q){$q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%subscriber%');})->whereMonth('user_registered',Carbon::now()->subMonths(2-$i)->month )->whereYear('user_registered',Carbon::now()->subMonths(2-$i)->year )->get()->count()}},
                                @endfor
                            ],
                        },
                        {
                            name: "{{ $series[1] }}",
                            data: [
                                @for($i=0; $i<3;$i++)
                                    {{ User::whereHas('meta',function ($q){$q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%editor%');})->whereMonth('user_registered',Carbon::now()->subMonths(2-$i)->month )->whereYear('user_registered',Carbon::now()->subMonths(2-$i)->year )->get()->count()}},
                                @endfor
                            ],
                        },


                        {
                            name: "{{ $series[1] }}",
                            data: [
                                @for($i=0; $i<3;$i++)
                                    {{ User::whereHas('meta',function ($q){ $q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%contributor%');})->whereMonth('user_registered',Carbon::now()->subMonths(2-$i)->month )->whereYear('user_registered',Carbon::now()->subMonths(2-$i)->year )->get()->count() }},
                                @endfor

                            ],
                        },


                    ],


                    chart: {
                        height: 220,
                        toolbar: {
                            show: false,
                        },
                        offsetX: -30,
                        type: "bar",
                        fontFamily: "inherit",
                        foreColor: "#adb0bb",
                    },
                    colors: [
                        "var(--color-darkprimary)",
                        "var(--color-primary)",
                        "var(--color-secondary)",
                    ],
                    plotOptions: {
                        bar: {
                            borderRadius: 5,
                            columnWidth: "55%",
                            distributed: false,
                            endingShape: "rounded",
                        },
                    }, stroke: {
                        colors: ["transparent"],
                        width: 5
                    },
                    dataLabels: {
                        enabled: true,
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'right'
                    },
                    grid: {
                        yaxis: {
                            lines: {
                                show: false,
                            },
                        },
                        xaxis: {
                            lines: {
                                show: false,
                            },
                        },
                    },
                    xaxis: {
                        categories: [
                                @for($i=0; $i<3;$i++)
                            ["{{ Carbon::now()->subMonths(2-$i)->monthName.' '.Carbon::now()->year }}"],
                            @endfor
                        ],
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false,
                        },
                    },
                    yaxis: {
                        labels: {
                            show: false,
                        },
                    },
                    tooltip: {
                        theme: "dark",
                    },
                };

                var chart = new ApexCharts(document.querySelector("#salary"), options);
                chart.render();
            });
        </script>

        @isset($aiStats)
            <div class="flex items-center justify-between mt-5 mb-3">
                <h3 class="text-xl">AI Usage</h3>
                <a href="{{ route('ai-generations.index') }}" class="text-primary text-sm font-semibold">See full breakdown &rarr;</a>
            </div>
            <div class="col-span-12 grid grid-cols-12 gap-3">
                @foreach([
                    ['label' => 'All Time', 'icon' => 'ti-sum', 'data' => $aiStats['all']],
                    ['label' => 'This Month', 'icon' => 'ti-calendar', 'data' => $aiStats['this_month']],
                    ['label' => 'Last Month', 'icon' => 'ti-calendar-due', 'data' => $aiStats['last_month']],
                ] as $card)
                    <div class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12">
                        <div class="card">
                            <div class="card-body flex-row py-4 flex items-center gap-2">
                                <div class="bg-primary h-10 w-10 p-1 text-center text-white flex-shrink-0" style="border-radius: 100px">
                                    <i class="ti {{ $card['icon'] }} text-2xl"></i>
                                </div>
                                <div>
                                    <h5 class="text-xl leading-normal">{{ $card['label'] }}</h5>
                                    <span class="text-muted">{{ number_format($card['data']['count']) }} generations</span>
                                </div>
                                <div class="ms-auto text-end">
                                    <div class="text-lg font-semibold">{{ number_format($card['data']['tokens']) }} tokens</div>
                                    <div class="text-muted">${{ number_format($card['data']['cost'], 4) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($aiTopCompanies->isNotEmpty())
                <div class="admin-data-table w-full mt-3">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Top Companies by AI Cost</th>
                                    <th>Employees</th>
                                    <th>Generations</th>
                                    <th>Tokens</th>
                                    <th>Cost (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aiTopCompanies as $company)
                                    <tr>
                                        <td>{{ $company['company_name'] }}</td>
                                        <td>{{ number_format($company['employee_count']) }}</td>
                                        <td>{{ number_format($company['count']) }}</td>
                                        <td>{{ number_format($company['tokens']) }}</td>
                                        <td>${{ number_format($company['cost'], 4) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endisset
    </div>

</x-admin-layout>
