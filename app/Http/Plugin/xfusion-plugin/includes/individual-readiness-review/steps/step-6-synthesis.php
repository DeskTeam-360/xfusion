<?php
/**
 * Step 6 — AI Development Synthesis™.
 *
 * UI-only prototype: static dummy content matching the IRR mockup (6 summary
 * cards, readiness gauge on a 0-6 scale, development roadmap timeline,
 * recommended focus areas, executive coaching summary). No Laravel calls are
 * made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_synthesis_js(): string
{
    return <<<'JS'
synthesis: function () {
    return '<h2 class="xirr-section-title">Step 6. AI Development Synthesis™</h2>' +
        '<p class="xirr-section-desc">FUSION has synthesized your year of evidence, conversations, and commitments into your Annual Development Synthesis™.<br>This is your official annual developmental record.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>This synthesis cannot be edited. It serves as a trusted foundation for future growth and strategic alignment.</span></div>' +
        '<div id="xirr-synthesis-body"></div>';
}
JS;
}

function xfirr_wizard_synthesis_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function summaryCard(icon, title, body, cta) {
        return '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">' + icon + ' ' + esc(title) + '</h4>' +
            '<p class="xirr-muted">' + body + '</p>' +
            (cta || '') + '</div>';
    }

    function checkList(items) {
        return '<ul class="xirr-check-list">' + items.map(function (i) {
            return '<li><span class="xirr-check">&#10003;</span>' + esc(i) + '</li>';
        }).join('') + '</ul>';
    }

    function gauge(value, max, label, color) {
        var frac = Math.max(0, Math.min(100, Math.round((value / max) * 100)));
        return '<div class="xirr-donut-wrap">' +
            '<div class="xirr-donut-chart">' +
            '<svg class="xirr-donut" viewBox="0 0 36 36" aria-hidden="true">' +
            '<circle class="xirr-donut-track" cx="18" cy="18" r="15.9155"></circle>' +
            '<circle class="xirr-donut-value" cx="18" cy="18" r="15.9155" stroke="' + color + '" stroke-dasharray="' + frac + ' ' + (100 - frac) + '"></circle>' +
            '</svg>' +
            '<div class="xirr-donut-center"><div class="xirr-donut-score">' + value + '<span>/' + max + '</span></div></div>' +
            '</div><div class="xirr-donut-label">' + esc(label) + '</div></div>';
    }

    function roadmap(items) {
        return '<div class="xirr-roadmap-list">' + items.map(function (it, i) {
            return '<div class="xirr-roadmap-item">' +
                '<div class="xirr-roadmap-rail"><div class="xirr-roadmap-dot"></div><div class="xirr-roadmap-line"></div></div>' +
                '<div><div class="xirr-roadmap-period">' + esc(it[0]) + '</div><div class="xirr-roadmap-text">' + esc(it[1]) + '</div></div>' +
                '</div>';
        }).join('') + '</div>';
    }

    function attentionCard(icon, title, desc) {
        return '<div class="xirr-activate-card"><div style="font-size:1.5rem">' + icon + '</div><h4>' + esc(title) + '</h4><p>' + esc(desc) + '</p></div>';
    }

    window.initSynthesisStep = function () {
        var body = document.getElementById('xirr-synthesis-body');
        if (!body) return;

        body.innerHTML =
            '<div class="xirr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            summaryCard('&#128203;', 'Annual Development Summary™', 'You demonstrated consistent growth across key behaviors and leadership contributions. You strengthened operational execution, communication, and strategic thinking while expanding your influence within the organization.', '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a>') +
            summaryCard('&#128200;', 'Behavioral Growth Summary™', 'Your Behavioral Driver performance improved across all five drivers. Get Real and Be Intentional showed the strongest growth. Foster Grit continues to be an area of opportunity.<br><br><strong style="font-size:1.3rem;color:var(--navy)">4.24</strong> Average Score <span style="color:#16a34a">&#8593; 0.38 vs last year</span>', '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a>') +
            summaryCard('&#10024;', 'Strength Summary™', 'Operational Excellence', checkList(['Problem Solving', 'Accountability', 'Clear Communication', 'Team Development']) + '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a>') +
            '</div>' +

            '<div class="xirr-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">' +
            summaryCard('&#128221;', 'Opportunity Summary™', '<b>Strategic Foresight</b> — Develop long-term strategic planning and future-focused decision making.<br><b>Delegation</b> — Increase delegation of tasks and development of team members.<br><b>Change Leadership</b> — Strengthen leading others through change and uncertainty.', '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a>') +
            '<div class="xirr-card" style="margin-bottom:0;text-align:center"><h4 style="margin-top:0;text-align:left">Readiness Summary™</h4>' +
            gauge(4.2, 6.0, 'Developing (↑0.4 vs last year)', '#2f6f3e') +
            '<p class="xirr-muted" style="text-align:left;margin-top:.75rem">You are progressing well toward greater readiness. Continued focus on strategic leadership and delegation will accelerate your impact and future readiness.</p>' +
            '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a></div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4 style="margin-top:0">Development Roadmap™</h4>' +
            roadmap([
                ['Q3 2025', 'Improve delegation and empowerment'],
                ['Q4 2025', 'Build strategic planning capabilities'],
                ['Q1 2026', 'Strengthen change leadership and influence'],
                ['Q2 2026', 'Expand cross-functional collaboration'],
            ]) + '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a></div>' +
            '</div>' +

            '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1.2fr 1fr;gap:1rem">' +
            '<div class="xirr-card"><h4 style="margin-top:0">Recommended Focus Areas™</h4>' +
            '<p class="xirr-muted" style="margin-top:-.2rem">Focusing on these areas will drive the greatest impact on your growth and organizational contribution over the next 12 months.</p>' +
            checkList(['Strategic Leadership', 'Delegation & Empowerment', 'Change Leadership', 'Influencing & Communication', 'Coaching Mindset']) +
            '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a></div>' +
            '<div class="xirr-card"><h4 style="margin-top:0">Executive Coaching Summary™</h4>' +
            '<p class="xirr-muted">Your coaching conversations focused on growth, alignment, and leadership impact. Continue leveraging coaching to accelerate delegation, strategic leadership, and influence.</p>' +
            '<p class="xirr-muted" style="margin-bottom:.2rem">Coaching Engagement</p>' +
            '<p style="font-weight:700;color:#16a34a;margin:0 0 .6rem">High</p>' +
            '<p class="xirr-muted" style="margin-bottom:.2rem">Recommendation</p>' +
            '<p style="margin:0 0 .6rem">Maintain bi-weekly coaching cadence.</p>' +
            '<a href="javascript:void(0)" class="xirr-link">View details &rarr;</a></div>' +
            '</div>' +

            '<div class="xirr-banner" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;margin-top:1rem">&#9989; <span><b>AI Development Synthesis Complete.</b> Your annual developmental synthesis is ready to publish.</span></div>';
    };
})();
JS;
}
