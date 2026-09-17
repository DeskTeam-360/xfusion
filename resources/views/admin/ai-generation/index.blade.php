<x-admin-layout>
    <div class="px-5 py-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between mb-5">
            <div>
                <h1 class="text-2xl font-semibold text-dark dark:text-white">AI Generations</h1>
                <p class="mt-1 max-w-3xl text-sm text-muted dark:text-darklink">Every AI Assessment/Synthesis/Brief generated across ARP, QBR, ARR, IRR, and 1-on-1 — newest first.</p>
            </div>
            <form method="get" class="flex items-center gap-2">
                <select name="module" class="admin-data-table__control" onchange="this.form.submit()">
                    <option value="">All modules</option>
                    @foreach(['QBR', 'ARP', 'ARR', 'IRR', '1-on-1'] as $m)
                        <option value="{{ $m }}" @selected($module === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="col-span-12 grid grid-cols-12 gap-4" style="margin-bottom:24px;">
            @foreach([
                ['label' => 'All Time', 'icon' => 'ti-sum', 'color' => 'bg-primary', 'data' => $stats['all']],
                ['label' => 'This Month', 'icon' => 'ti-calendar', 'color' => 'bg-success', 'data' => $stats['this_month']],
                ['label' => 'Last Month', 'icon' => 'ti-calendar-due', 'color' => 'bg-warning', 'data' => $stats['last_month']],
            ] as $card)
                <div class="lg:col-span-4 md:col-span-6 sm:col-span-12 col-span-12">
                    <div class="card shadow-md h-full" style="border-top: 3px solid; border-top-color: var(--color-{{ str_replace('bg-', '', $card['color']) }})">
                        <div class="card-body flex-row py-4 flex items-center gap-2">
                            <div class="{{ $card['color'] }} h-10 w-10 p-1 text-center text-white flex-shrink-0" style="border-radius: 100px">
                                <i class="ti {{ $card['icon'] }} text-2xl"></i>
                            </div>
                            <div>
                                <h5 class="text-xl leading-normal">{{ $card['label'] }}</h5>
                                <span class="text-muted">{{ number_format($card['data']['count']) }} generations</span>
                            </div>
                            <div class="ms-auto text-end">
                                <div class="text-lg font-semibold">{{ number_format($card['data']['tokens']) }} tokens</div>
                                <div class="text-muted">${{ number_format($card['data']['cost'], 4) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <h2 style="margin:0 0 12px">AI Usage by Company</h2>
        <div class="admin-data-table w-full" style="margin-bottom:24px;">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Employees</th>
                            <th>Generations</th>
                            <th>Tokens</th>
                            <th>Cost (USD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($perCompany as $company)
                            <tr>
                                <td>{{ $company['company_name'] }}</td>
                                <td>{{ number_format($company['employee_count']) }}</td>
                                <td>{{ number_format($company['count']) }}</td>
                                <td>{{ number_format($company['tokens']) }}</td>
                                <td>${{ number_format($company['cost'], 4) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-muted dark:text-darklink">
                                    No AI generations yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <h2 style="margin:0 0 12px">All Generations</h2>
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
                            <th style="width:11rem; text-align:right">Action</th>
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
                                <td style="width:11rem; text-align:right; vertical-align:top">
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
    </div>
</x-admin-layout>
