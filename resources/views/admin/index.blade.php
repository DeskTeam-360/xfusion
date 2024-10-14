@php use App\Models\Company;use App\Models\CompanyEmployee;use App\Models\CourseList;use App\Models\User;use Carbon\Carbon; @endphp
<x-admin-layout xmlns:livewire="http://www.w3.org/1999/html">

    <div class="px-5 text-3xl">
        Dashboard
    </div>
    <div class="px-5 py-5">
        <div class="col-span-12 grid grid-cols-12 gap-3">
            <div class="lg:col-span-3 md:col-span-6 sm:col-span-6 col-span-12">
                <div class="card shadow-none w-full" style="height: 280px">
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

            <div class="lg:col-span-3 col-span-12 grid grid-cols-12 gap-3 h-fit">



                <div class="lg:col-span-12 col-span-12 h-fit">
                    <div class="card">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                            <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                                <i class="ti ti-users text-2xl"></i>
                            </div>
                            <div class="">
                                <h5 class="xl:text-xl text-base leading-normal">
                                    {{ User::whereHas('meta',function ($q){$q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%contributor%');})->count() }}
                                </h5>
                                <span class="text-md flex items-center gap-1 ">
                                    Contributor
                                </span>
                            </div>
                            <a class="ms-auto text-2xl" style="border-radius: 40px">
                                <i class="ti ti-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-12 col-span-12 h-fit">
                    <div class="card">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                            <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                                <i class="ti ti-users text-2xl"></i>
                            </div>
                            <div class="">
                                <h5 class="xl:text-xl text-base leading-normal">
                                    {{ Company::count() }}
                                </h5>
                                <span class="text-lg flex items-center gap-1">
                                    Company
                                </span>
                            </div>
                            <a class="ms-auto text-2xl" style="border-radius: 40px">
                                <i class="ti ti-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-12 col-span-12 h-fit">
                    <div class="card">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                            <div class="bg-success h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                                <i class="ti ti-users text-2xl"></i>
                            </div>
                            <div class="">
                                <h5 class="xl:text-xl text-base leading-normal">
                                    {{ User::whereHas('meta',function ($q){
$q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%subscriber%');
})->count() }}
                                </h5>
                                <span class="text-lg flex items-center gap-1">
                                    Employee
                                </span>
                            </div>
                            <a class="ms-auto text-2xl" style="border-radius: 40px">
                                <i class="ti ti-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
            <div class="lg:col-span-6 md:col-span-6 sm:col-span-12 col-span-12">
                <h2 class="text-2xl">Company</h2>
                <div class="overflow-y-auto" style="height: 240px">
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
                                Name Company
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
                                Employee
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
                                Date Subscribe
                            </th>
                            <th scope="col" class="text-center p-2 font-semibold text-dark dark:text-white ">
                                Options
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-darkborder">
                        @foreach(Company::orderBy('title')->get() as $c)
                            <tr>
                                <td class="p-2 ps-0 whitespace-nowrap" style="padding-left: 25px">
                                    {{ $c->title }}
                                </td>
                                <td class=" whitespace-nowrap  dark:text-darklink p-2 text-center">
                                    {{ CompanyEmployee::where('company_id',$c->id)->count() }}
                                </td>
                                <td class="p-2 whitespace-nowrap text-center">
                                    {{ $c->created_at->format('F d, Y') }}
                                </td>
                                <td class=" whitespace-nowrap  dark:text-darklink p-2 text-center">
                                    <a href="{{ route('company.show',$c->id) }}"><i class="ti ti-eye text-xl"></i></a>
{{--                                    <svg width="19" height="7" viewBox="0 0 19 7" fill="none"--}}
{{--                                         xmlns="http://www.w3.org/2000/svg" class="m-auto">--}}
{{--                                        <ellipse cx="9.38496" cy="3.37143" rx="2.62074" ry="3.09552" fill="#AB9AE0"/>--}}
{{--                                        <ellipse cx="3.09517" cy="3.37143" rx="2.62074" ry="3.09552" fill="#AB9AE0"/>--}}
{{--                                        <ellipse cx="15.6747" cy="3.37143" rx="2.62074" ry="3.09552" fill="#4E51BF"/>--}}
{{--                                    </svg>--}}
                                </td>
                            </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="lg:col-span-6 md:col-span-6 sm:col-span-12 col-span-12">
                <div class="card">
                    <div class="card-body pb-8">
                        <h5 class="card-title">User Growth</h5>
                        <p class="card-subtitle">Every month</p>
                        <div class="-me-12">
                            <div id="salary" class="" ></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="lg:col-span-6 md:col-span-6 sm:col-span-12 col-span-12">
                <h2 class="text-2xl">Contributor</h2> <br>
                <div class="overflow-y-auto" style="height: 290px">
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
                                Name Contributor
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
                                Date Subscribe
                            </th>
                            <th scope="col" class="text-center p-2 font-semibold text-dark dark:text-white ">
                                Options
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-darkborder">
                        @foreach(User::whereHas('meta',function ($q){ $q->where('meta_key',config('app.wp_prefix', 'wp_') . 'capabilities')->where('meta_value','like','%contributor%');})->get()->take(10) as $c)
                            <tr>
                                <td class="p-2 ps-0 whitespace-nowrap" style="padding-left: 25px">
                                    {{ $c->user_nicename }}
                                </td>
                                <td class="p-2 whitespace-nowrap text-center">
                                    {{ $c->created_at->format('F d, Y') }}
                                </td>
                                <td class=" whitespace-nowrap  dark:text-darklink p-2 text-center">
                                    <a href="#"><i class="ti ti-eye text-xl"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
            @php($pageTitle = ['Revitalize', 'Transform','Sustain'])
            @foreach($pageTitle as $pt)
                <div class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12">
                    <div class="card">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                                                        <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                                                            <i class="ti ti-users text-2xl"></i>
                                                        </div>
                            <div class="">
                                <h5 class="text-3xl leading-normal">
                                    {{ CourseList::where('course_title',$pt)->count() }}
                                </h5>
                                <span class="text-lg flex items-center gap-1">
                                {{ $pt }}
                            </span>
                            </div>
                            <a class="ms-auto text-2xl" style="border-radius: 40px">
                                <i class="ti ti-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>


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
    </div>
</x-admin-layout>
