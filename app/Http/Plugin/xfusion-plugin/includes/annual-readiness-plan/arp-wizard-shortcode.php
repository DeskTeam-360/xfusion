<?php
/**
 * XFusion — Annual Readiness Plan™ (ARP) Interactive Tool
 *
 * Usage: [fusion_arp_wizard]
 *
 * 7-step wizard shell. All steps persist via Laravel API (/api/v1/arps/*).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/styles.php';
require_once __DIR__ . '/arp-picker.php';
require_once __DIR__ . '/arp-save-draft.php';
require_once __DIR__ . '/arp-load-draft.php';
require_once __DIR__ . '/arp-plan-service.php';
require_once __DIR__ . '/arp-readiness-service.php';
require_once __DIR__ . '/arp-strategic-service.php';
require_once __DIR__ . '/arp-ai-review-service.php';
require_once __DIR__ . '/arp-publish-service.php';
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
        'arp_id'           => '0',
        'company_id'       => '0',
        'status'           => 'Draft',
        'version'          => '1.0',
        'executive_owner'  => '',
        'last_saved'       => '',
    ], $atts, 'fusion_arp_wizard');

    $arpId = (int) $atts['arp_id'];
    if ($arpId < 1 && isset($_GET['arp_id'])) {
        $arpId = absint($_GET['arp_id']);
    }

    if ($arpId < 1) {
        return xfarp_render_picker_gate();
    }

    $atts['arp_id'] = (string) $arpId;

    // Fetch real ARP context from Laravel — the "Organization" field shown in
    // the sidebar should be the leader's company GROUP name, not the parent
    // company title (ARP is scoped per-company, but leaders think in terms
    // of the group they run).
    $arpContext = xfarp_picker_api_request('GET', "/{$arpId}", ['user_id' => get_current_user_id()]);
    $arpData    = [];
    $canEdit    = false;
    if ($arpContext['ok'] && is_array($arpContext['body']['data'] ?? null)) {
        $arpData = $arpContext['body']['data'];
        $atts['organization'] = $arpData['group_name'] ?? $arpData['company_name'] ?? $atts['organization'];
        $atts['plan_year']    = (string) ($arpData['year'] ?? $atts['plan_year']);
        $atts['status']       = ucfirst((string) ($arpData['status'] ?? $atts['status']));
        $atts['company_id']   = (string) ($arpData['company_id'] ?? $atts['company_id']);
        $atts['version']      = (string) ($arpData['version'] ?? $atts['version']);
        $canEdit              = (bool) ($arpData['can_edit'] ?? false);

        if (! empty($arpData['updated_at'])) {
            $atts['last_saved'] = wp_date('F j, Y g:i A', strtotime($arpData['updated_at']));
        }
    }

    // Real company-group roster for the Executive Owner dropdowns on Steps 3
    // and 4 — replaces the previous hardcoded name list.
    $groupMembers = [];
    $membersContext = xfarp_picker_api_request('GET', "/{$arpId}/group-members", ['user_id' => get_current_user_id()]);
    if ($membersContext['ok'] && is_array($membersContext['body']['data'] ?? null)) {
        $groupMembers = $membersContext['body']['data'];
    }

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

    $companyId = (int) $atts['company_id'];
    if ($companyId < 1 && function_exists('xfusion_wp_user_linked_company_id')) {
        $companyId = xfusion_wp_user_linked_company_id((int) get_current_user_id());
    }

    $wizardConfig = [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('xfarp_wizard_save_draft'),
        'userId'       => get_current_user_id(),
        'companyId'    => $companyId,
        'planYear'     => (int) $atts['plan_year'],
        'arpId'        => (int) $atts['arp_id'],
        'version'      => $atts['version'],
        'createdAt'    => $arpData['created_at'] ?? null,
        'publishedAt'  => $arpData['published_at'] ?? null,
        'canEdit'      => $canEdit,
        'groupMembers' => $groupMembers,
    ];

    $saveJs      = xfarp_wizard_save_draft_js();
    $loadJs      = xfarp_wizard_load_draft_js();
    $planSvcJs   = xfarp_wizard_plan_service_js();
    $readinessSvcJs = xfarp_wizard_readiness_service_js();
    $strategicSvcJs = xfarp_wizard_strategic_service_js();
    $aiReviewSvcJs  = xfarp_wizard_ai_review_service_js();
    $publishSvcJs   = xfarp_wizard_publish_service_js();

    ob_start();
    ?>
<div id="xfarp-wiz"<?php echo $canEdit ? '' : ' data-view-only="1"'; ?>>

    <?php if (! $canEdit) : ?>
    <div class="xar-banner warn" style="margin:1rem 1.75rem 0">
        &#128065; <span><b>View only.</b> You are viewing this Annual Readiness Plan™ as a member. Only leaders of this organization's group can edit or publish it.</span>
    </div>
    <?php endif; ?>

    <div class="xar-header">
        <div class="xar-header-inner">
            <div>
                <h1>ANNUAL READINESS PLAN (ARP)</h1>
                <p>Strategic Readiness Planning Object</p>
            </div>
            <div class="xar-header-actions">
                <?php if ($canEdit) : ?>
                <button type="button" class="xar-btn xar-btn-outline-white" id="xar-save-draft">Save Draft</button>
                <?php endif; ?>
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
                <div class="xar-row" style="justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                    <h4 style="margin:0">ARP Information</h4>
                    <a href="javascript:void(0)" class="xar-link" id="xar-change-arp" style="font-size:14px">Change plan</a>
                </div>
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
            <?php if ($canEdit) : ?>
            <button type="button" class="xar-btn xar-btn-outline" id="xar-save-draft-2">Save Draft</button>
            <?php endif; ?>
            <button type="button" class="xar-btn xar-btn-accent" id="xar-next-step-2">Next Step &rarr;</button>
        </div>
    </div>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
window.XFARP_WIZARD = <?php echo wp_json_encode($wizardConfig); ?>;
<?php echo $panelsJs . "\n\n" . $readinessJs . "\n\n" . $strategicJs . "\n\n" . $learningJs . "\n\n" . $aiReviewJs . "\n\n" . $publishJs . "\n\n" . $coreJs . "\n\n" . $saveJs . "\n\n" . $loadJs . "\n\n" . $planSvcJs . "\n\n" . $readinessSvcJs . "\n\n" . $strategicSvcJs . "\n\n" . $aiReviewSvcJs . "\n\n" . $publishSvcJs; ?>
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_arp_wizard', 'xfusion_arp_wizard_shortcode');
