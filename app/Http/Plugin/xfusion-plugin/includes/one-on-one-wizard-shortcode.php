<?php
/**
 * XFusion — 1-on-1 Alignment Capture™ Interactive Tool (UI shell)
 *
 * Usage: [fusion_one_on_one_wizard]
 *
 * Visual-only prototype of the 6-step wizard described in the FUSION 1-on-1
 * Framework docs (see app/Http/Plugin/1. 1-1/*.png for the reference mockups).
 * All data shown is static/dummy — no backend calls, no persistence. Once the
 * layout is approved this gets wired to real endpoints step by step.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfusion_one_on_one_wizard_shortcode(): string
{
    ob_start();
    ?>
<div id="xfoo-wiz">

    <!-- Header -->
    <div class="xfw-header">
        <div class="xfw-header-inner">
            <div>
                <h1>1-ON-1 ALIGNMENT CAPTURE&trade; INTERACTIVE TOOL</h1>
                <p>Continuous Alignment Process</p>
            </div>
            <div class="xfw-header-actions">
                <button class="xfw-btn xfw-btn-outline-white" id="xfw-save-draft">Save Draft</button>
                <button class="xfw-btn xfw-btn-accent" id="xfw-next-step">Next Step &rarr;</button>
            </div>
        </div>
    </div>

    <!-- Step indicator -->
    <div class="xfw-steps">
        <div class="xfw-steps-inner" id="xfw-steps-inner">
            <!-- filled by JS -->
        </div>
    </div>

    <!-- Body -->
    <div class="xfw-body">
        <div class="xfw-main" id="xfw-main">
            <!-- filled by JS per step -->
        </div>

        <aside class="xfw-sidebar">
            <div class="xfw-card">
                <h4>Meeting Information</h4>
                <dl class="xfw-dl">
                    <dt>Employee</dt><dd>Michael Wilson</dd>
                    <dt>Leader</dt><dd>James Scott</dd>
                    <dt>Group</dt><dd>Operations</dd>
                    <dt>Meeting Date</dt><dd>May 14, 2025</dd>
                    <dt>Meeting Time</dt><dd>9:00 AM</dd>
                    <dt>Recurrence</dt><dd>Bi-Weekly</dd>
                    <dt>Status</dt><dd><span class="xfw-badge amber">Draft</span></dd>
                </dl>
            </div>

            <div class="xfw-card">
                <h4>About This Step</h4>
                <p class="xfw-muted" id="xfw-about-step">This step ensures both participants enter the conversation with the most relevant and comprehensive context available.</p>
            </div>

            <div class="xfw-card">
                <h4>Progress</h4>
                <div class="xfw-row" style="justify-content:space-between">
                    <span class="xfw-muted" id="xfw-progress-label">Step 1 of 6</span>
                    <span class="xfw-muted" id="xfw-progress-pct">17%</span>
                </div>
                <div class="xfw-progress-track"><div class="xfw-progress-fill" id="xfw-progress-fill" style="width:17%"></div></div>
                <p class="xfw-muted" style="margin-top:.6rem">Estimated Completion<br><strong>25 &ndash; 40 minutes</strong></p>
            </div>

            <div class="xfw-card">
                <h4>Have a question?</h4>
                <p class="xfw-muted">Learn more about how this step works in 1-on-1 Alignment Capture&trade;.</p>
                <a href="#" class="xfw-link">View Help Article &rarr;</a>
            </div>
        </aside>
    </div>

    <!-- Footer nav -->
    <div class="xfw-footer">
        <button class="xfw-btn xfw-btn-outline" id="xfw-prev-step">&larr; Previous Step</button>
        <span class="xfw-muted xfw-autosave">&#10003; Draft autosaved 10:32 AM</span>
        <div class="xfw-row">
            <button class="xfw-btn xfw-btn-outline" id="xfw-save-draft-2">Save Draft</button>
            <button class="xfw-btn xfw-btn-accent" id="xfw-next-step-2">Next Step &rarr;</button>
        </div>
    </div>
</div>

<style>
#xfoo-wiz{--navy:#1e2a52;--navy-dark:#141d3d;--green:#5f9a3f;--green-light:#7cb356;--ink:#1f2937;--muted:#6b7280;--border:#e5e7eb;--bg:#f7f8fa;
    max-width:1440px;margin:0 auto;font-family:inherit;color:var(--ink);background:var(--bg);border-radius:.5rem;overflow:hidden;border:1px solid var(--border)}

/* Header */
.xfw-header{background:linear-gradient(120deg,var(--navy-dark) 0%,var(--navy) 55%,var(--green) 140%);padding:1.5rem 1.75rem;color:#fff}
.xfw-header-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.xfw-header h1{margin:0;font-size:1.5rem;letter-spacing:.02em;font-weight:800}
.xfw-header p{margin:.25rem 0 0;opacity:.85;font-size:.9rem}
.xfw-header-actions{display:flex;gap:.6rem;flex-shrink:0}

/* Buttons */
.xfw-btn{cursor:pointer;border-radius:.375rem;padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;border:1px solid transparent;white-space:nowrap}
.xfw-btn-outline-white{background:transparent;border-color:rgba(255,255,255,.5);color:#fff}
.xfw-btn-outline-white:hover{background:rgba(255,255,255,.1)}
.xfw-btn-accent{background:var(--green);border-color:var(--green);color:#fff}
.xfw-btn-accent:hover{background:var(--green-light)}
.xfw-btn-outline{background:#fff;border-color:var(--border);color:var(--ink)}
.xfw-btn-outline:hover{background:#f3f4f6}
.xfw-btn:disabled{opacity:.45;cursor:default}

/* Step indicator */
.xfw-steps{background:#fff;border-bottom:1px solid var(--border);padding:1.5rem 1.75rem .5rem}
.xfw-steps-inner{display:flex;align-items:flex-start;justify-content:space-between;position:relative;max-width:1100px;margin:0 auto}
.xfw-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;cursor:pointer}
.xfw-step .xfw-step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;border:2px solid var(--border);color:var(--muted);background:#fff;z-index:1}
.xfw-step.done .xfw-step-circle{background:#e9f5e1;border-color:var(--green);color:var(--green)}
.xfw-step.active .xfw-step-circle{background:var(--green);border-color:var(--green);color:#fff}
.xfw-step .xfw-step-label{margin-top:.5rem;font-size:.72rem;font-weight:700;text-align:center;color:var(--muted);max-width:110px;line-height:1.2}
.xfw-step.active .xfw-step-label,.xfw-step.done .xfw-step-label{color:var(--navy)}
.xfw-step-line{position:absolute;top:17px;left:50%;width:100%;height:2px;background:var(--border);z-index:0}
.xfw-step.done .xfw-step-line{background:var(--green)}
.xfw-step:last-child .xfw-step-line{display:none}
.xfw-step-underline{height:3px;background:var(--green);margin-top:.75rem;border-radius:2px}

/* Layout */
.xfw-body{display:flex;gap:1.5rem;padding:1.5rem 1.75rem;align-items:flex-start}
.xfw-main{flex:1;min-width:0}
.xfw-sidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:1rem}
@media (max-width:1100px){.xfw-body{flex-direction:column}.xfw-sidebar{width:100%}}

/* Cards */
.xfw-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem;margin-bottom:1rem}
.xfw-card h4{margin:0 0 .6rem;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:var(--navy);font-weight:800}
.xfw-card h3{margin:0 0 .4rem;font-size:.95rem;color:var(--navy)}
.xfw-muted{color:var(--muted);font-size:.82rem;line-height:1.5}
.xfw-row{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}

.xfw-dl{margin:0;font-size:.85rem}
.xfw-dl dt{color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;margin-top:.6rem}
.xfw-dl dt:first-child{margin-top:0}
.xfw-dl dd{margin:.15rem 0 0;font-weight:600;color:var(--ink)}

.xfw-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600}
.xfw-badge.amber{background:#fef3c7;color:#92400e}
.xfw-badge.green{background:#dcfce7;color:#166534}

.xfw-progress-track{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-top:.5rem}
.xfw-progress-fill{height:100%;background:var(--green);border-radius:999px}

.xfw-link{color:var(--green);font-size:.82rem;font-weight:600;text-decoration:none}
.xfw-link:hover{text-decoration:underline}

/* Info banner */
.xfw-banner{background:#eef4fc;border:1px solid #bfdbfe;color:#1e3a5f;border-radius:.5rem;padding:.85rem 1rem;font-size:.85rem;margin-bottom:1.25rem;display:flex;gap:.6rem;align-items:flex-start}
.xfw-banner.warn{background:#fff8e6;border-color:#fde68a;color:#7c5b00}
.xfw-banner b{display:block;margin-bottom:.1rem}

/* Evidence list (step 1) */
.xfw-evidence{border:1px solid var(--border);border-radius:.5rem;overflow:hidden}
.xfw-evidence-row{display:flex;align-items:center;gap:.85rem;padding:.85rem 1rem;border-bottom:1px solid var(--border)}
.xfw-evidence-row:last-child{border-bottom:none}
.xfw-evidence-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.xfw-evidence-title{font-weight:700;font-size:.88rem}
.xfw-evidence-desc{color:var(--muted);font-size:.78rem}
.xfw-evidence-status{margin-left:auto;color:var(--green);font-size:.8rem;font-weight:600;white-space:nowrap}

/* Insight grid (step 2 / step 6) */
.xfw-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media (max-width:760px){.xfw-grid-2{grid-template-columns:1fr}}
.xfw-insight-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem}
.xfw-insight-card .icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:.6rem}
.xfw-insight-card ul{margin:.4rem 0 0;padding-left:1.1rem;font-size:.83rem;color:var(--ink)}
.xfw-insight-card li{margin-bottom:.3rem}

/* Numbered list (discussion areas / suggested topics) */
.xfw-numbered{list-style:none;margin:0;padding:0}
.xfw-numbered li{display:flex;gap:.7rem;align-items:flex-start;margin-bottom:.55rem;font-size:.85rem}
.xfw-numbered .n{width:20px;height:20px;border-radius:50%;background:var(--green);color:#fff;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.05rem}

/* Preparation form (step 3) */
.xfw-prep-col h3{color:var(--green)}
.xfw-prep-col.leader h3{color:var(--navy)}
.xfw-scale-q{margin-bottom:1.1rem}
.xfw-scale-q label{font-weight:700;font-size:.82rem;display:block;margin-bottom:.15rem}
.xfw-scale-q .q-desc{color:var(--muted);font-size:.78rem;margin-bottom:.4rem}
.xfw-scale{display:flex;gap:.4rem}
.xfw-scale-btn{flex:1;text-align:center;padding:.4rem 0;border:1px solid var(--border);border-radius:.375rem;font-size:.85rem;font-weight:600;color:var(--ink);cursor:pointer;background:#fff}
.xfw-scale-btn.selected.employee{background:var(--green);border-color:var(--green);color:#fff}
.xfw-scale-btn.selected.leader{background:var(--navy);border-color:var(--navy);color:#fff}
.xfw-scale-labels{display:flex;justify-content:space-between;font-size:.7rem;color:var(--muted);margin-top:.2rem}
.xfw-textarea-field{margin-bottom:.9rem}
.xfw-textarea-field label{font-weight:700;font-size:.8rem;display:flex;justify-content:space-between}
.xfw-textarea-field label .count{font-weight:400;color:var(--muted);font-size:.72rem}
.xfw-textarea-field textarea{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.5rem .65rem;font-size:.82rem;margin-top:.3rem;box-sizing:border-box;font-family:inherit;resize:vertical}

/* Conversation guide (step 4) */
.xfw-guide-row{display:flex;gap:.85rem;padding:1rem 0;border-bottom:1px solid var(--border)}
.xfw-guide-row:last-child{border-bottom:none}
.xfw-guide-icon{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.xfw-guide-title{font-weight:700;font-size:.88rem;margin-bottom:.15rem}
.xfw-guide-desc{color:var(--muted);font-size:.8rem}
.xfw-guide-notes{margin-left:auto;flex-shrink:0;color:var(--muted);font-size:.8rem;white-space:nowrap;align-self:flex-start}

/* Commitments table (step 5) */
.xfw-commit-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem}
table.xfw-table{width:100%;border-collapse:collapse;font-size:.8rem}
table.xfw-table th{text-align:left;padding:.5rem .6rem;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border);font-size:.72rem;text-transform:uppercase}
table.xfw-table td{padding:.6rem;border-bottom:1px solid var(--border);vertical-align:middle}
.xfw-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:.35rem}
.xfw-dot.high{background:#ea580c}
.xfw-dot.medium{background:#ca8a04}
.xfw-dot.low{background:#2563eb}

/* Footer nav */
.xfw-footer{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.75rem;border-top:1px solid var(--border);background:#fff;flex-wrap:wrap;gap:.75rem}
.xfw-autosave{color:#16a34a}

/* Utility */
.xfw-hidden{display:none !important}
.xfw-section-title{font-size:1.05rem;font-weight:800;color:var(--navy);margin:0 0 .3rem}
.xfw-section-desc{color:var(--muted);font-size:.85rem;margin:0 0 1rem}
</style>

<script>
(function () {
    var STEPS = [
        { key: 'evidence',     label: 'Generate\nContinuous Evidence™', title: 'Step 1. Generate Continuous Evidence™' },
        { key: 'brief',        label: 'AI Meeting\nBrief™',              title: 'Step 2. AI Meeting Brief™' },
        { key: 'preparation',  label: 'Shared\nPreparation™',            title: 'Step 3. Shared Preparation™' },
        { key: 'conversation', label: 'Alignment\nConversation™',        title: 'Step 4. Alignment Conversation™' },
        { key: 'commitments',  label: 'Shared\nCommitments™',            title: 'Step 5. Shared Commitments™' },
        { key: 'synthesis',    label: 'AI Meeting\nSynthesis™',          title: 'Step 6. AI Meeting Synthesis™' },
    ];

    var ABOUT = [
        'This step ensures both participants enter the conversation with the most relevant and comprehensive context available. The system automatically gathers evidence so you can focus on the conversation, not the preparation.',
        'The AI Meeting Brief™ gives both participants a clear understanding of what matters most. Use these insights to prepare, reflect, and set the stage for a productive conversation. You will not see each other\'s preparation until Step 3.',
        'Preparation helps both participants enter the conversation with clarity and purpose. Take a few minutes to reflect honestly — open preparation leads to stronger alignment and better decisions.',
        'This is the heart of the 1-on-1. Use the guide to have a focused, open, and honest conversation. Take notes as needed, but spend most of your time listening and engaging.',
        'Shared Commitments™ turn conversation into action. These commitments appear in your next meeting and help track progress over time.',
        'AI Meeting Synthesis™ turns your conversation into clarity and action. This synthesis becomes the official record of your meeting and helps both participants stay aligned, accountable, and focused.',
    ];

    var root = document.getElementById('xfoo-wiz');
    if (!root) return;
    var current = 0;

    function renderSteps() {
        var el = root.querySelector('#xfw-steps-inner');
        el.innerHTML = STEPS.map(function (s, i) {
            var cls = i === current ? 'active' : (i < current ? 'done' : '');
            var label = s.label.split('\n').join('<br>');
            return '<div class="xfw-step ' + cls + '" data-step="' + i + '">' +
                '<div class="xfw-step-line"></div>' +
                '<div class="xfw-step-circle">' + (i < current ? '&#10003;' : (i + 1)) + '</div>' +
                '<div class="xfw-step-label">' + label + '</div>' +
                (i === current ? '<div class="xfw-step-underline" style="width:100%"></div>' : '') +
                '</div>';
        }).join('');
        el.querySelectorAll('[data-step]').forEach(function (n) {
            n.addEventListener('click', function () { goTo(parseInt(n.dataset.step, 10)); });
        });
    }

    function renderSidebar() {
        root.querySelector('#xfw-about-step').textContent = ABOUT[current];
        root.querySelector('#xfw-progress-label').textContent = 'Step ' + (current + 1) + ' of 6';
        var pct = Math.round(((current + 1) / 6) * 100);
        root.querySelector('#xfw-progress-pct').textContent = pct + '%';
        root.querySelector('#xfw-progress-fill').style.width = pct + '%';
    }

    function renderMain() {
        var main = root.querySelector('#xfw-main');
        main.innerHTML = PANELS[STEPS[current].key]();
        var prevBtn = root.querySelector('#xfw-prev-step');
        prevBtn.disabled = current === 0;
        var isLast = current === STEPS.length - 1;
        ['#xfw-next-step', '#xfw-next-step-2'].forEach(function (sel) {
            var btn = root.querySelector(sel);
            btn.textContent = isLast ? 'Complete Meeting' : 'Next Step →';
        });

        // Wire up interactive bits scoped to this render only
        main.querySelectorAll('.xfw-scale-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('.xfw-scale');
                group.querySelectorAll('.xfw-scale-btn').forEach(function (b) { b.classList.remove('selected'); });
                btn.classList.add('selected');
            });
        });
        main.querySelectorAll('textarea[data-maxlen]').forEach(function (t) {
            var counter = t.closest('.xfw-textarea-field').querySelector('.count');
            var max = parseInt(t.dataset.maxlen, 10);
            t.addEventListener('input', function () {
                counter.textContent = t.value.length + ' / ' + max;
            });
        });
    }

    function goTo(i) {
        current = Math.max(0, Math.min(STEPS.length - 1, i));
        renderSteps();
        renderSidebar();
        renderMain();
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    root.querySelector('#xfw-next-step').addEventListener('click', function () { goTo(current + 1); });
    root.querySelector('#xfw-next-step-2').addEventListener('click', function () { goTo(current + 1); });
    root.querySelector('#xfw-prev-step').addEventListener('click', function () { goTo(current - 1); });

    // -------------------------------------------------------------------
    // Per-step panel builders — all data below is static/dummy placeholder
    // -------------------------------------------------------------------
    var PANELS = {

        evidence: function () {
            var rows = [
                ['#e9f5e1;color:#3f7d1f', '\u{1F465}', 'Previous 1-on-1', 'Summary, commitments, and key themes from your last conversation.'],
                ['#f1e9fb;color:#7c3aed', '\u{1F4CB}', 'Previous Commitments', 'Open commitments, progress updates, and completion history.'],
                ['#e0f2f7;color:#0891b2', '\u{1F464}', 'Individual Insights™', 'Behavioral Driver trends, AI insights, and development themes.'],
                ['#fde8d7;color:#ea580c', '✅', 'Activities', 'Recent learning activities and engagement.'],
                ['#dbeafe;color:#2563eb', '\u{1F4CA}', 'Self-Assessments', 'Recent self-assessments and behavioral metrics.'],
                ['#e9f5e1;color:#3f7d1f', '\u{1F527}', 'Development Tools', 'Tools completed and insights generated.'],
                ['#f1e9fb;color:#7c3aed', '\u{1F4C8}', 'Behavioral Driver Trends', 'Current trends across the 5 FUSION Behavioral Drivers.'],
                ['#fde8d7;color:#ea580c', '\u{1F4A1}', 'AI Insight Trends', 'AI-generated insights and observed patterns over time.'],
                ['#dbeafe;color:#2563eb', '\u{1F3AF}', 'QBR Priorities', 'Current Quarterly Business Review™ priorities and progress.'],
                ['#e9f5e1;color:#3f7d1f', '\u{1F6A9}', 'ARP Priorities', 'Annual Readiness Plan™ priorities and strategic context.'],
                ['#f1e9fb;color:#7c3aed', '⭐', 'Previous 360 Review™', 'Most recent 360 feedback themes and insights.'],
                ['#fde8d7;color:#ea580c', '\u{1F3E2}', 'Organizational Context', 'Role, team, organizational goals, and readiness priorities.'],
            ];
            return '<h2 class="xfw-section-title">Step 1. Generate Continuous Evidence™</h2>' +
                '<p class="xfw-section-desc">FUSION automatically assembles the most current context and evidence to support a productive 1-on-1 conversation. This evidence is used to prepare both participants and strengthen alignment.</p>' +
                '<div class="xfw-banner">ℹ️ <span>This evidence is read-only. It is generated from across the platform and cannot be edited.</span></div>' +
                '<div class="xfw-card" style="padding:0">' +
                '<div class="xfw-evidence">' + rows.map(function (r) {
                    var bg = r[0].split(';')[0].replace('#e9f5e1;color:#3f7d1f','').trim();
                    return '<div class="xfw-evidence-row">' +
                        '<div class="xfw-evidence-icon" style="background:' + r[0].split(';')[0] + ';color:' + r[0].split(';')[1].replace('color:','') + '">' + r[1] + '</div>' +
                        '<div><div class="xfw-evidence-title">' + r[2] + '</div><div class="xfw-evidence-desc">' + r[3] + '</div></div>' +
                        '<div class="xfw-evidence-status">&#10003; Up to date</div>' +
                        '</div>';
                }).join('') + '</div></div>' +
                '<p class="xfw-muted" style="margin-top:1rem">&#10003; All evidence is current through May 14, 2025 10:32 AM</p>';
        },

        brief: function () {
            function card(color, icon, title, items) {
                return '<div class="xfw-insight-card"><div class="icon" style="background:' + color + '22;color:' + color + '">' + icon + '</div>' +
                    '<h3>' + title + '</h3><ul>' + items.map(function (i) { return '<li>' + i + '</li>'; }).join('') + '</ul>' +
                    '<a href="#" class="xfw-link">View Details &rarr;</a></div>';
            }
            var discussion = [
                'Review progress on critical QBR priorities and related commitments.',
                'Discuss current obstacle impacting Project Phoenix timeline.',
                'Explore support needed for upcoming cross-functional initiative.',
                'Talk about development focus: strategic communication.',
                'Align on expectations for upcoming customer launch.',
            ];
            return '<h2 class="xfw-section-title">Step 2. AI Meeting Brief™</h2>' +
                '<p class="xfw-section-desc">FUSION AI analyzes your continuous evidence and prepares insights to help both participants have a more meaningful and productive conversation.</p>' +
                '<div class="xfw-banner">ℹ️ <span>This brief is AI-generated and read-only. Use these insights to guide your conversation. <b>The AI prepares &mdash; people converse.</b></span></div>' +
                '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
                card('#16a34a', '\u{1F3AF}', 'Alignment Snapshot™', ['Priorities show strong alignment with QBR and ARP strategic themes.', 'Employee is making steady progress on key commitments.', 'Development focus aligns with role expectations.', 'Overall alignment rating trending upward over last 3 meetings.']) +
                card('#7c3aed', '\u{1F464}', 'Development Snapshot™', ['Strengths in problem solving and cross-functional collaboration.', 'Growth opportunity in strategic communication.', 'Recent learning activities support current development goals.', 'Development momentum is on track.']) +
                '</div>' +
                '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
                card('#ea580c', '\u{1F4CB}', 'Commitment Review™', ['6 active commitments (3 by employee, 3 by leader).', '83% of commitments are on track.', '2 commitments due within the next 2 weeks.', 'No overdue commitments.']) +
                card('#0891b2', '\u{1F4C8}', 'Behavioral Trends™', ['Foster Grit trending up over last 60 days.', 'Be Intentional remains strong and consistent.', 'Drive Growth shows improvement in goal-setting behavior.', 'Insights based on activities, reflections, and assessments.']) +
                '</div>' +
                '<div class="xfw-card" style="margin-bottom:1rem">' +
                '<h3>Suggested Discussion Areas™</h3>' +
                '<ol class="xfw-numbered">' + discussion.map(function (d, i) { return '<li><span class="n">' + (i + 1) + '</span>' + d + '</li>'; }).join('') + '</ol>' +
                '<a href="#" class="xfw-link">View Details &rarr;</a>' +
                '</div>' +
                '<div class="xfw-grid-2">' +
                card('#ca8a04', '\u{1F4A1}', 'Emerging Opportunities™', ['Opportunity to lead cross-departmental planning session.', 'Potential to expand influence with stakeholder communication.', 'New stretch assignment aligns with career goals.']) +
                card('#dc2626', '⚠️', 'Potential Barriers™', ['Competing priorities may impact commitment completion.', 'Resource constraints noted in recent reflections.', 'Communication bottlenecks with other teams.']) +
                '</div>';
        },

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
                '<div class="xfw-section-label" style="margin-top:1rem;font-weight:800;color:var(--navy);font-size:.8rem">Open Reflections</div>' +
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
                '<div class="xfw-section-label" style="margin-top:1rem;font-weight:800;color:var(--navy);font-size:.8rem">Open Reflections</div>' +
                textField('Coaching topics to discuss', 1000) +
                textField('Organizational updates to share', 1000) +
                textField('Top discussion priorities', 1000) +
                '</div>' +
                '</div>' +
                '<div class="xfw-banner warn" style="margin-top:1rem">&#128274; <span><b>Your preparation is private</b>Neither participant\'s preparation is visible to the other until Step 4 (Alignment Conversation™). This ensures an open, honest, and productive conversation.</span></div>';
        },

        conversation: function () {
            var items = [
                ['#dbeafe;color:#2563eb', '\u{1F3AF}', '1. Current Priorities', 'Discuss current priorities, alignment with team and organizational goals, and any shifts or new focus areas.'],
                ['#dcfce7;color:#16a34a', '\u{1F4C8}', '2. Progress', 'Reflect on progress since the last meeting. What\'s working well and what impact is being made?'],
                ['#f1e9fb;color:#7c3aed', '⚠️', '3. Barriers', 'Explore obstacles or challenges that may be impacting performance or progress.'],
                ['#fde8d7;color:#ea580c', '\u{1F464}', '4. Development', 'Discuss growth opportunities, skill development, and experiences that will support future success.'],
                ['#e0f2f7;color:#0891b2', '\u{1F91D}', '5. Support', 'Talk about the support needed and how leadership can better enable success.'],
                ['#e9f5e1;color:#3f7d1f', '⭐', '6. Future Opportunities', 'Look ahead. What opportunities exist for growth, impact, or advancement?'],
            ];
            return '<h2 class="xfw-section-title">Step 4. Alignment Conversation™</h2>' +
                '<p class="xfw-section-desc">This is your conversation. Use the insights, ratings, and preparation to guide a meaningful discussion. Focus on listening, understanding, and aligning. The system is here to support your dialogue.</p>' +
                '<div class="xfw-banner">ℹ️ <span>This section is for conversation, not documentation. Use the prompts below to guide your discussion. Add notes only when needed to capture key takeaways or action items.</span></div>' +
                '<div class="xfw-card">' +
                '<h3 style="margin-bottom:.5rem">Conversation Guide</h3>' +
                items.map(function (it) {
                    return '<div class="xfw-guide-row">' +
                        '<div class="xfw-guide-icon" style="background:' + it[0].split(';')[0] + ';color:' + it[0].split(';')[1].replace('color:','') + '">' + it[1] + '</div>' +
                        '<div><div class="xfw-guide-title">' + it[2] + '</div><div class="xfw-guide-desc">' + it[3] + '</div></div>' +
                        '<div class="xfw-guide-notes">&#128172; Notes (optional) &#9662;</div>' +
                        '</div>';
                }).join('') +
                '</div>' +
                '<div class="xfw-card" style="margin-top:1rem">' +
                '<h3>Conversation Notes <span class="xfw-muted" style="font-weight:400">(private to both participants)</span></h3>' +
                '<textarea rows="4" placeholder="Capture key discussion points, insights, or follow-up items here..." style="width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.6rem;font-size:.85rem;box-sizing:border-box;font-family:inherit"></textarea>' +
                '<p class="xfw-muted" style="margin-top:.4rem">&#128274; These notes are private to both participants and become part of the meeting record.</p>' +
                '</div>';
        },

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
        },

        synthesis: function () {
            function card(color, icon, title, body) {
                return '<div class="xfw-insight-card"><div class="icon" style="background:' + color + '22;color:' + color + '">' + icon + '</div>' +
                    '<h3>' + title + '</h3>' + body +
                    '<a href="#" class="xfw-link">View Details &rarr;</a></div>';
            }
            return '<h2 class="xfw-section-title">Step 6. AI Meeting Synthesis™</h2>' +
                '<p class="xfw-section-desc">FUSION analyzes the conversation, preparation, commitments, and historical trends to create a comprehensive meeting record and actionable insights for both participants.</p>' +
                '<div class="xfw-banner">ℹ️ <span>This summary is AI-generated and based on the data and discussion from this 1-on-1 conversation. Review and reflect on the insights together.</span></div>' +
                '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
                card('#2563eb', '\u{1F4CB}', 'Meeting Summary™', '<ul><li>Strong alignment on QBR priorities</li><li>Progress made on Project Phoenix</li><li>Roadblocks identified in data access</li><li>Development plan for strategic communication</li></ul>') +
                card('#16a34a', '\u{1F3AF}', 'Alignment Summary™',
                    '<p class="xfw-muted">How aligned both participants are on priorities, goals, and expectations.</p>' +
                    '<div style="font-size:1.6rem;font-weight:800;color:var(--green)">4.2<span style="font-size:1rem;color:var(--muted);font-weight:400"> / 5</span></div>' +
                    '<div class="xfw-muted">Aligned</div>' +
                    '<div class="xfw-progress-track" style="margin:.5rem 0"><div class="xfw-progress-fill" style="width:84%"></div></div>') +
                '</div>' +
                '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
                card('#7c3aed', '\u{1F464}', 'Development Summary™', '<ul><li>Strategic communication skills</li><li>Cross-functional collaboration</li><li>Data-driven decision making</li><li>Leadership presence in stakeholder updates</li></ul>') +
                card('#ea580c', '\u{1F4CB}', 'Commitment Summary™', '<ul><li>Employee Commitments: <b>3 active</b></li><li>Leader Commitments: <b>3 active</b></li><li>Open Commitments: <b>6 total</b></li></ul>') +
                '</div>' +
                '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
                card('#dc2626', '⚠️', 'Emerging Risks™', '<ul><li>Data access delays could impact timeline</li><li>Competing priorities may affect capacity</li><li>Communication bottlenecks with other teams</li></ul>') +
                card('#16a34a', '\u{1F4C8}', 'Emerging Opportunities™', '<ul><li>Expand stakeholder communication plan</li><li>Implement micro-learning strategy</li><li>Leverage cross-functional planning session</li></ul>') +
                '</div>' +
                '<div class="xfw-card" style="margin-bottom:1rem">' +
                '<h3>Suggested Coaching Topics™</h3>' +
                '<div class="xfw-row">' + ['Strategic Communication', 'Stakeholder Management', 'Data-Driven Decisions', 'Career Growth Planning'].map(function (t) {
                    return '<span class="xfw-badge" style="background:#eef2ff;color:#4338ca">' + t + '</span>';
                }).join('') + '</div>' +
                '</div>' +
                '<div class="xfw-card">' +
                '<h3>Recommended Follow-up™</h3>' +
                '<ul class="xfw-numbered" style="list-style:none"><li>&#9989; Check-in on data access resolution by May 28</li><li>&#128101; Schedule cross-departmental planning session by June 4</li><li>&#128196; Review communication strategy progress in next 1-on-1</li></ul>' +
                '</div>';
        },
    };

    renderSteps();
    renderSidebar();
    renderMain();
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_one_on_one_wizard', 'xfusion_one_on_one_wizard_shortcode');
