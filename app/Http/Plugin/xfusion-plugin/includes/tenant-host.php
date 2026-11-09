<?php
/**
 * Subdomain model: <company>.<base domain>.
 *
 * ONE WordPress installation, ONE database. The label in front of the base
 * domain is read as the company's custom URL (wp_companies.slug); nothing is
 * duplicated per company. The path model (/{slug}/annual-readiness-plan/ on the
 * main domain) keeps working as a fallback.
 *
 * Off by default. Turn on per environment in wp-config.php:
 *
 *   define('XFUSION_TENANT_BASE_DOMAIN', 'xperiencefusion.com');          // production
 *   define('XFUSION_TENANT_BASE_DOMAIN', 'sandbox.xperiencefusion.com');  // sandbox
 *
 * Central login: people sign in on the main domain only. After login (or when a
 * company subdomain finds nobody signed in) a one-time token (see wp-sso.php,
 * needs XFUSION_SSO_SECRET) creates the session on the company subdomain, so
 * no cookie is shared between subdomains.
 *
 * This file holds the pure helpers; the hooks live in tenant-routing.php.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/** Lower-cased base domain from wp-config.php, or '' when the subdomain model is off. */
function xfusion_tenant_base_domain(): string
{
    if (! defined('XFUSION_TENANT_BASE_DOMAIN')) {
        return '';
    }

    return strtolower(trim((string) XFUSION_TENANT_BASE_DOMAIN, " \t\n\r\0\x0B."));
}

function xfusion_tenant_enabled(): bool
{
    return xfusion_tenant_base_domain() !== '';
}

/** Labels that can never be a company (they are real hosts or common service names). */
function xfusion_tenant_reserved(): array
{
    $reserved = [
        'www', 'admin', 'sandbox', 'api', 'app', 'mail', 'smtp', 'imap', 'pop', 'ftp',
        'cpanel', 'whm', 'webmail', 'webdisk', 'autodiscover', 'autoconfig',
        'staging', 'dev', 'test', 'static', 'cdn', 'assets', 'login', 'auth', 'ns1', 'ns2',
    ];

    return (array) apply_filters('xfusion_tenant_reserved_labels', $reserved);
}

/** A single DNS label: letters, digits and inner dashes, 1-63 characters, and not reserved. */
function xfusion_tenant_label_valid(string $label): bool
{
    if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label)) {
        return false;
    }

    return ! in_array($label, xfusion_tenant_reserved(), true);
}

/** Host of the WordPress "home" option (the main site), lower-cased. */
function xfusion_tenant_main_host(): string
{
    $host = wp_parse_url((string) get_option('home'), PHP_URL_HOST);

    return is_string($host) ? strtolower($host) : '';
}

/** The Host header of this request, lower-cased, without port; '' if it looks wrong. */
function xfusion_tenant_current_host(): string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = (string) preg_replace('/:\d+$/', '', $host);

    return preg_match('/^[a-z0-9.-]+$/', $host) ? $host : '';
}

/**
 * The company label in a host: "acme.xperiencefusion.com" => "acme".
 * '' when the model is off, the host is the main site, has more than one
 * label in front of the base domain, or the label is reserved/invalid.
 */
function xfusion_tenant_slug_from_host(?string $host = null): string
{
    $base = xfusion_tenant_base_domain();
    if ($base === '') {
        return '';
    }

    $host = $host === null ? xfusion_tenant_current_host() : strtolower($host);
    $suffix = '.' . $base;
    if ($host === '' || strlen($host) <= strlen($suffix) || substr($host, -strlen($suffix)) !== $suffix) {
        return '';
    }

    $label = substr($host, 0, -strlen($suffix));

    return xfusion_tenant_label_valid($label) ? $label : '';
}

function xfusion_tenant_scheme(): string
{
    $scheme = wp_parse_url((string) get_option('home'), PHP_URL_SCHEME);

    return $scheme === 'http' ? 'http' : 'https';
}

/** https://<slug>.<base>/<path> — $path starts with "/" and may carry a query string. */
function xfusion_tenant_url(string $slug, string $path = '/'): string
{
    return xfusion_tenant_scheme() . '://' . $slug . '.' . xfusion_tenant_base_domain() . '/' . ltrim($path, '/');
}

/** Same path on the main site (central login, wp-admin, password reset). */
function xfusion_main_url(string $path = '/'): string
{
    return xfusion_tenant_scheme() . '://' . xfusion_tenant_main_host() . '/' . ltrim($path, '/');
}

/** The URL for one module page of a company: subdomain when the model is on, path otherwise. */
function xfusion_company_url(string $slug, string $page): string
{
    if ($slug !== '' && xfusion_tenant_enabled() && xfusion_tenant_label_valid($slug)) {
        return xfusion_tenant_url($slug, '/' . $page . '/');
    }

    return home_url('/' . ($slug !== '' ? $slug . '/' : '') . $page . '/');
}

/** Replace the host of an absolute URL, keeping scheme, path and query. */
function xfusion_tenant_replace_host(string $url, string $newHost): string
{
    $parts = wp_parse_url($url);
    if (! is_array($parts) || empty($parts['host'])) {
        return $url;
    }

    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

    return (isset($parts['scheme']) ? $parts['scheme'] . '://' : '//')
        . $newHost . $port
        . ($parts['path'] ?? '')
        . (isset($parts['query']) ? '?' . $parts['query'] : '')
        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}
