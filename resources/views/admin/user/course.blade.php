@php use App\Models\Company;use App\Models\CompanyEmployee;use App\Models\CourseList;use App\Models\User;use App\Models\WpPost;use Carbon\Carbon; @endphp
<x-admin-layout>


    <div class="container full-container py-5">
        <div class="row">
            <div class="text-3xl col">
                Activity Done - {{ User::find($user)->user_nicename }}
            </div>
            <a href="" class="btn btn-success col">
                Detail
            </a>
        </div>

        <livewire:user-course :user="$user"/>
    </div>
</x-admin-layout>
