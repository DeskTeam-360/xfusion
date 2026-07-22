<?php
/**
 * XFusion — Individual Readiness Review™ (IRR) Interactive Tool
 *
 * Usage: [fusion_irr_wizard]
 *
 * 7-step wizard shell. UI-only prototype matching the IRR mockups — no
 * Laravel calls anywhere yet (evidence, assessment, conversation notes,
 * commitments, synthesis, and publish are all static/local dummy data).
 * Structurally identical to the QBR wizard shell (qbr-wizard-shortcode.php).
 *
 * Naming note: product-facing name is "Individual Readiness Review™ / IRR"
 * (formerly "360 Review"); underlying tables stay `wp_fusion_360_*`.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/styles.php';
require_once __DIR__ . '/irr-picker.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/steps/step-1-evidence.php';
require_once __DIR__ . '/steps/step-2-evidence-review.php';
require_once __DIR__ . '/steps/step-3-assessment.php';
require_once __DIR__ . '/steps/step-4-conversation.php';
require_once __DIR__ . '/steps/step-5-commitments.php';
require_once __DIR__ . '/steps/step-6-synthesis.php';
require_once __DIR__ . '/steps/step-7-publish.php';

function xfusion_irr_wizard_shortcode($atts = []): string
{
    if (! is_user_logged_in()) {
        return '<p class="xirr-muted">' . esc_html__('Please log in to use the Individual Readiness Review™ wizard.', 'xfusion') . '</p>';
    }

    $atts = shortcode_atts([
        'irr_id' => '0',
    ], $atts, 'fusion_irr_wizard');

    $irrId = sanitize_text_field($atts['irr_id']);
    if ($irrId === '0' && isset($_GET['irr_id'])) {
        $irrId = sanitize_text_field(wp_unslash($_GET['irr_id']));
    }

    if ($irrId === '' || $irrId === '0') {
        return xfirr_render_picker_gate();
    }

    // UI-only prototype: no Laravel lookup — the sidebar shows static sample
    // Review Summary data matching the mockups, regardless of which dummy
    // record was picked (new-YYYY or an id from the picker list).
    $currentUser  = wp_get_current_user();
    $employeeName = $currentUser->display_name !== '' ? $currentUser->display_name : $currentUser->user_login;
    $year         = (int) wp_date('Y');
    if (str_starts_with($irrId, 'new-')) {
        $year = (int) substr($irrId, 4) ?: $year;
    }

    $panelFns = [
        xfirr_wizard_step_evidence_js(),
        xfirr_wizard_step_evidence_review_js(),
        xfirr_wizard_step_assessment_js(),
        xfirr_wizard_step_conversation_js(),
        xfirr_wizard_step_commitments_js(),
        xfirr_wizard_step_synthesis_js(),
        xfirr_wizard_step_publish_js(),
    ];

    $panelsJs = 'var PANELS = {' . "\n" . implode(",\n\n", $panelFns) . "\n" . '};';
    $coreJs   = xfirr_wizard_core_js();
    $css      = xfirr_wizard_styles_css();

    $evidenceInitJs      = xfirr_wizard_evidence_init_js();
    $evidenceReviewInitJs = xfirr_wizard_evidence_review_init_js();
    $assessmentInitJs    = xfirr_wizard_assessment_init_js();
    $conversationInitJs  = xfirr_wizard_conversation_init_js();
    $commitmentsInitJs   = xfirr_wizard_commitments_init_js();
    $synthesisInitJs     = xfirr_wizard_synthesis_init_js();
    $publishInitJs       = xfirr_wizard_publish_init_js();

    $wizardConfig = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'userId'  => get_current_user_id(),
        'irrId'   => $irrId,
        'canEdit' => true,
    ];

    // UI-only prototype: "Save Draft" is a local no-op for now — it just
    // confirms visually. No Laravel calls are made from any step yet.
    $saveDraftDispatchJs = <<<'JS'
window.xirrSaveDraft = function () {
    var status = document.getElementById('xirr-autosave-status');
    if (status) {
        var now = new Date();
        var time = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        status.innerHTML = '<span class="xirr-autosave-check" aria-hidden="true">&#10003;</span> Draft autosaved ' + time + ' (UI shell — not yet connected).';
    }
};
['#xirr-save-draft', '#xirr-save-draft-2'].forEach(function (sel) {
    var btn = document.querySelector(sel);
    if (btn) btn.addEventListener('click', window.xirrSaveDraft);
});
JS;

    ob_start();
    ?>
<div id="xfirr-wiz">

    <div class="xirr-header">
        <div class="xirr-header-inner">
            <div>
                <h1>INDIVIDUAL READINESS REVIEW&trade;</h1>
                <p>Annual Individual Readiness Review</p>
            </div>
            <div class="xirr-header-actions">
                <button type="button" class="xirr-btn xirr-btn-outline-white" id="xirr-save-draft">Save Draft</button>
                <button type="button" class="xirr-btn xirr-btn-accent" id="xirr-next-step">Next Step &rarr;</button>
            </div>
        </div>
    </div>

    <div class="xirr-steps">
        <div class="xirr-steps-inner" id="xirr-steps-inner"></div>
    </div>

    <div class="xirr-body">
        <div class="xirr-main" id="xirr-main"></div>

        <aside class="xirr-sidebar">
            <div class="xirr-card">
                <div class="xirr-row" style="justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                    <h4 style="margin:0">Review Summary</h4>
                    <a href="javascript:void(0)" class="xirr-link" id="xirr-change-irr" style="font-size:14px">Change review</a>
                </div>
                <dl class="xirr-dl">
                    <dt>Employee</dt><dd id="xirr-si-employee"><?php echo esc_html($employeeName); ?></dd>
                    <dt>Role</dt><dd id="xirr-si-role">Operations Manager</dd>
                    <dt>Manager</dt><dd id="xirr-si-manager">James Scott</dd>
                    <dt>Group</dt><dd id="xirr-si-group">Leadership Team</dd>
                    <dt>Organization</dt><dd id="xirr-si-org">Northwind Energy Co-op</dd>
                    <dt>Review Year</dt><dd id="xirr-si-year"><?php echo esc_html((string) $year); ?></dd>
                    <dt>Status</dt><dd id="xirr-si-status"><span class="xirr-badge amber">In Progress</span></dd>
                </dl>
            </div>

            <div id="xirr-sidebar-panels"></div>
        </aside>
    </div>

    <div class="xirr-footer">
        <button type="button" class="xirr-btn xirr-btn-outline" id="xirr-prev-step" data-action="close">Close</button>
        <span class="xirr-muted xirr-autosave" id="xirr-autosave-status">
            <span class="xirr-autosave-check" aria-hidden="true">&#10003;</span>
            Draft autosaved &mdash;
        </span>
        <div class="xirr-row">
            <button type="button" class="xirr-btn xirr-btn-outline" id="xirr-save-draft-2">Save Draft</button>
            <button type="button" class="xirr-btn xirr-btn-accent" id="xirr-next-step-2">Next Step &rarr;</button>
        </div>
    </div>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
window.XFIRR_WIZARD = <?php echo wp_json_encode($wizardConfig); ?>;
<?php
echo $panelsJs . "\n\n" . $coreJs . "\n\n"
    . $evidenceInitJs . "\n\n" . $evidenceReviewInitJs . "\n\n" . $assessmentInitJs . "\n\n"
    . $conversationInitJs . "\n\n" . $commitmentsInitJs . "\n\n" . $synthesisInitJs . "\n\n" . $publishInitJs . "\n\n"
    . $saveDraftDispatchJs;
?>
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_irr_wizard', 'xfusion_irr_wizard_shortcode');
