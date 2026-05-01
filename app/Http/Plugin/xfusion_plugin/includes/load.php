<?php
/**
 * Muat semua modul plugin (bekas WPCode + bridge theme).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

$xfusion_include_files = [
    'woocommerce-mindful-mothers-checkout.php',
    'woocommerce-mindful-mothers-email.php',
    'learndash-topic-global-search.php',
    'learndash-menu-highlighter.php',
    'learndash-topic-list.php',
    'learndash-topic-list-by-id.php',
    'learndash-course-progress.php',
    'grit-chart-individual.php',
    'grit-chart-team.php',
    'learndash-mark-topic-complete.php',
    'learndash-redirect-topics.php',
    'learndash-topic-category-progress-bar.php',
    'gravity-forms-bridge.php',
    'gravity-forms-course-list-mark-complete.php',
    'search-index-maintenance.php',
];

foreach ($xfusion_include_files as $xfusion_file) {
    $xfusion_path = __DIR__ . '/' . $xfusion_file;
    if (is_readable($xfusion_path)) {
        require_once $xfusion_path;
    }
}

if (!function_exists('xfusion_company_api_request')) {
    $xfusion_company_paths = [
        __DIR__ . '/wordpress_xfusion_company_shortcode.php',
        dirname(XFUSION_PLUGIN_DIR) . '/wordpress_xfusion_company_shortcode.php',
    ];
    foreach ($xfusion_company_paths as $xfusion_company_shortcode_file) {
        if (is_readable($xfusion_company_shortcode_file)) {
            require_once $xfusion_company_shortcode_file;
            break;
        }
    }
}
