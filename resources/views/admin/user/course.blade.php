@php use App\Models\Company;use App\Models\CompanyEmployee;use App\Models\CourseList;use App\Models\User;use App\Models\WpPost;use Carbon\Carbon; @endphp
<x-admin-layout>


    <div class="container full-container py-5">
        <div class="text-3xl">
            Activity Done - {{ User::find($user)->user_nicename }}
        </div>
        <livewire:user-course :user="$user"/>
    </div>
</x-admin-layout>
