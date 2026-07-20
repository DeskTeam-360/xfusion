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
        '<div class="xfw-card" style="margin-bottom:1rem">' +
        '<p class="xfw-muted" style="margin:0 0 .75rem">Generate the post-meeting synthesis from preparation, conversation notes, and commitments from earlier steps.</p>' +
        '<button type="button" class="xfw-btn xfw-btn-accent" id="xfw-generate-synthesis">Generate AI Meeting Synthesis\u2122</button>' +
        '<p class="xfw-muted" id="xfw-generate-synthesis-status" style="margin-top:.5rem"></p>' +
        '</div>' +
        '<div id="xfw-synthesis-content"></div>';
}
JS;
}
