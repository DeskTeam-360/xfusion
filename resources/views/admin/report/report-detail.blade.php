@php use App\Models\WpGfEntryMeta;use App\Models\WpGfFormMeta; @endphp
<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">

            <h1 class="text-3xl font-bold text-center text-blue-600">{{ \App\Models\User::find($user)->user_nicename }}</h1>
            <h2 class="text-2xl font-bold text-center text-blue-600">{{ \App\Models\CourseGroup::find($id)->sub_title }}</h2>
            <br><br>
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

                    </tr>
                    </thead>
                    <tbody class="border">
                    @foreach($courseGroup->courseGroupDetails as $details)

                        @php

                            $user_id = $user;

                            $entry_id = \App\Models\WpGfEntry::select('id', 'created_by', 'date_created')
                                                                                ->where('form_id', $details->courseList->wp_gf_form_id)
                                                                                ->where('created_by', $user)
                                                                                ->whereNotNull('created_by')
                                                                                ->whereIn(\Illuminate\Support\Facades\DB::raw('(created_by, date_created)'), function($query)  use ($details) {
                                                                                    $query->select(\Illuminate\Support\Facades\DB::raw('created_by, MAX(date_created)'))
                                                                                          ->from('wp_gf_entry')
                                                                                          ->where('form_id', $details->courseList->wp_gf_form_id)
                                                                                          ->whereNotNull('created_by')
                                                                                          ->groupBy('created_by');
                                                                                })
                                                                                ->pluck('id');
                            if (isset($entry_id[0])){
                                $entry_id=$entry_id[0];
                                $data = WpGfFormMeta::where('form_id', $details->courseList->wp_gf_form_id)->first();
                            $data_entry = WpGfEntryMeta::where('form_id', $details->courseList->wp_gf_form_id)->where('entry_id', $entry_id)->get();


                            $count_fields = 0;
                            $array_entry = [];

                            foreach($data->getFields()->fields as $field){
                                if ($field->label!="HTML Block"){
                                $count_fields += 1;
                                $array_entry[$field->id] = null;
                                }
                            }

                            foreach ($data_entry as $entry){
                                $array_entry[$entry->meta_key] = $entry['meta_value'];
                            }

                            $data_fields = $data->getFields()->fields;

                            }else{
                                $data = WpGfFormMeta::where('form_id', $details->courseList->wp_gf_form_id)->first();
                                $count_fields = 0;
                            $array_entry = [];
                                foreach($data->getFields()->fields as $field){
                                    if ($field->label!="HTML Block"){
                                $count_fields += 1;
                                $array_entry[$field->id] = null;
                                $data_fields = $data->getFields()->fields;
                                }
                                }
                            }


                            $first = 1;

                        @endphp


                        @foreach ($data_fields as $index=>$field)
                            @if ($field->label!="HTML Block")
                            <tr class="border-b">
                                @if ($first == 1)
                                    <td class="border px-4 py-4 text-center  {{ isset($array_entry[$field->id])?'':'text-error' }}" rowspan="{{ $count_fields }}">
                                        {{ $details->courseList->page_title }}
                                    </td>
                                @endif

                                <td class="border px-4 py-4 text-center  {{ isset($array_entry[$field->id])?'':'text-error' }}">{{ $field->label }}</td>
                                <td class="border px-4 py-4 text-center  {{ isset($array_entry[$field->id])?'':'text-error' }}">{{ $array_entry[$field->id]??'-' }}</td>

                                @php($first=0)

                            </tr>
                            @endif
                        @endforeach

                    @endforeach


                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-admin-layout>
