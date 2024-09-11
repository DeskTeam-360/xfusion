<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">

            <h1 class="text-3xl font-bold text-center text-blue-600">Report @if($role=='administrator') Course @else Company @endif</h1>

            <div class="customizer-box label btn" style="margin-top: 40px;">REVITALIZE</div>
            <div class="customizer-box label btn"
                style="margin-bottom: 30px; background-color: white; border-color:black; color:black;">
                LEVEL {{$level_id}}</div>
            @php
                $temp_loop = 0;
            @endphp
            @foreach($data_form as $df)
                @if($temp_loop == 0)
                    <div class="flex gap-8">
                @endif
{{--                    {{ \App\Models\Log::create(['log1' => $df['link']]) }}--}}

                    <div class="card mb-4"> <!-- Added mb-4 for bottom margin -->
                        <div class="card-body">
                            <div class="card-title">
                                @php $form_id = \App\Models\CourseList::where('url', $df['link'])->pluck('wp_gf_form_id')[0]; @endphp
                                @php
                                    try {
                                        $entry_id = \App\Models\WpGfEntry::select('id', 'created_by', 'date_created')
                                                            ->where('form_id', $form_id)
                                                            ->where('created_by', $df['user_id'])
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
//                                    @dd($entry_id)
                                @endphp

                                @if($entry_id)
                                    <a href="{{ route('course-detail', [$level_id, $user_id, $form_id, $entry_id]) }}">{{ $df['title'] }}</a>
                                @else
                                    <a href="#" class="inline-block text-red-600">{{ $df['title'] }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                        @php
                            $temp_loop += 1;
                        @endphp
                @if($temp_loop == 2)
                    </div>
                    @php
                        $temp_loop = 0;
                    @endphp
                @endif
            @endforeach


        </div>
    </div>
</x-admin-layout>
