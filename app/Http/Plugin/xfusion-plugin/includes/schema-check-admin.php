<?php
/**
 * Tools → FUSION Schema Check.
 *
 * Asks Laravel (GET /api/v1/schema-check) whether every table and column listed in
 * Laravel's config/fusion_schema.php exists in the shared database, and lists what is
 * missing. Read-only; administrators only. Needs XFUSION_API_BEARER_TOKEN to
 * match FUSION_API_TOKEN in Laravel's .env.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('admin_menu', function (): void {
    add_management_page('FUSION Schema Check', 'FUSION Schema Check', 'manage_options', 'xfusion-schema-check', 'xfusion_schema_check_page');
});

function xfusion_schema_check_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die('Not allowed.');
    }

    echo '<div class="wrap"><h1>FUSION Schema Check</h1>';
    echo '<p>Plugin version: <strong>' . esc_html(defined('XFUSION_PLUGIN_VERSION') ? XFUSION_PLUGIN_VERSION : 'unknown') . '</strong></p>';

    if (! function_exists('xfusion_company_api_request')) {
        echo '<p>The Laravel API helper is not loaded.</p></div>';

        return;
    }

    $res = xfusion_company_api_request('/schema-check');
    if (! $res['ok']) {
        echo '<div class="notice notice-error"><p>Could not run the check: ' . esc_html((string) $res['error']) . '</p></div></div>';

        return;
    }

    $data = (array) $res['body'];
    $problems = [];
    foreach ((array) ($data['tables'] ?? []) as $t) {
        if (($t['status'] ?? 'ok') !== 'ok') {
            $problems[] = $t;
        }
    }

    if (! empty($data['ok'])) {
        echo '<div class="notice notice-success"><p><strong>Schema is complete.</strong> ' . count((array) ($data['tables'] ?? [])) . ' tables checked, no missing tables or columns.</p></div></div>';

        return;
    }

    echo '<div class="notice notice-error"><p><strong>' . count($problems) . ' problem(s) found.</strong> Run the listed SQL files on the database, then reload this page.</p></div>';

    if ($problems) {
        echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>Table</th><th>Problem</th><th>Fix with</th></tr></thead><tbody>';
        foreach ($problems as $t) {
            $what = ($t['status'] ?? '') === 'missing_table'
                ? 'Table is missing'
                : 'Missing columns: ' . implode(', ', (array) ($t['missing'] ?? []));
            echo '<tr><td><code>' . esc_html((string) $t['table']) . '</code></td><td>' . esc_html($what) . '</td><td><code>'
                . esc_html((string) ($t['sql_hint'] ?? '')) . '</code></td></tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}
