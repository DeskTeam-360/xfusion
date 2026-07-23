<?php
/**
 * ARR picker gate — shown before the [fusion_arr_wizard] wizard opens.
 *
 * UI-only prototype: fully static/local, no Laravel/AJAX calls. Lists a
 * couple of dummy reviews and lets the user "open" one or "start" a new one
 * by setting ?arr_id= and reloading — mirrors the QBR/IRR picker UX without
 * any backend wiring yet. ARR is organization-wide (one per company/year).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_render_picker_gate(): string
{
    ob_start();
    ?>
<div id="xfarr-picker">
    <div class="xarr-card" id="xfarr-picker-body">
        <h2>Annual Readiness Review&trade; (ARR)</h2>
        <p class="xarr-muted">Select an existing review, or start a new Annual Readiness Review™ for this year.</p>

        <table>
            <thead><tr><th>Organization</th><th>Review Year</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <tr>
                    <td>Northwind Energy Co-op</td><td>2025</td>
                    <td><span class="xarr-badge">In Progress</span></td>
                    <td><a href="javascript:void(0)" class="xarr-open-link" data-open="1">Open</a></td>
                </tr>
                <tr>
                    <td>Northwind Energy Co-op</td><td>2024</td>
                    <td><span class="xarr-badge active">Published</span></td>
                    <td><a href="javascript:void(0)" class="xarr-open-link" data-open="2">Open</a></td>
                </tr>
            </tbody>
        </table>

        <div class="xfarr-new-form">
            <div style="font-weight:700;font-size:.85rem;margin-bottom:.5rem">Start a new Annual Readiness Review™</div>
            <input type="number" id="xfarr-new-year" value="<?php echo esc_attr(wp_date('Y')); ?>" style="width:6rem"/>
            <button type="button" id="xfarr-new-btn">+ Start Review</button>
        </div>
    </div>
</div>

<style>
#xfarr-picker{max-width:760px;margin:0 auto}
#xfarr-picker .xarr-card{border:1px solid #e5e7eb;border-radius:.5rem;padding:1.5rem;background:#fff}
#xfarr-picker h2{margin:0 0 .3rem;font-size:1.15rem;color:#1e2a52}
#xfarr-picker .xarr-muted{color:#6b7280;font-size:.85rem;line-height:1.5}
#xfarr-picker table{width:100%;border-collapse:collapse;margin-top:1rem;font-size:.85rem}
#xfarr-picker th{text-align:left;padding:.5rem;color:#6b7280;font-size:.72rem;text-transform:uppercase;border-bottom:1px solid #e5e7eb}
#xfarr-picker td{padding:.6rem .5rem;border-bottom:1px solid #f3f4f6}
#xfarr-picker .xarr-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;background:#fef3c7;color:#92400e}
#xfarr-picker .xarr-badge.active{background:#dcfce7;color:#166534}
#xfarr-picker a.xarr-open-link{color:#5f9a3f;font-weight:600;text-decoration:underline}
#xfarr-picker .xfarr-new-form{margin-top:1.25rem;border-top:1px solid #e5e7eb;padding-top:1rem}
#xfarr-picker input{border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.85rem;margin:.2rem .4rem .2rem 0}
#xfarr-picker button{cursor:pointer;border:1px solid #5f9a3f;background:#5f9a3f;color:#fff;border-radius:.375rem;padding:.4rem 1rem;font-size:.85rem;font-weight:600}
</style>

<script>
(function () {
    var root = document.getElementById('xfarr-picker');
    if (!root) return;

    function openArr(id) {
        var url = new URL(window.location.href);
        url.searchParams.set('arr_id', id);
        window.location.href = url.toString();
    }

    root.querySelectorAll('[data-open]').forEach(function (a) {
        a.addEventListener('click', function () { openArr(a.dataset.open); });
    });

    var newBtn = document.getElementById('xfarr-new-btn');
    if (newBtn) {
        newBtn.addEventListener('click', function () {
            openArr('new-' + document.getElementById('xfarr-new-year').value);
        });
    }
})();
</script>
    <?php

    return (string) ob_get_clean();
}
