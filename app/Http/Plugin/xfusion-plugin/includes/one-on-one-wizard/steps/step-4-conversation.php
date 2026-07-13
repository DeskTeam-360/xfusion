<?php
/**
 * Step 4 — Alignment Conversation™ (UI shell, static dummy data).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_conversation_js(): string
{
    return <<<'JS'
conversation: function () {
    var items = [
        ['priorities', '#dbeafe;color:#2563eb', '\u{1F3AF}', '1. Current Priorities', 'Discuss current priorities, alignment with team and organizational goals, and any shifts or new focus areas.'],
        ['progress', '#dcfce7;color:#16a34a', '\u{1F4C8}', '2. Progress', 'Reflect on progress since the last meeting. What\'s working well and what impact is being made?'],
        ['barriers', '#f1e9fb;color:#7c3aed', '⚠️', '3. Barriers', 'Explore obstacles or challenges that may be impacting performance or progress.'],
        ['development', '#fde8d7;color:#ea580c', '\u{1F464}', '4. Development', 'Discuss growth opportunities, skill development, and experiences that will support future success.'],
        ['support', '#e0f2f7;color:#0891b2', '\u{1F91D}', '5. Support', 'Talk about the support needed and how leadership can better enable success.'],
        ['future_opportunities', '#e9f5e1;color:#3f7d1f', '⭐', '6. Future Opportunities', 'Look ahead. What opportunities exist for growth, impact, or advancement?'],
    ];
    function guideItem(it) {
        return '<div class="xfw-guide-item" data-section="' + it[0] + '">' +
            '<div class="xfw-guide-row">' +
            '<div class="xfw-guide-icon" style="background:' + it[1].split(';')[0] + ';color:' + it[1].split(';')[1].replace('color:','') + '">' + it[2] + '</div>' +
            '<div class="xfw-guide-body"><div class="xfw-guide-title">' + it[3] + '</div><div class="xfw-guide-desc">' + it[4] + '</div></div>' +
            '<button type="button" class="xfw-guide-notes-toggle" aria-expanded="false">' +
            '&#128172; Notes (optional) <span class="xfw-chevron">&#9662;</span></button>' +
            '</div>' +
            '<div class="xfw-guide-notes-panel xfw-hidden" data-field="' + it[0] + '" data-type="textarea">' +
            '<textarea rows="3" placeholder="Add optional notes for this section..." data-maxlen="1000"></textarea>' +
            '<div class="xfw-guide-notes-meta"><span class="count">0 / 1000</span></div>' +
            '</div></div>';
    }
    return '<h2 class="xfw-section-title">Step 4. Alignment Conversation™</h2>' +
        '<p class="xfw-section-desc">This is your conversation. Use the insights, ratings, and preparation to guide a meaningful discussion. Focus on listening, understanding, and aligning. The system is here to support your dialogue.</p>' +
        '<div class="xfw-banner">ℹ️ <span>This section is for conversation, not documentation. Use the prompts below to guide your discussion. Add notes only when needed to capture key takeaways or action items.</span></div>' +
        '<div class="xfw-card">' +
        '<h3 style="margin-bottom:.5rem">Conversation Guide</h3>' +
        items.map(guideItem).join('') +
        '</div>' +
        '<div class="xfw-card" style="margin-top:1rem">' +
        '<h3>Conversation Notes <span class="xfw-muted" style="font-weight:400">(private to both participants)</span></h3>' +
        '<div class="xfw-textarea-field" data-field="general" data-type="textarea">' +
        '<textarea rows="4" placeholder="Capture key discussion points, insights, or follow-up items here..." data-maxlen="2000" style="width:100%;border:1px solid var(--border);border-radius:.375rem;padding:.6rem;font-size:.85rem;box-sizing:border-box;font-family:inherit"></textarea>' +
        '<div class="xfw-guide-notes-meta" style="text-align:right;margin-top:.25rem"><span class="count">0 / 2000</span></div>' +
        '</div>' +
        '<p class="xfw-muted" style="margin-top:.4rem">&#128274; These notes are private to both participants and become part of the meeting record.</p>' +
        '</div>';
}
JS;
}
