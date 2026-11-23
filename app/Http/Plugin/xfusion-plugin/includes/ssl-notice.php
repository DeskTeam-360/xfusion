<?php
/**
 * Reminder: company domains that still need an SSL certificate.
 *
 * Certificates cannot be requested by API on this server, so someone must open cPanel,
 * SSL/TLS Status, and click "Run AutoSSL". WordPress cannot ask cPanel (the token lives in
 * Laravel), so it asks Laravel's GET /api/v1/domain-ssl and shows the answer — on the wp-admin
 * dashboard, FUSION Overview and the Schema Check page, for administrators only. The answer is
 * cached for 5 minutes; add ?xf_ssl_refresh=1 to any of those pages to ask again at once.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_SSL_NOTICE_TRANSIENT = 'xfusion_ssl_pending';

/** @return array{domains: list<array<string, mixed>>, cpanel_url: string}|null null = unknown / nothing to show */
function xfusion_ssl_pending(): ?array
{
    if (! function_exists('xfusion_company_api_request')) {
        return null;
    }

    if (isset($_GET['xf_ssl_refresh']) && current_user_can('manage_options')) {
        delete_transient(XFUSION_SSL_NOTICE_TRANSIENT);
    }

    $cached = get_transient(XFUSION_SSL_NOTICE_TRANSIENT);
    if (is_array($cached)) {
        return $cached['domains'] === [] ? null : $cached;
    }

    $res = xfusion_company_api_request('/domain-ssl');
    $body = is_array($res['body'] ?? null) ? $res['body'] : [];
    if (! $res['ok'] || empty($body['connected'])) {
        // Laravel unreachable or cPanel not connected: stay quiet, and do not ask again for a minute.
        set_transient(XFUSION_SSL_NOTICE_TRANSIENT, ['domains' => [], 'cpanel_url' => ''], MINUTE_IN_SECONDS);

        return null;
    }

    $data = ['domains' => array_values((array) ($body['domains'] ?? [])), 'cpanel_url' => (string) ($body['cpanel_url'] ?? '')];
    set_transient(XFUSION_SSL_NOTICE_TRANSIENT, $data, 5 * MINUTE_IN_SECONDS);

    return $data['domains'] === [] ? null : $data;
}

/** The reminder as HTML ('' when nothing needs a certificate). */
function xfusion_ssl_notice_html(): string
{
    if (! current_user_can('manage_options')) {
        return '';
    }
    $pending = xfusion_ssl_pending();
    if ($pending === null) {
        return '';
    }

    $labels = [
        'none' => 'no certificate yet',
        'self-signed' => 'self-signed certificate',
        'expired' => 'certificate expired',
        'expiring' => 'certificate expires soon',
    ];

    $count = count($pending['domains']);
    $html = '<div class="notice notice-warning inline" style="padding:10px 14px;margin:10px 0"><p><strong>'
        . esc_html($count . ' company domain' . ($count === 1 ? ' still needs' : 's still need') . ' a certificate.') . '</strong></p><ul style="list-style:disc;margin-left:1.2em">';
    foreach ($pending['domains'] as $d) {
        $state = (string) ($d['state'] ?? '');
        $html .= '<li>' . esc_html((string) ($d['fqdn'] ?? '')) . ' — ' . esc_html($labels[$state] ?? $state)
            . (! empty($d['company']) ? ' (' . esc_html((string) $d['company']) . ')' : '') . '</li>';
    }
    $html .= '</ul><p>In cPanel open <strong>SSL/TLS Status</strong> and click <strong>Run AutoSSL</strong>';
    if ($pending['cpanel_url'] !== '') {
        $html .= ' (<a href="' . esc_url($pending['cpanel_url']) . '" target="_blank" rel="noopener">open cPanel</a>)';
    }
    $html .= '. <a href="' . esc_url(add_query_arg('xf_ssl_refresh', 1)) . '">Check again</a></p></div>';

    return $html;
}

// wp-admin: the dashboard and our own pages (not every screen, so it does not nag).
add_action('admin_notices', function (): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (! $screen || ! in_array($screen->id, ['dashboard', 'toplevel_page_xfusion-overview', 'tools_page_xfusion-schema-check'], true)) {
        return;
    }

    echo xfusion_ssl_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
});
