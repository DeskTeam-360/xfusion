@php
    use Carbon\Carbon;
@endphp
<div class="col-span-12 grid grid-cols-12 gap-3">

    <div class="lg:col-span-6 md:col-span-6 sm:col-span-6 col-span-12 flex gap-1 flex-wrap">
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
        <div class="card  flex-row py-4 flex items-center gap-2">
          
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
    <div class="lg:col-span-4 md:col-span-4 sm:col-span-6 col-span-12 flex gap-1 flex-wrap">
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
