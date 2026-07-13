<?php
/**
 * CSS for the 1-on-1 wizard shortcode. Kept as a plain string function so it
 * can be edited independently of the PHP/JS logic.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_styles_css(): string
{
    return <<<'CSS'
#xfoo-wiz{--navy:#1e2a52;--navy-dark:#141d3d;--green:#5f9a3f;--green-light:#7cb356;--ink:#1f2937;--muted:#6b7280;--border:#e5e7eb;--bg:#f7f8fa;
    max-width:1440px;margin:0 auto;font-family:inherit;font-size:18px;color:var(--ink);background:var(--bg);border-radius:.5rem;overflow:hidden;border:1px solid var(--border)}
#xfoo-wiz h1{font-size:40px}
#xfoo-wiz h2{font-size:32px}
#xfoo-wiz h3{font-size:30px}
#xfoo-wiz h4{font-size:28px}
#xfoo-wiz p{font-size:18px}

/* Header */
.xfw-header{background:linear-gradient(120deg,var(--navy-dark) 0%,var(--navy) 55%,var(--green) 140%);padding:1.5rem 1.75rem;color:#fff}
.xfw-header-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.xfw-header h1{margin:0;letter-spacing:.02em;font-weight:600}
.xfw-header p{margin:.25rem 0 0;opacity:.85}
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
.xfw-steps-inner{display:flex;align-items:flex-start;justify-content:space-between;position:relative;max-width:90%;margin:0 auto}
.xfw-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;cursor:pointer}
.xfw-step .xfw-step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;border:2px solid var(--border);color:var(--muted);background:#fff;z-index:1}
.xfw-step.done .xfw-step-circle{background:#e9f5e1;border-color:var(--green);color:var(--green)}
.xfw-step.active .xfw-step-circle{background:var(--green);border-color:var(--green);color:#fff}
.xfw-step .xfw-step-label{margin-top:.5rem;font-size:16px;font-weight:700;text-align:center;color:var(--muted);max-width:90%;line-height:1.2}
.xfw-step.active .xfw-step-label,.xfw-step.done .xfw-step-label{color:var(--navy)}
.xfw-step-line{position:absolute;top:17px;left:50%;width:100%;height:2px;background:var(--border);z-index:0}
.xfw-step.done .xfw-step-line{background:var(--green)}
.xfw-step:last-child .xfw-step-line{display:none}
.xfw-step-underline{height:3px;background:var(--green);margin-top:.75rem;border-radius:2px}

/* Layout */
.xfw-body{display:flex;gap:1.5rem;padding:1.5rem 1.75rem;align-items:flex-start}
.xfw-main{flex:1;min-width:0}
.xfw-sidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:1rem}

/* Cards */
.xfw-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem;margin-bottom:1rem}
.xfw-card h4{margin:0 0 .6rem;text-transform:uppercase;letter-spacing:.04em;color:var(--navy);font-weight:500}
.xfw-card h3{margin:0 0 .4rem;font-size:.95rem;color:var(--navy)}
.xfw-muted{color:var(--muted);line-height:1.5}
.xfw-row{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}

.xfw-dl{margin:0;font-size:16px}
.xfw-dl dt{color:var(--muted);font-size:16px;text-transform:uppercase;letter-spacing:.03em;margin-top:.6rem}
.xfw-dl dt:first-child{margin-top:0}
.xfw-dl dd{margin:.15rem 0 0;font-weight:600;color:var(--ink);font-size:16px}
.xfw-dl .xfw-badge{font-size:16px}

.xfw-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:16px;font-weight:600}
.xfw-badge.amber{background:#fef3c7;color:#92400e}
.xfw-badge.green{background:#dcfce7;color:#166534}

.xfw-progress-track{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-top:.5rem}
.xfw-progress-fill{height:100%;background:var(--green);border-radius:999px}

.xfw-link{color:var(--green);font-size:18px;font-weight:600;text-decoration:none}
.xfw-link:hover{text-decoration:underline}

/* Info banner */
.xfw-banner{background:#eef4fc;border:1px solid #bfdbfe;color:#1e3a5f;border-radius:.5rem;padding:.85rem 1rem;font-size:.85rem;margin-bottom:1.25rem;display:flex;gap:.6rem;align-items:flex-start}
.xfw-banner.warn{background:#fff8e6;border-color:#fde68a;color:#7c5b00}
.xfw-banner b{display:block;margin-bottom:.1rem}

/* Evidence list (step 1) */
.xfw-evidence{border:1px solid var(--border);border-radius:.5rem;overflow:hidden}
.xfw-evidence-row{display:flex;align-items:center;gap:.85rem;padding:.85rem 1rem;border-bottom:1px solid var(--border)}
.xfw-evidence-row:last-child{border-bottom:none}
.xfw-evidence-icon{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.xfw-evidence-title{font-weight:700;font-size:24px}
.xfw-evidence-desc{color:var(--muted)}
.xfw-evidence-status{margin-left:auto;color:var(--green);font-size:18px;font-weight:600;white-space:nowrap}

/* Insight grid (step 2 / step 6) */
.xfw-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.xfw-insight-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem}
.xfw-insight-card .icon{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:.6rem}
.xfw-insight-card ul{margin:.4rem 0 0;padding-left:1.1rem;font-size:.83rem;color:var(--ink)}
.xfw-insight-card li{margin-bottom:.3rem;font-size:18px;}

/* Numbered list (discussion areas / suggested topics) */
.xfw-numbered{list-style:none;margin:0;padding:0}
.xfw-numbered li{display:flex;gap:.7rem;align-items:flex-start;margin-bottom:.55rem;font-size:18px}
.xfw-numbered .n{width:20px;height:20px;border-radius:50%;background:var(--green);color:#fff;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.05rem}

/* Preparation form (step 3) */
.xfw-prep-col h3{color:var(--green)}
.xfw-prep-col.leader h3{color:var(--navy)}
.xfw-scale-q{margin-bottom:1.1rem}
.xfw-scale-q label{font-weight:700;font-size:16px;display:block;margin-bottom:.15rem}
.xfw-scale-q .q-desc{color:var(--muted);font-size:16px;margin-bottom:.4rem}
.xfw-scale{display:flex;gap:.4rem}
.xfw-scale-btn{flex:1;text-align:center;padding:.4rem 0;border:1px solid var(--border);border-radius:.375rem;font-size:.85rem;font-weight:600;color:var(--ink);cursor:pointer;background:#fff}
.xfw-scale-btn.selected.employee{background:var(--green);border-color:var(--green);color:#fff}
.xfw-scale-btn.selected.leader{background:var(--navy);border-color:var(--navy);color:#fff}
.xfw-scale-labels{display:flex;justify-content:space-between;font-size:.7rem;color:var(--muted);margin-top:.2rem}
.xfw-textarea-field{margin-bottom:.9rem}
.xfw-textarea-field label{font-weight:700;font-size:16px;display:flex;justify-content:space-between}
.xfw-textarea-field label .count{font-weight:400;color:var(--muted);font-size:.72rem}
.xfw-textarea-field textarea{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.5rem .65rem;font-size:16px;margin-top:.3rem;box-sizing:border-box;font-family:inherit;resize:vertical}

/* Conversation guide (step 4) */
.xfw-guide-row{display:flex;gap:.85rem;padding:1rem 0;border-bottom:1px solid var(--border)}
.xfw-guide-row:last-child{border-bottom:none}
.xfw-guide-icon{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.xfw-guide-title{font-weight:700;font-size:18px;margin-bottom:.15rem}
.xfw-guide-desc{color:var(--muted);font-size:16px}
.xfw-guide-notes{margin-left:auto;flex-shrink:0;color:var(--muted);font-size:16px;white-space:nowrap;align-self:flex-start}

/* Commitments table (step 5) */
.xfw-commit-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem}
table.xfw-table{width:100%;border-collapse:collapse;font-size:.8rem;min-width:640px}
table.xfw-table th{text-align:left;padding:.5rem .6rem;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border);font-size:.72rem;text-transform:uppercase}
table.xfw-table td{padding:.6rem;border-bottom:1px solid var(--border);vertical-align:middle;font-size:.70rem;}
.xfw-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:.35rem}
.xfw-dot.high{background:#ea580c}
.xfw-dot.medium{background:#ca8a04}
.xfw-dot.low{background:#2563eb}

/* Footer nav */
.xfw-footer{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.75rem;border-top:1px solid var(--border);background:#fff;flex-wrap:wrap;gap:.75rem}
.xfw-autosave{color:#16a34a}

/* Utility */
.xfw-hidden{display:none !important}
.xfw-section-title{font-weight:800;color:var(--navy);margin:0 0 .3rem}
.xfw-section-desc{color:var(--muted);margin:0 0 1rem}
.xfw-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;margin:0 -.25rem;padding:0 .25rem}

/* ── Tablet (≤1024px) ── */
@media (max-width:1024px){
#xfoo-wiz h1{font-size:32px}
#xfoo-wiz h2{font-size:26px}
#xfoo-wiz h3{font-size:24px}
#xfoo-wiz h4{font-size:22px}
.xfw-evidence-title{font-size:20px}
.xfw-header,.xfw-steps,.xfw-body,.xfw-footer{padding-left:1.25rem;padding-right:1.25rem}
.xfw-body{flex-direction:column}
.xfw-sidebar{width:100%}
.xfw-steps{overflow-x:hidden}
.xfw-steps-inner{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem .75rem;max-width:100%;min-width:0;padding:0;justify-items:center}
.xfw-step{flex:none;min-width:0;width:100%;max-width:160px}
.xfw-step .xfw-step-circle{width:30px;height:30px;font-size:14px}
.xfw-step-line{display:none}
.xfw-step .xfw-step-label{font-size:12px;max-width:100%;line-height:1.25}
.xfw-step-underline{width:100%!important;margin-top:.5rem}
.xfw-guide-notes{white-space:normal}
}

/* ── Mobile (≤768px) ── */
@media (max-width:768px){
#xfoo-wiz{font-size:16px;border-radius:0;border-left:none;border-right:none}
#xfoo-wiz h1{font-size:26px;line-height:1.15}
#xfoo-wiz h2{font-size:22px;line-height:1.2}
#xfoo-wiz h3{font-size:20px}
#xfoo-wiz h4{font-size:20px}
#xfoo-wiz p{font-size:16px}
.xfw-evidence-title{font-size:18px}
.xfw-evidence-status{font-size:16px}
.xfw-header{padding:1.25rem 1rem}
.xfw-header-inner{flex-direction:column;align-items:stretch}
.xfw-header-actions{width:100%}
.xfw-header-actions .xfw-btn{flex:1;text-align:center}
.xfw-steps{padding:1rem 1rem .75rem}
.xfw-steps-inner{gap:1rem .5rem}
.xfw-step{max-width:140px}
.xfw-step .xfw-step-circle{width:28px;height:28px;font-size:13px}
.xfw-step .xfw-step-label{font-size:11px}
.xfw-body{padding:1rem;gap:1rem}
.xfw-grid-2{grid-template-columns:1fr}
.xfw-card{padding:1rem;margin-bottom:.75rem}
.xfw-evidence-row{flex-wrap:wrap;align-items:flex-start;padding:.75rem}
.xfw-evidence-status{margin-left:0;width:100%;padding-left:calc(36px + .85rem);margin-top:.35rem;white-space:normal}
.xfw-guide-row{flex-wrap:wrap;padding:.75rem 0}
.xfw-guide-notes{margin-left:0;width:100%;margin-top:.5rem;white-space:normal}
.xfw-commit-head{flex-direction:column;align-items:stretch;gap:.5rem}
.xfw-commit-head .xfw-btn{width:100%;text-align:center}
.xfw-footer{flex-direction:column;align-items:stretch;padding:1rem;gap:.75rem}
.xfw-footer>.xfw-btn{width:100%;text-align:center}
.xfw-footer .xfw-row{width:100%;flex-direction:column}
.xfw-footer .xfw-row .xfw-btn{width:100%;text-align:center}
.xfw-autosave{text-align:center;font-size:14px}
.xfw-banner{font-size:15px;padding:.75rem}
.xfw-btn{padding:.65rem 1rem;font-size:15px;white-space:normal}
.xfw-dl,.xfw-dl dt,.xfw-dl dd,.xfw-dl .xfw-badge{font-size:15px}
.xfw-link{font-size:16px}
.xfw-numbered li,.xfw-insight-card li{font-size:16px}
.xfw-guide-title{font-size:16px}
.xfw-guide-desc,.xfw-scale-q label,.xfw-scale-q .q-desc,.xfw-textarea-field label{font-size:15px}
.xfw-textarea-field textarea{font-size:15px}
}

/* ── Small mobile (≤480px) ── */
@media (max-width:480px){
#xfoo-wiz h1{font-size:22px}
#xfoo-wiz h2{font-size:20px}
.xfw-header-actions{flex-direction:column}
.xfw-header-actions .xfw-btn{width:100%}
.xfw-steps-inner{grid-template-columns:repeat(2,1fr);gap:1rem .5rem}
.xfw-step{max-width:none}
.xfw-step .xfw-step-circle{width:26px;height:26px;font-size:12px}
.xfw-step .xfw-step-label{font-size:10px}
.xfw-scale{gap:.25rem}
.xfw-scale-btn{padding:.5rem 0;font-size:14px;min-width:0}
.xfw-evidence-icon{width:46px;height:46px}
.xfw-evidence-status{padding-left:calc(32px + .85rem)}
.xfw-numbered .n{width:22px;height:22px;font-size:11px}
}
CSS;
}
