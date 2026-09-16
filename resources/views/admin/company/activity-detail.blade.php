<x-admin-layout>
    <div class="px-5 pt-5">
        <a href="{{ route('company.show-detail', $id) }}" class="text-muted"><i class="ti ti-arrow-left"></i> Back to {{ $company->title }}</a>
    </div>
    <div class="px-5 text-3xl">
        {{ $label }} — {{ $company->title }}
    </div>
    <div class="px-5 py-5">
        <div class="admin-data-table w-full">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $record['title'] }}</td>
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
                                <td colspan="4" class="py-10 text-center text-muted dark:text-darklink">
                                    No activity yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
