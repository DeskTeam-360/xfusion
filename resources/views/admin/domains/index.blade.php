<x-admin-layout>
    <div class="px-5 py-5">
        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-dark dark:text-white">Domains</h1>
            <p class="mt-1 max-w-3xl text-sm text-muted dark:text-darklink">
                Company subdomains
                @if($overview['base']) under <code>{{ $overview['base'] }}</code>@endif:
                which are in use by a company, which are still missing, and which no company owns.
            </p>
        </div>

        <x-admin.ssl-notice />

        @if($overview['error'])
            <div class="alert alert-warning mb-4" role="alert">{{ $overview['error'] }}</div>
        @elseif(! $automation)
            <div class="alert alert-warning mb-4" role="alert">
                cPanel is not connected, so only the companies' expected addresses are listed and their state is unknown.
                Set <code>CPANEL_AUTOMATION_ENABLED</code> and the other <code>CPANEL_*</code> values (see <code>config/cpanel.php</code>).
            </div>
        @endif

        @if($overview['base'])
            <div class="grid grid-cols-12 gap-4 mb-5">
                @foreach([
                    ['In use', $overview['counts']['in_use'], '#166534', '#dcfce7'],
                    ['Missing', $overview['counts']['missing'], '#92400e', '#fef3c7'],
                    ['Unused', $overview['counts']['unused'], '#991b1b', '#fee2e2'],
                ] as [$label, $count, $ink, $bg])
                    <div class="lg:col-span-4 md:col-span-4 sm:col-span-12 col-span-12">
                        <div class="card shadow-md h-full">
                            <div class="card-body py-4 flex items-center gap-3">
                                <span style="display:inline-block;min-width:2.5rem;text-align:center;padding:.25rem .7rem;border-radius:999px;font-weight:700;background:{{ $bg }};color:{{ $ink }}">{{ $count }}</span>
                                <span class="text-lg font-semibold">{{ $label }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="admin-data-table w-full">
                <div class="overflow-x-auto">
                    <table class="admin-table" style="min-width:0; table-layout:auto">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Company</th>
                                <th>Employees</th>
                                <th>State</th>
                                <th>Certificate</th>
                                <th style="width:9rem; text-align:right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overview['rows'] as $row)
                                @php
                                    $badge = [
                                        'in_use' => ['In use', '#dcfce7', '#166534'],
                                        'missing' => ['Missing', '#fef3c7', '#92400e'],
                                        'unused' => ['Unused', '#fee2e2', '#991b1b'],
                                        'unknown' => ['Unknown', '#f3f4f6', '#374151'],
                                        'system' => ['System', '#e0e7ff', '#3730a3'],
                                    ][$row['status']];
                                    $sslBadge = [
                                        'valid' => ['Valid', '#dcfce7', '#166534'],
                                        'expiring' => ['Expiring', '#fef3c7', '#92400e'],
                                        'expired' => ['Expired', '#fee2e2', '#991b1b'],
                                        'self-signed' => ['Self-signed', '#fee2e2', '#991b1b'],
                                        'none' => ['None yet', '#fef3c7', '#92400e'],
                                    ][$row['ssl']] ?? null;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="font-semibold">{{ $row['fqdn'] }}</a>
                                        @if($row['dir'])<div class="text-muted text-xs">{{ $row['dir'] }}</div>@endif
                                    </td>
                                    <td>
                                        @if($row['company'])
                                            <a href="{{ route('company.show-detail', $row['company_id']) }}">{{ $row['company'] }}</a>
                                        @else
                                            <span class="text-muted">{{ $row['status'] === 'system' ? '— system site, not a company' : '— no company' }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['employees'] !== null ? number_format($row['employees']) : '—' }}</td>
                                    <td>
                                        <span style="display:inline-block;padding:.15rem .65rem;border-radius:999px;font-size:.8rem;font-weight:600;background:{{ $badge[1] }};color:{{ $badge[2] }}">{{ $badge[0] }}</span>
                                    </td>
                                    <td>
                                        @if($sslBadge)
                                            <span style="display:inline-block;padding:.15rem .65rem;border-radius:999px;font-size:.8rem;font-weight:600;background:{{ $sslBadge[1] }};color:{{ $sslBadge[2] }}">{{ $sslBadge[0] }}</span>
                                            @if($row['ssl_expires'])<div class="text-muted text-xs">until {{ $row['ssl_expires'] }}</div>@endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right">
                                        @if($row['status'] === 'missing')
                                            <form method="post" action="{{ route('domains.create', $row['slug']) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">Create</button>
                                            </form>
                                        @elseif($row['status'] === 'unused' && $canRemove)
                                            <form method="post" action="{{ route('domains.remove', $row['slug']) }}"
                                                  onsubmit="return confirm('Remove {{ $row['fqdn'] }} from cPanel? Nothing uses it.')">
                                                @csrf
                                                <button type="submit" class="btn btn-error btn-sm">Remove</button>
                                            </form>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-muted dark:text-darklink">No company has a custom URL yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="mt-3 text-xs text-muted dark:text-darklink">
                <strong>In use</strong>: a company owns the name and the subdomain exists. <strong>Missing</strong>: a company has the custom URL but the subdomain does not exist in cPanel yet.
                <strong>Unused</strong>: the subdomain exists but no company owns it. <strong>System</strong>: a site of its own (such as the admin) that is not a company subdomain; it is never offered for removal.
                @unless($canRemove) Removing is switched off (<code>CPANEL_DELETE_ON_REMOVE</code>). @endunless
            </p>
        @endif
    </div>
</x-admin-layout>
