<?php
/**
 * ARR picker gate — shown before the [fusion_arr_wizard] wizard opens.
 *
 * UI-only prototype: fully static/local, no Laravel/AJAX calls. Lists a
 * couple of dummy reviews and lets the user "open" one or "start" a new one
 * by setting ?arr_id= and reloading — mirrors the QBR/IRR picker UX without
 * any backend wiring yet. ARR is organization-wide (one per company/year).
 *
 * Styled like the 1-on-1 / ARP / QBR / IRR picker gates: the wizard's own
 * branded header + shared component styles (card, table, badge, input,
 * btn, link), not a small standalone card with ad-hoc CSS.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_render_picker_gate(): string
{
    $css = xfarr_wizard_styles_css();

    ob_start();
    ?>
<div id="xfarr-wiz">

    <div class="xarr-header">
        <div class="xarr-header-inner">
            <div>
                <h1>ANNUAL READINESS REVIEW&trade; (ARR)</h1>
                <p>Select an organization to begin</p>
            </div>
        </div>
    </div>

    <div style="padding:1.5rem 1.75rem">
        <div id="xfarr-picker">
            <div class="xarr-card" id="xfarr-picker-body">
                <h2>Annual Readiness Review&trade; (ARR)</h2>
                <p class="xarr-muted">Select an existing review, or start a new Annual Readiness Review™ for this year.</p>

                <div class="xarr-table-scroll"><table class="xarr-table">
                    <thead><tr><th>Organization</th><th>Review Year</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr>
                            <td>Northwind Energy Co-op</td><td>2025</td>
                            <td><span class="xarr-badge amber">In Progress</span></td>
                            <td><a href="javascript:void(0)" class="xarr-link" data-open="1">Open &rarr;</a></td>
                        </tr>
                        <tr>
                            <td>Northwind Energy Co-op</td><td>2024</td>
                            <td><span class="xarr-badge green">Published</span></td>
                            <td><a href="javascript:void(0)" class="xarr-link" data-open="2">Open &rarr;</a></td>
                        </tr>
                    </tbody>
                </table></div>

                <div class="xfarr-new-form">
                    <div style="font-weight:700;font-size:15px;color:var(--navy);margin-bottom:.5rem">Start a new Annual Readiness Review™</div>
                    <input type="number" class="xarr-input" id="xfarr-new-year" value="<?php echo esc_attr(wp_date('Y')); ?>" style="width:6rem"/>
                    <button type="button" class="xarr-btn xarr-btn-accent" id="xfarr-new-btn">+ Start Review</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style><?php echo $css; ?>
#xfarr-picker h2{margin:0 0 .3rem;font-size:22px;color:var(--navy)}
#xfarr-picker .xfarr-new-form{margin-top:1.25rem;border-top:1px solid var(--border);padding-top:1rem}
#xfarr-picker .xfarr-new-form input{margin:.2rem .5rem .5rem 0;width:auto;display:inline-block}
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
