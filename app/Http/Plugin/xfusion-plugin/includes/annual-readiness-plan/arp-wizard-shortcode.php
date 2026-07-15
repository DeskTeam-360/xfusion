<?php
/**
 * XFusion — Annual Readiness Plan™ (ARP) Interactive Tool
 *
 * Usage: [fusion_arp_wizard]
 *
 * 7-step wizard shell. Steps 1–2 are interactive form UI; steps 3–7 are
 * placeholders until their content is implemented.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/styles.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/steps/step-1-foundation.php';
require_once __DIR__ . '/steps/step-2-future-state.php';
require_once __DIR__ . '/steps/step-3-readiness.php';
require_once __DIR__ . '/steps/step-4-priorities.php';
require_once __DIR__ . '/steps/step-5-learning.php';
require_once __DIR__ . '/steps/step-6-ai-review.php';
require_once __DIR__ . '/steps/step-7-publish.php';

function xfusion_arp_wizard_shortcode($atts = []): string
{
    if (! is_user_logged_in()) {
        return '<p class="xar-muted">' . esc_html__('Please log in to use the Annual Readiness Plan™ wizard.', 'xfusion') . '</p>';
    }

    $atts = shortcode_atts([
        'organization'     => 'Northwind Solar Co-op',
        'plan_year'        => (string) wp_date('Y'),
        'status'           => 'Draft',
        'version'          => '1.0',
        'executive_owner'  => '',
        'last_saved'       => '',
    ], $atts, 'fusion_arp_wizard');

    $currentUser = wp_get_current_user();
    $ownerName   = $atts['executive_owner'] !== ''
        ? $atts['executive_owner']
        : trim($currentUser->display_name !== '' ? $currentUser->display_name : $currentUser->user_login);

    $panelFns = [
        xfarp_wizard_step_foundation_js(),
        xfarp_wizard_step_future_state_js(),
        xfarp_wizard_step_readiness_js(),
        xfarp_wizard_step_priorities_js(),
        xfarp_wizard_step_learning_js(),
        xfarp_wizard_step_ai_review_js(),
        xfarp_wizard_step_publish_js(),
    ];

    $panelsJs    = 'var PANELS = {' . "\n" . implode(",\n\n", $panelFns) . "\n" . '};';
    $coreJs      = xfarp_wizard_core_js();
    $readinessJs = xfarp_wizard_readiness_init_js();
    $strategicJs = xfarp_wizard_strategic_init_js();
    $learningJs  = xfarp_wizard_learning_init_js();
    $aiReviewJs  = xfarp_wizard_ai_review_init_js();
    $publishJs   = xfarp_wizard_publish_init_js();
    $css         = xfarp_wizard_styles_css();

    $wizardConfig = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('xfarp_wizard_save_draft'),
        'userId'  => get_current_user_id(),
    ];

    ob_start();
    ?>
<div id="xfarp-wiz">

    <div class="xar-header">
        <div class="xar-header-inner">
            <div>
                <h1>ANNUAL READINESS PLAN (ARP)</h1>
                <p>Strategic Readiness Planning Object</p>
            </div>
            <div class="xar-header-actions">
                <button type="button" class="xar-btn xar-btn-outline-white" id="xar-save-draft">Save Draft</button>
                <button type="button" class="xar-btn xar-btn-accent" id="xar-next-step">Next Step &rarr;</button>
            </div>
        </div>
    </div>

    <div class="xar-steps">
        <div class="xar-steps-inner" id="xar-steps-inner"></div>
    </div>

    <div class="xar-body">
        <div class="xar-main" id="xar-main"></div>

        <aside class="xar-sidebar">
            <div class="xar-card">
                <h4>ARP Information</h4>
                <dl class="xar-dl">
                    <dt>Organization</dt><dd id="xar-si-org"><?php echo esc_html($atts['organization']); ?></dd>
                    <dt>Plan Year</dt><dd id="xar-si-year"><?php echo esc_html($atts['plan_year']); ?></dd>
                    <dt>Status</dt><dd id="xar-si-status"><span class="xar-badge amber"><?php echo esc_html($atts['status']); ?></span></dd>
                    <dt>Version</dt><dd id="xar-si-version"><?php echo esc_html($atts['version']); ?></dd>
                    <dt>Executive Owner</dt><dd id="xar-si-owner"><?php echo esc_html($ownerName); ?></dd>
                    <dt>Last Saved</dt><dd id="xar-si-saved"><?php echo esc_html($atts['last_saved'] !== '' ? $atts['last_saved'] : '—'); ?></dd>
                </dl>
            </div>

            <div id="xar-sidebar-panels"></div>
        </aside>
    </div>

    <div class="xar-footer">
        <button type="button" class="xar-btn xar-btn-outline" id="xar-prev-step" data-action="close">Close</button>
        <span class="xar-muted xar-autosave" id="xar-autosave-status">
            <span class="xar-autosave-check" aria-hidden="true">&#10003;</span>
            Draft autosaved &mdash;
        </span>
        <div class="xar-row">
            <button type="button" class="xar-btn xar-btn-outline" id="xar-save-draft-2">Save Draft</button>
            <button type="button" class="xar-btn xar-btn-accent" id="xar-next-step-2">Next Step &rarr;</button>
        </div>
    </div>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
window.XFARP_WIZARD = <?php echo wp_json_encode($wizardConfig); ?>;
<?php echo $panelsJs . "\n\n" . $readinessJs . "\n\n" . $strategicJs . "\n\n" . $learningJs . "\n\n" . $aiReviewJs . "\n\n" . $publishJs . "\n\n" . $coreJs; ?>
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_arp_wizard', 'xfusion_arp_wizard_shortcode');
