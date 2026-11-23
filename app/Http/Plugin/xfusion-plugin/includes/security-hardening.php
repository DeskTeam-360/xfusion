<?php
/**
 * Small hardening switches found in the sandbox review.
 *
 *  - The REST list of users (/wp-json/wp/v2/users) answered anyone, signed in or not, with every
 *    person's id, display name and login-like slug — across all companies. Signed-out visitors no
 *    longer get it (editors and administrators signed in are unaffected).
 *
 *  - XML-RPC (xmlrpc.php) is switched off.
 *
 * Turn a switch off with its filter, e.g. add_filter('xfusion_restrict_rest_users', '__return_false').
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_filter('rest_endpoints', function (array $endpoints): array {
    if (is_user_logged_in() || ! apply_filters('xfusion_restrict_rest_users', true)) {
        return $endpoints;
    }

    foreach (array_keys($endpoints) as $route) {
        if ($route === '/wp/v2/users' || strpos((string) $route, '/wp/v2/users/') === 0) {
            unset($endpoints[$route]);
        }
    }

    return $endpoints;
});

// xmlrpc.php is an old remote-access door (password guessing in bulk via system.multicall). Nothing here
// uses it. Turn back on with add_filter('xfusion_disable_xmlrpc', '__return_false').
add_filter('xmlrpc_enabled', function ($enabled) {
    return apply_filters('xfusion_disable_xmlrpc', true) ? false : $enabled;
});
add_filter('xmlrpc_methods', function ($methods) {
    return apply_filters('xfusion_disable_xmlrpc', true) ? [] : $methods;
});
