<?php

namespace App\Support;

/**
 * Public WordPress/LMS base URL = same host as Laravel, with the leading "admin." label removed.
 *
 * Examples:
 * - https://admin.sandbox.example.com → https://sandbox.example.com
 * - https://admin.example.com → https://example.com
 *
 * Optional WORDPRESS_URL in .env overrides this when the public site is not on the derived host.
 */
final class WordpressPublicUrl
{
    public static function base(): string
    {
        $explicit = config('app.wordpress_url');
        if (is_string($explicit) && $explicit !== '') {
            return rtrim($explicit, '/');
        }

        [$scheme, $host] = self::schemeAndHostFromRequestOrAppUrl();

        $host = self::stripAdminSubdomain($host);

        return rtrim($scheme . '://' . $host, '/');
    }

    /**
     * Link to one record's wizard on the WordPress site.
     *
     * With the company's slug it is the company URL (/{slug}/arp/?arp_id=7);
     * without one it falls back to the plain module page, which still works.
     *
     * @param  'arp'|'arr'|'qbr'|'irr'|'one-on-one'  $module
     */
    public static function recordUrl(string $module, int $recordId, ?string $companySlug = null): string
    {
        // module => [company-URL key, plain page slug, query param]
        $map = [
            'arp' => ['arp', 'annual-readiness-plan', 'arp_id'],
            'arr' => ['arr', 'annual-readiness-review', 'arr_id'],
            'qbr' => ['qbr', 'quarterly-business-review', 'qbr_id'],
            'irr' => ['irr', 'individual-readiness-review', 'irr_id'],
            'one-on-one' => ['1on1', '1-on-1-alignment', 'conversation_id'],
        ];
        [$companyKey, $page, $param] = $map[$module];

        $path = filled($companySlug) ? $companySlug.'/'.$page : $page;

        return self::base().'/'.$path.'/?'.$param.'='.$recordId;
    }

    /**
     * @return array{0: string, 1: string} [scheme, host]
     */
    private static function schemeAndHostFromRequestOrAppUrl(): array
    {
        if (function_exists('request') && request()->getHost() !== '') {
            return [request()->getScheme(), request()->getHost()];
        }

        $appUrl = (string) config('app.url', 'http://localhost');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

        return [$scheme, $host];
    }

    private static function stripAdminSubdomain(string $host): string
    {
        $lower = strtolower($host);
        if (str_starts_with($lower, 'admin.')) {
            return substr($host, strlen('admin.'));
        }

        return $host;
    }
}
