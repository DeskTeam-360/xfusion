{{--
    Reminder for administrators: company subdomains that still need a certificate.
    Certificates cannot be requested by API on this server, so someone has to press
    "Run AutoSSL" in cPanel. Shown only when cPanel is connected and something is pending.
--}}
@php
    $sslPending = null;
    if (\App\Support\WpAdmin::isAdministrator((int) \Illuminate\Support\Facades\Auth::id())
        && \App\Services\CpanelSubdomainService::enabled()) {
        try {
            $sslPending = app(\App\Services\DomainOverviewService::class)->pendingSsl();
        } catch (\Throwable $e) {
            $sslPending = null; // a notice must never break the page
        }
    }
    $sslLabels = [
        'none' => 'no certificate yet',
        'self-signed' => 'self-signed certificate',
        'expired' => 'certificate expired',
        'expiring' => 'certificate expires soon',
    ];
@endphp

@if($sslPending && $sslPending['connected'] && $sslPending['domains'])
    <div class="alert alert-warning mb-4" role="alert">
        <strong>{{ count($sslPending['domains']) }} company {{ \Illuminate\Support\Str::plural('domain', count($sslPending['domains'])) }} still need{{ count($sslPending['domains']) === 1 ? 's' : '' }} a certificate.</strong>
        <ul style="margin:.4rem 0 .4rem 1.2rem;list-style:disc">
            @foreach($sslPending['domains'] as $d)
                <li>{{ $d['fqdn'] }} — {{ $sslLabels[$d['state']] ?? $d['state'] }}@if($d['company']) ({{ $d['company'] }})@endif</li>
            @endforeach
        </ul>
        In cPanel open <strong>SSL/TLS Status</strong> and click <strong>Run AutoSSL</strong>
        (<a href="https://{{ config('cpanel.host') }}:{{ config('cpanel.port') }}/" target="_blank" rel="noopener">open cPanel</a>),
        then check <a href="{{ route('domains.index') }}">Organization &rsaquo; Domains</a>.
    </div>
@endif
