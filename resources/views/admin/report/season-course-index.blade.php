<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">

            <h1 class="text-3xl font-bold text-center text-blue-600">
                Report {{ $role=='administrator' ? 'Course':'Company' }}
            </h1>

            <div class="customizer-box label btn"
                 style="margin-top: 40px;">{{ strtoupper(\App\Models\CourseGroup::find($season_id)->title) }}</div>
            <div class="customizer-box label btn"
                 style="margin-bottom: 30px; background-color: white; border-color:black; color:black;">
                {{ \App\Models\CourseGroup::find($season_id)->title.' - '.\App\Models\CourseGroup::find($season_id)->sub_title }}</div>
            @php
                $temp_loop = 0;
            @endphp
            <div class="flex flex-wrap gap-1">
                @foreach($data_form as $df)
                    <div class="card "> <!-- Added mb-4 for bottom margin -->
                        <div class="card-body">
                            <div class="card-title">
                                @php
                                    $form_id = $df->courseList->wp_gf_form_id;
                                        try {
                                            $entry_id = \Illuminate\Support\Facades\DB::table('wp_gf_entry')->select('id', 'created_by', 'date_created')
                                                                ->where('form_id', $form_id)
                                                                ->where('created_by', $user_id)
                                                                ->whereNotNull('created_by')
                                                                ->whereIn(\Illuminate\Support\Facades\DB::raw('(created_by, date_created)'), function($query) use ($form_id) {
                                                                    $query->select(\Illuminate\Support\Facades\DB::raw('created_by, MAX(date_created)'))
                                                                          ->from('wp_gf_entry')
                                                                          ->where('form_id', $form_id)
                                                                          ->whereNotNull('created_by')
                                                                          ->groupBy('created_by');
                                                                })
                                                                ->pluck('id')[0];
                                        } catch (exception) {
                                            $entry_id = false;
                                        }
                                @endphp
                                @if($entry_id)
                                    <a href="{{ route('course-detail', [$season_id, $user_id, $form_id, $entry_id]) }}">{{ $df->courseList->page_title }}</a>
                                @else
                                    <a href="#" class="inline-block"
                                       style="color: #FF2D20">{{ $df->courseList->page_title }}</a>
                                @endif
                            </div>
                        </div>
                    </div>

                @endforeach
            </div>


        </div>
    </div>
</x-admin-layout>
