<?php
/**
 * XFusion — Quarterly Business Review™ (QBR) Interactive Tool
 *
 * Usage: [fusion_qbr_wizard]
 *
 * 7-step wizard shell. All steps persist via Laravel API (/api/v1/qbrs/*).
 * Structurally identical to the ARP wizard (arp-wizard-shortcode.php) —
 * picker gate keyed on ?qbr_id=, leader-edits/member-views access model,
 * one QBR per (company group, quarter, year).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/styles.php';
require_once __DIR__ . '/qbr-picker.php';
require_once __DIR__ . '/qbr-evidence-service.php';
require_once __DIR__ . '/qbr-assessment-service.php';
require_once __DIR__ . '/qbr-collaboration-service.php';
require_once __DIR__ . '/qbr-commitments-service.php';
require_once __DIR__ . '/qbr-synthesis-service.php';
require_once __DIR__ . '/qbr-publish-service.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/steps/step-1-evidence.php';
require_once __DIR__ . '/steps/step-2-evidence-review.php';
require_once __DIR__ . '/steps/step-3-assessment.php';
require_once __DIR__ . '/steps/step-4-collaboration.php';
require_once __DIR__ . '/steps/step-5-commitments.php';
require_once __DIR__ . '/steps/step-6-synthesis.php';
require_once __DIR__ . '/steps/step-7-publish.php';

function xfusion_qbr_wizard_shortcode($atts = []): string
{
    if (! is_user_logged_in()) {
        return '<p class="xqbr-muted">' . esc_html__('Please log in to use the Quarterly Business Review™ wizard.', 'xfusion') . '</p>';
    }

    $atts = shortcode_atts([
        'organization' => '',
        'qbr_id'       => '0',
    ], $atts, 'fusion_qbr_wizard');

    $qbrId = (int) $atts['qbr_id'];
    if ($qbrId < 1 && isset($_GET['qbr_id'])) {
        $qbrId = absint($_GET['qbr_id']);
    }

    if ($qbrId < 1) {
        return xfqbr_render_picker_gate();
    }

    $qbrContext = xfqbr_picker_api_request('GET', "/{$qbrId}", ['user_id' => get_current_user_id()]);
    $qbrData    = [];
    $canEdit    = false;
    if ($qbrContext['ok'] && is_array($qbrContext['body']['data'] ?? null)) {
        $qbrData = $qbrContext['body']['data'];
        $canEdit = (bool) ($qbrData['can_edit'] ?? false);
    }

    $organization = $qbrData['group_name'] ?? $qbrData['company_name'] ?? $atts['organization'];
    $quarter      = (int) ($qbrData['quarter'] ?? 0);
    $year         = (int) ($qbrData['year'] ?? wp_date('Y'));
    $quarterMonths = [1 => 'Jan – Mar', 2 => 'Apr – Jun', 3 => 'Jul – Sep', 4 => 'Oct – Dec'];
    $quarterLabel  = $quarter >= 1 && $quarter <= 4 ? 'Q' . $quarter . ' ' . $year . ' (' . $quarterMonths[$quarter] . ')' : '—';
    $status        = ucfirst((string) ($qbrData['status'] ?? 'draft'));
    $facilitator   = $qbrData['facilitator_name'] ?? '';
    if ($facilitator === '') {
        $currentUser = wp_get_current_user();
        $facilitator = $currentUser->display_name !== '' ? $currentUser->display_name : $currentUser->user_login;
    }
    $lastSaved = ! empty($qbrData['updated_at']) ? wp_date('F j, Y g:i A', strtotime($qbrData['updated_at'])) : '';

    $panelFns = [
        xfqbr_wizard_step_evidence_js(),
        xfqbr_wizard_step_evidence_review_js(),
        xfqbr_wizard_step_assessment_js(),
        xfqbr_wizard_step_collaboration_js(),
        xfqbr_wizard_step_commitments_js(),
        xfqbr_wizard_step_synthesis_js(),
        xfqbr_wizard_step_publish_js(),
    ];

    $panelsJs = 'var PANELS = {' . "\n" . implode(",\n\n", $panelFns) . "\n" . '};';
    $coreJs   = xfqbr_wizard_core_js();
    $css      = xfqbr_wizard_styles_css();

    $evidenceInitJs      = xfqbr_wizard_evidence_init_js();
    $evidenceReviewInitJs = xfqbr_wizard_evidence_review_init_js();
    $assessmentInitJs    = xfqbr_wizard_assessment_init_js();
    $collaborationInitJs = xfqbr_wizard_collaboration_init_js();
    $commitmentsInitJs   = xfqbr_wizard_commitments_init_js();
    $synthesisInitJs     = xfqbr_wizard_synthesis_init_js();
    $publishInitJs       = xfqbr_wizard_publish_init_js();

    $evidenceSvcJs     = xfqbr_wizard_evidence_service_js();
    $assessmentSvcJs   = xfqbr_wizard_assessment_service_js();
    $collaborationSvcJs = xfqbr_wizard_collaboration_service_js();
    $commitmentsSvcJs  = xfqbr_wizard_commitments_service_js();
    $synthesisSvcJs    = xfqbr_wizard_synthesis_service_js();
    $publishSvcJs      = xfqbr_wizard_publish_service_js();

    $wizardConfig = [
        'ajaxUrl'         => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('xfqbr_wizard_save_draft'),
        'userId'          => get_current_user_id(),
        'qbrId'           => $qbrId,
        'canEdit'         => $canEdit,
        'discussionNotes' => $qbrData['discussion_notes'] ?? '',
        'stepProgress'    => is_array($qbrData['step_progress'] ?? null) ? $qbrData['step_progress'] : new stdClass(),
    ];

    // Generic header/footer "Save Draft" — dispatches to whichever step has an
    // explicit save action (KPIs, discussion notes + decisions, commitments).
    // Steps 1/3/6 save via their own dedicated Generate/Save buttons instead.
    $saveDraftDispatchJs = <<<'JS'
window.xqbrSaveDraft = function () {
    var key = (STEPS[current] || {}).key;
    if (key === 'evidence_review') {
        var kpiBtn = document.getElementById('xqbr-kpi-save');
        if (kpiBtn) { kpiBtn.click(); return; }
    }
    if (key === 'assessment') {
        var contextEl = document.getElementById('xqbr-leadership-context');
        var ratingEl = document.querySelector('input[name="xqbr-agreement"]:checked');
        var status = document.getElementById('xqbr-autosave-status');
        if (!contextEl) { return; }
        if (status) status.innerHTML = '<span class="xqbr-autosave-check" aria-hidden="true">&#10003;</span> Saving…';
        window.xqbrSaveLeadershipContext(contextEl.value, ratingEl ? ratingEl.value : '').then(function (res) {
            if (!status) return;
            status.innerHTML = (res && res.success)
                ? '<span class="xqbr-autosave-check" aria-hidden="true">&#10003;</span> Draft saved.'
                : '<span style="color:#dc2626">&#9888; Save failed.</span>';
        });
        return;
    }
    if (key === 'collaboration') {
        var notesBtn = document.getElementById('xqbr-save-notes-btn');
        var decisionsBtn = document.getElementById('xqbr-save-decisions-btn');
        if (notesBtn) notesBtn.click();
        if (decisionsBtn) decisionsBtn.click();
        return;
    }
    if (key === 'commitments') {
        var commitBtn = document.getElementById('xqbr-save-commitments-btn');
        if (commitBtn) { commitBtn.click(); return; }
    }
    var status = document.getElementById('xqbr-autosave-status');
    if (status) {
        status.innerHTML = '<span class="xqbr-autosave-check" aria-hidden="true">&#10003;</span> This step saves automatically via its own action button.';
    }
};
['#xqbr-save-draft', '#xqbr-save-draft-2'].forEach(function (sel) {
    var btn = document.querySelector(sel);
    if (btn) btn.addEventListener('click', window.xqbrSaveDraft);
});
JS;

    ob_start();
    ?>
<div id="xfqbr-wiz"<?php echo $canEdit ? '' : ' data-view-only="1"'; ?>>

    <?php if (! $canEdit) : ?>
    <div class="xqbr-banner warn" style="margin:1rem 1.75rem 0">
        &#128065; <span><b>View only.</b> You are viewing this Quarterly Business Review™ as a member. Only leaders of this group can edit or publish it.</span>
    </div>
    <?php endif; ?>

    <div class="xqbr-header">
        <div class="xqbr-header-inner">
            <div>
                <h1>QUARTERLY BUSINESS REVIEW&trade; (QBR)</h1>
                <p>Organizational Readiness Review</p>
            </div>
            <div class="xqbr-header-actions">
                <?php if ($canEdit) : ?>
                <button type="button" class="xqbr-btn xqbr-btn-outline-white" id="xqbr-save-draft">Save Draft</button>
                <?php endif; ?>
                <button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-next-step">Next Step &rarr;</button>
            </div>
        </div>
    </div>

    <div class="xqbr-steps">
        <div class="xqbr-steps-inner" id="xqbr-steps-inner"></div>
    </div>

    <div class="xqbr-body">
        <div class="xqbr-main" id="xqbr-main"></div>

        <aside class="xqbr-sidebar">
            <div class="xqbr-card">
                <div class="xqbr-row" style="justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                    <h4 style="margin:0">QBR Information</h4>
                    <a href="javascript:void(0)" class="xqbr-link" id="xqbr-change-qbr" style="font-size:14px">Change plan</a>
                </div>
                <dl class="xqbr-dl">
                    <dt>Organization</dt><dd id="xqbr-si-org"><?php echo esc_html($organization); ?></dd>
                    <dt>Quarter</dt><dd id="xqbr-si-quarter"><?php echo esc_html($quarterLabel); ?></dd>
                    <dt>Facilitator</dt><dd id="xqbr-si-facilitator"><?php echo esc_html($facilitator); ?></dd>
                    <dt>Status</dt><dd id="xqbr-si-status"><span class="xqbr-badge amber"><?php echo esc_html($status); ?></span></dd>
                    <dt>Last Saved</dt><dd id="xqbr-si-saved"><?php echo esc_html($lastSaved !== '' ? $lastSaved : '—'); ?></dd>
                </dl>
            </div>

            <div id="xqbr-sidebar-panels"></div>
        </aside>
    </div>

    <div class="xqbr-footer">
        <button type="button" class="xqbr-btn xqbr-btn-outline" id="xqbr-prev-step" data-action="close">Close</button>
        <span class="xqbr-muted xqbr-autosave" id="xqbr-autosave-status">
            <span class="xqbr-autosave-check" aria-hidden="true">&#10003;</span>
            Draft autosaved &mdash;
        </span>
        <div class="xqbr-row">
            <?php if ($canEdit) : ?>
            <button type="button" class="xqbr-btn xqbr-btn-outline" id="xqbr-save-draft-2">Save Draft</button>
            <?php endif; ?>
            <button type="button" class="xqbr-btn xqbr-btn-accent" id="xqbr-next-step-2">Next Step &rarr;</button>
        </div>
    </div>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
window.XFQBR_WIZARD = <?php echo wp_json_encode($wizardConfig); ?>;
<?php
echo $panelsJs . "\n\n" . $coreJs . "\n\n"
    . $evidenceInitJs . "\n\n" . $evidenceReviewInitJs . "\n\n" . $assessmentInitJs . "\n\n"
    . $collaborationInitJs . "\n\n" . $commitmentsInitJs . "\n\n" . $synthesisInitJs . "\n\n" . $publishInitJs . "\n\n"
    . $evidenceSvcJs . "\n\n" . $assessmentSvcJs . "\n\n" . $collaborationSvcJs . "\n\n"
    . $commitmentsSvcJs . "\n\n" . $synthesisSvcJs . "\n\n" . $publishSvcJs . "\n\n"
    . $saveDraftDispatchJs;
?>
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_qbr_wizard', 'xfusion_qbr_wizard_shortcode');
