<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <table
                class="border-collapse border-wishka-400 w-full text-sm text-left rounded table-auto">
                <thead class=" text-md text-uppercase uppercase dark:bg-dark  text-bold">
                <tr class="border-b-[3px] border-gray-200 border-collapse">
                    <th class="py-4 px-6">#</th>
                    <th class="py-4 px-6">Name</th>
                    <th class="py-4 px-6">Date apply</th>
                </tr>
                </thead>
                <tbody>

                @foreach(\App\Models\WpUserMeta::where('meta_key','keap_tags')->where('meta_value','like',"%$id%")->get() as $index=>$user)
                    <tr class="border-b border-gray-200 ">
                        <td class="py-4 px-6">{{ $index+1 }}</td>
                        <td class="py-4 px-6">
                            {{ \App\Models\WpUserMeta::where('meta_key','first_name')->where('user_id',$user->user_id)->first()->meta_value }}
                            {{ \App\Models\WpUserMeta::where('meta_key','last_name')->where('user_id',$user->user_id)->first()->meta_value }}
                        </td>
                        <td class="py-4 px-6">
                            @php
                                $tags = \App\Models\WpUserMeta::where('meta_key','keap_tags')->where('user_id',$user->user_id)->first()->meta_value;
                                $tagApply = \App\Models\WpUserMeta::where('meta_key','keap_tags_applies')->where('user_id',$user->user_id)->first()->meta_value;
                                $tags = explode(';',$tags);
                                $tagApply = explode(';',$tagApply);

                            @endphp
                            @isset($tagApply[array_search($id,$tags)]) {{ \Carbon\Carbon::parse($tagApply[array_search($id,$tags)])->format('Y/m/d H:i') }} @endisset
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
