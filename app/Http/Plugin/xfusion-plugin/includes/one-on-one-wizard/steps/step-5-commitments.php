<?php
/**
 * Step 5 — Shared Commitments™ (UI shell, static dummy data).
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
    function table(rows, colHeader) {
        return '<table class="xfw-table"><thead><tr><th>Commitment</th><th>Priority</th><th>' + colHeader + '</th><th>Target Date</th><th>Success Indicator</th><th>Status</th></tr></thead><tbody>' +
            rows.map(function (r) {
                return '<tr><td>' + r[0] + '</td>' +
                    '<td><span class="xfw-dot ' + r[1].toLowerCase() + '"></span>' + r[1] + '</td>' +
                    '<td>' + r[2] + '</td>' +
                    '<td>' + r[3] + '</td>' +
                    '<td>' + r[4] + '</td>' +
                    '<td><span class="xfw-badge green">' + r[5] + '</span></td></tr>';
            }).join('') + '</tbody></table>';
    }
    var employeeRows = [
        ['Complete Project Phoenix requirements document and share for review.', 'High', 'Be Intentional', 'May 28, 2025', 'Requirements doc submitted and reviewed', 'Active'],
        ['Strengthen cross-functional communication with weekly updates to stakeholders.', 'Medium', 'Foster Grit', 'Jun 11, 2025', 'Weekly updates sent consistently', 'Active'],
        ['Complete Leading with Impact micro-learning module and apply one new strategy.', 'Low', 'Drive Growth', 'Jun 20, 2025', 'Module complete and strategy applied', 'Active'],
    ];
    var leaderRows = [
        ['Provide feedback on Project Phoenix document within 3 business days.', 'High', 'Michael Wilson', 'May 21, 2025', 'Feedback provided on document', 'Active'],
        ['Remove roadblocks related to data access for project completion.', 'Medium', 'Michael Wilson', 'Jun 4, 2025', 'Access granted and confirmed', 'Active'],
        ['Schedule and hold mid-point check-in on development goals.', 'Low', 'Michael Wilson', 'Jun 18, 2025', 'Check-in completed and documented', 'Active'],
    ];
    return '<h2 class="xfw-section-title">Step 5. Shared Commitments™</h2>' +
        '<p class="xfw-section-desc">Create clear, actionable commitments that drive accountability and results. Both participants add commitments. Open commitments will automatically appear in your next 1-on-1.</p>' +
        '<div class="xfw-banner">ℹ️ <span>Commitments should be specific, measurable, and meaningful. Choose a Behavioral Driver™ to connect your commitment to what matters most.</span></div>' +
        '<div class="xfw-card">' +
        '<div class="xfw-commit-head"><h3 style="margin:0">Employee Commitments</h3><button class="xfw-btn xfw-btn-outline">+ Add Commitment</button></div>' +
        '<p class="xfw-muted" style="margin-top:-.4rem;margin-bottom:.6rem">Commitments you will take action on before our next meeting.</p>' +
        table(employeeRows, 'Behavioral Driver™') +
        '</div>' +
        '<div class="xfw-card" style="margin-top:1rem">' +
        '<div class="xfw-commit-head"><h3 style="margin:0">Leader Commitments</h3><button class="xfw-btn xfw-btn-outline">+ Add Commitment</button></div>' +
        '<p class="xfw-muted" style="margin-top:-.4rem;margin-bottom:.6rem">Commitments you will take action on to support your employee.</p>' +
        table(leaderRows, 'Related Employee') +
        '</div>' +
        '<div class="xfw-card" style="margin-top:1rem;background:#fbfaf5">' +
        '<h3>Commitment Tips</h3>' +
        '<ul class="xfw-numbered" style="list-style:disc;padding-left:1.2rem"><li>Be specific about what will be done.</li><li>Set a realistic target date.</li><li>Define how success will be measured.</li><li>Align commitments to Behavioral Drivers™ for stronger impact.</li></ul>' +
        '</div>';
}
JS;
}
