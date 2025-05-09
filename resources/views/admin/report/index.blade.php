<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <h1 class="text-3xl font-bold text-center text-blue-600">
                Report Site
            </h1>
            <livewire:export-result/>
{{--            <div class="customizer-box label btn" style="margin-bottom: 30px; margin-top: 40px;">REVITALIZE</div>--}}
{{--            <div class="flex flex-wrap gap-8">--}}
{{--                @foreach($data as $d)--}}
{{--                    <div class="card w-full sm:w-1/2 md:w-1/3 lg:w-1/4">--}}
{{--                        <div class="card">--}}
{{--                            <div class="card-body">--}}
{{--                                <div class="card-title">--}}
{{--                                    <a href="{{ route('report.course-group' , $d->id) }}">{{ $d->sub_title }}</a>--}}
{{--                                </div>--}}
{{--                                <div class="card-subtitle">--}}
{{--                                    {{ \App\Models\CourseGroupDetail::where('course_group_id', $d->id)->count() }}--}}
{{--                                    Courses--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                @endforeach--}}
{{--            </div>--}}

        </div>

{{--        <div class="customizer-box label btn" style="margin-bottom: 30px; margin-top: 40px;">SUSTAIN</div>--}}
{{--        <div class="flex flex-wrap gap-8">--}}
{{--            @foreach($data_level as $d)--}}
{{--                <div class="card w-full sm:w-1/2 md:w-1/3 lg:w-1/4">--}}
{{--                    <div class="card-body">--}}
{{--                        <div class="card-title">--}}
{{--                            <a href="{{ route('report.course-group' , $d->id) }}">{{ $d->sub_title }}</a>--}}
{{--                        </div>--}}
{{--                        <div class="card-subtitle">--}}
{{--                            {{ \App\Models\CourseGroupDetail::where('course_group_id', $d->id)->count() }} Courses--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            @endforeach--}}
{{--        </div>--}}

    </div>
</x-admin-layout>
