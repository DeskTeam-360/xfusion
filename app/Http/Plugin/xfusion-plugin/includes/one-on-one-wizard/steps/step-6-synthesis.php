<?php
/**
 * Step 6 — AI Meeting Synthesis™ (dynamic from generated synthesis).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_synthesis_js(): string
{
    return <<<'JS'
synthesis: function () {
    return '<h2 class="xfw-section-title">Step 6. AI Meeting Synthesis™</h2>' +
        '<p class="xfw-section-desc">FUSION analyzes the conversation, preparation, commitments, and historical trends to create a comprehensive meeting record and actionable insights for both participants.</p>' +
        '<div class="xfw-banner">ℹ️ <span>This summary is AI-generated and based on the data and discussion from this 1-on-1 conversation. Review and reflect on the insights together.</span></div>' +
        '<div id="xfw-synthesis-content"></div>';
}
JS;
}
