<?php

/*
|--------------------------------------------------------------------------
| cPanel automation for company subdomains
|--------------------------------------------------------------------------
|
| When a company is created, or its custom URL (slug) changes, Laravel creates
| <slug>.<WORDPRESS_TENANT_BASE_DOMAIN> as a subdomain in cPanel (document root =
| the WordPress folder) and asks cPanel to run AutoSSL so it gets a certificate.
|
| OFF by default. It also needs WORDPRESS_TENANT_BASE_DOMAIN (the subdomain
| model) to be set. Tokens go in .env only; never log or commit them.
|
| Notes:
|  - DNS must already resolve the new name to this server (a wildcard record at
|    the DNS host does that). AutoSSL validates over HTTP, so a name that does
|    not resolve yet fails and is retried by the next AutoSSL run (daily).
|  - Removing a subdomain is OFF by default: its document root is the shared
|    WordPress folder, so deleting must be a deliberate step.
*/

return [
    'enabled' => (bool) env('CPANEL_AUTOMATION_ENABLED', false),

    // cPanel account that owns the WordPress site.
    'host' => env('CPANEL_HOST'),            // e.g. server1.example.com
    'port' => (int) env('CPANEL_PORT', 2083),
    'user' => env('CPANEL_USER'),            // cPanel username
    'token' => env('CPANEL_API_TOKEN'),      // cPanel > Manage API Tokens

    // The domain cPanel hosts (the "root domain" of the subdomain). The tenant base
    // domain must end with it: base sandbox.xperiencefusion.com, root xperiencefusion.com.
    'root_domain' => env('CPANEL_ROOT_DOMAIN', 'xperiencefusion.com'),

    // Folder of the WordPress site, relative to the cPanel account's home directory
    // (e.g. public_html or public_html/sandbox). Every company subdomain points here.
    'document_root' => env('CPANEL_DOCUMENT_ROOT'),

    // How to start AutoSSL after adding a subdomain:
    //   uapi = the account's own token (cPanel UAPI SSL::start_autossl_check)
    //   whm  = a WHM token (start_autossl_check_for_one_user); needs the whm_* values below
    //   none = do nothing; the daily AutoSSL run picks the name up
    'autossl' => env('CPANEL_AUTOSSL', 'uapi'),
    'whm_host' => env('WHM_HOST'),
    'whm_port' => (int) env('WHM_PORT', 2087),
    'whm_user' => env('WHM_USER', 'root'),
    'whm_token' => env('WHM_API_TOKEN'),

    // Delete the cPanel subdomain when a company is deleted or its custom URL changes.
    'delete_on_remove' => (bool) env('CPANEL_DELETE_ON_REMOVE', false),

    // How saving a company triggers the subdomain sync:
    //   after_response = right after the page has been sent (default; no queue worker needed)
    //   sync           = inside the request, so a slow or failing cPanel shows up at once (good for debugging)
    //   queue          = the 'database' queue; needs `php artisan queue:work` running
    'dispatch' => env('CPANEL_DISPATCH', 'after_response'),

    'timeout' => (int) env('CPANEL_TIMEOUT', 25),
    'verify_ssl' => (bool) env('CPANEL_VERIFY_SSL', true),
];
