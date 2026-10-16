<?php
/**
 * Company-scoped URLs: /{company-slug}/arp, /arr, /qbr, /irr, /1on1.
 *
 * Each URL loads the existing module Page (the one holding the shortcode)
 * natively, so the theme template and shortcode render exactly as before —
 * the company slug only adds context (the picker filters to that company)
 * and access control. Slugs live in wp_companies.slug (Laravel admin).
 *
 * Non-admins who open a module Page directly (e.g. /annual-readiness-plan/)
 * are redirected to their own company's URL when it is a plain picker view;
 * administrators keep the old URLs unchanged.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_COMPANY_ROUTES_VERSION = '1';

/** module key in the URL => slug of the WordPress Page that holds its shortcode. */
function xfusion_company_route_modules(): array
{
    return [
        'arp' => 'annual-readiness-plan',
        'arr' => 'annual-readiness-review',
        'qbr' => 'quarterly-business-review',
        'irr' => 'individual-readiness-review',
        '1on1' => '1-on-1-alignment',
    ];
}

add_action('init', function (): void {
    foreach (xfusion_company_route_modules() as $module => $page) {
        add_rewrite_rule(
            '^([a-z0-9]+(?:-[a-z0-9]+)*)/' . $module . '/?$',
            'index.php?pagename=' . $page . '&xf_company=$matches[1]&xf_module=' . $module,
            'top'
        );
    }

    // Flush once per version so no manual "Save Permalinks" is needed after deploy.
    if (get_option('xfusion_company_routes_version') !== XFUSION_COMPANY_ROUTES_VERSION) {
        flush_rewrite_rules(false);
        update_option('xfusion_company_routes_version', XFUSION_COMPANY_ROUTES_VERSION);
    }
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'xf_company';
    $vars[] = 'xf_module';

    return $vars;
});

/** @return array{id: int, slug: string, title: string}|null */
function xfusion_company_by_slug(string $slug): ?array
{
    global $wpdb;
    $table = $wpdb->prefix . 'companies';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, slug, title FROM {$table} WHERE slug = %s LIMIT 1", $slug), ARRAY_A);

    return $row ? ['id' => (int) $row['id'], 'slug' => (string) $row['slug'], 'title' => (string) $row['title']] : null;
}

function xfusion_company_slug_by_id(int $companyId): string
{
    global $wpdb;
    $table = $wpdb->prefix . 'companies';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$table} WHERE id = %d", $companyId));

    return is_string($slug) ? $slug : '';
}

function xfusion_user_belongs_to_company(int $userId, int $companyId): bool
{
    if ($userId < 1 || $companyId < 1) {
        return false;
    }
    if (function_exists('xfusion_wp_user_linked_company_id') && xfusion_wp_user_linked_company_id($userId) === $companyId) {
        return true;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'company_employees';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND company_id = %d", $userId, $companyId)) > 0;
}

/**
 * A slug that is not a company must not hijack a genuine page that happens
 * to be called /{something}/arp — hand it back to WordPress as a normal path.
 */
add_filter('request', function (array $vars): array {
    if (empty($vars['xf_company'])) {
        return $vars;
    }
    if (xfusion_company_by_slug((string) $vars['xf_company']) === null) {
        $original = $vars['xf_company'] . '/' . ($vars['xf_module'] ?? '');

        return ['pagename' => $original];
    }

    return $vars;
});

add_action('template_redirect', function (): void {
    $slug = (string) get_query_var('xf_company');

    if ($slug !== '') {
        $company = xfusion_company_by_slug($slug);
        if ($company === null) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(home_url(add_query_arg([]))));
            exit;
        }

        $userId = get_current_user_id();
        if (! current_user_can('manage_options') && ! xfusion_user_belongs_to_company($userId, $company['id'])) {
            wp_safe_redirect(home_url('/resources/resource-menu/'));
            exit;
        }

        $GLOBALS['xfusion_route_company'] = $company;

        return;
    }

    // Old module URL: administrators keep it, everyone else is moved to the
    // company URL — but only for the plain picker view, so deep links that
    // carry an explicit record id (?arp_id=…) keep working.
    if (! is_user_logged_in() || current_user_can('manage_options') || ! is_page()) {
        return;
    }
    foreach (array_keys($_GET) as $param) {
        if (substr((string) $param, -3) === '_id') {
            return;
        }
    }

    $pageSlug = (string) get_queried_object()->post_name;
    $module = array_search($pageSlug, xfusion_company_route_modules(), true);
    if ($module === false || ! function_exists('xfusion_wp_user_linked_company_id')) {
        return;
    }

    $companySlug = xfusion_company_slug_by_id(xfusion_wp_user_linked_company_id(get_current_user_id()));
    if ($companySlug === '') {
        return;
    }

    $target = home_url('/' . $companySlug . '/' . $module . '/');
    $query = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    wp_safe_redirect($query !== '' ? $target . '?' . $query : $target, 302);
    exit;
});

/** Lets the pickers know the visitor is a WordPress administrator (no-access redirects don't apply to them). */
add_action('wp_head', function (): void {
    if (is_user_logged_in() && current_user_can('manage_options')) {
        echo '<script>window.XFUSION_IS_WP_ADMIN = true;</script>' . "\n";
    }
});

/** Lets the wizard pickers narrow their list to the company in the URL. */
add_action('wp_head', function (): void {
    $company = $GLOBALS['xfusion_route_company'] ?? null;
    if (! is_array($company)) {
        return;
    }
    echo '<script>window.XFUSION_ROUTE_COMPANY_ID = ' . (int) $company['id'] . ';' .
        'window.xfusionFilterByRouteCompany = function (rows) {' .
        'var id = window.XFUSION_ROUTE_COMPANY_ID;' .
        'if (!id || !Array.isArray(rows)) { return rows; }' .
        'return rows.filter(function (r) { return !r || r.company_id === undefined || r.company_id === null || Number(r.company_id) === id; });' .
        '};</script>' . "\n";
});
