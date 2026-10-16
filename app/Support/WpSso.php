<?php

namespace App\Support;

/**
 * One-time sign-in hand-off from the Laravel admin to the WordPress site.
 *
 * Laravel signs a short-lived token (HMAC-SHA256 over user id, expiry, a
 * single-use id and the target path) with a secret shared with the plugin
 * (WP_SSO_SECRET here / XFUSION_SSO_SECRET in wp-config.php); the plugin
 * verifies it, marks it used, logs the same wp_users.ID in and redirects.
 */
final class WpSso
{
    public const TTL_SECONDS = 60;

    public static function secret(): string
    {
        return (string) config('app.wp_sso_secret', '');
    }

    /** A target path on the WordPress site: "/…", never protocol-relative or with a scheme. */
    public static function isSafePath(string $path): bool
    {
        // \x5C = backslash, written as a hex escape so no string-escaping layer can mangle it.
        return (bool) preg_match('#^/(?!/)[^\s\x5C]*$#', $path);
    }

    public static function token(int $userId, string $path): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode([
            'uid' => $userId,
            'exp' => time() + self::TTL_SECONDS,
            'jti' => bin2hex(random_bytes(12)),
            'to' => $path,
        ], JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');

        return $payload.'.'.hash_hmac('sha256', $payload, self::secret());
    }

    /** Where to send the browser for a given WordPress path ("/slug/arp/?x=1"). */
    public static function destination(int $userId, string $path): string
    {
        $base = WordpressPublicUrl::base();

        if (self::secret() === '') {
            return $base.$path; // SSO not configured: plain link, user signs in on WordPress as before
        }

        return $base.'/?xf_sso='.self::token($userId, $path);
    }

    /** Link for views: any absolute WordPress URL (or "/path") routed through the hand-off. */
    public static function link(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $base = WordpressPublicUrl::base();
        $path = str_starts_with($url, $base) ? substr($url, strlen($base)) : $url;
        if ($path === '' || $path[0] !== '/') {
            return $url;
        }

        return route('wp-sso', ['to' => $path]);
    }
}
