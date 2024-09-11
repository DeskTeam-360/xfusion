<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">

            <h1 class="text-3xl font-bold text-center text-blue-600">{{ \App\Models\WpGfEntry::find($entryId)->user->user_nicename }}</h1>

            <div class="customizer-box label btn" style="margin-top: 40px;">REVITALIZE</div>
            <div class="customizer-box label btn"
                style="margin-bottom: 30px; background-color: white; border-color:black; color:black;">
                LEVEL {{ $level_id }}</div>

            <div class="overflow-x-auto">
                <table class="w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-center">
                                <div class="flex items-center">
                                    <i class="fas fa-user mr-1 text-gray-500"></i>
                                    Name LMS
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-birthday-cake mr-1 text-gray-500"></i>
                                    Questions
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-map-marker-alt mr-1 text-gray-500"></i>
                                    Answers
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-user-tag mr-1 text-gray-500"></i>
                                    Options
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $loop_count = 0;
                        @endphp
                        @foreach ($data_fields as $field)
                            <tr class="border-b">
                                @if ($loop_count == 0)
                                    <td class="border px-4 py-4 text-center" rowspan="{{ $count_fields }}">
                                        {{ $lms }}</td>
                                @endif

                                <td class="border px-4 py-4 text-center">{{ $field->label }}</td>
                                <td class="border px-4 py-4 text-center">{{ $array_entry[$field->id] }}</td>
{{--                                <td class="border px-4 py-4 text-center"></td>--}}

                                @if ($loop_count == 0)
                                    <td class="border px-4 py-4 text-center" rowspan="{{ $count_fields }}"><a
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                            href="#">Detail</a></td>
                                @endif

                                @php
                                    $loop_count += 1;
                                @endphp
                            </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-admin-layout>
