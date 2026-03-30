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
