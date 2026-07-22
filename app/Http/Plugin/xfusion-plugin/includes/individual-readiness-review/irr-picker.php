<?php
/**
 * IRR picker gate — shown before the [fusion_irr_wizard] wizard opens.
 *
 * UI-only prototype: fully static/local, no Laravel/AJAX calls. Lists a
 * couple of dummy reviews and lets the user "open" one or "start" a new one
 * by setting ?irr_id= and reloading — mirrors the QBR/ARP picker UX without
 * any backend wiring yet.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_render_picker_gate(): string
{
    ob_start();
    ?>
<div id="xfirr-picker">
    <div class="xirr-card" id="xfirr-picker-body">
        <h2>Individual Readiness Review&trade;</h2>
        <p class="xirr-muted">Select an existing review, or start a new Individual Readiness Review™ for this year.</p>

        <table>
            <thead><tr><th>Employee</th><th>Review Year</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <tr>
                    <td>Alex Johnson</td><td>2025</td>
                    <td><span class="xirr-badge">In Progress</span></td>
                    <td><a href="javascript:void(0)" class="xirr-open-link" data-open="1">Open</a></td>
                </tr>
                <tr>
                    <td>Alex Johnson</td><td>2024</td>
                    <td><span class="xirr-badge active">Published</span></td>
                    <td><a href="javascript:void(0)" class="xirr-open-link" data-open="2">Open</a></td>
                </tr>
            </tbody>
        </table>

        <div class="xfirr-new-form">
            <div style="font-weight:700;font-size:.85rem;margin-bottom:.5rem">Start a new Individual Readiness Review™</div>
            <input type="number" id="xfirr-new-year" value="<?php echo esc_attr(wp_date('Y')); ?>" style="width:6rem"/>
            <button type="button" id="xfirr-new-btn">+ Start Review</button>
        </div>
    </div>
</div>

<style>
#xfirr-picker{max-width:760px;margin:0 auto}
#xfirr-picker .xirr-card{border:1px solid #e5e7eb;border-radius:.5rem;padding:1.5rem;background:#fff}
#xfirr-picker h2{margin:0 0 .3rem;font-size:1.15rem;color:#1e2a52}
#xfirr-picker .xirr-muted{color:#6b7280;font-size:.85rem;line-height:1.5}
#xfirr-picker table{width:100%;border-collapse:collapse;margin-top:1rem;font-size:.85rem}
#xfirr-picker th{text-align:left;padding:.5rem;color:#6b7280;font-size:.72rem;text-transform:uppercase;border-bottom:1px solid #e5e7eb}
#xfirr-picker td{padding:.6rem .5rem;border-bottom:1px solid #f3f4f6}
#xfirr-picker .xirr-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;background:#fef3c7;color:#92400e}
#xfirr-picker .xirr-badge.active{background:#dcfce7;color:#166534}
#xfirr-picker a.xirr-open-link{color:#5f9a3f;font-weight:600;text-decoration:underline}
#xfirr-picker .xfirr-new-form{margin-top:1.25rem;border-top:1px solid #e5e7eb;padding-top:1rem}
#xfirr-picker input{border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.85rem;margin:.2rem .4rem .2rem 0}
#xfirr-picker button{cursor:pointer;border:1px solid #5f9a3f;background:#5f9a3f;color:#fff;border-radius:.375rem;padding:.4rem 1rem;font-size:.85rem;font-weight:600}
</style>

<script>
(function () {
    var root = document.getElementById('xfirr-picker');
    if (!root) return;

    function openIrr(id) {
        var url = new URL(window.location.href);
        url.searchParams.set('irr_id', id);
        window.location.href = url.toString();
    }

    root.querySelectorAll('[data-open]').forEach(function (a) {
        a.addEventListener('click', function () { openIrr(a.dataset.open); });
    });

    var newBtn = document.getElementById('xfirr-new-btn');
    if (newBtn) {
        newBtn.addEventListener('click', function () {
            openIrr('new-' + document.getElementById('xfirr-new-year').value);
        });
    }
})();
</script>
    <?php

    return (string) ob_get_clean();
}
