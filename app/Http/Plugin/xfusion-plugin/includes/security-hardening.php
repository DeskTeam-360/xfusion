<?php
/**
 * Small hardening switches found in the sandbox review.
 *
 *  - The REST list of users (/wp-json/wp/v2/users) answered anyone, signed in or not, with every
 *    person's id, display name and login-like slug — across all companies. Signed-out visitors no
 *    longer get it (editors and administrators signed in are unaffected).
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
