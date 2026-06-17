<div class="admin-data-table w-full">
    <div class="admin-data-table__toolbar">
        <div class="admin-data-table__field">
            <span class="admin-data-table__label">Per page</span>
            <select wire:model.live="perPage" class="admin-data-table__control">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="-1">All</option>
            </select>
        </div>

        @if($searchable)
            <div class="admin-data-table__field admin-data-table__field--search">
                <span class="admin-data-table__label">Search</span>
                <input wire:model.live="search"
                       class="admin-data-table__control"
                       type="search"
                       placeholder="Search...">
            </div>
        @endif

        @if($dateSearch)
            <div class="admin-data-table__field w-full sm:w-auto">
                <span class="admin-data-table__label">Date</span>
                <input wire:model="param1"
                       class="admin-data-table__control"
                       type="date">
            </div>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
            <tr>
                @foreach($model::tableField() as $field)
                    <th class="{{ $field['class'] ?? '' }}"
                        style="{{ isset($field['width']) ? 'width:'.$field['width'].';' : '' }}{{ isset($field['text-align']) ? 'text-align:'.$field['text-align'].';' : '' }}">
                        <a @isset($field['sort']) wire:click.prevent="sortBy('{{ $field['sort'] }}')" @endisset role="button" href="#">
                            {{ $field['label'] }}
                            @isset($field['sort'])
                                @include('components.argon.data-table-component.sort-icon', ['field' => $field['sort']])
                            @endif
                        </a>
                    </th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @forelse ($datas as $index => $data)
                <tr>
                    @foreach ($model::tableData($data) as $cell)
                        @if($cell == null)
                            @continue
                        @endif
                        @switch($cell['type'])
                            @case('index')
                                <td class="{{ $cell['class'] ?? '' }}"
                                    style="{{ isset($cell['text-align']) ? 'text-align:'.$cell['text-align'].';' : '' }}">
                                    {{ $index + 1 + (request()->get('page') ? request()->get('page') - 1 : 0) * $perPage }}
                                </td>
                                @break
                            @case('string')
                                <td class="{{ $cell['class'] ?? '' }}"
                                    style="{{ isset($cell['text-align']) ? 'text-align:'.$cell['text-align'].';' : '' }}">{{ $cell['data'] }}</td>
                                @break
                            @case('thousand_format')
                                <td class="{{ $cell['class'] ?? '' }}"
                                    style="{{ isset($cell['text-align']) ? 'text-align:'.$cell['text-align'].';' : '' }}">{{ thousand_format($cell['data']) }}</td>
                                @break
                            @case('raw_html')
                                <td class="{{ $cell['class'] ?? '' }}"
                                    style="{{ isset($cell['text-align']) ? 'text-align:'.$cell['text-align'].';' : '' }}">{!! $cell['data'] !!}</td>
                                @break
                            @case('img')
                                <td class="{{ $cell['class'] ?? '' }}"
                                    style="{{ isset($cell['text-align']) ? 'text-align:'.$cell['text-align'].';' : '' }}">
                                    <img src="{{ $cell['data'] }}" alt=""
                                         style="{{ isset($cell['width']) ? 'width:'.$cell['width'].';' : '' }}{{ isset($cell['height']) ? 'height:'.$cell['height'].';' : '' }}">
                                </td>
                                @break
                            @case('action')
                                <td class="{{ $cell['class'] ?? 'admin-table__cell-actions' }}"
                                    style="{{ isset($cell['text-align']) ? 'text-align:'.$cell['text-align'].';' : '' }}">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @foreach($cell['data'] as $action)
                                            <a @isset($action['link']) href="{{ $action['link'] }}" @else href="#" wire:click.prevent="{{ $action['live'] }}" @endisset
                                               class="btn inline-flex items-center {{ $action['btn_class'] ?? 'btn-light-primary' }}">
                                                @isset($action['icon'])
                                                    <i class="{{ $action['icon'] }}"></i>
                                                @endisset
                                                {{ $action['title'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </td>
                                @break
                        @endswitch
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($model::tableField()) }}" class="py-10 text-center text-muted dark:text-darklink">
                        No records found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($perPage != -1)
        <div class="admin-data-table__pagination">
            @php
                $currentPage = $datas->currentPage();
                $lastPage = $datas->lastPage();
                $link = fn ($page) => '?page=' . $page;
            @endphp

            @if($lastPage > 1)
                <div class="flex flex-wrap justify-center gap-1 text-sm">
                    @if($currentPage > 1)
                        <a href="{{ $link($currentPage - 1) }}" class="btn btn-light-secondary px-3 py-1">&laquo;</a>
                    @endif

                    @for ($i = 1; $i <= $lastPage; $i++)
                        @if($i == 1 || $i == $lastPage || ($i >= $currentPage - 1 && $i <= $currentPage + 1))
                            <a href="{{ $link($i) }}"
                               class="btn px-3 py-1 {{ $i == $currentPage ? 'btn-primary pointer-events-none' : 'btn-light-secondary' }}">
                                {{ $i }}
                            </a>
                        @elseif($i == 2 && $currentPage > 4 || $i == $lastPage - 1 && $currentPage < $lastPage - 3)
                            <span class="px-2 py-1 text-muted">...</span>
                        @endif
                    @endfor

                    @if($currentPage < $lastPage)
                        <a href="{{ $link($currentPage + 1) }}" class="btn btn-light-secondary px-3 py-1">&raquo;</a>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if($extras)
        <div class="border-t border-border px-4 py-4 dark:border-darkborder">
            {!! $model::extras($datas) !!}
        </div>
    @endif
</div>
