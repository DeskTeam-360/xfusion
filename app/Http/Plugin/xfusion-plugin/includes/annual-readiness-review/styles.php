<?php
/**
 * CSS for the Annual Readiness Review™ wizard shortcode.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_styles_css(): string
{
    return <<<'CSS'
#xfarr-wiz{--navy:#1e2a52;--navy-dark:#141d3d;--green:#5f9a3f;--green-light:#7cb356;--ink:#1f2937;--muted:#6b7280;--border:#e5e7eb;--bg:#f7f8fa;
    max-width:1440px;margin:0 auto;font-family:inherit;font-size:18px;color:var(--ink);background:var(--bg);border-radius:.5rem;overflow:hidden;border:1px solid var(--border)}
#xfarr-wiz h1{font-size:40px}
#xfarr-wiz h2{font-size:32px}
#xfarr-wiz h3{font-size:30px}
#xfarr-wiz h4{font-size:28px}
#xfarr-wiz p{font-size:18px}

/* Loading state */
.xarr-spinner-row{display:flex;align-items:center;gap:.6rem;padding:.75rem 0;color:var(--muted);font-size:16px}
.xarr-spinner{width:18px;height:18px;border:2px solid var(--border);border-top-color:var(--green);border-radius:50%;display:inline-block;animation:xarr-spin .7s linear infinite;flex-shrink:0}
.xarr-spinner-inline{width:14px;height:14px;vertical-align:-2px;margin-right:.15rem}
@keyframes xarr-spin{to{transform:rotate(360deg)}}
.xarr-loading-stack{display:flex;flex-direction:column;gap:.35rem}
.xarr-skeleton-list{opacity:.92}
.xarr-skeleton-card{pointer-events:none;border-color:#eef0f3;background:linear-gradient(90deg,#fafafa 0%,#f3f4f6 50%,#fafafa 100%);background-size:200% 100%;animation:xarr-shimmer 1.4s ease-in-out infinite}
.xarr-skeleton-card .xarr-skeleton-circle{width:30px;height:30px;border-radius:50%;background:#e5e7eb;color:transparent;flex-shrink:0}
.xarr-skeleton-line{height:12px;border-radius:999px;background:linear-gradient(90deg,#eceff3 0%,#f8f9fb 50%,#eceff3 100%);background-size:200% 100%;animation:xarr-shimmer 1.4s ease-in-out infinite;margin-bottom:.55rem}
.xarr-skeleton-line:last-child{margin-bottom:0}
.xarr-skeleton-line.w-full{width:100%}
.xarr-skeleton-line.w-95{width:95%}
.xarr-skeleton-line.w-90{width:90%}
.xarr-skeleton-line.w-80{width:80%}
.xarr-skeleton-line.w-75{width:75%}
.xarr-skeleton-line.w-70{width:70%}
.xarr-skeleton-line.w-65{width:65%}
.xarr-skeleton-line.w-60{width:60%}
.xarr-skeleton-line.w-55{width:55%}
.xarr-skeleton-line.w-50{width:50%}
.xarr-skeleton-line.w-45{width:45%}
.xarr-skeleton-line.w-40{width:40%}
.xarr-skeleton-line.w-35{width:35%}
.xarr-skeleton-line.w-20{width:20%}
.xarr-skeleton-stats .xarr-skeleton-line{height:14px;margin-bottom:.7rem}
@keyframes xarr-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.xarr-main-loading{position:relative;min-height:120px}
.xarr-main-loading::after{content:'';position:absolute;inset:0;background:rgba(255,255,255,.55);z-index:2;pointer-events:none}
.xarr-main-loading .xarr-step-loading-bar{position:absolute;top:0;left:0;right:0;height:3px;z-index:3;background:linear-gradient(90deg,transparent,var(--green),transparent);background-size:200% 100%;animation:xarr-shimmer 1s linear infinite}

/* View-only mode (non-leader members) — CSS-based so it survives any
   re-render from Step 3/4's async data load without re-applying JS. */
#xfarr-wiz[data-view-only="1"] #xarr-main input,
#xfarr-wiz[data-view-only="1"] #xarr-main textarea,
#xfarr-wiz[data-view-only="1"] #xarr-main select{
    pointer-events:none;background:#f3f4f6;opacity:.85}
#xfarr-wiz[data-view-only="1"] #xarr-main button,
#xfarr-wiz[data-view-only="1"] #xarr-main .xarr-add-link,
#xfarr-wiz[data-view-only="1"] #xarr-main .xarr-prio-delete,
#xfarr-wiz[data-view-only="1"] #xarr-main a.xarr-icon-btn{
    display:none !important}

/* Header */
.xarr-header{background:linear-gradient(120deg,var(--navy-dark) 0%,var(--navy) 55%,var(--green) 140%);padding:1.5rem 1.75rem;color:#fff}
.xarr-header-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.xarr-header h1{margin:0;letter-spacing:.02em;font-weight:600}
.xarr-header p{margin:.25rem 0 0;opacity:.85}
.xarr-header-actions{display:flex;gap:.6rem;flex-shrink:0}

/* Buttons */
.xarr-btn{cursor:pointer;border-radius:.375rem;padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;border:1px solid transparent;white-space:nowrap}
.xarr-btn-outline-white{background:transparent;border-color:rgba(255,255,255,.5);color:#fff}
.xarr-btn-outline-white:hover{background:rgba(255,255,255,.1)}
.xarr-btn-accent{background:var(--green);border-color:var(--green);color:#fff}
.xarr-btn-accent:hover{background:var(--green-light)}
.xarr-btn-outline{background:#fff;border-color:var(--border);color:var(--ink)}
.xarr-btn-outline:hover{background:#f3f4f6}
.xarr-btn:disabled{opacity:.45;cursor:default}

/* Step indicator */
.xarr-steps{background:#fff;border-bottom:1px solid var(--border);padding:1.5rem 1.75rem .5rem}
.xarr-steps-inner{display:flex;align-items:flex-start;justify-content:space-between;position:relative;max-width:96%;margin:0 auto}
.xarr-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;cursor:pointer}
.xarr-step .xarr-step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;border:2px solid var(--border);color:var(--muted);background:#fff;z-index:1}
.xarr-step.done .xarr-step-circle{background:#e9f5e1;border-color:var(--green);color:var(--green)}
.xarr-step.active .xarr-step-circle{background:var(--green);border-color:var(--green);color:#fff}
.xarr-step .xarr-step-label{margin-top:.5rem;font-size:13px;font-weight:700;text-align:center;color:var(--muted);max-width:95%;line-height:1.25}
.xarr-step.active .xarr-step-label,.xarr-step.done .xarr-step-label{color:var(--navy)}
.xarr-step-line{position:absolute;top:17px;left:50%;width:100%;height:2px;background:var(--border);z-index:0}
.xarr-step.done .xarr-step-line{background:var(--green)}
.xarr-step:last-child .xarr-step-line{display:none}
.xarr-step-underline{height:3px;background:var(--green);margin-top:.75rem;border-radius:2px}

/* Layout */
.xarr-body{display:flex;gap:1.5rem;padding:1.5rem 1.75rem;align-items:flex-start}
.xarr-main{flex:1;min-width:0}
.xarr-sidebar{width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:1rem}

/* Cards */
.xarr-card{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem;margin-bottom:1rem}
.xarr-card h4{margin:0 0 .6rem;text-transform:uppercase;letter-spacing:.04em;color:var(--navy);font-weight:500}
.xarr-about-step{display:flex;gap:.85rem;align-items:flex-start}
.xarr-about-step-icon{width:50px;height:50px;flex-shrink:0;display:block;object-fit:contain;margin-top:.15rem}
.xarr-about-step-body{min-width:0;flex:1}
.xarr-about-step-body .xarr-muted{margin:0 0 .65rem}
.xarr-about-step-body .xarr-muted:last-child{margin-bottom:0}
.xarr-progress-row{display:flex;align-items:center;gap:.65rem}
.xarr-progress-row .xarr-progress-track{flex:1;margin-top:0}
.xarr-progress-row .xarr-progress-pct{flex-shrink:0;font-weight:600;color:var(--navy)}
.xarr-muted{color:var(--muted);line-height:1.5}
.xarr-row{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}

.xarr-dl{margin:0;font-size:16px}
.xarr-dl dt{color:var(--muted);font-size:16px;text-transform:uppercase;letter-spacing:.03em;margin-top:.6rem}
.xarr-dl dt:first-child{margin-top:0}
.xarr-dl dd{margin:.15rem 0 0;font-weight:600;color:var(--ink);font-size:16px}
.xarr-dl .xarr-badge{font-size:16px}

.xarr-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:16px;font-weight:600}
.xarr-badge.amber{background:#fef3c7;color:#92400e}
.xarr-badge.green{background:#dcfce7;color:#166534}
.xarr-badge.gray{background:#f3f4f6;color:#374151}

.xarr-progress-track{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-top:.5rem}
.xarr-progress-fill{height:100%;background:var(--green);border-radius:999px}

.xarr-link{color:var(--green);font-size:16px;font-weight:600;text-decoration:none}
.xarr-link:hover{text-decoration:underline}

/* ARP form fields */
.xarr-field{margin-bottom:1.35rem}
.xarr-field-head{display:flex;justify-content:space-between;align-items:baseline;gap:1rem;margin-bottom:.25rem}
.xarr-field-label{font-weight:800;font-size:18px;color:var(--navy);text-transform:uppercase;letter-spacing:.02em;margin:0}
.xarr-field-count{font-weight:400;color:var(--muted);font-size:.8rem;white-space:nowrap}
.xarr-field-desc{color:var(--muted);font-size:16px;margin:0 0 .45rem;line-height:1.45}
.xarr-field textarea{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.65rem .75rem;font-size:16px;box-sizing:border-box;font-family:inherit;resize:vertical;min-height:5.5rem;background:#fff;color:var(--ink)}
.xarr-field textarea:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
.xarr-field-ai{display:flex;justify-content:flex-end;margin-top:.45rem}
.xarr-ai-assist{display:inline-flex;align-items:center;gap:.35rem;background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:13px;font-weight:700;font-family:inherit;letter-spacing:.04em;text-transform:uppercase}
.xarr-ai-assist:hover{text-decoration:underline}
.xarr-ai-assist img{width:18px;height:18px;display:block;object-fit:contain}

/* Form controls (shared) */
.xarr-input{width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.5rem .65rem;font-size:15px;box-sizing:border-box;font-family:inherit;background:#fff;color:var(--ink)}
.xarr-input:focus{outline:2px solid var(--green);outline-offset:-1px;border-color:var(--green)}
textarea.xarr-input{resize:vertical;min-height:4.5rem;line-height:1.45}
select.xarr-input{appearance:auto;cursor:pointer}
select.xarr-input[multiple]{min-height:4.75rem;padding:.35rem}
.xarr-form-field{min-width:0}
.xarr-form-field label{display:block;font-weight:700;font-size:13px;color:var(--navy);margin-bottom:.35rem}
.xarr-req{color:#dc2626}

/* Priority / strategic cards */
.xarr-add-row{display:flex;justify-content:flex-start;margin:0 0 1rem}
.xarr-add-link{background:none;border:none;padding:0;cursor:pointer;color:var(--green);font-size:15px;font-weight:700;font-family:inherit;text-decoration:none}
.xarr-add-link:hover{text-decoration:underline}
.xarr-prio-list{display:flex;flex-direction:column;gap:1rem}
.xarr-prio-card{display:flex;gap:.85rem;align-items:stretch;background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.15rem;position:relative}
.xarr-prio-rail{display:flex;flex-direction:column;align-items:center;gap:.45rem;padding-top:.15rem;flex-shrink:0}
.xarr-drag{color:var(--muted);font-size:14px;letter-spacing:-2px;line-height:1;cursor:grab;user-select:none}
.xarr-prio-num{width:30px;height:30px;border-radius:50%;background:transparent;border:2px solid var(--navy);color:var(--navy);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;box-sizing:border-box}
.xarr-prio-body{flex:1;min-width:0;position:relative;padding-right:1.75rem}
.xarr-prio-grid{display:grid;gap:.85rem 1rem;margin-bottom:.85rem}
.xarr-prio-grid:last-child{margin-bottom:0}
.xarr-prio-grid-1{grid-template-columns:1fr}
.xarr-prio-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.xarr-icon-btn{position:absolute;top:0;right:0;width:32px;height:32px;border:none;background:transparent;color:var(--muted);cursor:pointer;border-radius:.375rem;font-size:1.1rem;line-height:1;padding:0;display:flex;align-items:center;justify-content:center}
.xarr-icon-btn:hover{color:#b91c1c;background:#fef2f2}
.xarr-prio-delete{background:transparent !important;color:#87B14B !important;text-decoration:none !important}
.xarr-prio-delete:hover{background:transparent !important;color:#E1706D !important;text-decoration:none !important}
.xarr-prio-delete img{display:block;width:18px;height:18px;object-fit:contain}
.xarr-owner-field{display:flex;align-items:center;gap:.5rem}
.xarr-owner-field .xarr-input{flex:1}
.xarr-avatar{width:32px;height:32px;border-radius:50%;background:var(--navy);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}

/* Info icon on field labels */
.xarr-info-icon{display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;border:1.5px solid var(--muted);color:var(--muted);font-size:10px;font-weight:700;font-style:normal;vertical-align:middle;margin-left:.25rem;text-transform:none;letter-spacing:0}

/* Info banner */
.xarr-banner{background:#eef4fc;border:1px solid #bfdbfe;color:#1e3a5f;border-radius:.5rem;padding:.85rem 1rem;font-size:.9rem;margin-bottom:1.25rem;display:flex;gap:.65rem;align-items:flex-start;line-height:1.45}
.xarr-banner.warn{background:#fff8e6;border-color:#fde68a;color:#7c5b00}
.xarr-banner b{font-weight:700}
.xarr-banner-icon{flex-shrink:0;line-height:1.2}
.xarr-btn-sm{padding:.35rem .75rem;font-size:.75rem}

/* Publish step */
.xarr-summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:.85rem 1.5rem;margin:0}
.xarr-summary-item{margin:0}
.xarr-summary-item dt{color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.03em;margin:0 0 .2rem}
.xarr-summary-item dd{margin:0;font-weight:700;color:var(--ink);font-size:16px}
.xarr-review-list{display:flex;flex-direction:column}
.xarr-review-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--border);width:100%;text-align:left;background:transparent;border-left:none;border-right:none;border-top:none;font:inherit;color:inherit}
.xarr-review-row-link{cursor:pointer;border-radius:.375rem;margin:0 -.35rem;padding-left:.35rem;padding-right:.35rem;transition:background .15s ease}
.xarr-review-row-link:hover{background:#f3f4f6}
.xarr-review-row-link:focus-visible{outline:2px solid var(--green);outline-offset:2px}
.xarr-review-row:last-child{border-bottom:none;padding-bottom:0}
.xarr-review-row:first-child{padding-top:0}
.xarr-review-left{display:flex;align-items:flex-start;gap:.75rem;min-width:0;flex:1}
.xarr-review-go{color:var(--green);font-size:1.25rem;font-weight:700;flex-shrink:0;line-height:1}
.xarr-review-check{width:28px;height:28px;border-radius:50%;background:#e9f5e1;color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
.xarr-review-status{color:var(--green);font-size:14px;font-weight:600;margin-top:.15rem}
.xarr-activate-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
.xarr-activate-card{border:1px solid var(--border);border-radius:.5rem;padding:1rem;background:#fafafa}
.xarr-activate-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xarr-activate-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xarr-activate-card p{margin:0;font-size:14px}
.xarr-action-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
.xarr-action-card{border:1px solid var(--border);border-radius:.5rem;padding:1.1rem;background:#fff;display:flex;flex-direction:column;align-items:flex-start}
.xarr-action-card img{display:block;width:40px;height:40px;object-fit:contain;margin-bottom:.65rem}
.xarr-action-card h4{margin:0 0 .35rem;font-size:15px;font-weight:700;color:var(--navy);text-transform:none;letter-spacing:0}
.xarr-action-card p{margin:0 0 1rem;font-size:14px;flex:1}
.xarr-action-card .xarr-btn{width:100%;text-align:center}

/* AI Review dashboard */
.xarr-ai-block{margin-bottom:1rem}
.xarr-ai-heading{margin:0 0 1rem;font-size:1.1rem;font-weight:800;color:var(--navy)}
.xarr-ai-split{display:grid;grid-template-columns:180px 1fr;gap:1.5rem;align-items:center}
.xarr-ai-copy p{margin:0 0 .85rem;line-height:1.5}
.xarr-donut-wrap{display:flex;flex-direction:column;align-items:center;width:150px;margin:0 auto}
.xarr-donut-chart{position:relative;width:150px;height:150px}
.xarr-donut{width:150px;height:150px;transform:rotate(-90deg)}
.xarr-donut-track{fill:none;stroke:#e5e7eb;stroke-width:3.2}
.xarr-donut-value{fill:none;stroke-width:3.2;stroke-linecap:round}
.xarr-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;pointer-events:none}
.xarr-donut-score{font-size:1.35rem;font-weight:800;color:var(--navy);line-height:1.1}
.xarr-donut-score span{font-size:.75rem;font-weight:600;color:var(--muted)}
.xarr-donut-label{margin-top:.5rem;font-size:.85rem;font-weight:600;color:var(--muted);text-align:center;line-height:1.2}
.xarr-check-list{list-style:none;margin:0;padding:0}
.xarr-check-list li{display:flex;gap:.55rem;align-items:flex-start;margin-bottom:.45rem;font-size:16px;line-height:1.4;color:var(--ink)}
.xarr-check{width:20px;height:20px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;margin-top:.1rem}
.xarr-stat-list{display:flex;flex-direction:column;gap:.55rem}
.xarr-stat-row{display:flex;align-items:center;gap:.55rem;font-size:16px}
.xarr-stat-row strong{margin-left:auto;color:var(--navy)}
.xarr-dot{width:10px;height:10px;border-radius:50%;display:inline-block;flex-shrink:0}
.xarr-dot.green{background:#16a34a}
.xarr-dot.amber{background:#ca8a04}
.xarr-dot.red{background:#dc2626}
.xarr-table-scroll{overflow-x:auto}
table.xarr-table{width:100%;border-collapse:collapse;font-size:15px}
table.xarr-table th{text-align:left;padding:.65rem .75rem;color:var(--muted);font-weight:700;border-bottom:1px solid var(--border);font-size:.75rem;text-transform:uppercase;letter-spacing:.03em}
table.xarr-table td{padding:.85rem .75rem;border-bottom:1px solid var(--border);vertical-align:top}
table.xarr-table tr:last-child td{border-bottom:none}
table.xarr-table.xarr-table-gaps{border:1px solid var(--border);border-radius:.35rem;overflow:hidden}
table.xarr-table.xarr-table-gaps th,
table.xarr-table.xarr-table-gaps td{border-right:1px solid var(--border)}
table.xarr-table.xarr-table-gaps th:last-child,
table.xarr-table.xarr-table-gaps td:last-child{border-right:none}
table.xarr-table.xarr-table-gaps th{background:#f8fafc;color:var(--navy)}
table.xarr-table.xarr-table-gaps .xarr-gap-desc-col{width:42%}
table.xarr-table.xarr-table-gaps .xarr-gap-area{width:18%;color:var(--navy)}
table.xarr-table.xarr-table-gaps .xarr-gap-impact,
table.xarr-table.xarr-table-gaps .xarr-gap-priority{width:12%;white-space:nowrap}
.xarr-gap-area strong{font-weight:700}
.xarr-gap-desc{font-size:15px;font-weight:400;color:var(--ink);line-height:1.45}
.xarr-impact{display:inline-flex;align-items:center;gap:.4rem;font-weight:600;font-size:14px}
.xarr-impact .xarr-dot{width:8px;height:8px}
.xarr-impact.high .xarr-dot{background:#dc2626}
.xarr-impact.medium .xarr-dot{background:#ca8a04}
.xarr-impact.low .xarr-dot{background:#2563eb}
.xarr-badge-pill{display:inline-block;padding:.2rem .65rem;border-radius:999px;font-size:12px;font-weight:700}
.xarr-badge-pill.high{background:#fee2e2;color:#991b1b}
.xarr-badge-pill.medium{background:#fef3c7;color:#92400e}
.xarr-badge-pill.low{background:#dbeafe;color:#1e40af}
.xarr-align-list{display:flex;flex-direction:column;gap:.85rem}
.xarr-align-row.xarr-progress-row{display:grid;grid-template-columns:minmax(0,12rem) 1fr 2.75rem;gap:.75rem;align-items:center;margin-bottom:0}
.xarr-align-label{font-size:15px;color:var(--ink);line-height:1.35}
.xarr-align-row.xarr-progress-row .xarr-progress-track{height:10px;margin-top:0}
.xarr-align-row.xarr-progress-row .xarr-progress-pct{font-size:15px;font-weight:700;color:var(--navy);text-align:right}
.xarr-risk-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem}
.xarr-risk-card{display:flex;gap:.65rem;align-items:flex-start;border:1px solid var(--border);border-radius:.5rem;padding:.85rem;background:#fafafa}
.xarr-risk-card p{margin:.2rem 0 0;font-size:13px}
.xarr-risk-title{font-size:15px;color:var(--navy)}
.xarr-risk-icon{flex-shrink:0;display:flex;align-items:center;justify-content:center}
.xarr-risk-card.high .xarr-risk-icon{color:#dc2626}
.xarr-risk-card.medium .xarr-risk-icon{color:#ea580c}
.xarr-risk-card.low .xarr-risk-icon{color:#ca8a04}
.xarr-risk-card.strength .xarr-risk-icon{color:#16a34a}
.xarr-focus-list{display:flex;flex-direction:column;gap:.75rem}
.xarr-focus-item{display:flex;align-items:flex-start;gap:.75rem;font-size:16px;line-height:1.45;color:var(--ink)}
.xarr-focus-item img{flex-shrink:0;width:36px;height:36px;object-fit:contain}

/* Footer nav */
.xarr-footer{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.75rem;border-top:1px solid var(--border);background:#fff;flex-wrap:wrap;gap:.75rem}
.xarr-autosave{color:#16a34a;display:inline-flex;align-items:center;gap:.4rem}
.xarr-autosave-check{width:18px;height:18px;border-radius:50%;background:#16a34a;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0}

/* Evidence checklist (Step 1) */
.xarr-evidence-list{display:flex;flex-direction:column}
.xarr-evidence-row{display:flex;align-items:center;gap:.85rem;padding:.85rem 0;border-bottom:1px solid var(--border)}
.xarr-evidence-row:last-child{border-bottom:none}
.xarr-evidence-icon{width:36px;height:36px;border-radius:50%;background:#eef4fc;color:var(--navy);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.xarr-evidence-title{font-weight:700;font-size:15px}
.xarr-evidence-desc{color:var(--muted);font-size:14px}
.xarr-evidence-status{margin-left:auto;font-size:14px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:.35rem}
.xarr-evidence-status.ok{color:var(--green)}
.xarr-evidence-status.pending{color:var(--muted)}

/* Metric cards (Step 2) */
.xarr-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
.xarr-metric-card{border:1px solid var(--border);border-radius:.5rem;padding:1rem;background:#fff}
.xarr-metric-label{color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.03em;margin:0 0 .4rem}
.xarr-metric-value{font-size:1.6rem;font-weight:800;color:var(--navy);line-height:1}
.xarr-metric-value .unit{font-size:1rem;font-weight:400;color:var(--muted)}
.xarr-metric-trend{font-size:13px;font-weight:600;margin-top:.35rem}
.xarr-metric-trend.up{color:#16a34a}
.xarr-metric-trend.down{color:#dc2626}
.xarr-metric-trend.flat{color:var(--muted)}
.xarr-metric-value.no-data{color:var(--muted);font-size:1rem;font-weight:600}
@media (max-width:1024px){.xarr-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:480px){.xarr-metric-grid{grid-template-columns:1fr}}

/* Discussion guide (Step 4) */
.xarr-guide-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin-bottom:1.25rem}
.xarr-guide-card{border:1px solid var(--border);border-radius:.5rem;padding:.9rem;background:#fafafa}
.xarr-guide-card h4{margin:0 0 .35rem;font-size:14px;color:var(--navy);text-transform:none;letter-spacing:0}
.xarr-guide-card p{margin:0;font-size:13px;color:var(--muted)}
@media (max-width:1024px){.xarr-guide-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:480px){.xarr-guide-grid{grid-template-columns:1fr}}

/* Utility */
.xarr-hidden{display:none !important}
.xarr-section-title{font-weight:800;color:var(--navy);margin:0 0 .3rem}
.xarr-section-desc{color:var(--muted);margin:0 0 1.25rem}
.xarr-placeholder{padding:2rem 1rem;text-align:center}

/* ── Tablet (≤1024px) ── */
@media (max-width:1024px){
#xfarr-wiz h1{font-size:32px}
#xfarr-wiz h2{font-size:26px}
#xfarr-wiz h3{font-size:24px}
#xfarr-wiz h4{font-size:22px}
.xarr-header,.xarr-steps,.xarr-body,.xarr-footer{padding-left:1.25rem;padding-right:1.25rem}
.xarr-body{flex-direction:column}
.xarr-sidebar{width:100%}
.xarr-steps{overflow-x:hidden}
.xarr-steps-inner{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem .75rem;max-width:100%;min-width:0;padding:0;justify-items:center}
.xarr-step{flex:none;min-width:0;width:100%;max-width:150px}
.xarr-step .xarr-step-circle{width:30px;height:30px;font-size:14px}
.xarr-step-line{display:none}
.xarr-step .xarr-step-label{font-size:11px;max-width:100%;line-height:1.25}
.xarr-step-underline{width:100%!important;margin-top:.5rem}
.xarr-prio-grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}
.xarr-ai-split{grid-template-columns:1fr;justify-items:center}
.xarr-ai-copy{width:100%}
.xarr-risk-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xarr-activate-grid,.xarr-action-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.xarr-summary-grid{grid-template-columns:1fr}
}

/* ── Mobile (≤768px) ── */
@media (max-width:768px){
#xfarr-wiz{font-size:16px;border-radius:0;border-left:none;border-right:none}
#xfarr-wiz h1{font-size:26px;line-height:1.15}
#xfarr-wiz h2{font-size:22px;line-height:1.2}
#xfarr-wiz h3{font-size:20px}
#xfarr-wiz h4{font-size:20px}
#xfarr-wiz p{font-size:16px}
.xarr-header{padding:1.25rem 1rem}
.xarr-header-inner{flex-direction:column;align-items:stretch}
.xarr-header-actions{width:100%}
.xarr-header-actions .xarr-btn{flex:1;text-align:center}
.xarr-steps{padding:1rem 1rem .75rem}
.xarr-steps-inner{grid-template-columns:repeat(3,1fr);gap:1rem .5rem}
.xarr-step{max-width:140px}
.xarr-step .xarr-step-circle{width:28px;height:28px;font-size:13px}
.xarr-step .xarr-step-label{font-size:10px}
.xarr-body{padding:1rem;gap:1rem}
.xarr-card{padding:1rem;margin-bottom:.75rem}
.xarr-footer{flex-direction:column;align-items:stretch;padding:1rem;gap:.75rem}
.xarr-footer>.xarr-btn{width:100%;text-align:center}
.xarr-footer .xarr-row{width:100%;flex-direction:column}
.xarr-footer .xarr-row .xarr-btn{width:100%;text-align:center}
.xarr-autosave{text-align:center;font-size:14px;justify-content:center}
.xarr-btn{padding:.65rem 1rem;font-size:15px;white-space:normal}
.xarr-dl,.xarr-dl dt,.xarr-dl dd,.xarr-dl .xarr-badge{font-size:15px}
.xarr-field-label{font-size:16px}
.xarr-field-desc,.xarr-field textarea{font-size:15px}
.xarr-prio-card{flex-direction:column;padding:1rem}
.xarr-prio-rail{flex-direction:row;justify-content:flex-start;gap:.65rem}
.xarr-prio-body{padding-right:0;padding-top:.25rem}
.xarr-prio-grid-4{grid-template-columns:1fr}
.xarr-icon-btn{top:-.25rem;right:-.25rem}
.xarr-risk-grid{grid-template-columns:1fr}
.xarr-donut-wrap{width:130px}
.xarr-donut-chart,.xarr-donut{width:130px;height:130px}
.xarr-activate-grid,.xarr-action-grid{grid-template-columns:1fr}
.xarr-review-row{flex-wrap:wrap}
}

/* ── Small mobile (≤480px) ── */
@media (max-width:480px){
#xfarr-wiz h1{font-size:22px}
#xfarr-wiz h2{font-size:20px}
.xarr-header-actions{flex-direction:column}
.xarr-header-actions .xarr-btn{width:100%}
.xarr-steps-inner{grid-template-columns:repeat(2,1fr);gap:1rem .5rem}
.xarr-step{max-width:none}
.xarr-step .xarr-step-circle{width:26px;height:26px;font-size:12px}
.xarr-step .xarr-step-label{font-size:10px}
}

/* Dashboard filter bar (Step 2) */
.xarr-filter-bar{display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1rem}
.xarr-filter-field{min-width:0}
.xarr-filter-field label{display:block;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.03em;margin-bottom:.3rem}

/* KPI trend rows (Step 2) */
.xarr-kpi-list{display:flex;flex-direction:column;gap:.55rem}
.xarr-kpi-row{display:flex;align-items:center;gap:.5rem;font-size:14px}
.xarr-kpi-row .name{flex:1;color:var(--ink)}
.xarr-kpi-row .delta{font-weight:700;display:inline-flex;align-items:center;gap:.2rem}
.xarr-kpi-row .delta.up{color:#16a34a}
.xarr-kpi-row .delta.down{color:#dc2626}

/* Historical bar chart (Step 2) */
.xarr-bar-chart{display:flex;align-items:flex-end;gap:.85rem;height:110px;padding:0 .25rem}
.xarr-bar-chart .xarr-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%}
.xarr-bar-chart .xarr-bar{width:100%;max-width:36px;background:#5f9a3f;border-radius:.25rem .25rem 0 0}
.xarr-bar-chart .xarr-bar-label{margin-top:.4rem;font-size:12px;color:var(--muted)}
.xarr-bar-chart .xarr-bar-value{font-size:11px;color:var(--navy);font-weight:700;margin-bottom:.25rem}

/* Trend highlight chips (Step 2) */
.xarr-highlight-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
.xarr-highlight-item{display:flex;gap:.65rem;align-items:flex-start}
.xarr-highlight-item .icon{font-size:1.3rem;flex-shrink:0}
.xarr-highlight-item p{margin:0;font-size:13px;color:var(--muted);line-height:1.4}
@media (max-width:1024px){.xarr-highlight-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}

/* Data quality / privacy flow (Step 1) */
.xarr-privacy-flow{display:flex;flex-direction:column;gap:.4rem;align-items:stretch;margin:.75rem 0}
.xarr-privacy-step{border:1px solid var(--border);border-radius:.375rem;padding:.5rem .75rem;font-size:13px;font-weight:600;color:var(--navy);text-align:center;background:#fafafa}
.xarr-privacy-step.highlight{background:#e9f5e1;border-color:var(--green);color:var(--green)}
.xarr-privacy-arrow{text-align:center;color:var(--muted);font-size:12px}

/* Executive reflection fields (Step 4) */
.xarr-reflect-field{margin-bottom:1.35rem;display:flex;gap:.75rem}
.xarr-reflect-icon{width:34px;height:34px;border-radius:50%;background:#eef4fc;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;margin-top:.1rem}
.xarr-reflect-body{flex:1;min-width:0}
.xarr-reflect-body label{display:block;font-weight:800;font-size:15px;color:var(--navy);text-transform:uppercase;letter-spacing:.02em;margin-bottom:.15rem}
.xarr-reflect-body .xarr-muted{margin:0 0 .4rem;font-size:14px}

/* Recommendation guide chips (Step 5 sidebar) */
.xarr-guide-check{display:flex;flex-direction:column;gap:.5rem}
.xarr-guide-check div{display:flex;gap:.5rem;align-items:flex-start;font-size:14px}

/* Synthesis section list (Step 6) */
.xarr-synth-list{display:flex;flex-direction:column}
.xarr-synth-row{display:flex;gap:.85rem;align-items:flex-start;padding:1rem 0;border-bottom:1px solid var(--border)}
.xarr-synth-row:last-child{border-bottom:none;padding-bottom:0}
.xarr-synth-row:first-child{padding-top:0}
.xarr-synth-icon{width:40px;height:40px;border-radius:50%;background:#eef4fc;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.xarr-synth-body{flex:1;min-width:0}
.xarr-synth-body h4{margin:0 0 .2rem;font-size:15px;color:var(--navy);text-transform:none;letter-spacing:0}
.xarr-synth-body p{margin:0;font-size:14px;color:var(--muted)}
CSS;
}
