<?php
/**
 * Step 3 — Shared Preparation™ (UI shell, static dummy data).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_preparation_js(): string
{
    return <<<'JS'
preparation: function () {
    function scaleQ(role, label, desc, min, max, selected) {
        var btns = '';
        for (var i = 1; i <= 5; i++) {
            btns += '<div class="xfw-scale-btn' + (i === selected ? ' selected ' + role : '') + '">' + i + '</div>';
        }
        return '<div class="xfw-scale-q"><label>' + label + '</label><div class="q-desc">' + desc + '</div>' +
            '<div class="xfw-scale">' + btns + '</div>' +
            '<div class="xfw-scale-labels"><span>' + min + '</span><span>' + max + '</span></div></div>';
    }
    function textField(label, maxlen) {
        return '<div class="xfw-textarea-field"><label>' + label + '<span class="count">0 / ' + maxlen + '</span></label>' +
            '<textarea rows="2" placeholder="Enter your response..." data-maxlen="' + maxlen + '"></textarea></div>';
    }
    return '<h2 class="xfw-section-title">Step 3. Shared Preparation™</h2>' +
        '<p class="xfw-section-desc">Both participants complete their preparation independently before the meeting. Your responses help create a more focused, productive, and meaningful conversation.</p>' +
        '<div class="xfw-banner">ℹ️ <span>You will not see each other\'s preparation until the Alignment Conversation™ (Step 4). Please take a few minutes to complete your section below.</span></div>' +
        '<div class="xfw-grid-2">' +
        '<div class="xfw-card xfw-prep-col employee">' +
        '<h3>Employee Preparation</h3><p class="xfw-muted">Your reflection to prepare for a productive conversation.</p>' +
        scaleQ('employee', 'Alignment Clarity', 'How clear are you on your current priorities?', 'Not Clear', 'Very Clear', 4) +
        scaleQ('employee', 'Current Workload Sustainability', 'How sustainable is your current workload?', 'Not Sustainable', 'Very Sustainable', 3) +
        scaleQ('employee', 'Confidence in Current Priorities', 'How confident are you in your current priorities?', 'Not Confident', 'Very Confident', 4) +
        '<div class="xfw-section-label" style="margin-top:1rem;font-weight:800;color:var(--navy);font-size:18px;">Open Reflections</div>' +
        textField('Biggest accomplishment since last meeting', 1000) +
        textField('Biggest current obstacle', 1000) +
        textField('Support needed from your leader', 1000) +
        textField('Development focus', 1000) +
        '</div>' +
        '<div class="xfw-card xfw-prep-col leader">' +
        '<h3>Leader Preparation</h3><p class="xfw-muted">Your reflection to prepare for a productive conversation.</p>' +
        scaleQ('leader', 'Priority Alignment', 'How well aligned are priorities with team and org goals?', 'Not Aligned', 'Highly Aligned', 4) +
        scaleQ('leader', 'Observed Progress', 'How would you rate their progress since our last meeting?', 'Minimal Progress', 'Exceptional Progress', 3) +
        scaleQ('leader', 'Support Effectiveness', 'How effective has the support you\'ve provided been?', 'Not Effective', 'Very Effective', 4) +
        '<div class="xfw-section-label" style="margin-top:1rem;font-weight:800;color:var(--navy);font-size:18px;">Open Reflections</div>' +
        textField('Coaching topics to discuss', 1000) +
        textField('Organizational updates to share', 1000) +
        textField('Top discussion priorities', 1000) +
        '</div>' +
        '</div>' +
        '<div class="xfw-banner warn" style="margin-top:1rem">&#128274; <span><b>Your preparation is private</b>Neither participant\'s preparation is visible to the other until Step 4 (Alignment Conversation™). This ensures an open, honest, and productive conversation.</span></div>';
}
JS;
}
