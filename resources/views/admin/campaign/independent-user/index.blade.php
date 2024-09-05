<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <div>
                <div class="gap-3 p-4 lg:grid lg:grid-cols-12">
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
                            </tr>
                            </thead>
                            <tbody>
                            @foreach (explode(';',$keapTag) as $index=>$data)
                                <tr class="border-b border-gray-200 ">
                                    <td class="py-4 px-6">{{ \App\Models\Tag::find($data)->id }}</td>
                                    <td class="py-4 px-6">{{ \App\Models\Tag::find($data)->name }}</td>
                                    <td class="py-4 px-6">{{ \App\Models\Tag::find($data)->description}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


        </div>
    </div>
</x-admin-layout>
