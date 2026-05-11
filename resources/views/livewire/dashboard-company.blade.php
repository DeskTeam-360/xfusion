@php
    use Carbon\Carbon;
@endphp
<div class="col-span-12 grid grid-cols-12 gap-3">

    <div class="lg:col-span-6 md:col-span-6 sm:col-span-6 col-span-12 w-full">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Total employees --}}
            <div class="card group border border-border shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-darkborder">
                <div class="card-body flex flex-col gap-4 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="ti ti-users text-2xl"></i>
                        </div>
                        @if($companyId !== '')
                            <a href="{{ route('company.show', $companyId) }}"
                               class="inline-flex items-center gap-1 rounded-full border border-border px-2.5 py-1 text-xs font-semibold text-primary transition-colors hover:bg-primary/10 dark:border-darkborder"
                               aria-label="View employee list details">
                                <span>Details</span>
                                <i class="ti ti-arrow-right text-sm"></i>
                            </a>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted dark:text-darklink mb-1">Workforce</p>
                        <p class="tabular-nums text-3xl font-bold leading-none text-dark dark:text-white">
                            {{ $userEmployee->count() }}
                        </p>
                        <p class="mt-1 text-sm text-muted dark:text-darklink">
                            Total employees in your company
                        </p>
                    </div>
                </div>
            </div>

            {{-- Completed (on track / done) --}}
            <div class="card border border-border shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-darkborder">
                <div class="card-body flex flex-row flex-wrap items-center gap-4 p-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                        <i class="ti ti-circle-check text-2xl"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted dark:text-darklink mb-1">Completion</p>
                        <p class="tabular-nums text-3xl font-bold leading-none text-dark dark:text-white">
                            {{ $complete }}
                        </p>
                        <p class="mt-1 text-sm text-muted dark:text-darklink leading-snug">
                            Employees marked as having completed assigned activities
                        </p>
                    </div>
                </div>
            </div>

            {{-- Incomplete / pending --}}
            <div class="card border border-border shadow-sm transition-shadow duration-200 hover:shadow-md sm:col-span-2 lg:col-span-1 dark:border-darkborder">
                <div class="card-body flex flex-row flex-wrap items-center gap-4 p-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                        <i class="ti ti-clock-hour-5 text-2xl"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted dark:text-darklink mb-1">Pending</p>
                        <p class="tabular-nums text-3xl font-bold leading-none text-dark dark:text-white">
                            {{ $inComplete }}
                        </p>
                        <p class="mt-1 text-sm text-muted dark:text-darklink leading-snug">
                            Employees without completed scheduling or coursework in this dashboard
                        </p>
                    </div>
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
