<?php
/**
 * XFusion — 1-on-1 Alignment Capture™ Interactive Tool
 *
 * Usage: [fusion_one_on_one_wizard]
 *        [fusion_one_on_one_wizard conversation_id="123"]
 *
 * Step 0 meeting picker → 6-step wizard. Save/load via GF (steps 3–4) and
 * Laravel API (step 5 commitments).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/styles.php';
require_once __DIR__ . '/preparation-gf-mapping.php';
require_once __DIR__ . '/conversation-gf-mapping.php';
require_once __DIR__ . '/gf-entry-service.php';
require_once __DIR__ . '/commitments-fusion-service.php';
require_once __DIR__ . '/step-1-evidence-service.php';
require_once __DIR__ . '/brief-wizard-service.php';
require_once __DIR__ . '/synthesis-wizard-service.php';
require_once __DIR__ . '/save-draft.php';
require_once __DIR__ . '/load-draft.php';
require_once __DIR__ . '/meeting-picker.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/steps/step-1-evidence.php';
require_once __DIR__ . '/steps/step-2-brief.php';
require_once __DIR__ . '/steps/step-3-preparation.php';
require_once __DIR__ . '/steps/step-4-conversation.php';
require_once __DIR__ . '/steps/step-5-commitments.php';
require_once __DIR__ . '/steps/step-6-synthesis.php';

function xfusion_one_on_one_wizard_shortcode($atts = []): string
{
    if (! is_user_logged_in()) {
        return '<p class="xfw-muted">' . esc_html__('Please log in to use the 1-on-1 Alignment Capture™ wizard.', 'xfusion') . '</p>';
    }

    $atts = shortcode_atts([
        'conversation_id' => '0',
        'user_role'       => '',
    ], $atts, 'fusion_one_on_one_wizard');

    $conversationId = absint($atts['conversation_id']);
    if ($conversationId < 1 && isset($_GET['conversation_id'])) {
        $conversationId = absint($_GET['conversation_id']);
    }
    $userRole = sanitize_key($atts['user_role']);

    $panelFns = [
        xfoo_wizard_step_evidence_js(),
        xfoo_wizard_step_brief_js(),
        xfoo_wizard_step_preparation_js(),
        xfoo_wizard_step_conversation_js(),
        xfoo_wizard_step_commitments_js(),
        xfoo_wizard_step_synthesis_js(),
    ];

    $panelsJs   = 'var PANELS = {' . "\n" . implode(",\n\n", $panelFns) . "\n" . '};';
    $coreJs     = xfoo_wizard_core_js();
    $pickerJs   = xfoo_wizard_meeting_picker_js();
    $commitJs   = xfoo_wizard_commitments_js();
    $evidenceJs = xfoo_wizard_evidence_js();
    $briefJs    = xfoo_wizard_brief_js();
    $synthesisJs = xfoo_wizard_synthesis_js();
    $saveJs     = xfoo_wizard_save_draft_js();
    $loadJs     = xfoo_wizard_load_draft_js();
    $css        = xfoo_wizard_styles_css();

    $wizardConfig = [
        'ajaxUrl'                => admin_url('admin-ajax.php'),
        'nonce'                  => wp_create_nonce('xfoo_wizard_save_draft'),
        'ooNonce'                => wp_create_nonce('xfusion_one_on_one'),
        'userId'                 => get_current_user_id(),
        'conversationId'         => $conversationId,
        'pairId'                 => 0,
        'userRole'               => $userRole,
        'prepConfigured'         => xfoo_preparation_gf_is_configured(),
        'conversationConfigured' => xfoo_conversation_gf_is_configured(),
    ];

    $workspaceHidden = $conversationId < 1 ? ' xfw-hidden' : '';

    ob_start();
    ?>
<div id="xfoo-wiz" data-conversation-id="<?php echo esc_attr((string) $conversationId); ?>"<?php echo $userRole !== '' ? ' data-user-role="' . esc_attr($userRole) . '"' : ''; ?>>

    <div class="xfw-header">
        <div class="xfw-header-inner">
            <div>
                <h1>1-ON-1 ALIGNMENT CAPTURE&trade; INTERACTIVE TOOL</h1>
                <p>Continuous Alignment Process</p>
            </div>
            <div class="xfw-header-actions xfw-wizard-only<?php echo esc_attr($workspaceHidden); ?>">
                <button class="xfw-btn xfw-btn-outline-white" id="xfw-save-draft">Save Draft</button>
                <button class="xfw-btn xfw-btn-accent" id="xfw-next-step">Next Step &rarr;</button>
            </div>
        </div>
    </div>

    <div id="xfw-meeting-gate" class="xfw-meeting-gate<?php echo $conversationId > 0 ? ' xfw-hidden' : ''; ?>">
        <div class="xfw-card xfw-gate-card">
            <h2 class="xfw-section-title" style="margin-top:0">Select your 1-on-1 meeting</h2>
            <p class="xfw-section-desc">Open an existing meeting or pick a company group to schedule a new one. You may be a leader in one group and a member in another.</p>
            <div class="xfw-gate-columns">
                <div class="xfw-gate-col xfw-gate-col-schedule">
                    <h3 class="xfw-gate-col-title">Schedule a new meeting</h3>
                    <p class="xfw-muted xfw-gate-col-desc">Select the company group context, then pick a team member if you are the leader.</p>
                    <div id="xfw-gate-pairs"></div>
                    <div id="xfw-gate-conversations" class="xfw-hidden"></div>
                </div>
                <div class="xfw-gate-col xfw-gate-col-meetings">
                    <h3 class="xfw-gate-col-title">Your meetings</h3>
                    <p class="xfw-muted xfw-gate-col-desc">All scheduled and past meetings across your company groups.</p>
                    <div id="xfw-gate-all-meetings"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="xfw-wizard-workspace" class="<?php echo $conversationId > 0 ? '' : 'xfw-hidden'; ?>">

        <div class="xfw-steps">
            <div class="xfw-steps-inner" id="xfw-steps-inner"></div>
        </div>

        <div class="xfw-body">
            <div class="xfw-main" id="xfw-main"></div>

            <aside class="xfw-sidebar">
                <div class="xfw-card">
                    <div class="xfw-row" style="justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                        <h4 style="margin:0">Meeting Information</h4>
                        <a href="#" class="xfw-link" id="xfw-change-meeting" style="font-size:.8rem">Change</a>
                    </div>
                    <dl class="xfw-dl">
                        <dt>Employee</dt><dd id="xfw-si-employee">&mdash;</dd>
                        <dt>Leader</dt><dd id="xfw-si-leader">&mdash;</dd>
                        <dt>Your role</dt><dd id="xfw-si-role">&mdash;</dd>
                        <dt>Meeting Date</dt><dd id="xfw-si-date">&mdash;</dd>
                        <dt>Meeting Time</dt><dd id="xfw-si-time">&mdash;</dd>
                        <dt>Status</dt><dd id="xfw-si-status"><span class="xfw-badge amber">—</span></dd>
                        <dt id="xfw-si-link-row" style="display:none">Meeting Link</dt><dd id="xfw-si-link"></dd>
                    </dl>
                </div>

                <div id="xfw-sidebar-panels"></div>
            </aside>
        </div>

        <div class="xfw-footer">
            <button class="xfw-btn xfw-btn-outline" id="xfw-prev-step">&larr; Previous Step</button>
            <span class="xfw-muted xfw-autosave">Select a meeting to begin</span>
            <div class="xfw-row">
                <button class="xfw-btn xfw-btn-outline" id="xfw-save-draft-2">Save Draft</button>
                <button class="xfw-btn xfw-btn-accent" id="xfw-next-step-2">Next Step &rarr;</button>
            </div>
        </div>
    </div>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
window.XFW_WIZARD = <?php echo wp_json_encode($wizardConfig); ?>;
<?php echo $panelsJs . "\n\n" . $coreJs . "\n\n" . $pickerJs . "\n\n" . $commitJs . "\n\n" . $evidenceJs . "\n\n" . $briefJs . "\n\n" . $synthesisJs . "\n\n" . $loadJs . "\n\n" . $saveJs; ?>
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_one_on_one_wizard', 'xfusion_one_on_one_wizard_shortcode');
