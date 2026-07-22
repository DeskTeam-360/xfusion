<?php
/**
 * CSS for the Quarterly Business Review™ wizard shortcode.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_styles_css(): string
{
    return <<<'CSS'
#xfqbr-wiz{--navy:#1e2a52;--navy-dark:#141d3d;--green:#5f9a3f;--green-light:#7cb356;--ink:#1f2937;--muted:#6b7280;--border:#e5e7eb;--bg:#f7f8fa;
    max-width:1440px;margin:0 auto;font-family:inherit;font-size:18px;color:var(--ink);background:var(--bg);border-radius:.5rem;overflow:hidden;border:1px solid var(--border)}
#xfqbr-wiz h1{font-size:40px}
#xfqbr-wiz h2{font-size:32px}
#xfqbr-wiz h3{font-size:30px}
#xfqbr-wiz h4{font-size:28px}
#xfqbr-wiz p{font-size:18px}

/* Loading state */
.xqbr-spinner-row{display:flex;align-items:center;gap:.6rem;padding:1rem 0;color:var(--muted);font-size:16px}
.xqbr-spinner{width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--green);border-radius:50%;display:inline-block;animation:xqbr-spin .7s linear infinite;flex-shrink:0}
@keyframes xqbr-spin{to{transform:rotate(360deg)}}

/* View-only mode (non-leader members) — CSS-based so it survives any
   re-render from Step 3/4's async data load without re-applying JS. */
#xfqbr-wiz[data-view-only="1"] #xqbr-main input,
#xfqbr-wiz[data-view-only="1"] #xqbr-main textarea,
#xfqbr-wiz[data-view-only="1"] #xqbr-main select{
    pointer-events:none;background:#f3f4f6;opacity:.85}
#xfqbr-wiz[data-view-only="1"] #xqbr-main button,
#xfqbr-wiz[data-view-only="1"] #xqbr-main .xqbr-add-link,
#xfqbr-wiz[data-view-only="1"] #xqbr-main .xqbr-prio-delete,
#xfqbr-wiz[data-view-only="1"] #xqbr-main a.xqbr-icon-btn{
    display:none !important}

/* Header */
.xqbr-header{background:linear-gradient(120deg,var(--navy-dark) 0%,var(--navy) 55%,var(--green) 140%);padding:1.5rem 1.75rem;color:#fff}
.xqbr-header-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.xqbr-header h1{margin:0;letter-spacing:.02em;font-weight:600}
.xqbr-header p{margin:.25rem 0 0;opacity:.85}
.xqbr-header-actions{display:flex;gap:.6rem;flex-shrink:0}

/* Buttons */
.xqbr-btn{cursor:pointer;border-radius:.375rem;padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;border:1px solid transparent;white-space:nowrap}
.xqbr-btn-outline-white{background:transparent;border-color:rgba(255,255,255,.5);color:#fff}
.xqbr-btn-outline-white:hover{background:rgba(255,255,255,.1)}
.xqbr-btn-accent{background:var(--green);border-color:var(--green);color:#fff}
.xqbr-btn-accent:hover{background:var(--green-light)}
.xqbr-btn-outline{background:#fff;border-color:var(--border);color:var(--ink)}
.xqbr-btn-outline:hover{background:#f3f4f6}
.xqbr-btn:disabled{opacity:.45;cursor:default}

/* Step indicator */
.xqbr-steps{background:#fff;border-bottom:1px solid var(--border);padding:1.5rem 1.75rem .5rem}
.xqbr-steps-inner{display:flex;align-items:flex-start;justify-content:space-between;position:relative;max-width:96%;margin:0 auto}
.xqbr-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;cursor:pointer}
.xqbr-step .xqbr-step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;border:2px solid var(--border);color:var(--muted);background:#fff;z-index:1}
.xqbr-step.done .xqbr-step-circle{background:#e9f5e1;border-color:var(--green);color:var(--green)}
.xqbr-step.active .xqbr-step-circle{background:var(--green);border-color:var(--green);color:#fff}
.xqbr-step .xqbr-step-label{margin-top:.5rem;font-size:13px;font-weight:700;text-align:center;color:var(--muted);max-width:95%;line-height:1.25}
.xqbr-step.active .xqbr-step-label,.xqbr-step.done .xqbr-step-label{color:var(--navy)}
.xqbr-step-line{position:absolute;top:17px;left:50%;width:100%;height:2px;background:var(--border);z-index:0}
.xqbr-step.done .xqbr-step-line{background:var(--green)}
.xqbr-step:last-child .xqbr-step-line{display:none}
.xqbr-step-underline{height:3px;background:var(--green);margin-top:.75rem;border-radius:2px}

/* Layout */
.xqbr-body{display:flex;gap:1.5rem;padding:1.5rem 1.75rem;align-items:flex-start}
.xqbr-main{flex:1;min-width:0}
.xqbr-sidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:1rem}

/* Cards */
.xqbr-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem;margin-bottom:1rem}
.xqbr-card h4{margin:0 0 .6rem;text-transform:uppercase;letter-spacing:.04em;color:var(--navy);font-weight:500}
.xqbr-about-step{display:flex;gap:.85rem;align-items:flex-start}
.xqbr-about-step-icon{width:50px;height:50px;flex-shrink:0;display:block;object-fit:contain;margin-top:.15rem}
.xqbr-about-step-body{min-width:0;flex:1}
.xqbr-about-step-body .xqbr-muted{margin:0 0 .65rem}
.xqbr-about-step-body .xqbr-muted:last-child{margin-bottom:0}
.xqbr-progress-row{display:flex;align-items:center;gap:.65rem}
.xqbr-progress-row .xqbr-progress-track{flex:1;margin-top:0}
.xqbr-progress-row .xqbr-progress-pct{flex-shrink:0;font-weight:600;color:var(--navy)}
.xqbr-muted{color:var(--muted);line-height:1.5}
.xqbr-row{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}

.xqbr-dl{margin:0;font-size:16px}
.xqbr-dl dt{color:var(--muted);font-size:16px;text-transform:uppercase;letter-spacing:.03em;margin-top:.6rem}
.xqbr-dl dt:first-child{margin-top:0}
.xqbr-dl dd{margin:.15rem 0 0;font-weight:600;color:var(--ink);font-size:16px}
.xqbr-dl .xqbr-badge{font-size:16px}

.xqbr-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:16px;font-weight:600}
.xqbr-badge.amber{background:#fef3c7;color:#92400e}
.xqbr-badge.green{background:#dcfce7;color:#166534}

.xqbr-progress-track{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-top:.5rem}
.xqbr-progress-fill{height:100%;background:var(--green);border-radius:999px}

.xqbr-link{color:var(--green);font-size:16px;font-weight:600;text-decoration:none}
.xqbr-link:hover{text-decoration:underline}

/* ARP form fields */
.xqbr-field{margin-bottom:1.35rem}
.xqbr-field-head{display:flex;justify-content:space-between;align-items:baseline;gap:1rem;margin-bottom:.25rem}
.xqbr-field-label{font-weight:800;font-size:18px;color:var(--navy);text-transform:uppercase;letter-spacing:.02em;margin:0}
.xqbr-field-count{font-weight:400;color:var(--muted);font-size:.8rem;white-space:nowrap}
.xqbr-field-desc{color:var(--muted);font-size:16px;margin:0 0 .45rem;line-height:1.45}
.xqbr-field textarea{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.65rem .75rem;font-size:16px;box-sizing:border-box;font-family:inherit;resize:vertical;min-height:5.5rem;background:#fff;color:var(--ink)}
.xqbr-field textarea:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
.xqbr-field-ai{display:flex;justify-content:flex-end;margin-top:.45rem}
.xqbr-ai-assist{display:inline-flex;align-items:center;gap:.35rem;background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:13px;font-weight:700;font-family:inherit;letter-spacing:.04em;text-transform:uppercase}
.xqbr-ai-assist:hover{text-decoration:underline}
.xqbr-ai-assist img{width:18px;height:18px;display:block;object-fit:contain}

/* Form controls (shared) */
.xqbr-input{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.5rem .65rem;font-size:15px;box-sizing:border-box;font-family:inherit;background:#fff;color:var(--ink)}
.xqbr-input:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
textarea.xqbr-input{resize:vertical;min-height:4.5rem;line-height:1.45}
select.xqbr-input{appearance:auto;cursor:pointer}
select.xqbr-input[multiple]{min-height:4.75rem;padding:.35rem}
.xqbr-form-field{min-width:0}
.xqbr-form-field label{display:block;font-weight:700;font-size:13px;color:var(--navy);margin-bottom:.35rem}
.xqbr-req{color:#dc2626}

/* Priority / strategic cards */
.xqbr-add-row{display:flex;justify-content:flex-start;margin:0 0 1rem}
.xqbr-add-link{background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:15px;font-weight:700;font-family:inherit;text-decoration:none}
.xqbr-add-link:hover{text-decoration:underline}
.xqbr-prio-list{display:flex;flex-direction:column;gap:1rem}
.xqbr-prio-card{display:flex;gap:.85rem;align-items:stretch;background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.15rem;position:relative}
.xqbr-prio-rail{display:flex;flex-direction:column;align-items:center;gap:.45rem;padding-top:.15rem;flex-shrink:0}
.xqbr-drag{color:var(--muted);font-size:14px;letter-spacing:-2px;line-height:1;cursor:grab;user-select:none}
.xqbr-prio-num{width:30px;height:30px;border-radius:50%;background:transparent;border:2px solid var(--navy);color:var(--navy);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;box-sizing:border-box}
.xqbr-prio-body{flex:1;min-width:0;position:relative;padding-right:1.75rem}
.xqbr-prio-grid{display:grid;gap:.85rem 1rem;margin-bottom:.85rem}
.xqbr-prio-grid:last-child{margin-bottom:0}
.xqbr-prio-grid-1{grid-template-columns:1fr}
.xqbr-prio-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.xqbr-icon-btn{position:absolute;top:0;right:0;width:32px;height:32px;border:none;background:transparent;color:var(--muted);cursor:pointer;border-radius:.375rem;font-size:1.1rem;line-height:1;padding:0;display:flex;align-items:center;justify-content:center}
.xqbr-icon-btn:hover{color:#b91c1c;background:#fef2f2}
.xqbr-prio-delete{background:transparent !important;color:#87B14B !important;text-decoration:none !important}
.xqbr-prio-delete:hover{background:transparent !important;color:#E1706D !important;text-decoration:none !important}
.xqbr-prio-delete img{display:block;width:18px;height:18px;object-fit:contain}
.xqbr-owner-field{display:flex;align-items:center;gap:.5rem}
.xqbr-owner-field .xqbr-input{flex:1}
.xqbr-avatar{width:32px;height:32px;border-radius:50%;background:var(--navy);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}

/* Info icon on field labels */
.xqbr-info-icon{display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;border:1.5px solid var(--muted);color:var(--muted);font-size:10px;font-weight:700;font-style:normal;vertical-align:middle;margin-left:.25rem;text-transform:none;letter-spacing:0}

/* Info banner */
.xqbr-banner{background:#eef4fc;border:1px solid #bfdbfe;color:#1e3a5f;border-radius:.5rem;padding:.85rem 1rem;font-size:.9rem;margin-bottom:1.25rem;display:flex;gap:.65rem;align-items:flex-start;line-height:1.45}
.xqbr-banner.warn{background:#fff8e6;border-color:#fde68a;color:#7c5b00}
.xqbr-banner b{font-weight:700}
.xqbr-banner-icon{flex-shrink:0;line-height:1.2}
.xqbr-btn-sm{padding:.35rem .75rem;font-size:.75rem}

/* Publish step */
.xqbr-summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:.85rem 1.5rem;margin:0}
.xqbr-summary-item{margin:0}
.xqbr-summary-item dt{color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.03em;margin:0 0 .2rem}
.xqbr-summary-item dd{margin:0;font-weight:700;color:var(--ink);font-size:16px}
.xqbr-review-list{display:flex;flex-direction:column}
.xqbr-review-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--border)}
.xqbr-review-row:last-child{border-bottom:none;padding-bottom:0}
.xqbr-review-row:first-child{padding-top:0}
.xqbr-review-left{display:flex;align-items:flex-start;gap:.75rem;min-width:0}
.xqbr-review-check{width:28px;height:28px;border-radius:50%;background:#e9f5e1;color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
.xqbr-review-status{color:var(--green);font-size:14px;font-weight:600;margin-top:.15rem}
.xqbr-activate-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
.xqbr-activate-card{border:1px solid var(--border);border-radius:.5rem;padding:1rem;background:#fafafa}
.xqbr-activate-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xqbr-activate-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xqbr-activate-card p{margin:0;font-size:14px}
.xqbr-action-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
.xqbr-action-card{border:1px solid var(--border);border-radius:.5rem;padding:1.1rem;background:#fff;display:flex;flex-direction:column;align-items:flex-start}
.xqbr-action-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xqbr-action-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xqbr-action-card p{margin:0 0 1rem;font-size:14px;flex:1}
.xqbr-action-card .xqbr-btn{width:100%;text-align:center}

/* AI Review dashboard */
.xqbr-ai-block{margin-bottom:1rem}
.xqbr-ai-heading{margin:0 0 1rem;font-size:1.1rem;font-weight:800;color:var(--navy)}
.xqbr-ai-split{display:grid;grid-template-columns:180px 1fr;gap:1.5rem;align-items:center}
.xqbr-ai-copy p{margin:0 0 .85rem;line-height:1.5}
.xqbr-donut-wrap{display:flex;flex-direction:column;align-items:center;width:150px;margin:0 auto}
.xqbr-donut-chart{position:relative;width:150px;height:150px}
.xqbr-donut{width:150px;height:150px;transform:rotate(-90deg)}
.xqbr-donut-track{fill:none;stroke:#e5e7eb;stroke-width:3.2}
.xqbr-donut-value{fill:none;stroke-width:3.2;stroke-linecap:round}
.xqbr-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;pointer-events:none}
.xqbr-donut-score{font-size:1.35rem;font-weight:800;color:var(--navy);line-height:1.1}
.xqbr-donut-score span{font-size:.75rem;font-weight:600;color:var(--muted)}
.xqbr-donut-label{margin-top:.5rem;font-size:.85rem;font-weight:600;color:var(--muted);text-align:center;line-height:1.2}
.xqbr-check-list{list-style:none;margin:0;padding:0}
.xqbr-check-list li{display:flex;gap:.55rem;align-items:flex-start;margin-bottom:.45rem;font-size:16px;line-height:1.4;color:var(--ink)}
.xqbr-check{width:20px;height:20px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;margin-top:.1rem}
.xqbr-stat-list{display:flex;flex-direction:column;gap:.55rem}
.xqbr-stat-row{display:flex;align-items:center;gap:.55rem;font-size:16px}
.xqbr-stat-row strong{margin-left:auto;color:var(--navy)}
.xqbr-dot{width:10px;height:10px;border-radius:50%;display:inline-block;flex-shrink:0}
.xqbr-dot.green{background:#16a34a}
.xqbr-dot.amber{background:#ca8a04}
.xqbr-dot.red{background:#dc2626}
.xqbr-table-scroll{overflow-x:auto}
table.xqbr-table{width:100%;border-collapse:collapse;font-size:15px}
table.xqbr-table th{text-align:left;padding:.65rem .75rem;color:var(--muted);font-weight:700;border-bottom:1px solid var(--border);font-size:.75rem;text-transform:uppercase;letter-spacing:.03em}
table.xqbr-table td{padding:.85rem .75rem;border-bottom:1px solid var(--border);vertical-align:top}
table.xqbr-table tr:last-child td{border-bottom:none}
table.xqbr-table.xqbr-table-gaps{border:1px solid var(--border);border-radius:.35rem;overflow:hidden}
table.xqbr-table.xqbr-table-gaps th,
table.xqbr-table.xqbr-table-gaps td{border-right:1px solid var(--border)}
table.xqbr-table.xqbr-table-gaps th:last-child,
table.xqbr-table.xqbr-table-gaps td:last-child{border-right:none}
table.xqbr-table.xqbr-table-gaps th{background:#f8fafc;color:var(--navy)}
table.xqbr-table.xqbr-table-gaps .xqbr-gap-desc-col{width:42%}
table.xqbr-table.xqbr-table-gaps .xqbr-gap-area{width:18%;color:var(--navy)}
table.xqbr-table.xqbr-table-gaps .xqbr-gap-impact,
table.xqbr-table.xqbr-table-gaps .xqbr-gap-priority{width:12%;white-space:nowrap}
.xqbr-gap-area strong{font-weight:700}
.xqbr-gap-desc{font-size:15px;font-weight:400;color:var(--ink);line-height:1.45}
.xqbr-impact{display:inline-flex;align-items:center;gap:.4rem;font-weight:600;font-size:14px}
.xqbr-impact .xqbr-dot{width:8px;height:8px}
.xqbr-impact.high .xqbr-dot{background:#dc2626}
.xqbr-impact.medium .xqbr-dot{background:#ca8a04}
.xqbr-impact.low .xqbr-dot{background:#2563eb}
.xqbr-badge-pill{display:inline-block;padding:.2rem .65rem;border-radius:999px;font-size:12px;font-weight:700}
.xqbr-badge-pill.high{background:#fee2e2;color:#991b1b}
.xqbr-badge-pill.medium{background:#fef3c7;color:#92400e}
.xqbr-badge-pill.low{background:#dbeafe;color:#1e40af}
.xqbr-align-list{display:flex;flex-direction:column;gap:.85rem}
.xqbr-align-row.xqbr-progress-row{display:grid;grid-template-columns:minmax(0,12rem) 1fr 2.75rem;gap:.75rem;align-items:center;margin-bottom:0}
.xqbr-align-label{font-size:15px;color:var(--ink);line-height:1.35}
.xqbr-align-row.xqbr-progress-row .xqbr-progress-track{height:10px;margin-top:0}
.xqbr-align-row.xqbr-progress-row .xqbr-progress-pct{font-size:15px;font-weight:700;color:var(--navy);text-align:right}
.xqbr-risk-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem}
.xqbr-risk-card{display:flex;gap:.65rem;align-items:flex-start;border:1px solid var(--border);border-radius:.5rem;padding:.85rem;background:#fafafa}
.xqbr-risk-card p{margin:.2rem 0 0;font-size:13px}
.xqbr-risk-title{font-size:15px;color:var(--navy)}
.xqbr-risk-icon{flex-shrink:0;display:flex;align-items:center;justify-content:center}
.xqbr-risk-card.high .xqbr-risk-icon{color:#dc2626}
.xqbr-risk-card.medium .xqbr-risk-icon{color:#ea580c}
.xqbr-risk-card.low .xqbr-risk-icon{color:#ca8a04}
.xqbr-risk-card.strength .xqbr-risk-icon{color:#16a34a}
.xqbr-focus-list{display:flex;flex-direction:column;gap:.75rem}
.xqbr-focus-item{display:flex;align-items:flex-start;gap:.75rem;font-size:16px;line-height:1.45;color:var(--ink)}
.xqbr-focus-item img{flex-shrink:0;width:36px;height:36px;object-fit:contain}

/* Footer nav */
.xqbr-footer{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.75rem;border-top:1px solid var(--border);background:#fff;flex-wrap:wrap;gap:.75rem}
.xqbr-autosave{color:#16a34a;display:inline-flex;align-items:center;gap:.4rem}
.xqbr-autosave-check{width:18px;height:18px;border-radius:50%;background:#16a34a;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0}

/* Evidence checklist (Step 1) */
.xqbr-evidence-list{display:flex;flex-direction:column}
.xqbr-evidence-row{display:flex;align-items:center;gap:.85rem;padding:.85rem 0;border-bottom:1px solid var(--border)}
.xqbr-evidence-row:last-child{border-bottom:none}
.xqbr-evidence-icon{width:36px;height:36px;border-radius:50%;background:#eef4fc;color:var(--navy);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.xqbr-evidence-title{font-weight:700;font-size:15px}
.xqbr-evidence-desc{color:var(--muted);font-size:14px}
.xqbr-evidence-status{margin-left:auto;font-size:14px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:.35rem}
.xqbr-evidence-status.ok{color:var(--green)}
.xqbr-evidence-status.pending{color:var(--muted)}

/* Metric cards (Step 2) */
.xqbr-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
.xqbr-metric-card{border:1px solid var(--border);border-radius:.5rem;padding:1rem;background:#fff}
.xqbr-metric-label{color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.03em;margin:0 0 .4rem}
.xqbr-metric-value{font-size:1.6rem;font-weight:800;color:var(--navy);line-height:1}
.xqbr-metric-value .unit{font-size:1rem;font-weight:400;color:var(--muted)}
.xqbr-metric-trend{font-size:13px;font-weight:600;margin-top:.35rem}
.xqbr-metric-trend.up{color:#16a34a}
.xqbr-metric-trend.down{color:#dc2626}
.xqbr-metric-trend.flat{color:var(--muted)}
.xqbr-metric-value.no-data{color:var(--muted);font-size:1rem;font-weight:600}
@media (max-width:1024px){.xqbr-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:480px){.xqbr-metric-grid{grid-template-columns:1fr}}

/* Discussion guide (Step 4) */
.xqbr-guide-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin-bottom:1.25rem}
.xqbr-guide-card{border:1px solid var(--border);border-radius:.5rem;padding:.9rem;background:#fafafa}
.xqbr-guide-card h4{margin:0 0 .35rem;font-size:14px;color:var(--navy);text-transform:none;letter-spacing:0}
.xqbr-guide-card p{margin:0;font-size:13px;color:var(--muted)}
@media (max-width:1024px){.xqbr-guide-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:480px){.xqbr-guide-grid{grid-template-columns:1fr}}

/* Utility */
.xqbr-hidden{display:none !important}
.xqbr-section-title{font-weight:800;color:var(--navy);margin:0 0 .3rem}
.xqbr-section-desc{color:var(--muted);margin:0 0 1.25rem}
.xqbr-placeholder{padding:2rem 1rem;text-align:center}

/* ── Tablet (≤1024px) ── */
@media (max-width:1024px){
#xfqbr-wiz h1{font-size:32px}
#xfqbr-wiz h2{font-size:26px}
#xfqbr-wiz h3{font-size:24px}
#xfqbr-wiz h4{font-size:22px}
.xqbr-header,.xqbr-steps,.xqbr-body,.xqbr-footer{padding-left:1.25rem;padding-right:1.25rem}
.xqbr-body{flex-direction:column}
.xqbr-sidebar{width:100%}
.xqbr-steps{overflow-x:hidden}
.xqbr-steps-inner{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem .75rem;max-width:100%;min-width:0;padding:0;justify-items:center}
.xqbr-step{flex:none;min-width:0;width:100%;max-width:150px}
.xqbr-step .xqbr-step-circle{width:30px;height:30px;font-size:14px}
.xqbr-step-line{display:none}
.xqbr-step .xqbr-step-label{font-size:11px;max-width:100%;line-height:1.25}
.xqbr-step-underline{width:100%!important;margin-top:.5rem}
.xqbr-prio-grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}
.xqbr-ai-split{grid-template-columns:1fr;justify-items:center}
.xqbr-ai-copy{width:100%}
.xqbr-risk-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xqbr-activate-grid,.xqbr-action-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xqbr-summary-grid{grid-template-columns:1fr}
}

/* ── Mobile (≤768px) ── */
@media (max-width:768px){
#xfqbr-wiz{font-size:16px;border-radius:0;border-left:none;border-right:none}
#xfqbr-wiz h1{font-size:26px;line-height:1.15}
#xfqbr-wiz h2{font-size:22px;line-height:1.2}
#xfqbr-wiz h3{font-size:20px}
#xfqbr-wiz h4{font-size:20px}
#xfqbr-wiz p{font-size:16px}
.xqbr-header{padding:1.25rem 1rem}
.xqbr-header-inner{flex-direction:column;align-items:stretch}
.xqbr-header-actions{width:100%}
.xqbr-header-actions .xqbr-btn{flex:1;text-align:center}
.xqbr-steps{padding:1rem 1rem .75rem}
.xqbr-steps-inner{grid-template-columns:repeat(3,1fr);gap:1rem .5rem}
.xqbr-step{max-width:140px}
.xqbr-step .xqbr-step-circle{width:28px;height:28px;font-size:13px}
.xqbr-step .xqbr-step-label{font-size:10px}
.xqbr-body{padding:1rem;gap:1rem}
.xqbr-card{padding:1rem;margin-bottom:.75rem}
.xqbr-footer{flex-direction:column;align-items:stretch;padding:1rem;gap:.75rem}
.xqbr-footer>.xqbr-btn{width:100%;text-align:center}
.xqbr-footer .xqbr-row{width:100%;flex-direction:column}
.xqbr-footer .xqbr-row .xqbr-btn{width:100%;text-align:center}
.xqbr-autosave{text-align:center;font-size:14px;justify-content:center}
.xqbr-btn{padding:.65rem 1rem;font-size:15px;white-space:normal}
.xqbr-dl,.xqbr-dl dt,.xqbr-dl dd,.xqbr-dl .xqbr-badge{font-size:15px}
.xqbr-field-label{font-size:16px}
.xqbr-field-desc,.xqbr-field textarea{font-size:15px}
.xqbr-prio-card{flex-direction:column;padding:1rem}
.xqbr-prio-rail{flex-direction:row;justify-content:flex-start;gap:.65rem}
.xqbr-prio-body{padding-right:0;padding-top:.25rem}
.xqbr-prio-grid-4{grid-template-columns:1fr}
.xqbr-icon-btn{top:-.25rem;right:-.25rem}
.xqbr-risk-grid{grid-template-columns:1fr}
.xqbr-donut-wrap{width:130px}
.xqbr-donut-chart,.xqbr-donut{width:130px;height:130px}
.xqbr-activate-grid,.xqbr-action-grid{grid-template-columns:1fr}
.xqbr-review-row{flex-wrap:wrap}
}

/* ── Small mobile (≤480px) ── */
@media (max-width:480px){
#xfqbr-wiz h1{font-size:22px}
#xfqbr-wiz h2{font-size:20px}
.xqbr-header-actions{flex-direction:column}
.xqbr-header-actions .xqbr-btn{width:100%}
.xqbr-steps-inner{grid-template-columns:repeat(2,1fr);gap:1rem .5rem}
.xqbr-step{max-width:none}
.xqbr-step .xqbr-step-circle{width:26px;height:26px;font-size:12px}
.xqbr-step .xqbr-step-label{font-size:10px}
}
CSS;
}
