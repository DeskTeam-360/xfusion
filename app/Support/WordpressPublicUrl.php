<?php

namespace App\Support;

/**
 * Resolves the public WordPress site base URL (LMS front-end).
 * When this app runs on admin.{domain}, the public site is typically {domain} (admin. removed).
 */
final class WordpressPublicUrl
{
    public static function base(): string
    {
        $explicit = config('app.wordpress_url');
        if (is_string($explicit) && $explicit !== '') {
            return rtrim($explicit, '/');
        }

        if (function_exists('request') && request()->getHost() !== '') {
            $scheme = request()->getScheme();
            $host = request()->getHost();
        } else {
            $appUrl = (string) config('app.url', 'http://localhost');
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
            $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        }

        $host = self::stripAdminSubdomain($host);

        return rtrim($scheme . '://' . $host, '/');
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
