<?php
/**
 * Step 3 — AI Development Assessment™.
 *
 * UI-only prototype: static dummy content matching the IRR mockups
 * (behavioral strengths/opportunities, behavioral pattern summary, readiness
 * gauge on a 0-6 scale, leadership/organizational contributions, development
 * progress line chart). No Laravel calls are made from this step for now.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_step_assessment_js(): string
{
    return <<<'JS'
assessment: function () {
    return '<h2 class="xirr-section-title">Step 3. AI Development Assessment™</h2>' +
        '<p class="xirr-section-desc">FUSION AI analyzes a full year of evidence to identify patterns, strengths, opportunities, and readiness indicators.<br>Review the AI assessment below before adding your reflections in the next step.</p>' +
        '<div class="xirr-banner">&#8505;&#65039; <span>This assessment is AI-generated and based on your evidence from across the platform. It cannot be edited.</span></div>' +
        '<div id="xirr-assessment-body"></div>';
}
JS;
}

function xfirr_wizard_assessment_init_js(): string
{
    return <<<'JS'
(function () {
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function point(fraction) {
        var theta = (-90 + fraction * 180) * Math.PI / 180;
        return { x: 110 + 75 * Math.sin(theta), y: 110 - 75 * Math.cos(theta) };
    }
    function arcPath(f1, f2) {
        var p1 = point(f1), p2 = point(f2);
        return 'M ' + p1.x + ' ' + p1.y + ' A 75 75 0 0 1 ' + p2.x + ' ' + p2.y;
    }
    function rpmGauge(value, max, zoneLabel, zoneColor) {
        var frac = Math.max(0, Math.min(1, value / max));
        var needleDeg = -90 + frac * 180;
        return '<div style="text-align:center">' +
            '<svg viewBox="0 0 220 130" style="width:230px;max-width:100%">' +
            '<path d="' + arcPath(0, 0.4) + '" stroke="#dc2626" stroke-width="14" fill="none"/>' +
            '<path d="' + arcPath(0.4, 0.65) + '" stroke="#f59e0b" stroke-width="14" fill="none"/>' +
            '<path d="' + arcPath(0.65, 1) + '" stroke="#16a34a" stroke-width="14" fill="none"/>' +
            '<line x1="110" y1="110" x2="110" y2="45" stroke="#1e2a52" stroke-width="4" transform="rotate(' + needleDeg + ' 110 110)"/>' +
            '<circle cx="110" cy="110" r="7" fill="#1e2a52"/>' +
            '</svg>' +
            '<div style="font-size:2rem;font-weight:800;color:var(--navy);margin-top:-1rem">' + value.toFixed(1) + '<span style="font-size:1rem;font-weight:500;color:var(--muted)"> of ' + max.toFixed(1) + '</span></div>' +
            '<div style="font-weight:600;color:' + zoneColor + '">' + esc(zoneLabel) + '</div>' +
            '</div>';
    }

    function strengthRow(icon, title, desc, tagLabel, tagValue) {
        return '<div class="xirr-evidence-row" style="align-items:flex-start">' +
            '<div class="xirr-evidence-icon">' + icon + '</div>' +
            '<div><div class="xirr-evidence-title">' + esc(title) + '</div><div class="xirr-evidence-desc">' + esc(desc) + '</div></div>' +
            '<div class="xirr-evidence-status" style="text-align:right"><div class="xirr-muted" style="font-size:12px">' + tagLabel + '</div><div style="font-weight:700;color:var(--navy)">' + tagValue + '</div></div>' +
            '</div>';
    }

    function progressRow(label, value, max) {
        max = max || 6;
        var pct = Math.round((value / max) * 100);
        return '<div class="xirr-align-row xirr-progress-row">' +
            '<div class="xirr-align-label">' + esc(label) + '</div>' +
            '<div class="xirr-progress-track"><div class="xirr-progress-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="xirr-progress-pct">' + value + '</div>' +
            '</div>';
    }

    window.initAssessmentStep = function () {
        var body = document.getElementById('xirr-assessment-body');
        if (!body) return;

        body.innerHTML =
            '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Behavioral Strengths™</h4>' +
            strengthRow('&#9989;', 'Consistent Problem Solver', 'You consistently identify root causes and implement effective solutions.', 'Evidence', 'High') +
            strengthRow('&#128101;', 'Reliable &amp; Accountable', 'You follow through on commitments and deliver results.', 'Evidence', 'High') +
            strengthRow('&#128101;', 'Strong Operational Leader', 'You drive operational excellence and team performance.', 'Evidence', 'High') +
            strengthRow('&#9999;&#65039;', 'Clear Communicator', 'You communicate information clearly and adapt to your audience.', 'Evidence', 'Medium') +
            strengthRow('&#10024;', 'Adaptable &amp; Resilient', 'You adjust effectively to change and maintain momentum.', 'Evidence', 'Medium') +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Development Opportunities™</h4>' +
            strengthRow('&#10024;', 'Strategic Thinking', 'Expand long-term strategic planning and future-focused decision making.', 'Impact', 'High') +
            strengthRow('&#128101;', 'Delegation', 'Increase delegation of tasks and development of team members.', 'Impact', 'Medium') +
            strengthRow('&#128203;', 'Change Leadership', 'Strengthen leading others through change and uncertainty.', 'Impact', 'Medium') +
            strengthRow('&#10024;', 'Influencing', 'Grow ability to influence without authority across the organization.', 'Impact', 'Medium') +
            strengthRow('&#10024;', 'Coaching Mindset', 'Deepen coaching skills to drive performance and growth.', 'Impact', 'Low') +
            '</div></div>' +

            '<div class="xirr-card"><h4>Behavioral Pattern Summary™</h4>' +
            '<p class="xirr-muted" style="margin-top:-.2rem">Over the past year, your patterns show steady growth in operational execution, communication, and accountability. You thrive in environments where you can drive results and solve complex problems.</p>' +
            '<div class="xirr-pattern-grid">' +
            '<div class="xirr-pattern-item"><div class="label">Primary Pattern</div><span class="xirr-pattern-chip">Executor</span></div>' +
            '<div class="xirr-pattern-item"><div class="label">Secondary Pattern</div><span class="xirr-pattern-chip">Influencer</span></div>' +
            '<div class="xirr-pattern-item"><div class="label">Energy Pattern</div><span class="xirr-pattern-chip">Problem Solver</span></div>' +
            '<div class="xirr-pattern-item"><div class="label">Growth Edge</div><span class="xirr-pattern-chip" style="background:#fef3c7;color:#92400e">Strategic Thinker</span></div>' +
            '</div></div>' +

            '<div class="xirr-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Readiness Indicators™</h4>' +
            rpmGauge(4.2, 6.0, 'Developing (↑0.4 vs last year)', '#ca8a04') +
            progressRow('Self-Awareness', 4.1) + progressRow('Learning Agility', 4.2) + progressRow('Accountability', 4.5) +
            progressRow('Adaptability', 4.0) + progressRow('Leadership Impact', 4.2) + progressRow('Future Readiness', 4.3) +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Leadership Contributions™</h4>' +
            '<ul class="xirr-check-list">' +
            '<li>Drives operational performance with a focus on results.</li>' +
            '<li>Supports team success through coaching and guidance.</li>' +
            '<li>Takes ownership of challenges and delivers solutions.</li>' +
            '<li>Builds trust through reliability and clear communication.</li>' +
            '<li>Elevates team and promotes continuous improvement.</li>' +
            '</ul></div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Organizational Contribution™</h4>' +
            '<ul class="xirr-check-list">' +
            '<li>Actively contributes to QBR priorities and objectives.</li>' +
            '<li>Supports cross-functional collaboration and alignment.</li>' +
            '<li>Demonstrates the values and behaviors that strengthen organizational culture.</li>' +
            '<li>Helps move the organization forward through execution and accountability.</li>' +
            '</ul></div>' +
            '</div>' +

            '<div class="xirr-grid-2" style="display:grid;grid-template-columns:1.4fr 1fr;gap:1rem">' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Development Progress™</h4>' +
            '<svg viewBox="0 0 400 160" style="width:100%;height:auto">' +
            '<polyline points="20,120 55,110 90,100 125,95 160,80 195,70 230,55 265,45 300,35 335,25" fill="none" stroke="#2f6f3e" stroke-width="2"/>' +
            '<polyline points="20,135 55,130 90,128 125,120 160,118 195,112 230,108 265,102 300,98 335,95" fill="none" stroke="#9ca3af" stroke-width="2" stroke-dasharray="4 3"/>' +
            '</svg>' +
            '<div class="xirr-row" style="gap:1.25rem"><span style="display:inline-flex;align-items:center;gap:.4rem;font-size:13px;color:var(--muted)"><span style="width:18px;height:2px;background:#2f6f3e;display:inline-block"></span>Your Progress</span>' +
            '<span style="display:inline-flex;align-items:center;gap:.4rem;font-size:13px;color:var(--muted)"><span style="width:18px;height:2px;background:#9ca3af;display:inline-block"></span>Organization Average</span></div>' +
            '</div>' +
            '<div class="xirr-card" style="margin-bottom:0"><h4>Key Takeaway</h4>' +
            '<p class="xirr-muted">You have shown consistent improvement throughout the year with the strongest growth in Adaptability and Communication.</p>' +
            '</div></div>';
    };
})();
JS;
}
