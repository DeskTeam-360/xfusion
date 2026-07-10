<?php
/**
 * XFusion — 1-on-1 Alignment Capture™ Interactive Tool (UI shell)
 *
 * Usage: [fusion_one_on_one_wizard]
 *
 * Visual-only prototype of the 6-step wizard described in the FUSION 1-on-1
 * Framework docs (see app/Http/Plugin/1. 1-1/*.png for the reference mockups).
 * All data shown is static/dummy — no backend calls, no persistence. Once the
 * layout is approved this gets wired to real endpoints step by step.
 *
 * Code is split per file for maintainability:
 *   styles.php           — all CSS
 *   core.php             — step navigation / sidebar / render dispatch (JS)
 *   steps/step-N-*.php   — one file per wizard step, each returning its
 *                          panel-builder JS as a `key: function () {...}` entry
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/styles.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/steps/step-1-evidence.php';
require_once __DIR__ . '/steps/step-2-brief.php';
require_once __DIR__ . '/steps/step-3-preparation.php';
require_once __DIR__ . '/steps/step-4-conversation.php';
require_once __DIR__ . '/steps/step-5-commitments.php';
require_once __DIR__ . '/steps/step-6-synthesis.php';

function xfusion_one_on_one_wizard_shortcode(): string
{
    $panelFns = [
        xfoo_wizard_step_evidence_js(),
        xfoo_wizard_step_brief_js(),
        xfoo_wizard_step_preparation_js(),
        xfoo_wizard_step_conversation_js(),
        xfoo_wizard_step_commitments_js(),
        xfoo_wizard_step_synthesis_js(),
    ];

    $panelsJs = 'var PANELS = {' . "\n" . implode(",\n\n", $panelFns) . "\n" . '};';
    $coreJs   = xfoo_wizard_core_js();
    $css      = xfoo_wizard_styles_css();

    ob_start();
    ?>
<div id="xfoo-wiz">

    <!-- Header -->
    <div class="xfw-header">
        <div class="xfw-header-inner">
            <div>
                <h1>1-ON-1 ALIGNMENT CAPTURE&trade; INTERACTIVE TOOL</h1>
                <p>Continuous Alignment Process</p>
            </div>
            <div class="xfw-header-actions">
                <button class="xfw-btn xfw-btn-outline-white" id="xfw-save-draft">Save Draft</button>
                <button class="xfw-btn xfw-btn-accent" id="xfw-next-step">Next Step &rarr;</button>
            </div>
        </div>
    </div>

    <!-- Step indicator -->
    <div class="xfw-steps">
        <div class="xfw-steps-inner" id="xfw-steps-inner">
            <!-- filled by JS -->
        </div>
    </div>

    <!-- Body -->
    <div class="xfw-body">
        <div class="xfw-main" id="xfw-main">
            <!-- filled by JS per step -->
        </div>

        <aside class="xfw-sidebar">
            <div class="xfw-card">
                <h4>Meeting Information</h4>
                <dl class="xfw-dl">
                    <dt>Employee</dt><dd>Michael Wilson</dd>
                    <dt>Leader</dt><dd>James Scott</dd>
                    <dt>Group</dt><dd>Operations</dd>
                    <dt>Meeting Date</dt><dd>May 14, 2025</dd>
                    <dt>Meeting Time</dt><dd>9:00 AM</dd>
                    <dt>Recurrence</dt><dd>Bi-Weekly</dd>
                    <dt>Status</dt><dd><span class="xfw-badge amber">Draft</span></dd>
                </dl>
            </div>

            <div class="xfw-card">
                <h4>About This Step</h4>
                <p class="xfw-muted" id="xfw-about-step">This step ensures both participants enter the conversation with the most relevant and comprehensive context available.</p>
            </div>

            <div class="xfw-card">
                <h4>Progress</h4>
                <div class="xfw-row" style="justify-content:space-between">
                    <span class="xfw-muted" id="xfw-progress-label">Step 1 of 6</span>
                    <span class="xfw-muted" id="xfw-progress-pct">17%</span>
                </div>
                <div class="xfw-progress-track"><div class="xfw-progress-fill" id="xfw-progress-fill" style="width:17%"></div></div>
                <p class="xfw-muted" style="margin-top:.6rem">Estimated Completion<br><strong>25 &ndash; 40 minutes</strong></p>
            </div>

            <div class="xfw-card">
                <h4>Have a question?</h4>
                <p class="xfw-muted">Learn more about how this step works in 1-on-1 Alignment Capture&trade;.</p>
                <a href="#" class="xfw-link">View Help Article &rarr;</a>
            </div>
        </aside>
    </div>

    <!-- Footer nav -->
    <div class="xfw-footer">
        <button class="xfw-btn xfw-btn-outline" id="xfw-prev-step">&larr; Previous Step</button>
        <span class="xfw-muted xfw-autosave">&#10003; Draft autosaved 10:32 AM</span>
        <div class="xfw-row">
            <button class="xfw-btn xfw-btn-outline" id="xfw-save-draft-2">Save Draft</button>
            <button class="xfw-btn xfw-btn-accent" id="xfw-next-step-2">Next Step &rarr;</button>
        </div>
    </div>
</div>

<style><?php echo $css; ?></style>

<script>
(function () {
<?php echo $panelsJs . "\n\n" . $coreJs; ?>
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_one_on_one_wizard', 'xfusion_one_on_one_wizard_shortcode');
