<?php
/**
 * Step 2 — AI Meeting Brief™ (dynamic from generated brief).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_brief_js(): string
{
    return <<<'JS'
brief: function () {
    return '<h2 class="xfw-section-title">Step 2. AI Meeting Brief™</h2>' +
        '<p class="xfw-section-desc">FUSION AI analyzes your continuous evidence and prepares insights to help both participants have a more meaningful and productive conversation.</p>' +
        '<div class="xfw-banner">ℹ️ <span>This brief is AI-generated and read-only. Use these insights to guide your conversation. <b>The AI prepares &mdash; people converse.</b></span></div>' +
        '<div class="xfw-card" style="margin-bottom:1rem">' +
        '<p class="xfw-muted" style="margin:0 0 .75rem">Generate the meeting brief from the continuous evidence assembled in Step 1.</p>' +
        '<button type="button" class="xfw-btn xfw-btn-accent" id="xfw-generate-brief">Generate AI Meeting Brief\u2122</button>' +
        '<p class="xfw-muted" id="xfw-generate-brief-status" style="margin-top:.5rem"></p>' +
        '</div>' +
        '<div id="xfw-brief-content"></div>';
}
JS;
}
