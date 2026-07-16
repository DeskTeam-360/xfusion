<?php
/**
 * CSS for the Annual Readiness Plan™ wizard shortcode.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_styles_css(): string
{
    return <<<'CSS'
#xfarp-wiz{--navy:#1e2a52;--navy-dark:#141d3d;--green:#5f9a3f;--green-light:#7cb356;--ink:#1f2937;--muted:#6b7280;--border:#e5e7eb;--bg:#f7f8fa;
    max-width:1440px;margin:0 auto;font-family:inherit;font-size:18px;color:var(--ink);background:var(--bg);border-radius:.5rem;overflow:hidden;border:1px solid var(--border)}
#xfarp-wiz h1{font-size:40px}
#xfarp-wiz h2{font-size:32px}
#xfarp-wiz h3{font-size:30px}
#xfarp-wiz h4{font-size:28px}
#xfarp-wiz p{font-size:18px}

/* Header */
.xar-header{background:linear-gradient(120deg,var(--navy-dark) 0%,var(--navy) 55%,var(--green) 140%);padding:1.5rem 1.75rem;color:#fff}
.xar-header-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.xar-header h1{margin:0;letter-spacing:.02em;font-weight:600}
.xar-header p{margin:.25rem 0 0;opacity:.85}
.xar-header-actions{display:flex;gap:.6rem;flex-shrink:0}

/* Buttons */
.xar-btn{cursor:pointer;border-radius:.375rem;padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;border:1px solid transparent;white-space:nowrap}
.xar-btn-outline-white{background:transparent;border-color:rgba(255,255,255,.5);color:#fff}
.xar-btn-outline-white:hover{background:rgba(255,255,255,.1)}
.xar-btn-accent{background:var(--green);border-color:var(--green);color:#fff}
.xar-btn-accent:hover{background:var(--green-light)}
.xar-btn-outline{background:#fff;border-color:var(--border);color:var(--ink)}
.xar-btn-outline:hover{background:#f3f4f6}
.xar-btn:disabled{opacity:.45;cursor:default}

/* Step indicator */
.xar-steps{background:#fff;border-bottom:1px solid var(--border);padding:1.5rem 1.75rem .5rem}
.xar-steps-inner{display:flex;align-items:flex-start;justify-content:space-between;position:relative;max-width:96%;margin:0 auto}
.xar-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;cursor:pointer}
.xar-step .xar-step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;border:2px solid var(--border);color:var(--muted);background:#fff;z-index:1}
.xar-step.done .xar-step-circle{background:#e9f5e1;border-color:var(--green);color:var(--green)}
.xar-step.active .xar-step-circle{background:var(--green);border-color:var(--green);color:#fff}
.xar-step .xar-step-label{margin-top:.5rem;font-size:13px;font-weight:700;text-align:center;color:var(--muted);max-width:95%;line-height:1.25}
.xar-step.active .xar-step-label,.xar-step.done .xar-step-label{color:var(--navy)}
.xar-step-line{position:absolute;top:17px;left:50%;width:100%;height:2px;background:var(--border);z-index:0}
.xar-step.done .xar-step-line{background:var(--green)}
.xar-step:last-child .xar-step-line{display:none}
.xar-step-underline{height:3px;background:var(--green);margin-top:.75rem;border-radius:2px}

/* Layout */
.xar-body{display:flex;gap:1.5rem;padding:1.5rem 1.75rem;align-items:flex-start}
.xar-main{flex:1;min-width:0}
.xar-sidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:1rem}

/* Cards */
.xar-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem;margin-bottom:1rem}
.xar-card h4{margin:0 0 .6rem;text-transform:uppercase;letter-spacing:.04em;color:var(--navy);font-weight:500}
.xar-about-step{display:flex;gap:.85rem;align-items:flex-start}
.xar-about-step-icon{width:50px;height:50px;flex-shrink:0;display:block;object-fit:contain;margin-top:.15rem}
.xar-about-step-body{min-width:0;flex:1}
.xar-about-step-body .xar-muted{margin:0 0 .65rem}
.xar-about-step-body .xar-muted:last-child{margin-bottom:0}
.xar-progress-row{display:flex;align-items:center;gap:.65rem}
.xar-progress-row .xar-progress-track{flex:1;margin-top:0}
.xar-progress-row .xar-progress-pct{flex-shrink:0;font-weight:600;color:var(--navy)}
.xar-muted{color:var(--muted);line-height:1.5}
.xar-row{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}

.xar-dl{margin:0;font-size:16px}
.xar-dl dt{color:var(--muted);font-size:16px;text-transform:uppercase;letter-spacing:.03em;margin-top:.6rem}
.xar-dl dt:first-child{margin-top:0}
.xar-dl dd{margin:.15rem 0 0;font-weight:600;color:var(--ink);font-size:16px}
.xar-dl .xar-badge{font-size:16px}

.xar-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:16px;font-weight:600}
.xar-badge.amber{background:#fef3c7;color:#92400e}
.xar-badge.green{background:#dcfce7;color:#166534}

.xar-progress-track{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-top:.5rem}
.xar-progress-fill{height:100%;background:var(--green);border-radius:999px}

.xar-link{color:var(--green);font-size:16px;font-weight:600;text-decoration:none}
.xar-link:hover{text-decoration:underline}

/* ARP form fields */
.xar-field{margin-bottom:1.35rem}
.xar-field-head{display:flex;justify-content:space-between;align-items:baseline;gap:1rem;margin-bottom:.25rem}
.xar-field-label{font-weight:800;font-size:18px;color:var(--navy);text-transform:uppercase;letter-spacing:.02em;margin:0}
.xar-field-count{font-weight:400;color:var(--muted);font-size:.8rem;white-space:nowrap}
.xar-field-desc{color:var(--muted);font-size:16px;margin:0 0 .45rem;line-height:1.45}
.xar-field textarea{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.65rem .75rem;font-size:16px;box-sizing:border-box;font-family:inherit;resize:vertical;min-height:5.5rem;background:#fff;color:var(--ink)}
.xar-field textarea:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
.xar-field-ai{display:flex;justify-content:flex-end;margin-top:.45rem}
.xar-ai-assist{display:inline-flex;align-items:center;gap:.35rem;background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:13px;font-weight:700;font-family:inherit;letter-spacing:.04em;text-transform:uppercase}
.xar-ai-assist:hover{text-decoration:underline}
.xar-ai-assist img{width:18px;height:18px;display:block;object-fit:contain}

/* Form controls (shared) */
.xar-input{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.5rem .65rem;font-size:15px;box-sizing:border-box;font-family:inherit;background:#fff;color:var(--ink)}
.xar-input:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
textarea.xar-input{resize:vertical;min-height:4.5rem;line-height:1.45}
select.xar-input{appearance:auto;cursor:pointer}
select.xar-input[multiple]{min-height:4.75rem;padding:.35rem}
.xar-form-field{min-width:0}
.xar-form-field label{display:block;font-weight:700;font-size:13px;color:var(--navy);margin-bottom:.35rem}
.xar-req{color:#dc2626}

/* Priority / strategic cards */
.xar-add-row{display:flex;justify-content:flex-start;margin:0 0 1rem}
.xar-add-link{background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:15px;font-weight:700;font-family:inherit;text-decoration:none}
.xar-add-link:hover{text-decoration:underline}
.xar-prio-list{display:flex;flex-direction:column;gap:1rem}
.xar-prio-card{display:flex;gap:.85rem;align-items:stretch;background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.15rem;position:relative}
.xar-prio-rail{display:flex;flex-direction:column;align-items:center;gap:.45rem;padding-top:.15rem;flex-shrink:0}
.xar-drag{color:var(--muted);font-size:14px;letter-spacing:-2px;line-height:1;cursor:grab;user-select:none}
.xar-prio-num{width:30px;height:30px;border-radius:50%;background:transparent;border:2px solid var(--navy);color:var(--navy);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;box-sizing:border-box}
.xar-prio-body{flex:1;min-width:0;position:relative;padding-right:1.75rem}
.xar-prio-grid{display:grid;gap:.85rem 1rem;margin-bottom:.85rem}
.xar-prio-grid:last-child{margin-bottom:0}
.xar-prio-grid-1{grid-template-columns:1fr}
.xar-prio-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.xar-icon-btn{position:absolute;top:0;right:0;width:32px;height:32px;border:none;background:transparent;color:var(--muted);cursor:pointer;border-radius:.375rem;font-size:1.1rem;line-height:1;padding:0;display:flex;align-items:center;justify-content:center}
.xar-icon-btn:hover{color:#b91c1c;background:#fef2f2}
.xar-prio-delete{background:transparent !important;color:#87B14B !important;text-decoration:none !important}
.xar-prio-delete:hover{background:transparent !important;color:#E1706D !important;text-decoration:none !important}
.xar-prio-delete img{display:block;width:18px;height:18px;object-fit:contain}
.xar-owner-field{display:flex;align-items:center;gap:.5rem}
.xar-owner-field .xar-input{flex:1}
.xar-avatar{width:32px;height:32px;border-radius:50%;background:var(--navy);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}

/* Info icon on field labels */
.xar-info-icon{display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;border:1.5px solid var(--muted);color:var(--muted);font-size:10px;font-weight:700;font-style:normal;vertical-align:middle;margin-left:.25rem;text-transform:none;letter-spacing:0}

/* Info banner */
.xar-banner{background:#eef4fc;border:1px solid #bfdbfe;color:#1e3a5f;border-radius:.5rem;padding:.85rem 1rem;font-size:.9rem;margin-bottom:1.25rem;display:flex;gap:.65rem;align-items:flex-start;line-height:1.45}
.xar-banner.warn{background:#fff8e6;border-color:#fde68a;color:#7c5b00}
.xar-banner b{font-weight:700}
.xar-banner-icon{flex-shrink:0;line-height:1.2}
.xar-btn-sm{padding:.35rem .75rem;font-size:.75rem}

/* Publish step */
.xar-summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:.85rem 1.5rem;margin:0}
.xar-summary-item{margin:0}
.xar-summary-item dt{color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.03em;margin:0 0 .2rem}
.xar-summary-item dd{margin:0;font-weight:700;color:var(--ink);font-size:16px}
.xar-review-list{display:flex;flex-direction:column}
.xar-review-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--border)}
.xar-review-row:last-child{border-bottom:none;padding-bottom:0}
.xar-review-row:first-child{padding-top:0}
.xar-review-left{display:flex;align-items:flex-start;gap:.75rem;min-width:0}
.xar-review-check{width:28px;height:28px;border-radius:50%;background:#e9f5e1;color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
.xar-review-status{color:var(--green);font-size:14px;font-weight:600;margin-top:.15rem}
.xar-activate-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
.xar-activate-card{border:1px solid var(--border);border-radius:.5rem;padding:1rem;background:#fafafa}
.xar-activate-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xar-activate-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xar-activate-card p{margin:0;font-size:14px}
.xar-action-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
.xar-action-card{border:1px solid var(--border);border-radius:.5rem;padding:1.1rem;background:#fff;display:flex;flex-direction:column;align-items:flex-start}
.xar-action-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xar-action-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xar-action-card p{margin:0 0 1rem;font-size:14px;flex:1}
.xar-action-card .xar-btn{width:100%;text-align:center}

/* AI Review dashboard */
.xar-ai-block{margin-bottom:1rem}
.xar-ai-heading{margin:0 0 1rem;font-size:1.1rem;font-weight:800;color:var(--navy)}
.xar-ai-split{display:grid;grid-template-columns:180px 1fr;gap:1.5rem;align-items:center}
.xar-ai-copy p{margin:0 0 .85rem;line-height:1.5}
.xar-donut-wrap{display:flex;flex-direction:column;align-items:center;width:150px;margin:0 auto}
.xar-donut-chart{position:relative;width:150px;height:150px}
.xar-donut{width:150px;height:150px;transform:rotate(-90deg)}
.xar-donut-track{fill:none;stroke:#e5e7eb;stroke-width:3.2}
.xar-donut-value{fill:none;stroke-width:3.2;stroke-linecap:round}
.xar-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;pointer-events:none}
.xar-donut-score{font-size:1.35rem;font-weight:800;color:var(--navy);line-height:1.1}
.xar-donut-score span{font-size:.75rem;font-weight:600;color:var(--muted)}
.xar-donut-label{margin-top:.5rem;font-size:.85rem;font-weight:600;color:var(--muted);text-align:center;line-height:1.2}
.xar-check-list{list-style:none;margin:0;padding:0}
.xar-check-list li{display:flex;gap:.55rem;align-items:flex-start;margin-bottom:.45rem;font-size:16px;line-height:1.4;color:var(--ink)}
.xar-check{width:20px;height:20px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;margin-top:.1rem}
.xar-stat-list{display:flex;flex-direction:column;gap:.55rem}
.xar-stat-row{display:flex;align-items:center;gap:.55rem;font-size:16px}
.xar-stat-row strong{margin-left:auto;color:var(--navy)}
.xar-dot{width:10px;height:10px;border-radius:50%;display:inline-block;flex-shrink:0}
.xar-dot.green{background:#16a34a}
.xar-dot.amber{background:#ca8a04}
.xar-dot.red{background:#dc2626}
.xar-table-scroll{overflow-x:auto}
table.xar-table{width:100%;border-collapse:collapse;font-size:15px}
table.xar-table th{text-align:left;padding:.65rem .75rem;color:var(--muted);font-weight:700;border-bottom:1px solid var(--border);font-size:.75rem;text-transform:uppercase;letter-spacing:.03em}
table.xar-table td{padding:.85rem .75rem;border-bottom:1px solid var(--border);vertical-align:top}
table.xar-table tr:last-child td{border-bottom:none}
table.xar-table.xar-table-gaps{border:1px solid var(--border);border-radius:.35rem;overflow:hidden}
table.xar-table.xar-table-gaps th,
table.xar-table.xar-table-gaps td{border-right:1px solid var(--border)}
table.xar-table.xar-table-gaps th:last-child,
table.xar-table.xar-table-gaps td:last-child{border-right:none}
table.xar-table.xar-table-gaps th{background:#f8fafc;color:var(--navy)}
table.xar-table.xar-table-gaps .xar-gap-desc-col{width:42%}
table.xar-table.xar-table-gaps .xar-gap-area{width:18%;color:var(--navy)}
table.xar-table.xar-table-gaps .xar-gap-impact,
table.xar-table.xar-table-gaps .xar-gap-priority{width:12%;white-space:nowrap}
.xar-gap-area strong{font-weight:700}
.xar-gap-desc{font-size:15px;font-weight:400;color:var(--ink);line-height:1.45}
.xar-impact{display:inline-flex;align-items:center;gap:.4rem;font-weight:600;font-size:14px}
.xar-impact .xar-dot{width:8px;height:8px}
.xar-impact.high .xar-dot{background:#dc2626}
.xar-impact.medium .xar-dot{background:#ca8a04}
.xar-impact.low .xar-dot{background:#2563eb}
.xar-badge-pill{display:inline-block;padding:.2rem .65rem;border-radius:999px;font-size:12px;font-weight:700}
.xar-badge-pill.high{background:#fee2e2;color:#991b1b}
.xar-badge-pill.medium{background:#fef3c7;color:#92400e}
.xar-badge-pill.low{background:#dbeafe;color:#1e40af}
.xar-align-list{display:flex;flex-direction:column;gap:.85rem}
.xar-align-row.xar-progress-row{display:grid;grid-template-columns:minmax(0,12rem) 1fr 2.75rem;gap:.75rem;align-items:center;margin-bottom:0}
.xar-align-label{font-size:15px;color:var(--ink);line-height:1.35}
.xar-align-row.xar-progress-row .xar-progress-track{height:10px;margin-top:0}
.xar-align-row.xar-progress-row .xar-progress-pct{font-size:15px;font-weight:700;color:var(--navy);text-align:right}
.xar-risk-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem}
.xar-risk-card{display:flex;gap:.65rem;align-items:flex-start;border:1px solid var(--border);border-radius:.5rem;padding:.85rem;background:#fafafa}
.xar-risk-card p{margin:.2rem 0 0;font-size:13px}
.xar-risk-title{font-size:15px;color:var(--navy)}
.xar-risk-icon{flex-shrink:0;display:flex;align-items:center;justify-content:center}
.xar-risk-card.high .xar-risk-icon{color:#dc2626}
.xar-risk-card.medium .xar-risk-icon{color:#ea580c}
.xar-risk-card.low .xar-risk-icon{color:#ca8a04}
.xar-risk-card.strength .xar-risk-icon{color:#16a34a}
.xar-focus-list{display:flex;flex-direction:column;gap:.75rem}
.xar-focus-item{display:flex;align-items:flex-start;gap:.75rem;font-size:16px;line-height:1.45;color:var(--ink)}
.xar-focus-item img{flex-shrink:0;width:36px;height:36px;object-fit:contain}

/* Footer nav */
.xar-footer{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.75rem;border-top:1px solid var(--border);background:#fff;flex-wrap:wrap;gap:.75rem}
.xar-autosave{color:#16a34a;display:inline-flex;align-items:center;gap:.4rem}
.xar-autosave-check{width:18px;height:18px;border-radius:50%;background:#16a34a;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0}

/* Utility */
.xar-hidden{display:none !important}
.xar-section-title{font-weight:800;color:var(--navy);margin:0 0 .3rem}
.xar-section-desc{color:var(--muted);margin:0 0 1.25rem}
.xar-placeholder{padding:2rem 1rem;text-align:center}

/* ── Tablet (≤1024px) ── */
@media (max-width:1024px){
#xfarp-wiz h1{font-size:32px}
#xfarp-wiz h2{font-size:26px}
#xfarp-wiz h3{font-size:24px}
#xfarp-wiz h4{font-size:22px}
.xar-header,.xar-steps,.xar-body,.xar-footer{padding-left:1.25rem;padding-right:1.25rem}
.xar-body{flex-direction:column}
.xar-sidebar{width:100%}
.xar-steps{overflow-x:hidden}
.xar-steps-inner{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem .75rem;max-width:100%;min-width:0;padding:0;justify-items:center}
.xar-step{flex:none;min-width:0;width:100%;max-width:150px}
.xar-step .xar-step-circle{width:30px;height:30px;font-size:14px}
.xar-step-line{display:none}
.xar-step .xar-step-label{font-size:11px;max-width:100%;line-height:1.25}
.xar-step-underline{width:100%!important;margin-top:.5rem}
.xar-prio-grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}
.xar-ai-split{grid-template-columns:1fr;justify-items:center}
.xar-ai-copy{width:100%}
.xar-risk-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xar-activate-grid,.xar-action-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xar-summary-grid{grid-template-columns:1fr}
}

/* ── Mobile (≤768px) ── */
@media (max-width:768px){
#xfarp-wiz{font-size:16px;border-radius:0;border-left:none;border-right:none}
#xfarp-wiz h1{font-size:26px;line-height:1.15}
#xfarp-wiz h2{font-size:22px;line-height:1.2}
#xfarp-wiz h3{font-size:20px}
#xfarp-wiz h4{font-size:20px}
#xfarp-wiz p{font-size:16px}
.xar-header{padding:1.25rem 1rem}
.xar-header-inner{flex-direction:column;align-items:stretch}
.xar-header-actions{width:100%}
.xar-header-actions .xar-btn{flex:1;text-align:center}
.xar-steps{padding:1rem 1rem .75rem}
.xar-steps-inner{grid-template-columns:repeat(3,1fr);gap:1rem .5rem}
.xar-step{max-width:140px}
.xar-step .xar-step-circle{width:28px;height:28px;font-size:13px}
.xar-step .xar-step-label{font-size:10px}
.xar-body{padding:1rem;gap:1rem}
.xar-card{padding:1rem;margin-bottom:.75rem}
.xar-footer{flex-direction:column;align-items:stretch;padding:1rem;gap:.75rem}
.xar-footer>.xar-btn{width:100%;text-align:center}
.xar-footer .xar-row{width:100%;flex-direction:column}
.xar-footer .xar-row .xar-btn{width:100%;text-align:center}
.xar-autosave{text-align:center;font-size:14px;justify-content:center}
.xar-btn{padding:.65rem 1rem;font-size:15px;white-space:normal}
.xar-dl,.xar-dl dt,.xar-dl dd,.xar-dl .xar-badge{font-size:15px}
.xar-field-label{font-size:16px}
.xar-field-desc,.xar-field textarea{font-size:15px}
.xar-prio-card{flex-direction:column;padding:1rem}
.xar-prio-rail{flex-direction:row;justify-content:flex-start;gap:.65rem}
.xar-prio-body{padding-right:0;padding-top:.25rem}
.xar-prio-grid-4{grid-template-columns:1fr}
.xar-icon-btn{top:-.25rem;right:-.25rem}
.xar-risk-grid{grid-template-columns:1fr}
.xar-donut-wrap{width:130px}
.xar-donut-chart,.xar-donut{width:130px;height:130px}
.xar-activate-grid,.xar-action-grid{grid-template-columns:1fr}
.xar-review-row{flex-wrap:wrap}
}

/* ── Small mobile (≤480px) ── */
@media (max-width:480px){
#xfarp-wiz h1{font-size:22px}
#xfarp-wiz h2{font-size:20px}
.xar-header-actions{flex-direction:column}
.xar-header-actions .xar-btn{width:100%}
.xar-steps-inner{grid-template-columns:repeat(2,1fr);gap:1rem .5rem}
.xar-step{max-width:none}
.xar-step .xar-step-circle{width:26px;height:26px;font-size:12px}
.xar-step .xar-step-label{font-size:10px}
}
CSS;
}
