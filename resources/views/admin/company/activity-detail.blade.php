<x-admin-layout>
    <div class="px-5 pt-5">
        <a href="{{ route('company.show-detail', $id) }}" class="text-muted"><i class="ti ti-arrow-left"></i> Back to {{ $company->title }}</a>
    </div>
    <div class="px-5 text-3xl">
        {{ $label }} — {{ $company->title }}
    </div>
    <div class="px-5 py-5">
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $record['title'] }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', (string) $record['status'])) }}</td>
                                <td>{{ $record['created_at']?->format('F d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ $record['wp_url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                                        Open in WordPress <i class="ti ti-external-link"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
