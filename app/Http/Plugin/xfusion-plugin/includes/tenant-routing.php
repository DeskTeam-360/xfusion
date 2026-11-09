<?php
/**
 * Subdomain model — the WordPress hooks (see tenant-host.php for the idea and
 * for how to switch it on). Every hook returns at once when
 * XFUSION_TENANT_BASE_DOMAIN is not defined, so nothing changes until then.
 *
 * What it does, in short:
 *  - a company subdomain is "the same WordPress" seen as that company;
 *  - sign-in happens on the main domain only; a one-time token (wp-sso.php)
 *    starts the session on the company subdomain — cookies are not shared;
 *  - on a company subdomain wp-admin and wp-login.php go back to the main
 *    domain (admin-ajax.php stays, the pages need it), URLs WordPress builds
 *    for the site stay on the subdomain, and pages are not cached.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/** True when this request is on a valid company subdomain. */
function xfusion_tenant_on_company_host(): bool
{
    return xfusion_tenant_enabled() && xfusion_tenant_slug_from_host() !== '';
}

/** Absolute URL of the current request on the current host. */
function xfusion_tenant_current_url(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/';
    }

    return xfusion_tenant_scheme() . '://' . xfusion_tenant_current_host() . $uri;
}

/** Where people land on their company subdomain when nothing more specific was asked. */
function xfusion_tenant_landing_path(): string
{
    return (string) apply_filters('xfusion_tenant_landing_path', '/resources/resource-menu/');
}

/**
 * The URL that signs $user in on their company subdomain and lands on the page
 * they were heading to. '' when it does not apply (model off, no secret, an
 * administrator with nothing specific requested, or no company) — the caller
 * then keeps WordPress's normal redirect.
 *
 * @param string $requested absolute URL (or path) the person asked for, '' if none
 */
function xfusion_tenant_login_target($user, string $requested): string
{
    if (! xfusion_tenant_enabled() || ! xfusion_sso_ready() || ! ($user instanceof WP_User) || ! $user->exists()) {
        return '';
    }

    $parts = $requested !== '' ? wp_parse_url($requested) : [];
    $parts = is_array($parts) ? $parts : [];
    $reqHost = strtolower((string) ($parts['host'] ?? ''));
    $reqPath = '/' . ltrim((string) ($parts['path'] ?? ''), '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
    $isAdmin = user_can($user, 'manage_options');

    // 1. They were heading to a company subdomain: take them there if they may.
    $slug = $reqHost !== '' ? xfusion_tenant_slug_from_host($reqHost) : '';
    $path = $reqPath;
    if ($slug !== '') {
        $company = xfusion_company_by_slug($slug);
        if ($company === null || ! ($isAdmin || xfusion_user_belongs_to_company((int) $user->ID, $company['id']))) {
            $slug = '';
        }
    }

    // 2. Otherwise their own company (administrators keep WordPress's normal redirect).
    if ($slug === '') {
        if ($isAdmin) {
            return '';
        }
        $slug = xfusion_company_slug_by_id(xfusion_wp_user_linked_company_id((int) $user->ID));
        if ($slug === '' || ! xfusion_tenant_label_valid($slug)) {
            return '';
        }

        // A page of the main site (not admin/login/files) keeps its path; anything else lands on the menu.
        $onMain = $reqHost === '' || $reqHost === xfusion_tenant_main_host();
        $keep = $onMain && ! preg_match('#^/(?:wp-admin|wp-login\.php|wp-json|wp-content|wp-includes)(?:/|$|\?)#', $reqPath) && $reqPath !== '/';
        $path = $keep ? $reqPath : xfusion_tenant_landing_path();
    }

    $token = xfusion_sso_make_token((int) $user->ID, $path);
    if ($token === '') {
        return '';
    }

    return xfusion_tenant_url($slug, '/') . '?xf_sso=' . rawurlencode($token);
}

// ---- Sign-in redirects (WordPress login and Ultimate Member) ----

add_filter('login_redirect', function ($redirectTo, $requested, $user) {
    $target = xfusion_tenant_login_target($user, (string) $requested);

    return $target !== '' ? $target : $redirectTo;
}, 20, 3);

// Ultimate Member computes its own redirect: apply_filters('um_login_redirect_url', $url, $user_id).
add_filter('um_login_redirect_url', function ($url, $userId = 0) {
    $target = xfusion_tenant_login_target(get_userdata((int) $userId), '');

    return $target !== '' ? $target : $url;
}, 20, 2);

// Someone already signed in on the main domain opens wp-login.php?redirect_to=<company subdomain URL>
// (a company page found nobody signed in there, or "Open in WordPress" from the Laravel admin):
// hand the session over straight away instead of showing the login form.
add_action('login_init', function (): void {
    if (! xfusion_tenant_enabled() || ! is_user_logged_in() || ($_REQUEST['action'] ?? 'login') !== 'login') {
        return;
    }

    $requested = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash((string) $_REQUEST['redirect_to'])) : '';
    $host = (string) wp_parse_url($requested, PHP_URL_HOST);
    if ($requested === '' || xfusion_tenant_slug_from_host($host) === '') {
        return;
    }

    $target = xfusion_tenant_login_target(wp_get_current_user(), $requested);
    if ($target !== '') {
        wp_safe_redirect($target);
        exit;
    }
}, 1);

// wp_safe_redirect() only goes to allowed hosts: let valid company subdomains through.
add_filter('allowed_redirect_hosts', function ($hosts, $host = '') {
    if (xfusion_tenant_enabled() && xfusion_tenant_slug_from_host((string) $host) !== '') {
        $hosts[] = (string) $host;
    }

    return $hosts;
}, 10, 2);

// ---- Keep the site on the subdomain it was opened on ----

// URLs WordPress builds for the site (menus, permalinks, our own links) stay on the company host.
add_filter('home_url', function ($url) {
    if (! xfusion_tenant_on_company_host()) {
        return $url;
    }
    $host = wp_parse_url((string) $url, PHP_URL_HOST);

    return (is_string($host) && strtolower($host) === xfusion_tenant_main_host())
        ? xfusion_tenant_replace_host((string) $url, xfusion_tenant_current_host())
        : $url;
}, 10, 1);

// The pages call admin-ajax.php: same origin, so the company session cookie is sent.
add_filter('admin_url', function ($url, $path = '') {
    if (! xfusion_tenant_on_company_host() || ! preg_match('#^(?:admin-ajax|admin-post)\.php#', (string) $path)) {
        return $url;
    }

    return xfusion_tenant_replace_host((string) $url, xfusion_tenant_current_host());
}, 10, 2);

// Logging out on a company host ends the session of that host, then returns to the main login.
add_filter('logout_url', function ($url) {
    return xfusion_tenant_on_company_host() ? xfusion_tenant_replace_host((string) $url, xfusion_tenant_current_host()) : $url;
}, 10, 1);

add_filter('logout_redirect', function ($redirectTo, $requested = '') {
    if (! xfusion_tenant_on_company_host()) {
        return $redirectTo;
    }

    return add_query_arg('loggedout', 'true', xfusion_main_url('/wp-login.php'));
}, 10, 2);

// wp-login.php and wp-admin live on the main domain only (administrators never hold a session here).
add_action('init', function (): void {
    if (! xfusion_tenant_on_company_host()) {
        return;
    }

    global $pagenow;
    $isLogout = $pagenow === 'wp-login.php' && ($_REQUEST['action'] ?? '') === 'logout';
    $isAjax = (function_exists('wp_doing_ajax') && wp_doing_ajax()) || in_array($pagenow, ['admin-ajax.php', 'admin-post.php'], true);

    if (($pagenow === 'wp-login.php' && ! $isLogout) || (is_admin() && ! $isAjax)) {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        wp_redirect(esc_url_raw(xfusion_main_url($uri)), 302);
        exit;
    }
}, 0);

// WordPress "corrects" any host that is not the site's own back to it — not for company hosts.
add_filter('redirect_canonical', function ($redirect) {
    return xfusion_tenant_on_company_host() ? false : $redirect;
}, 5, 1);

// Company pages show per-person data: never let a page cache store them.
add_action('send_headers', function (): void {
    if (xfusion_tenant_on_company_host()) {
        if (! defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
        header('Vary: Host', false);
    }
});

// ---- Which pages belong on a company subdomain ----

/**
 * Pages that always stay on a company subdomain although they are not rows in
 * wp_course_lists: the paths get_company_info() (xfusion_plugin.php) already
 * brands with the company logo. Keep in step with $arrayLinks there.
 *
 * @return list<string>
 */
function xfusion_tenant_static_paths(): array
{
    $paths = [
        '/lms-home-screen/',
        '/topics/dependability/',
        '/account/',
        '/resources/resource-menu/',
        '/start-here/',
        '/individual-contributor/',
        '/leader/',
    ];

    $user = wp_get_current_user();
    if ($user instanceof WP_User && $user->exists()) {
        $paths[] = '/user/' . str_replace(' ', '+', strtolower((string) $user->user_login)) . '/';
    }

    return $paths;
}

/** Is this URL (without query) a row of the course list (wp_course_lists.url)? Fails open if the table cannot be read. */
function xfusion_tenant_url_in_course_list(string $url): bool
{
    global $wpdb;

    $candidates = function_exists('xfusion_course_list_url_candidates') ? xfusion_course_list_url_candidates($url) : [$url];
    foreach ($candidates as $candidate) {
        // Stored with or without the trailing slash.
        $candidates[] = rtrim($candidate, '/');
        $candidates[] = rtrim($candidate, '/') . '/';
    }
    $candidates = array_values(array_unique($candidates));

    $placeholders = implode(',', array_fill(0, count($candidates), '%s'));
    $table = $wpdb->prefix . 'course_lists';
    $suppress = $wpdb->suppress_errors(true);
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table from $wpdb->prefix, placeholders from count().
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE url IN ({$placeholders})", ...$candidates));
    $failed = (string) $wpdb->last_error !== '';
    $wpdb->suppress_errors($suppress);

    return $failed ? true : (int) $count > 0;
}

/**
 * On a company subdomain only pages of the course list (plus the FUSION module
 * pages and the branded paths above) stay; everything else — blog, marketing,
 * unlisted pages — goes back to the main domain. Always true off a company host.
 */
function xfusion_tenant_page_allowed_on_company_host(): bool
{
    if (! xfusion_tenant_on_company_host()) {
        return true;
    }

    $path = '/' . ltrim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
    $url = xfusion_tenant_scheme() . '://' . xfusion_tenant_current_host() . $path;

    $allowed = in_array($path, xfusion_tenant_static_paths(), true);

    if (! $allowed && is_page() && function_exists('xfusion_company_route_modules')) {
        $page = get_queried_object();
        $allowed = $page instanceof WP_Post && in_array($page->post_name, array_values(xfusion_company_route_modules()), true);
    }

    if (! $allowed) {
        $allowed = xfusion_tenant_url_in_course_list($url);
    }

    return (bool) apply_filters('xfusion_tenant_page_allowed', $allowed, $url);
}
