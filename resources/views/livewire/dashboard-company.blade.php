@php
    use Carbon\Carbon;
@endphp
<div class="col-span-12 grid grid-cols-12 gap-3">

    <div class="lg:col-span-4 md:col-span-4 sm:col-span-6 col-span-12 flex gap-1 flex-wrap">
        <div class="card shadow-none w-full">
            <div class="card-body p-6">
                <div class="flex items-center gap-6">
                    <div>
                        <div class="rounded-md bg-primary w-16 h-16 flex items-center justify-center text-white">
                            <i class="ti ti-file-description text-4xl"></i>
                        </div>
                    </div>

                    <div class="items-center justify-between">
                        <h3 class="text-xl">
                            {{ $userEmployee->count() }} <br>
                            Total employee
                        </h3>
                    </div>

                    <a href="{{ route('company.show',$companyId) }}"
                       class="ms-auto text-primary flex gap-1 items-center">
                        <span class="text-xs font-semibold text-primary">See details</span>
                        <i class="ti ti-trending-up text-primary text-xl"></i>
                    </a>
                </div>

            </div>
        </div>
        <div class="card">
            <div class="card-body flex-row py-4 flex items-center gap-2">
                <div>
                    <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                        <i class="ti ti-users text-2xl"></i>
                    </div>
                </div>
                <div class="">
                    <h5 class="xl:text-xl text-base leading-normal">
                        {{ $complete }}
                    </h5>
                    <span class="text-md flex items-center gap-1 ">
                        Employee has complete the course
                    </span>
                </div>
                <div>
                    <div class="text-2xl" style="border-radius: 40px">
                        <i class="ti ti-arrow-up-right"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body flex-row py-4 flex items-center gap-2">
                <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                    <i class="ti ti-users text-2xl"></i>
                </div>
                <div class="">
                    <h5 class="xl:text-xl text-base leading-normal">
                        {{ $inComplete }}
                    </h5>
                    <span class="text-md flex items-center gap-1 ">
                        Employee has not yet completed the Course
                    </span>
                </div>
                <div>
                    <div class="text-2xl" style="border-radius: 40px">
                        <i class="ti ti-arrow-up-right"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body pb-8">
                <h5 class="card-title">User Growth</h5>
                <p class="card-subtitle">Every month</p>
                <div class="-me-12">
                    <div id="chart" class=""></div>
                </div>
            </div>
        </div>

    </div>

    <div class="lg:col-span-8 md:col-span-8 sm:col-span-12 col-span-12">
        <h2 class="text-2xl">Employee</h2>
        <br>
        <div class="overflow-y-auto" style="max-height: 90vh">
            <table class="min-w-full divide-y divide-border dark:divide-darkborder ">
                <thead>
                <tr>
                    <th scope="col" class="text-center p-2 ps-0 font-semibold text-dark dark:text-white ">
                        <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                             xmlns="http://www.w3.org/2000/svg" class="inline-block stroke-current">
                            <path
                                d="M19.024 3.08298H5.02399C3.91942 3.08298 3.02399 3.97841 3.02399 5.08298V19.083C3.02399 20.1875 3.91942 21.083 5.02399 21.083H19.024C20.1286 21.083 21.024 20.1875 21.024 19.083V5.08298C21.024 3.97841 20.1286 3.08298 19.024 3.08298Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3.02399 9.08298H21.024" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M9.02399 21.083V9.08298" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                        Name Employee
                    </th>
                    <th scope="col" class="text-center p-2 font-semibold text-dark dark:text-white text-nowrap">
                        <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                             class="inline-block stroke-current" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M14.6392 2.08298H6.63922C6.10879 2.08298 5.60008 2.29369 5.22501 2.66876C4.84993 3.04384 4.63922 3.55254 4.63922 4.08298V20.083C4.63922 20.6134 4.84993 21.1221 5.22501 21.4972C5.60008 21.8723 6.10879 22.083 6.63922 22.083H18.6392C19.1697 22.083 19.6784 21.8723 20.0534 21.4972C20.4285 21.1221 20.6392 20.6134 20.6392 20.083V8.08298L14.6392 2.08298Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14.6392 2.08298V8.08298H20.6392" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M16.6392 13.083H8.63922" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M16.6392 17.083H8.63922" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M10.6392 9.08298H9.63922H8.63922" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                        Completed course status
                    </th>
                    <th scope="col"
                        class="text-center  p-2 font-semibold text-dark dark:text-white ">
                        <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                             xmlns="http://www.w3.org/2000/svg" class="inline-block stroke-current">
                            <path
                                d="M19.4773 4.08298H5.47729C4.37273 4.08298 3.47729 4.97841 3.47729 6.08298V20.083C3.47729 21.1875 4.37273 22.083 5.47729 22.083H19.4773C20.5819 22.083 21.4773 21.1875 21.4773 20.083V6.08298C21.4773 4.97841 20.5819 4.08298 19.4773 4.08298Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16.4773 2.08298V6.08298" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M8.47729 2.08298V6.08298" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M3.47729 10.083H21.4773" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                        Date start course
                    </th>
                    <th scope="col" class="text-center p-2 font-semibold text-dark dark:text-white ">
                        Options
                    </th>
                </tr>
                </thead>
                <tbody class="divide-y divide-border dark:divide-darkborder">
                @foreach($userEmployee as $c)
                    <tr>
                        <td class="p-2 ps-0 whitespace-nowrap" style="padding-left: 25px">
                            {{ $c->user_nicename }}
                        </td>
                        <td class=" whitespace-nowrap  dark:text-darklink p-2 text-center">
                            @php
                                $link = \App\Models\ScheduleExecution::where('user_id',$c->ID)->get()->pluck('link')->toArray();
                                $courseComplete = \App\Models\WpGfEntry::where('created_by',$c->ID)->whereIn('source_url',$link)->where('status','active')->count();
                                $course = \App\Models\ScheduleExecution::where('user_id',$c->ID)->count();
                            @endphp
                            @if($course==0)
                                No course scheduled to employee
                            @elseif($course==$courseComplete)
                                Done
                            @else
                                {{ $courseComplete }}/{{ $course }}
                            @endif
                        </td>
                        <td class="p-2 whitespace-nowrap text-center">
                            @php($lms = \App\Models\WpGfEntry::where('created_by',$c->ID)->whereIn('source_url',$link)->where('status','active')->first() )
                            {{ $lms!=null ? Carbon::parse($lms->date_created)->format('F d,Y') : '-' }}
                        </td>
                        <td class=" whitespace-nowrap  dark:text-darklink p-2 text-center">
                            <a href="{{ route('company.schedule-user',[$companyId,$c->ID]) }}"><i class="ti ti-eye text-xl"></i></a>
                        </td>
                    </tr>

                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
    @php($series = ['Employee'])
    document.addEventListener("DOMContentLoaded", function () {
        var options = {
            series: [
                {
                    name: "{{ $series[0] }}",
                    data: [
                        @for($i=0; $i<3;$i++)

                            {{ $this->getDataUserGrowh($i) }},

                        @endfor
                    ],
                },
            ],


            chart: {
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

        var chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();
    });
</script>
