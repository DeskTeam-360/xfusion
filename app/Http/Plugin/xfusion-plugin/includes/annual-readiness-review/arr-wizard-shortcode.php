<?php
/**
 * XFusion — Annual Readiness Review™ (ARR) Interactive Tool
 *
 * Usage: [fusion_arr_wizard]
 *
 * 7-step wizard shell. Picker gate + Review Summary sidebar load real data
 * via Laravel (App\Http\Controllers\Api\ArrController); the 7 step panels
 * remain UI-only dummy content pending a follow-up pass, same as
 * ARP/QBR/IRR's incremental build-out.
 *
 * ARR sits at the top of the FUSION cycle: organization-wide (one per
 * company/year), synthesizing ARP + QBR + 1-on-1 + 360/IRR evidence and
 * feeding the next Annual Readiness Plan™.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/styles.php';
require_once __DIR__ . '/arr-picker.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/steps/step-1-evidence.php';
require_once __DIR__ . '/steps/step-2-dashboard.php';
require_once __DIR__ . '/steps/step-3-assessment.php';
require_once __DIR__ . '/steps/step-4-reflection.php';
require_once __DIR__ . '/steps/step-5-recommendations.php';
require_once __DIR__ . '/steps/step-6-synthesis.php';
require_once __DIR__ . '/steps/step-7-publish.php';

function xfusion_arr_wizard_shortcode($atts = []): string
{
    if (! is_user_logged_in()) {
        return '<p class="xarr-muted">' . esc_html__('Please log in to use the Annual Readiness Review™ wizard.', 'xfusion') . '</p>';
    }

    $atts = shortcode_atts([
        'arr_id' => '0',
    ], $atts, 'fusion_arr_wizard');

    $arrIdRaw = sanitize_text_field($atts['arr_id']);
    if ($arrIdRaw === '0' && isset($_GET['arr_id'])) {
        $arrIdRaw = sanitize_text_field(wp_unslash($_GET['arr_id']));
    }

    $arrId = absint($arrIdRaw);
    if ($arrId < 1) {
        return xfarr_render_picker_gate();
    }

    $arrContext = xfarr_picker_api_request('GET', "/{$arrId}", ['user_id' => get_current_user_id()]);
    if (! $arrContext['ok']) {
        $message = is_array($arrContext['body']) ? ($arrContext['body']['message'] ?? 'Unable to load this ARR.') : 'Unable to load this ARR.';

        return '<p class="xarr-muted">' . esc_html($message) . ' <a href="' . esc_url(remove_query_arg('arr_id')) . '">' . esc_html__('Back to ARR picker', 'xfusion') . '</a></p>';
    }

    $arrData = is_array($arrContext['body']['data'] ?? null) ? $arrContext['body']['data'] : [];
    $canEdit = (bool) ($arrData['can_edit'] ?? false);

    $orgName   = $arrData['company_name'] ?? '—';
    $year      = (int) ($arrData['year'] ?? wp_date('Y'));
    $ownerName = $arrData['executive_owner_name'] ?? '—';
    $status    = (string) ($arrData['status'] ?? 'draft');
    $statusLabels = [
        'draft' => 'Draft',
        'in_progress' => 'In Progress',
        'ready_to_publish' => 'Ready to Publish',
        'published' => 'Published',
        'archived' => 'Archived',
    ];
    $statusLabel = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    $statusBadgeClass = $status === 'published' ? 'green' : ($status === 'archived' ? 'gray' : 'amber');

    // Roster across the ARR's company — populates the Executive Owner
    // dropdown on Step 5 (Strategic Renewal Recommendations).
    $groupMembers = [];
    $membersContext = xfarr_picker_api_request('GET', "/{$arrId}/group-members", ['user_id' => get_current_user_id()]);
    if ($membersContext['ok'] && is_array($membersContext['body']['data'] ?? null)) {
        $groupMembers = $membersContext['body']['data'];
    }

    $panelFns = [
        xfarr_wizard_step_evidence_js(),
        xfarr_wizard_step_dashboard_js(),
        xfarr_wizard_step_assessment_js(),
        xfarr_wizard_step_reflection_js(),
        xfarr_wizard_step_recommendations_js(),
        xfarr_wizard_step_synthesis_js(),
        xfarr_wizard_step_publish_js(),
    ];

    $panelsJs = 'var PANELS = {' . "\n" . implode(",\n\n", $panelFns) . "\n" . '};';
    $coreJs   = xfarr_wizard_core_js();
    $css      = xfarr_wizard_styles_css();

    $evidenceInitJs       = xfarr_wizard_evidence_init_js();
    $dashboardInitJs      = xfarr_wizard_dashboard_init_js();
    $assessmentInitJs     = xfarr_wizard_assessment_init_js();
    $reflectionInitJs     = xfarr_wizard_reflection_init_js();
    $recommendationsInitJs = xfarr_wizard_recommendations_init_js();
    $synthesisInitJs      = xfarr_wizard_synthesis_init_js();
    $publishInitJs        = xfarr_wizard_publish_init_js();

    $wizardConfig = [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'userId'       => get_current_user_id(),
        'arrId'        => $arrId,
        'canEdit'      => $canEdit,
        'groupMembers' => $groupMembers,
        'stepProgress' => is_array($arrData['step_progress'] ?? null) ? $arrData['step_progress'] : new stdClass(),
    ];

    // UI-only prototype: "Save Draft" is a local no-op for now — it just
    // confirms visually. No Laravel calls are made from any step yet.
    $saveDraftDispatchJs = <<<'JS'
window.xarrSaveDraft = function () {
    var status = document.getElementById('xarr-autosave-status');
    if (status) {
        var now = new Date();
        var time = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        status.innerHTML = '<span class="xarr-autosave-check" aria-hidden="true">&#10003;</span> Draft autosaved ' + time + ' (UI shell — not yet connected).';
    }
};
['#xarr-save-draft', '#xarr-save-draft-2'].forEach(function (sel) {
    var btn = document.querySelector(sel);
    if (btn) btn.addEventListener('click', window.xarrSaveDraft);
});
JS;

    ob_start();
    ?>
<div id="xfarr-wiz"<?php echo $canEdit ? '' : ' data-view-only="1"'; ?>>

    <?php if (! $canEdit) : ?>
    <div class="xarr-banner warn" style="margin:1rem 1.75rem 0">
        &#128065; <span><b>View only.</b> You are viewing this Annual Readiness Review™ as a member. Only leaders of a group in this organization can edit or publish it.</span>
    </div>
    <?php endif; ?>

    <div class="xarr-header">
        <div class="xarr-header-inner">
            <div>
                <h1>ANNUAL READINESS REVIEW&trade; (ARR)</h1>
                <p>Organizational Learning &amp; Strategic Renewal Engine</p>
            </div>
            <div class="xarr-header-actions">
                <?php if ($canEdit) : ?>
                <button type="button" class="xarr-btn xarr-btn-outline-white" id="xarr-save-draft">Save Draft</button>
                <?php endif; ?>
                <button type="button" class="xarr-btn xarr-btn-accent" id="xarr-next-step">Next Step &rarr;</button>
            </div>
        </div>
    </div>

    <div class="xarr-steps">
        <div class="xarr-steps-inner" id="xarr-steps-inner"></div>
    </div>

    <div class="xarr-body">
        <div class="xarr-main" id="xarr-main"></div>

        <aside class="xarr-sidebar">
            <div class="xarr-card">
                <div class="xarr-row" style="justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                    <h4 style="margin:0">Review Summary</h4>
                    <a href="javascript:void(0)" class="xarr-link" id="xarr-change-arr" style="font-size:14px">Change review</a>
                </div>
                <dl class="xarr-dl">
                    <dt>Organization</dt><dd id="xarr-si-org"><?php echo esc_html($orgName); ?></dd>
                    <dt>Review Year</dt><dd id="xarr-si-year"><?php echo esc_html((string) $year); ?></dd>
                    <dt>Executive Owner</dt><dd id="xarr-si-owner"><?php echo esc_html($ownerName); ?></dd>
                    <dt>Status</dt><dd id="xarr-si-status"><span class="xarr-badge <?php echo esc_attr($statusBadgeClass); ?>"><?php echo esc_html($statusLabel); ?></span></dd>
                </dl>
            </div>

            <div id="xarr-sidebar-panels"></div>
        </aside>
    </div>

    <div class="xarr-footer">
        <button type="button" class="xarr-btn xarr-btn-outline" id="xarr-prev-step" data-action="close">Close</button>
        <span class="xarr-muted xarr-autosave" id="xarr-autosave-status">
            <span class="xarr-autosave-check" aria-hidden="true">&#10003;</span>
            Draft autosaved &mdash;
        </span>
        <div class="xarr-row">
            <?php if ($canEdit) : ?>
            <button type="button" class="xarr-btn xarr-btn-outline" id="xarr-save-draft-2">Save Draft</button>
            <?php endif; ?>
            <button type="button" class="xarr-btn xarr-btn-accent" id="xarr-next-step-2">Next Step &rarr;</button>
        </div>
    </div>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
window.XFARR_WIZARD = <?php echo wp_json_encode($wizardConfig); ?>;
<?php
echo $panelsJs . "\n\n" . $coreJs . "\n\n"
    . $evidenceInitJs . "\n\n" . $dashboardInitJs . "\n\n" . $assessmentInitJs . "\n\n"
    . $reflectionInitJs . "\n\n" . $recommendationsInitJs . "\n\n" . $synthesisInitJs . "\n\n" . $publishInitJs . "\n\n"
    . $saveDraftDispatchJs;
?>
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_arr_wizard', 'xfusion_arr_wizard_shortcode');
