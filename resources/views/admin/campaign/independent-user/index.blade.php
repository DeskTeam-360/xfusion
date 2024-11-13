<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <div>
                <div class="gap-3 p-4 lg:grid lg:grid-cols-12">
                    <h2 class="text-2xl col-span-12">
                        List tag
                        {{ \App\Models\WpUserMeta::where('user_id',$id)->where('meta_key','first_name')->first()->meta_value??'' }}
                        {{ \App\Models\WpUserMeta::where('user_id',$id)->where('meta_key','last_name')->first()->meta_value??'' }}
                    </h2>
                </div>
                <div class="grid grid-cols-1 gap-3 p-4 lg:grid-cols-1 xl:grid-cols-1">
                    <div class="overflow-x-auto relative ">
                        <table
                            class="border-collapse border-wishka-400 w-full text-sm text-left rounded table-auto">
                            <thead class=" text-md text-uppercase uppercase dark:bg-dark  text-bold">
                            <tr class="border-b-[3px] border-gray-200 border-collapse">
                                <th class="py-4 px-6">#</th>
                                <th class="py-4 px-6">Name</th>
                                <th class="py-4 px-6">Description</th>
                                <th class="py-4 px-6">Date Apply</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach ($keapTag as $index=>$data)
                                @php($test = \App\Models\Tag::find($data)->id??'')
                                @if($test!='')
                                    <tr class="border-b border-gray-200 ">
                                        <td class="py-4 px-6">{{ \App\Models\Tag::find($data)->id??'' }}</td>
                                        <td class="py-4 px-6">{{ \App\Models\Tag::find($data)->name??'' }}</td>
                                        <td class="py-4 px-6">{{ \App\Models\Tag::find($data)->description??''}}</td>
                                        <td class="py-4 px-6">
                                            @isset($keapTagApply[$index])
                                                {{ \Carbon\Carbon::parse($keapTagApply[$index])->format('D, d M Y H:i:s') }}
                                            @endisset
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


        </div>
    </div>
</x-admin-layout>
