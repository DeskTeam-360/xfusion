@php use App\Models\Company;use App\Models\CompanyEmployee;use App\Models\CourseList;use App\Models\User;use Carbon\Carbon;use Illuminate\Support\Facades\Auth; @endphp
<x-admin-layout xmlns:livewire="http://www.w3.org/1999/html">

    <div class="px-5 text-3xl">
        Dashboard
    </div>
    @php
        $user = Auth::user()->ID;
        $check = Company::where('user_id', $user)->get();
    @endphp


    <div class="px-5 py-5">
    @if($check->isEmpty())
        Please create a company first!
    @else
        <livewire:dashboard-company/>
    @endif
    </div>
</x-admin-layout>
