<?php
/**
 * One-time sign-in hand-off from the Laravel admin ("Open in WordPress").
 *
 * Laravel (App\Support\WpSso) redirects to /?xf_sso=<payload>.<signature>.
 * The payload carries the wp_users.ID, an expiry, a single-use id and the
 * target path, signed with HMAC-SHA256 using XFUSION_SSO_SECRET (define it in
 * wp-config.php — it must equal WP_SSO_SECRET in Laravel's .env). Without the
 * constant this file does nothing at all.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/** Only a plain path on this site: "/…", never protocol-relative, a scheme, or a backslash. */
function xfusion_sso_safe_path(string $path): bool
{
    // \x5C = backslash, written as a hex escape so no string-escaping layer can mangle it.
    return (bool) preg_match('#^/(?!/)[^\s\x5C]*$#', $path);
}

function xfusion_sso_b64url_decode(string $value): string
{
    return (string) base64_decode(strtr($value, '-_', '+/'), true);
}

add_action('init', function (): void {
    if (! isset($_GET['xf_sso']) || ! defined('XFUSION_SSO_SECRET') || (string) XFUSION_SSO_SECRET === '') {
        return;
    }

    $fail = static function (): void {
        wp_safe_redirect(home_url('/'));
        exit;
    };

    $token = (string) wp_unslash($_GET['xf_sso']);
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        $fail();
    }

    [$payload, $signature] = $parts;
    if (! hash_equals(hash_hmac('sha256', $payload, (string) XFUSION_SSO_SECRET), $signature)) {
        $fail();
    }

    $data = json_decode(xfusion_sso_b64url_decode($payload), true);
    if (! is_array($data)) {
        $fail();
    }

    $uid = (int) ($data['uid'] ?? 0);
    $exp = (int) ($data['exp'] ?? 0);
    $jti = (string) ($data['jti'] ?? '');
    $to = (string) ($data['to'] ?? '');

    // Short-lived: reject expired tokens and anything claiming an unreasonably long life.
    if ($uid < 1 || $jti === '' || $exp < time() || $exp > time() + 300 || ! xfusion_sso_safe_path($to)) {
        $fail();
    }

    // Single use.
    $usedKey = 'xf_sso_' . md5($jti);
    if (get_transient($usedKey)) {
        $fail();
    }
    set_transient($usedKey, 1, 10 * MINUTE_IN_SECONDS);

    $user = get_userdata($uid);
    if (! $user) {
        $fail();
    }

    // Already signed in as this person: nothing to switch. Anyone else: replace the session.
    if (get_current_user_id() !== $uid) {
        wp_clear_auth_cookie();
        wp_set_current_user($uid);
        wp_set_auth_cookie($uid, false, is_ssl());
    }

    wp_safe_redirect(home_url($to));
    exit;
}, 1);
