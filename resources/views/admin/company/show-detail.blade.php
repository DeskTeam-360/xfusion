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
        <div class="col-span-12">
            <livewire:table.master name="CompanyEmployee2" :param1="$id" />
        </div>
    </div>
</x-admin-layout>
