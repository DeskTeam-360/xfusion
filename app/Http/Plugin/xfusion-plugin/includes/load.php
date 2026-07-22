<?php
/**
 * Muat semua modul plugin (bekas WPCode + bridge theme).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

$xfusion_include_files = [
    'course-scoring-group-gauge-shortcode.php',
    'one-on-one-shortcode.php',
    'one-on-one-wizard/one-on-one-wizard-shortcode.php',
    'annual-readiness-plan/arp-db-migration.php',
    'annual-readiness-plan/arp-wizard-shortcode.php',
    'quarterly-business-review/qbr-wizard-shortcode.php',
    'individual-readiness-review/irr-wizard-shortcode.php',
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
    'xfusion-knowledge-cpt.php',
    'xfusion-llm-prompts-registry.php',
    'xfusion-llm-prompts-admin.php',
    'xfusion-one-on-one-briefs-admin.php',
    'xfusion-one-on-one-synthesis-admin.php',
    'xfusion-ai-insights-settings.php',
    'result-evaluation.php',
    'once-popup.php',
    'insight-date-filter.php',
    'cor-unified-insights.php',
    'send-evaluation-shortcode.php',
    'um-profile-courses-shared.php',
    'um-profile-courses-course-list.php',
    'um-profile-courses-tool-list.php',
    'um-profile-courses.php',
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
