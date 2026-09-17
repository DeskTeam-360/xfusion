<x-admin-layout>
    <x-admin.page
        title="AI Generations"
        description="Every AI Assessment/Synthesis/Brief generated across ARP, QBR, ARR, IRR, and 1-on-1 — newest first."
    >
        <x-slot:actions>
            <form method="get" class="flex items-center gap-2">
                <select name="module" class="admin-data-table__control" onchange="this.form.submit()">
                    <option value="">All modules</option>
                    @foreach(['QBR', 'ARP', 'ARR', 'IRR', '1-on-1'] as $m)
                        <option value="{{ $m }}" @selected($module === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:actions>

        <div class="admin-data-table w-full">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Type</th>
                            <th>Company</th>
                            <th>Record</th>
                            <th>Model</th>
                            <th>Tokens</th>
                            <th>Cost (USD)</th>
                            <th>Generated</th>
                            <th class="admin-table__col-actions" style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row['module'] }}</td>
                                <td>{{ $row['type'] }}</td>
                                <td>{{ $row['company_name'] }}</td>
                                <td>{{ $row['record_label'] }}</td>
                                <td>{{ $row['insight_model'] }}</td>
                                <td>{{ number_format($row['tokens_used']) }}</td>
                                <td>${{ number_format($row['cost_usd'], 4) }}</td>
                                <td>{{ $row['created_at']?->format('F d, Y H:i') }}</td>
                                <td class="admin-table__cell-actions" style="text-align:right">
                                    @if($row['wp_url'])
                                        <a href="{{ $row['wp_url'] }}" target="_blank" rel="noopener" class="btn inline-flex items-center btn-light-primary">
                                            Open in WordPress <i class="ti ti-external-link"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-10 text-center text-muted dark:text-darklink">
                                    No AI generations yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lastPage > 1)
                <div class="admin-data-table__pagination">
                    <div class="flex flex-wrap justify-center gap-1 text-sm">
                        @if($page > 1)
                            <a href="?page={{ $page - 1 }}&module={{ $module }}" class="btn btn-light-secondary px-3 py-1">&laquo;</a>
                        @endif
                        <span class="px-3 py-1">Page {{ $page }} of {{ $lastPage }} ({{ number_format($total) }} total)</span>
                        @if($page < $lastPage)
                            <a href="?page={{ $page + 1 }}&module={{ $module }}" class="btn btn-light-secondary px-3 py-1">&raquo;</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </x-admin.page>
</x-admin-layout>
