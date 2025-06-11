<div>
    <div class="gap-3 p-4 lg:grid lg:grid-cols-12">
        <div class="col-span-12 lg:col-span-2 items-stretch">
            <span>
            Per Page: &nbsp;
            <select wire:model.live="perPage"
                    class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full px-4 py-2  leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white"
                    style="">
                <option>10</option>
                <option>15</option>
                <option>25</option>
                <option value="-1">all</option>
            </select>
            </span>
        </div>
        <div class="md:col-span-6"></div>
        @if($searchable)
            <div class="col-span-12 lg:col-span-4 items-stretch">
                <span class="w-full">
                    Search
                    <input wire:model.live="search"
                           class=" bg-gray-200 appearance-none border-1 border rounded w-full py-2 px-4  leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white"
                           type="text" placeholder="Search...">
                </span>
            </div>
        @endif
        @if($dateSearch)
            <div class="flex items-center">
                <span class="w-full">
                    Tanggal
                    <input wire:model="param1"
                           class="text-dark bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full py-2 px-4  leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white"
                           type="date" placeholder="Search...">
                </span>
            </div>
        @endif
        {{--        dateSearch--}}

    </div>
    <div class="grid grid-cols-1 gap-3 p-4 lg:grid-cols-1 xl:grid-cols-1">
        <div class="overflow-x-auto relative ">
            <table
                class="border-collapse border-wishka-400 w-full text-sm text-left rounded table-auto">
                <thead class=" text-md text-uppercase uppercase dark:bg-dark  text-bold">
                <tr class="border-b-[3px] border-gray-200 border-collapse">
                    @foreach($model::tableField() as $field)
                        <th class="py-4 px-6" style="{{ isset($field['width'])?'width:'.$field['width']:'' }}
                        {{ isset($field['text-align'])?'text-align:'.$field['text-align']:'' }}
                        ">
                            <a @isset($field['sort']) wire:click.prevent="sortBy('{{ $field['sort'] }}')"
                               @endisset role="button" href="#">
                                {{$field['label']}} @isset($field['sort'])
                                    @include('components.argon.data-table-component.sort-icon', ['field' => $field['sort']])
                                @endif
                            </a>
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($datas as $index=>$data)

                    <tr class="border-b border-gray-200 ">
                        @foreach ($model::tableData($data) as $data)
                            @if($data == null)
                                @continue
                            @endif
                            @switch($data['type'])
                                @case('index')
                                    <td class="py-4 px-6"
                                        style="{{ isset($data['text-align'])?'text-align:'.$data['text-align']:'' }}">{{ $index+1 + (request()->get('page')?request()->get('page')-1:0)*$perPage }}</td>
                                    @break
                                @case('string')
                                    <td class="py-4 px-6"
                                        style="{{ isset($data['text-align'])?'text-align:'.$data['text-align']:'' }}">{{ $data['data'] }}</td>
                                    @break
                                @case('thousand_format')
                                    <td class="py-4 px-6"
                                        style="{{ isset($data['text-align'])?'text-align:'.$data['text-align']:'' }}">{{ thousand_format($data['data']) }}</td>
                                    @break
                                @case('raw_html')
                                    <td class="py-4 px-6"
                                        style="{{ isset($data['text-align'])?'text-align:'.$data['text-align']:'' }}">{!! $data['data'] !!}</td>
                                    @break
                                @case('img')
                                    <td class="py-4 px-6"
                                        style="{{ isset($data['text-align'])?'text-align:'.$data['text-align']:'' }}">
                                        <img src="{{ $data['data'] }}" alt=""
                                             style="{{ isset($data['width'])?'width:'.$data['width'].';':'' }}
                                             {{ isset($data['height'])?'height:'.$data['height'].';':'' }}">
                                    </td>
                                    @break
                                @case('action')
                                    <td class="py-4 px-6"
                                        style="{{ isset($data['text-align'])?'text-align:'.$data['text-align']:'' }}">
                                        @foreach($data['data'] as $action)
                                            <a @isset($action['link']) href='{{ $action['link'] }}' @else href='#'
                                               wire:click.prevent='{{$action['live']}}' @endisset
                                               class="
                                               @isset($action['bg'])
                                               @switch($action['bg'])
                                               @case('gray')
                                               bg-gray-300 hover:bg-gray-200
                                               @break
                                               @case('zinc')
                                               bg-zinc-300 hover:bg-zinc-200
                                               @break
                                               @case('neutral')
                                               bg-neutral-300 hover:bg-neutral-200
                                               @break
                                               @case('red')
                                               bg-red-300 hover:bg-red-200
                                               @break
                                               @case('orange')
                                               bg-orange-300 hover:bg-orange-200
                                               @break
                                               @case('amber')
                                               bg-amber-300 hover:bg-amber-200
                                               @break
                                               @case('yellow')
                                               bg-yellow-300 hover:bg-yellow-200
                                               @break
                                               @case('lime')
                                               bg-lime-300 hover:bg-lime-200
                                               @break
                                               @case('green')
                                               bg-green-300 hover:bg-green-200
                                               @break
                                               @case('emerald')
                                               bg-emerald-300 hover:bg-emerald-200
                                               @break
                                               @case('teal')
                                               bg-teal-300 hover:bg-teal-200
                                               @break
                                               @case('cyan')
                                               bg-cyan-300 hover:bg-cyan-200
                                               @break
                                               @case('sky')
                                               bg-sky-300 hover:bg-sky-200
                                               @break
                                               @case('blue')
                                               bg-blue-300 hover:bg-blue-200
                                               @break
                                               @case('indigo')
                                               bg-indigo-300 hover:bg-indigo-200
                                               @break
                                               @case('violet')
                                               bg-violet-300 hover:bg-violet-200
                                               @break
                                               @case('purple')
                                               bg-purple-300 hover:bg-purple-200
                                               @break
                                               @case('fuchsia')
                                               bg-fuchsia-300 hover:bg-fuchsia-200
                                               @break
                                               @case('rose')
                                               bg-rose-300 hover:bg-rose-200
                                               @break
                                               @case('slate')
                                               bg-slate-300 hover:bg-slate-200
                                               @break
                                               @default
                                               bg-blue-300 hover:bg-blue-200
                                               @endswitch
                                               @else
                                               bg-blue-300 hover:bg-blue-200
                                               @endisset
                                               py-1 px-3 rounded m-1">
                                                @isset($action['icon'])
                                                    <i class="{{ $action['icon'] }}"></i>
                                                @endisset
                                                {{ $action['title'] }}
                                            </a>
                                        @endforeach
                                    </td>
                                    @break
                            @endswitch
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($perPage!=-1)
            <div id="table_pagination" class="py-3">
{{--                {{ $datas->onEachSide(1)->links('pagination::tailwind') }}--}}
                {{--                {{ $datas->appends(request()->except('page'))->onEachSide(1)->links('pagination::tailwind') }}--}}

                @php
                    $currentPage = $datas->currentPage();
                    $lastPage = $datas->lastPage();
                    $search = request()->input('search');
                    $link = function($page) {
//                        return $datas->url($page) . ($search ? '&search=' . urlencode($search) : '');
                        return '?page='.$page;
                    };
                @endphp

                @if($lastPage > 1)
                    <div class="flex justify-center mt-4 space-x-1 text-sm">
                        {{-- Prev --}}
                        @if($currentPage > 1)
                            <a href="{{ $link($currentPage - 1) }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800 ">&laquo;</a>
                        @endif

                        {{-- Page links --}}
                        @for ($i = 1; $i <= $lastPage; $i++)
                            @if (
                                $i == 1 ||
                                $i == $lastPage ||
                                ($i >= $currentPage - 1 && $i <= $currentPage + 1)
                            )
                                <a href="{{ $link($i) }}"
                                   class="
                   {{ $i == $currentPage ? 'relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 dark:bg-gray-800 dark:border-gray-600' :
'relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800' }}">
                                    {{ $i }}
                                </a>
                            @elseif (
                                $i == 2 && $currentPage > 4 ||
                                $i == $lastPage - 1 && $currentPage < $lastPage - 3
                            )
                                <span class="px-2">...</span>
                            @endif
                        @endfor

                        {{-- Next --}}
                        @if($currentPage < $lastPage)
                            <a href="{{ $link($currentPage + 1) }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800">&raquo;</a>
                        @endif
                    </div>
                @endif


            </div>

        @endif
        @if($extras)
            <div>
                {!! $model::extras($datas) !!}
            </div>
        @endif
    </div>
</div>

