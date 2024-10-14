<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">

            <h1 class="text-3xl font-bold text-center text-blue-600">
                Report {{ $role=='administrator' ? 'Course':'Company' }}
            </h1>

            <div class="customizer-box label btn"
                 style="margin-top: 40px;">{{ strtoupper(\App\Models\CourseGroup::find($id)->title) }}</div>
            <div class="customizer-box label btn"
                 style="margin-bottom: 30px; background-color: white; border-color:black; color:black;">
                {{ \App\Models\CourseGroup::find($id)->title.' - '.\App\Models\CourseGroup::find($id)->sub_title }}
            </div>

            {{--            <livewire:table.master name="CourseGroup"/>--}}

            <div class="overflow-x-auto">
                <table class="w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-3 text-left">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-user mr-1 text-gray-500"></i>
                                ID
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <div class="flex items-center">
                                <i class="fas fa-user mr-1 text-gray-500"></i>
                                Name
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-birthday-cake mr-1 text-gray-500"></i>
                                Completed course status
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-map-marker-alt mr-1 text-gray-500"></i>
                                Date start course
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-user-tag mr-1 text-gray-500"></i>
                                Action
                            </div>
                        </th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($data as $d)
                        @php
                            try {
                                if ($role == 'administrator') {
                                    $user = \App\Models\User::where('id', $d)->first()['user_login'];

                                    $scheduleExec = \App\Models\WpGfEntry::where('created_by', $d)->count() >= \App\Models\CourseGroupBackup::where('season_id', $d)->count() ? 'Done' : 'On Progress';

                                    $c_list = \App\Models\CourseGroupDetail::where('course_group_id', $id)->orderBy('id')->pluck('course_list_id');

                                    $temp_array = [];

                                    foreach ($c_list as $c) {
                                        $course = \App\Models\CourseList::where('id', $c)->value('wp_gf_form_id');
                                        $temp_date = \App\Models\WpGfEntry::where('form_id', $course)->where('created_by', $d)->value('date_created');

                                        if ($temp_date) {
                                            $temp_array[] = $temp_date;
                                        }
                                    }
                                    sort($temp_array);

                                    $date = $temp_array[0];

                                    $dateRec = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('M d, Y');

                                    $result = 'yes';
                                } else {

                                    $companyId = \App\Models\Company::where('user_id', $userId)->pluck('ID')[0];
                                    $employees = \App\Models\CompanyEmployee::where('company_id', $companyId)->pluck('user_id')->toArray();

                                    if (in_array($d, $employees)) {
                                        $user = \App\Models\User::where('id', $d)->first()['user_login'];

                                        $scheduleExec = \App\Models\WpGfEntry::where('created_by', $d)->count() >= \App\Models\CourseGroupBackup::where('season_id', $d)->count() ? 'Done' : 'On Progress';

                                        $c_list = \App\Models\CourseGroupDetail::where('course_group_id', $id)->orderBy('id')->pluck('course_list_id');

                                        $temp_array = [];

                                        foreach ($c_list as $c) {
                                            $course = \App\Models\CourseList::where('id', $c)->value('wp_gf_form_id');
                                            $temp_date = \App\Models\WpGfEntry::where('form_id', $course)->where('created_by', $d)->value('date_created');

                                            if ($temp_date) {
                                                $temp_array[] = $temp_date;
                                            }
                                        }
                                        sort($temp_array);

                                        $date = $temp_array[0];

                                        $dateRec = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('M d, Y');

                                        $result = 'yes';
                                    } else {
                                        $result = 'no';
                                    }

                                }


                            } catch (\Exception $e) {
                                $result = 'no';
                            }

                        @endphp
                        @if($result == 'yes')
                            <tr class="border-b">
                                <td class="border px-4 py-4 text-center">{{ $d }}</td>
                                <td class="border px-4 py-4">{{ $user }}</td>
                                <td class="border px-4 py-4 text-center">{{ $scheduleExec }}</td>
                                <td class="border px-4 py-4 text-center">{{ $dateRec }}</td>
                                <td class="border px-4 py-4 text-center">
                                    <a class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-1"
                                       href="{{ route('season-course-index', [$id, $d]) }}">Course
                                    </a>
                                    <a style="margin-left: 10px;" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                       href="{{ route('report.course-group.user', [$id, $d]) }}">Full Course
                                    </a>
                                </td>
                                {{--                                                            <td class="border px-4 py-4 text-center"><a--}}
                                {{--                                                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"--}}
                                {{--                                                                    href="{{ route('season-employee-detail', ['entryId' => $d, 'dateCreated'=> $d->date_created]) }}">Detail</a></td>--}}
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-admin-layout>
