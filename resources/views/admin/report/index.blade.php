<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <h1 class="text-3xl font-bold text-center text-blue-600">Report @if($role=='administrator') Course @else Company @endif</h1>

            <div class="customizer-box label btn" style="margin-bottom: 30px; margin-top: 40px;">REVITALIZE</div>

            @php
                $temp_loop = 0;
            @endphp
            @foreach($data as $d)
                @if($temp_loop == 0)
                    <div class="flex gap-8">
                @endif
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">
                                @if($role == 'administrator')
                                    <a href="{{ route('season-course-employee', $d->id) }}">{{ $d->season_title }}</a>
                                @else
                                    <a href="{{ route('season-course-company', [$companyId, $d->id]) }}">{{ $d->season_title }}</a>
                                @endif

                            </div>
                            <div class="card-subtitle">
                                {{ \App\Models\CourseGroup::where('season_id', $d->id)->count() }} Courses
                            </div>
                        </div>
                    </div>
                        @php
                            $temp_loop += 1;
                        @endphp
                @if($temp_loop == 4)
                    </div>
                    @php
                        $temp_loop = 0;
                    @endphp
                @endif
            @endforeach


        </div>

        <div class="customizer-box label btn" style="margin-bottom: 30px; margin-top: 40px;">SUSTAIN</div>

        @php
            $temp_loop = 0;
        @endphp
        @foreach($data_level as $dl)
            @if($temp_loop == 0)
                <div class="flex gap-8">
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">
                                @if($role == 'administrator')
                                    <a href="{{ route('level-course-employee', $dl->id) }}">{{ $dl->level_title }}</a>
                                @else
                                    <a href="{{ route('level-course-company', [$companyId, $dl->id]) }}">{{ $dl->level_title }}</a>
                                @endif

                            </div>
                            <div class="card-subtitle">
                                {{ \App\Models\CourseGroup::where('level_id', $dl->id)->count() }} Courses
                            </div>
                        </div>
                    </div>
                    @php
                        $temp_loop += 1;
                    @endphp
                    @if($temp_loop == 4)
                </div>
                @php
                    $temp_loop = 0;
                @endphp
            @endif
        @endforeach

        </div>
    </div>
</x-admin-layout>
