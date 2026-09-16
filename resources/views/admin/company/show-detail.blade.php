<x-admin-layout>
    <div class="px-5 text-3xl">
        Company Detail - {{ $company->title }}
    </div>
    <div class="px-5 py-5">

        <div class="col-span-12 grid grid-cols-12 gap-3">

            <div class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12">
                <div class="card">
                    <div class="card-body flex-row py-4 flex items-center gap-2">
                        <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                            <i class="ti ti-users text-2xl"></i>
                        </div>
                        <div class="">
                            <h5 class="text-xl leading-normal">
                                Total Employee
                            </h5>
                            <span class="text-lg flex items-center gap-1">
                            </span>
                        </div>
                        <a class="ms-auto text-2xl" style="border-radius: 40px">
                            {{ $company->companyEmployees()->count() }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12">
                <div class="card">
                    <div class="card-body flex-row py-4 flex items-center gap-2">
                        <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                            <i class="ti ti-users text-2xl"></i>
                        </div>
                        <div class="">
                            <h5 class="text-xl leading-normal">
                                Total course completed
                            </h5>
                            <span class="text-lg flex items-center gap-1">
                            </span>
                        </div>
                        <a class="ms-auto text-2xl" style="border-radius: 40px">
                            {{ $companyEmployeesEntries->count() }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12">
                <div class="card">
                    <div class="card-body flex-row py-4 flex items-center gap-2">
                        <div class="bg-primary h-10 w-10 p-1 text-center text-white" style="border-radius: 100px">
                            <i class="ti ti-calendar-event text-2xl"></i>
                        </div>
                        <div class="">
                            <h5 class="text-xl leading-normal">
                                Company created at
                            </h5>
                            <span class="text-lg flex items-center gap-1">
                            </span>
                        </div>
                        <a class="ms-auto text-2xl" style="border-radius: 40px">
                            {{ $company->created_at->format('F d, Y') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <br><br>

        <div class="px-0 text-xl mb-3">FUSION Operating System™ Activity</div>
        <div class="col-span-12 grid grid-cols-12 gap-3">
            @foreach($fusionActivity as $item)
                <a href="{{ route('company.activity-detail', [$id, $item['type']]) }}" class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12 text-decoration-none text-reset">
                    <div class="card" style="cursor:pointer">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                            <div class="bg-primary h-10 w-10 p-1 text-center text-white flex-shrink-0" style="border-radius: 100px">
                                <i class="ti {{ $item['icon'] }} text-2xl"></i>
                            </div>
                            <div>
                                <h5 class="text-xl leading-normal">{{ $item['label'] }}</h5>
                                <span class="text-muted">
                                    @if($item['last_at'])
                                        Last created {{ \Illuminate\Support\Carbon::parse($item['last_at'])->format('F d, Y') }}
                                    @else
                                        No activity yet
                                    @endif
                                </span>
                            </div>
                            <span class="ms-auto text-2xl">
                                {{ $item['count'] }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <br><br>
        <div class="col-span-12">
            <livewire:table.master name="CompanyEmployee2" :param1="$id" />
        </div>
    </div>
</x-admin-layout>
