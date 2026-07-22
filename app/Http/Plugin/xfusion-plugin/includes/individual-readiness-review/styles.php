<?php
/**
 * CSS for the Individual Readiness Review™ wizard shortcode.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfirr_wizard_styles_css(): string
{
    return <<<'CSS'
#xfirr-wiz{--navy:#1e2a52;--navy-dark:#141d3d;--green:#5f9a3f;--green-light:#7cb356;--ink:#1f2937;--muted:#6b7280;--border:#e5e7eb;--bg:#f7f8fa;
    max-width:1440px;margin:0 auto;font-family:inherit;font-size:18px;color:var(--ink);background:var(--bg);border-radius:.5rem;overflow:hidden;border:1px solid var(--border)}
#xfirr-wiz h1{font-size:40px}
#xfirr-wiz h2{font-size:32px}
#xfirr-wiz h3{font-size:30px}
#xfirr-wiz h4{font-size:28px}
#xfirr-wiz p{font-size:18px}

/* Loading state */
.xirr-spinner-row{display:flex;align-items:center;gap:.6rem;padding:1rem 0;color:var(--muted);font-size:16px}
.xirr-spinner{width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--green);border-radius:50%;display:inline-block;animation:xirr-spin .7s linear infinite;flex-shrink:0}
@keyframes xirr-spin{to{transform:rotate(360deg)}}

/* View-only mode (non-leader members) — CSS-based so it survives any
   re-render from Step 3/4's async data load without re-applying JS. */
#xfirr-wiz[data-view-only="1"] #xirr-main input,
#xfirr-wiz[data-view-only="1"] #xirr-main textarea,
#xfirr-wiz[data-view-only="1"] #xirr-main select{
    pointer-events:none;background:#f3f4f6;opacity:.85}
#xfirr-wiz[data-view-only="1"] #xirr-main button,
#xfirr-wiz[data-view-only="1"] #xirr-main .xirr-add-link,
#xfirr-wiz[data-view-only="1"] #xirr-main .xirr-prio-delete,
#xfirr-wiz[data-view-only="1"] #xirr-main a.xirr-icon-btn{
    display:none !important}

/* Header */
.xirr-header{background:linear-gradient(120deg,var(--navy-dark) 0%,var(--navy) 55%,var(--green) 140%);padding:1.5rem 1.75rem;color:#fff}
.xirr-header-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.xirr-header h1{margin:0;letter-spacing:.02em;font-weight:600}
.xirr-header p{margin:.25rem 0 0;opacity:.85}
.xirr-header-actions{display:flex;gap:.6rem;flex-shrink:0}

/* Buttons */
.xirr-btn{cursor:pointer;border-radius:.375rem;padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;border:1px solid transparent;white-space:nowrap}
.xirr-btn-outline-white{background:transparent;border-color:rgba(255,255,255,.5);color:#fff}
.xirr-btn-outline-white:hover{background:rgba(255,255,255,.1)}
.xirr-btn-accent{background:var(--green);border-color:var(--green);color:#fff}
.xirr-btn-accent:hover{background:var(--green-light)}
.xirr-btn-outline{background:#fff;border-color:var(--border);color:var(--ink)}
.xirr-btn-outline:hover{background:#f3f4f6}
.xirr-btn:disabled{opacity:.45;cursor:default}

/* Step indicator */
.xirr-steps{background:#fff;border-bottom:1px solid var(--border);padding:1.5rem 1.75rem .5rem}
.xirr-steps-inner{display:flex;align-items:flex-start;justify-content:space-between;position:relative;max-width:96%;margin:0 auto}
.xirr-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;cursor:pointer}
.xirr-step .xirr-step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;border:2px solid var(--border);color:var(--muted);background:#fff;z-index:1}
.xirr-step.done .xirr-step-circle{background:#e9f5e1;border-color:var(--green);color:var(--green)}
.xirr-step.active .xirr-step-circle{background:var(--green);border-color:var(--green);color:#fff}
.xirr-step .xirr-step-label{margin-top:.5rem;font-size:13px;font-weight:700;text-align:center;color:var(--muted);max-width:95%;line-height:1.25}
.xirr-step.active .xirr-step-label,.xirr-step.done .xirr-step-label{color:var(--navy)}
.xirr-step-line{position:absolute;top:17px;left:50%;width:100%;height:2px;background:var(--border);z-index:0}
.xirr-step.done .xirr-step-line{background:var(--green)}
.xirr-step:last-child .xirr-step-line{display:none}
.xirr-step-underline{height:3px;background:var(--green);margin-top:.75rem;border-radius:2px}

/* Layout */
.xirr-body{display:flex;gap:1.5rem;padding:1.5rem 1.75rem;align-items:flex-start}
.xirr-main{flex:1;min-width:0}
.xirr-sidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:1rem}

/* Cards */
.xirr-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem;margin-bottom:1rem}
.xirr-card h4{margin:0 0 .6rem;text-transform:uppercase;letter-spacing:.04em;color:var(--navy);font-weight:500}
.xirr-about-step{display:flex;gap:.85rem;align-items:flex-start}
.xirr-about-step-icon{width:50px;height:50px;flex-shrink:0;display:block;object-fit:contain;margin-top:.15rem}
.xirr-about-step-body{min-width:0;flex:1}
.xirr-about-step-body .xirr-muted{margin:0 0 .65rem}
.xirr-about-step-body .xirr-muted:last-child{margin-bottom:0}
.xirr-progress-row{display:flex;align-items:center;gap:.65rem}
.xirr-progress-row .xirr-progress-track{flex:1;margin-top:0}
.xirr-progress-row .xirr-progress-pct{flex-shrink:0;font-weight:600;color:var(--navy)}
.xirr-muted{color:var(--muted);line-height:1.5}
.xirr-row{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}

.xirr-dl{margin:0;font-size:16px}
.xirr-dl dt{color:var(--muted);font-size:16px;text-transform:uppercase;letter-spacing:.03em;margin-top:.6rem}
.xirr-dl dt:first-child{margin-top:0}
.xirr-dl dd{margin:.15rem 0 0;font-weight:600;color:var(--ink);font-size:16px}
.xirr-dl .xirr-badge{font-size:16px}

.xirr-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:16px;font-weight:600}
.xirr-badge.amber{background:#fef3c7;color:#92400e}
.xirr-badge.green{background:#dcfce7;color:#166534}

.xirr-progress-track{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-top:.5rem}
.xirr-progress-fill{height:100%;background:var(--green);border-radius:999px}

.xirr-link{color:var(--green);font-size:16px;font-weight:600;text-decoration:none}
.xirr-link:hover{text-decoration:underline}

/* ARP form fields */
.xirr-field{margin-bottom:1.35rem}
.xirr-field-head{display:flex;justify-content:space-between;align-items:baseline;gap:1rem;margin-bottom:.25rem}
.xirr-field-label{font-weight:800;font-size:18px;color:var(--navy);text-transform:uppercase;letter-spacing:.02em;margin:0}
.xirr-field-count{font-weight:400;color:var(--muted);font-size:.8rem;white-space:nowrap}
.xirr-field-desc{color:var(--muted);font-size:16px;margin:0 0 .45rem;line-height:1.45}
.xirr-field textarea{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.65rem .75rem;font-size:16px;box-sizing:border-box;font-family:inherit;resize:vertical;min-height:5.5rem;background:#fff;color:var(--ink)}
.xirr-field textarea:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
.xirr-field-ai{display:flex;justify-content:flex-end;margin-top:.45rem}
.xirr-ai-assist{display:inline-flex;align-items:center;gap:.35rem;background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:13px;font-weight:700;font-family:inherit;letter-spacing:.04em;text-transform:uppercase}
.xirr-ai-assist:hover{text-decoration:underline}
.xirr-ai-assist img{width:18px;height:18px;display:block;object-fit:contain}

/* Form controls (shared) */
.xirr-input{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.5rem .65rem;font-size:15px;box-sizing:border-box;font-family:inherit;background:#fff;color:var(--ink)}
.xirr-input:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
textarea.xirr-input{resize:vertical;min-height:4.5rem;line-height:1.45}
select.xirr-input{appearance:auto;cursor:pointer}
select.xirr-input[multiple]{min-height:4.75rem;padding:.35rem}
.xirr-form-field{min-width:0}
.xirr-form-field label{display:block;font-weight:700;font-size:13px;color:var(--navy);margin-bottom:.35rem}
.xirr-req{color:#dc2626}

/* Priority / strategic cards */
.xirr-add-row{display:flex;justify-content:flex-start;margin:0 0 1rem}
.xirr-add-link{background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:15px;font-weight:700;font-family:inherit;text-decoration:none}
.xirr-add-link:hover{text-decoration:underline}
.xirr-prio-list{display:flex;flex-direction:column;gap:1rem}
.xirr-prio-card{display:flex;gap:.85rem;align-items:stretch;background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.15rem;position:relative}
.xirr-prio-rail{display:flex;flex-direction:column;align-items:center;gap:.45rem;padding-top:.15rem;flex-shrink:0}
.xirr-drag{color:var(--muted);font-size:14px;letter-spacing:-2px;line-height:1;cursor:grab;user-select:none}
.xirr-prio-num{width:30px;height:30px;border-radius:50%;background:transparent;border:2px solid var(--navy);color:var(--navy);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;box-sizing:border-box}
.xirr-prio-body{flex:1;min-width:0;position:relative;padding-right:1.75rem}
.xirr-prio-grid{display:grid;gap:.85rem 1rem;margin-bottom:.85rem}
.xirr-prio-grid:last-child{margin-bottom:0}
.xirr-prio-grid-1{grid-template-columns:1fr}
.xirr-prio-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.xirr-icon-btn{position:absolute;top:0;right:0;width:32px;height:32px;border:none;background:transparent;color:var(--muted);cursor:pointer;border-radius:.375rem;font-size:1.1rem;line-height:1;padding:0;display:flex;align-items:center;justify-content:center}
.xirr-icon-btn:hover{color:#b91c1c;background:#fef2f2}
.xirr-prio-delete{background:transparent !important;color:#87B14B !important;text-decoration:none !important}
.xirr-prio-delete:hover{background:transparent !important;color:#E1706D !important;text-decoration:none !important}
.xirr-prio-delete img{display:block;width:18px;height:18px;object-fit:contain}
.xirr-owner-field{display:flex;align-items:center;gap:.5rem}
.xirr-owner-field .xirr-input{flex:1}
.xirr-avatar{width:32px;height:32px;border-radius:50%;background:var(--navy);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}

/* Info icon on field labels */
.xirr-info-icon{display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;border:1.5px solid var(--muted);color:var(--muted);font-size:10px;font-weight:700;font-style:normal;vertical-align:middle;margin-left:.25rem;text-transform:none;letter-spacing:0}

/* Info banner */
.xirr-banner{background:#eef4fc;border:1px solid #bfdbfe;color:#1e3a5f;border-radius:.5rem;padding:.85rem 1rem;font-size:.9rem;margin-bottom:1.25rem;display:flex;gap:.65rem;align-items:flex-start;line-height:1.45}
.xirr-banner.warn{background:#fff8e6;border-color:#fde68a;color:#7c5b00}
.xirr-banner b{font-weight:700}
.xirr-banner-icon{flex-shrink:0;line-height:1.2}
.xirr-btn-sm{padding:.35rem .75rem;font-size:.75rem}

/* Publish step */
.xirr-summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:.85rem 1.5rem;margin:0}
.xirr-summary-item{margin:0}
.xirr-summary-item dt{color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.03em;margin:0 0 .2rem}
.xirr-summary-item dd{margin:0;font-weight:700;color:var(--ink);font-size:16px}
.xirr-review-list{display:flex;flex-direction:column}
.xirr-review-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--border)}
.xirr-review-row:last-child{border-bottom:none;padding-bottom:0}
.xirr-review-row:first-child{padding-top:0}
.xirr-review-left{display:flex;align-items:flex-start;gap:.75rem;min-width:0}
.xirr-review-check{width:28px;height:28px;border-radius:50%;background:#e9f5e1;color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
.xirr-review-status{color:var(--green);font-size:14px;font-weight:600;margin-top:.15rem}
.xirr-activate-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
.xirr-activate-card{border:1px solid var(--border);border-radius:.5rem;padding:1rem;background:#fafafa}
.xirr-activate-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xirr-activate-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xirr-activate-card p{margin:0;font-size:14px}
.xirr-action-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
.xirr-action-card{border:1px solid var(--border);border-radius:.5rem;padding:1.1rem;background:#fff;display:flex;flex-direction:column;align-items:flex-start}
.xirr-action-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xirr-action-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xirr-action-card p{margin:0 0 1rem;font-size:14px;flex:1}
.xirr-action-card .xirr-btn{width:100%;text-align:center}

/* AI Review dashboard */
.xirr-ai-block{margin-bottom:1rem}
.xirr-ai-heading{margin:0 0 1rem;font-size:1.1rem;font-weight:800;color:var(--navy)}
.xirr-ai-split{display:grid;grid-template-columns:180px 1fr;gap:1.5rem;align-items:center}
.xirr-ai-copy p{margin:0 0 .85rem;line-height:1.5}
.xirr-donut-wrap{display:flex;flex-direction:column;align-items:center;width:150px;margin:0 auto}
.xirr-donut-chart{position:relative;width:150px;height:150px}
.xirr-donut{width:150px;height:150px;transform:rotate(-90deg)}
.xirr-donut-track{fill:none;stroke:#e5e7eb;stroke-width:3.2}
.xirr-donut-value{fill:none;stroke-width:3.2;stroke-linecap:round}
.xirr-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;pointer-events:none}
.xirr-donut-score{font-size:1.35rem;font-weight:800;color:var(--navy);line-height:1.1}
.xirr-donut-score span{font-size:.75rem;font-weight:600;color:var(--muted)}
.xirr-donut-label{margin-top:.5rem;font-size:.85rem;font-weight:600;color:var(--muted);text-align:center;line-height:1.2}
.xirr-check-list{list-style:none;margin:0;padding:0}
.xirr-check-list li{display:flex;gap:.55rem;align-items:flex-start;margin-bottom:.45rem;font-size:16px;line-height:1.4;color:var(--ink)}
.xirr-check{width:20px;height:20px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;margin-top:.1rem}
.xirr-stat-list{display:flex;flex-direction:column;gap:.55rem}
.xirr-stat-row{display:flex;align-items:center;gap:.55rem;font-size:16px}
.xirr-stat-row strong{margin-left:auto;color:var(--navy)}
.xirr-dot{width:10px;height:10px;border-radius:50%;display:inline-block;flex-shrink:0}
.xirr-dot.green{background:#16a34a}
.xirr-dot.amber{background:#ca8a04}
.xirr-dot.red{background:#dc2626}
.xirr-table-scroll{overflow-x:auto}
table.xirr-table{width:100%;border-collapse:collapse;font-size:15px}
table.xirr-table th{text-align:left;padding:.65rem .75rem;color:var(--muted);font-weight:700;border-bottom:1px solid var(--border);font-size:.75rem;text-transform:uppercase;letter-spacing:.03em}
table.xirr-table td{padding:.85rem .75rem;border-bottom:1px solid var(--border);vertical-align:top}
table.xirr-table tr:last-child td{border-bottom:none}
table.xirr-table.xirr-table-gaps{border:1px solid var(--border);border-radius:.35rem;overflow:hidden}
table.xirr-table.xirr-table-gaps th,
table.xirr-table.xirr-table-gaps td{border-right:1px solid var(--border)}
table.xirr-table.xirr-table-gaps th:last-child,
table.xirr-table.xirr-table-gaps td:last-child{border-right:none}
table.xirr-table.xirr-table-gaps th{background:#f8fafc;color:var(--navy)}
table.xirr-table.xirr-table-gaps .xirr-gap-desc-col{width:42%}
table.xirr-table.xirr-table-gaps .xirr-gap-area{width:18%;color:var(--navy)}
table.xirr-table.xirr-table-gaps .xirr-gap-impact,
table.xirr-table.xirr-table-gaps .xirr-gap-priority{width:12%;white-space:nowrap}
.xirr-gap-area strong{font-weight:700}
.xirr-gap-desc{font-size:15px;font-weight:400;color:var(--ink);line-height:1.45}
.xirr-impact{display:inline-flex;align-items:center;gap:.4rem;font-weight:600;font-size:14px}
.xirr-impact .xirr-dot{width:8px;height:8px}
.xirr-impact.high .xirr-dot{background:#dc2626}
.xirr-impact.medium .xirr-dot{background:#ca8a04}
.xirr-impact.low .xirr-dot{background:#2563eb}
.xirr-badge-pill{display:inline-block;padding:.2rem .65rem;border-radius:999px;font-size:12px;font-weight:700}
.xirr-badge-pill.high{background:#fee2e2;color:#991b1b}
.xirr-badge-pill.medium{background:#fef3c7;color:#92400e}
.xirr-badge-pill.low{background:#dbeafe;color:#1e40af}
.xirr-align-list{display:flex;flex-direction:column;gap:.85rem}
.xirr-align-row.xirr-progress-row{display:grid;grid-template-columns:minmax(0,12rem) 1fr 2.75rem;gap:.75rem;align-items:center;margin-bottom:0}
.xirr-align-label{font-size:15px;color:var(--ink);line-height:1.35}
.xirr-align-row.xirr-progress-row .xirr-progress-track{height:10px;margin-top:0}
.xirr-align-row.xirr-progress-row .xirr-progress-pct{font-size:15px;font-weight:700;color:var(--navy);text-align:right}
.xirr-risk-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem}
.xirr-risk-card{display:flex;gap:.65rem;align-items:flex-start;border:1px solid var(--border);border-radius:.5rem;padding:.85rem;background:#fafafa}
.xirr-risk-card p{margin:.2rem 0 0;font-size:13px}
.xirr-risk-title{font-size:15px;color:var(--navy)}
.xirr-risk-icon{flex-shrink:0;display:flex;align-items:center;justify-content:center}
.xirr-risk-card.high .xirr-risk-icon{color:#dc2626}
.xirr-risk-card.medium .xirr-risk-icon{color:#ea580c}
.xirr-risk-card.low .xirr-risk-icon{color:#ca8a04}
.xirr-risk-card.strength .xirr-risk-icon{color:#16a34a}
.xirr-focus-list{display:flex;flex-direction:column;gap:.75rem}
.xirr-focus-item{display:flex;align-items:flex-start;gap:.75rem;font-size:16px;line-height:1.45;color:var(--ink)}
.xirr-focus-item img{flex-shrink:0;width:36px;height:36px;object-fit:contain}

/* Footer nav */
.xirr-footer{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.75rem;border-top:1px solid var(--border);background:#fff;flex-wrap:wrap;gap:.75rem}
.xirr-autosave{color:#16a34a;display:inline-flex;align-items:center;gap:.4rem}
.xirr-autosave-check{width:18px;height:18px;border-radius:50%;background:#16a34a;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0}

/* Evidence checklist (Step 1) */
.xirr-evidence-list{display:flex;flex-direction:column}
.xirr-evidence-row{display:flex;align-items:center;gap:.85rem;padding:.85rem 0;border-bottom:1px solid var(--border)}
.xirr-evidence-row:last-child{border-bottom:none}
.xirr-evidence-icon{width:36px;height:36px;border-radius:50%;background:#eef4fc;color:var(--navy);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.xirr-evidence-title{font-weight:700;font-size:15px}
.xirr-evidence-desc{color:var(--muted);font-size:14px}
.xirr-evidence-status{margin-left:auto;font-size:14px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:.35rem}
.xirr-evidence-status.ok{color:var(--green)}
.xirr-evidence-status.pending{color:var(--muted)}

/* Metric cards (Step 2) */
.xirr-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
.xirr-metric-card{border:1px solid var(--border);border-radius:.5rem;padding:1rem;background:#fff}
.xirr-metric-label{color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.03em;margin:0 0 .4rem}
.xirr-metric-value{font-size:1.6rem;font-weight:800;color:var(--navy);line-height:1}
.xirr-metric-value .unit{font-size:1rem;font-weight:400;color:var(--muted)}
.xirr-metric-trend{font-size:13px;font-weight:600;margin-top:.35rem}
.xirr-metric-trend.up{color:#16a34a}
.xirr-metric-trend.down{color:#dc2626}
.xirr-metric-trend.flat{color:var(--muted)}
.xirr-metric-value.no-data{color:var(--muted);font-size:1rem;font-weight:600}
@media (max-width:1024px){.xirr-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:480px){.xirr-metric-grid{grid-template-columns:1fr}}

/* Discussion guide (Step 4) */
.xirr-guide-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin-bottom:1.25rem}
.xirr-guide-card{border:1px solid var(--border);border-radius:.5rem;padding:.9rem;background:#fafafa}
.xirr-guide-card h4{margin:0 0 .35rem;font-size:14px;color:var(--navy);text-transform:none;letter-spacing:0}
.xirr-guide-card p{margin:0;font-size:13px;color:var(--muted)}
@media (max-width:1024px){.xirr-guide-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:480px){.xirr-guide-grid{grid-template-columns:1fr}}

/* Utility */
.xirr-hidden{display:none !important}
.xirr-section-title{font-weight:800;color:var(--navy);margin:0 0 .3rem}
.xirr-section-desc{color:var(--muted);margin:0 0 1.25rem}
.xirr-placeholder{padding:2rem 1rem;text-align:center}

/* ── Tablet (≤1024px) ── */
@media (max-width:1024px){
#xfirr-wiz h1{font-size:32px}
#xfirr-wiz h2{font-size:26px}
#xfirr-wiz h3{font-size:24px}
#xfirr-wiz h4{font-size:22px}
.xirr-header,.xirr-steps,.xirr-body,.xirr-footer{padding-left:1.25rem;padding-right:1.25rem}
.xirr-body{flex-direction:column}
.xirr-sidebar{width:100%}
.xirr-steps{overflow-x:hidden}
.xirr-steps-inner{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem .75rem;max-width:100%;min-width:0;padding:0;justify-items:center}
.xirr-step{flex:none;min-width:0;width:100%;max-width:150px}
.xirr-step .xirr-step-circle{width:30px;height:30px;font-size:14px}
.xirr-step-line{display:none}
.xirr-step .xirr-step-label{font-size:11px;max-width:100%;line-height:1.25}
.xirr-step-underline{width:100%!important;margin-top:.5rem}
.xirr-prio-grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}
.xirr-ai-split{grid-template-columns:1fr;justify-items:center}
.xirr-ai-copy{width:100%}
.xirr-risk-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xirr-activate-grid,.xirr-action-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xirr-summary-grid{grid-template-columns:1fr}
}

/* ── Mobile (≤768px) ── */
@media (max-width:768px){
#xfirr-wiz{font-size:16px;border-radius:0;border-left:none;border-right:none}
#xfirr-wiz h1{font-size:26px;line-height:1.15}
#xfirr-wiz h2{font-size:22px;line-height:1.2}
#xfirr-wiz h3{font-size:20px}
#xfirr-wiz h4{font-size:20px}
#xfirr-wiz p{font-size:16px}
.xirr-header{padding:1.25rem 1rem}
.xirr-header-inner{flex-direction:column;align-items:stretch}
.xirr-header-actions{width:100%}
.xirr-header-actions .xirr-btn{flex:1;text-align:center}
.xirr-steps{padding:1rem 1rem .75rem}
.xirr-steps-inner{grid-template-columns:repeat(3,1fr);gap:1rem .5rem}
.xirr-step{max-width:140px}
.xirr-step .xirr-step-circle{width:28px;height:28px;font-size:13px}
.xirr-step .xirr-step-label{font-size:10px}
.xirr-body{padding:1rem;gap:1rem}
.xirr-card{padding:1rem;margin-bottom:.75rem}
.xirr-footer{flex-direction:column;align-items:stretch;padding:1rem;gap:.75rem}
.xirr-footer>.xirr-btn{width:100%;text-align:center}
.xirr-footer .xirr-row{width:100%;flex-direction:column}
.xirr-footer .xirr-row .xirr-btn{width:100%;text-align:center}
.xirr-autosave{text-align:center;font-size:14px;justify-content:center}
.xirr-btn{padding:.65rem 1rem;font-size:15px;white-space:normal}
.xirr-dl,.xirr-dl dt,.xirr-dl dd,.xirr-dl .xirr-badge{font-size:15px}
.xirr-field-label{font-size:16px}
.xirr-field-desc,.xirr-field textarea{font-size:15px}
.xirr-prio-card{flex-direction:column;padding:1rem}
.xirr-prio-rail{flex-direction:row;justify-content:flex-start;gap:.65rem}
.xirr-prio-body{padding-right:0;padding-top:.25rem}
.xirr-prio-grid-4{grid-template-columns:1fr}
.xirr-icon-btn{top:-.25rem;right:-.25rem}
.xirr-risk-grid{grid-template-columns:1fr}
.xirr-donut-wrap{width:130px}
.xirr-donut-chart,.xirr-donut{width:130px;height:130px}
.xirr-activate-grid,.xirr-action-grid{grid-template-columns:1fr}
.xirr-review-row{flex-wrap:wrap}
}

/* ── Small mobile (≤480px) ── */
@media (max-width:480px){
#xfirr-wiz h1{font-size:22px}
#xfirr-wiz h2{font-size:20px}
.xirr-header-actions{flex-direction:column}
.xirr-header-actions .xirr-btn{width:100%}
.xirr-steps-inner{grid-template-columns:repeat(2,1fr);gap:1rem .5rem}
.xirr-step{max-width:none}
.xirr-step .xirr-step-circle{width:26px;height:26px;font-size:12px}
.xirr-step .xirr-step-label{font-size:10px}
}

/* Growth Timeline / Development Roadmap (Step 2 & Step 6) */
.xirr-timeline{display:flex;align-items:flex-start;gap:0;position:relative}
.xirr-timeline-item{flex:1;position:relative;padding:0 .5rem;text-align:left}
.xirr-timeline-dot{width:14px;height:14px;border-radius:50%;background:var(--green);margin-bottom:.6rem}
.xirr-timeline-track{position:absolute;top:6px;left:0;right:0;height:2px;background:var(--border);z-index:0}
.xirr-timeline-item h5{margin:0 0 .2rem;font-size:14px;color:var(--navy)}
.xirr-timeline-item p{margin:0;font-size:13px;color:var(--muted)}
.xirr-roadmap-list{display:flex;flex-direction:column;gap:0}
.xirr-roadmap-item{display:flex;gap:.75rem;position:relative;padding:0 0 1.1rem 0}
.xirr-roadmap-item:last-child{padding-bottom:0}
.xirr-roadmap-rail{display:flex;flex-direction:column;align-items:center;flex-shrink:0}
.xirr-roadmap-dot{width:10px;height:10px;border-radius:50%;background:var(--green);margin-top:.3rem}
.xirr-roadmap-line{flex:1;width:2px;background:var(--border);margin-top:.2rem}
.xirr-roadmap-item:last-child .xirr-roadmap-line{display:none}
.xirr-roadmap-period{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.03em;margin-bottom:.1rem}
.xirr-roadmap-text{font-size:15px;color:var(--ink)}

/* Development Conversation agreement / signatures (Step 4) */
.xirr-signature-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:end}
.xirr-signature-box{border:1px solid var(--border);border-radius:.5rem;padding:.85rem 1rem}
.xirr-signature-box .name{font-weight:700;color:var(--ink);font-size:15px}
.xirr-signature-box .role{color:var(--muted);font-size:13px;margin-bottom:.5rem}
.xirr-signed-badge{color:var(--green);font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:.35rem}
@media (max-width:768px){.xirr-signature-row{grid-template-columns:1fr}}

/* Readiness / behavioral pattern chips (Step 3) */
.xirr-pattern-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;text-align:center}
.xirr-pattern-item{}
.xirr-pattern-item .label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.03em;margin-bottom:.4rem}
.xirr-pattern-chip{display:inline-block;padding:.3rem .85rem;border-radius:999px;font-size:14px;font-weight:700;background:#eef4fc;color:var(--navy)}
@media (max-width:768px){.xirr-pattern-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
CSS;
}
