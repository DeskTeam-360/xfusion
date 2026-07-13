<?php
/**
 * Step 5 — Shared Commitments™ (editable UI → wp_fusion_one_on_one_commitments).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_commitments_js(): string
{
    return <<<'JS'
commitments: function () {
    function tableShell(role, title, desc, colHeader, btnLabel) {
        var cardClass = 'xfw-card' + (role === 'leader' ? ' xfw-commit-card-leader' : '');
        return '<div class="' + cardClass + '">' +
            '<div class="xfw-commit-head"><h3 style="margin:0">' + title + '</h3>' +
            '<button type="button" class="xfw-btn xfw-btn-outline" data-add-commitment="' + role + '">' + btnLabel + '</button></div>' +
            '<p class="xfw-muted" style="margin-top:-.4rem;margin-bottom:.6rem">' + desc + '</p>' +
            '<div class="xfw-table-scroll"><table class="xfw-table">' +
            '<thead><tr><th>Commitment</th><th>Priority</th><th>' + colHeader + '</th><th>Target Date</th><th>Success Indicator</th><th>Status</th></tr></thead>' +
            '<tbody class="xfw-commit-tbody" data-role="' + role + '">' +
            '<tr class="xfw-commit-empty"><td colspan="6" class="xfw-muted">No commitments yet. Click + Add Commitment.</td></tr>' +
            '</tbody></table></div></div>';
    }
    return '<h2 class="xfw-section-title">Step 5. Shared Commitments™</h2>' +
        '<p class="xfw-section-desc">Create clear, actionable commitments that drive accountability and results. Both participants add commitments. Open commitments will automatically appear in your next 1-on-1.</p>' +
        '<div class="xfw-banner">ℹ️ <span>Commitments should be specific, measurable, and meaningful. Choose a Behavioral Driver™ to connect your commitment to what matters most.</span></div>' +
        tableShell('employee', 'Employee Commitments', 'Commitments you will take action on before our next meeting.', 'Behavioral Driver™', '+ Add Commitment') +
        tableShell('leader', 'Leader Commitments', 'Commitments you will take action on to support your employee.', 'Related Employee', '+ Add Commitment') +
        '<div class="xfw-card" style="margin-top:1rem;background:#fbfaf5">' +
        '<h3>Commitment Tips</h3>' +
        '<ul class="xfw-numbered" style="list-style:disc;padding-left:1.2rem"><li>Be specific about what will be done.</li><li>Set a realistic target date.</li><li>Define how success will be measured.</li><li>Align commitments to Behavioral Drivers™ for stronger impact.</li></ul>' +
        '</div>';
}
JS;
}
