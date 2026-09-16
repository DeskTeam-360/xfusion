<x-admin-layout>
    <x-admin.page
        :title="$label . ' — ' . $company->title"
        description="Every record for this company, with a link to open it in the WordPress wizard."
    >
        <x-slot:actions>
            <a href="{{ route('company.show-detail', $id) }}" class="btn btn-light-secondary">
                <i class="ti ti-arrow-left"></i> Back to {{ $company->title }}
            </a>
        </x-slot:actions>

        <div class="admin-data-table w-full">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Record</th>
                            @if($metaColumnLabel)
                                <th>{{ $metaColumnLabel }}</th>
                            @endif
                            <th>Status</th>
                            <th>Created</th>
                            <th class="admin-table__col-actions" style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $record['title'] }}</td>
                                @if($metaColumnLabel)
                                    <td>{{ $record['meta'] }}</td>
                                @endif
                                <td>{{ ucfirst(str_replace('_', ' ', (string) $record['status'])) }}</td>
                                <td>{{ $record['created_at']?->format('F d, Y') }}</td>
                                <td class="admin-table__cell-actions" style="text-align:right">
                                    <a href="{{ $record['wp_url'] }}" target="_blank" rel="noopener" class="btn inline-flex items-center btn-light-primary">
                                        Open in WordPress <i class="ti ti-external-link"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $metaColumnLabel ? 5 : 4 }}" class="py-10 text-center text-muted dark:text-darklink">
                                    No activity yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-admin.page>
</x-admin-layout>
