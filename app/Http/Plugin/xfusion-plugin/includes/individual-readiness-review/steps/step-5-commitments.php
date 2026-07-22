<?php
/**
 * Step 5 — Annual Development Commitments™.
 *
 * UI-only prototype: static dummy content matching the IRR mockup. Starts
 * with an empty commitment table (per mockup: "0 of 5 commitments added")
 * and lets the user add up to 5 local-only rows. No Laravel calls are made
 * from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_commitments_js(): string
{
    return <<<'JS'
commitments: function () {
    return '<h2 class="xirr-section-title">Step 5. Annual Development Commitments™</h2>' +
        '<p class="xirr-section-desc">Create your plan for growth. These commitments will guide your development over the next year.<br>You can set up to 5 commitments. Each should be specific, meaningful, and aligned to your growth and organizational priorities.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>Your commitments will become part of your future 1-on-1 Alignment Capture™ conversations and will be tracked throughout the year.</span></div>' +

        '<div class="xirr-row" style="justify-content:space-between;margin-bottom:.75rem">' +
        '<span class="xirr-muted" id="xirr-commit-count">0 of 5 commitments added</span>' +
        '<button type="button" class="xirr-btn xirr-btn-outline" id="xirr-add-commitment">+ Add Commitment</button>' +
        '</div>' +
        '<div class="xirr-card"><div id="xirr-commitments-list"></div></div>' +

        '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1.3fr 1fr;gap:1rem">' +
        '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">Commitment Structure</h4>' +
        '<p class="xirr-muted" style="margin-top:-.2rem">Each commitment you create will include the following:</p>' +
        '<div class="xirr-guide-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">' +
        [['Commitment','Clear and actionable development focus'],['Owner','Who is responsible for this commitment'],
         ['Priority','High, Medium, or Low'],['Target Date','When you aim to achieve this'],
         ['Success Indicator','How you will measure success'],['Behavioral Driver™','Which driver this commitment strengthens'],
         ['Org Priority (Optional)','Link to ARP or QBR priority']].map(function (f) {
            return '<div><div style="font-weight:700;color:var(--navy);font-size:14px">' + f[0] + '</div><div class="xirr-muted" style="font-size:12px">' + f[1] + '</div></div>';
        }).join('') + '</div></div>' +
        '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">Alignment Guidance</h4>' +
        '<p class="xirr-muted" style="margin-top:-.2rem">Strong commitments are:</p>' +
        '<ul class="xirr-check-list">' +
        '<li><b>Specific</b> — Clearly define what you will focus on and why it matters.</li>' +
        '<li><b>Actionable</b> — Describe the key actions you will take.</li>' +
        '<li><b>Measurable</b> — Include a success indicator that shows progress.</li>' +
        '<li><b>Aligned</b> — Connect to a Behavioral Driver™ and (optionally) an organizational priority.</li>' +
        '<li><b>Achievable</b> — Set a realistic target date within the next 12 months.</li>' +
        '</ul></div></div>';
}
JS;
}

function xfirr_wizard_commitments_init_js(): string
{
    return <<<'JS'
(function () {
    var DRIVERS = ['Get Real', 'Be Intentional', 'Fill Buckets', 'Foster Grit', 'Drive Growth'];
    var cache = [];

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function row(i, c) {
        return '<div class="xirr-prio-card">' +
            '<div class="xirr-prio-rail"><span class="xirr-drag">&#8942;&#8942;</span><div class="xirr-prio-num">' + (i + 1) + '</div></div>' +
            '<div class="xirr-prio-body">' +
            '<div class="xirr-prio-grid xirr-prio-grid-1"><input class="xirr-input" data-f="title" placeholder="Commitment" value="' + esc(c.title) + '"></div>' +
            '<div class="xirr-prio-grid xirr-prio-grid-4">' +
            '<input class="xirr-input" data-f="owner" placeholder="Owner" value="' + esc(c.owner) + '">' +
            '<select class="xirr-input" data-f="priority">' + ['High','Medium','Low'].map(function (p) {
                return '<option' + (c.priority === p ? ' selected' : '') + '>' + p + '</option>';
            }).join('') + '</select>' +
            '<input type="date" class="xirr-input" data-f="target_date" value="' + esc(c.target_date) + '">' +
            '<select class="xirr-input" data-f="driver"><option value="">Behavioral Driver™</option>' + DRIVERS.map(function (d) {
                return '<option' + (c.driver === d ? ' selected' : '') + '>' + d + '</option>';
            }).join('') + '</select>' +
            '</div>' +
            '<div class="xirr-prio-grid xirr-prio-grid-1"><input class="xirr-input" data-f="success_indicator" placeholder="Success Indicator" value="' + esc(c.success_indicator) + '"></div>' +
            '<button type="button" class="xirr-icon-btn xirr-prio-delete" data-remove="' + i + '">&#10005;</button>' +
            '</div></div>';
    }

    function renderList() {
        var list = document.getElementById('xirr-commitments-list');
        var count = document.getElementById('xirr-commit-count');
        var addBtn = document.getElementById('xirr-add-commitment');
        if (!list) return;

        if (cache.length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:2.5rem 1rem">' +
                '<div style="font-size:1.8rem">&#128203;</div>' +
                '<p style="font-weight:700;margin:.5rem 0 .2rem">No commitments added yet.</p>' +
                '<p class="xirr-muted">Click "Add Commitment" above to create your first development commitment.</p></div>';
        } else {
            list.innerHTML = cache.map(function (c, i) { return row(i, c); }).join('');
            list.querySelectorAll('[data-f]').forEach(function (el) {
                el.addEventListener('input', function () {
                    var i = Array.prototype.indexOf.call(list.children, el.closest('.xirr-prio-card'));
                    cache[i][el.dataset.f] = el.value;
                });
            });
            list.querySelectorAll('[data-remove]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    cache.splice(parseInt(btn.dataset.remove, 10), 1);
                    renderList();
                });
            });
        }

        if (count) count.textContent = cache.length + ' of 5 commitments added';
        if (addBtn) addBtn.disabled = cache.length >= 5;
    }

    window.initCommitmentsStep = function () {
        var addBtn = document.getElementById('xirr-add-commitment');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                if (cache.length >= 5) return;
                cache.push({ title: '', owner: '', priority: 'Medium', target_date: '', driver: '', success_indicator: '' });
                renderList();
            });
        }
        renderList();
    };
})();
JS;
}
