<?php
/**
 * Step 2 — Future State™ (UI shell).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_future_state_js(): string
{
    return <<<'JS'
future_state: function () {
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';
    function field(key, label, desc, placeholder, maxlen, rows) {
        rows = rows || 4;
        return '<div class="xar-field" data-field="' + key + '">' +
            '<div class="xar-field-head">' +
            '<h3 class="xar-field-label">' + label + '</h3>' +
            '<span class="xar-field-count">0 / ' + maxlen + '</span>' +
            '</div>' +
            '<p class="xar-field-desc">' + desc + '</p>' +
            '<textarea rows="' + rows + '" placeholder="' + placeholder + '" data-maxlen="' + maxlen + '"></textarea>' +
            '<div class="xar-field-ai">' +
            '<button type="button" class="xar-ai-assist" data-field="' + key + '">' +
            '<img src="' + iconBase + 'Sparkle-Icon.svg" alt="" width="18" height="18">' +
            'Get AI Assistance' +
            '</button></div></div>';
    }
    return '<h2 class="xar-section-title">Step 2. Future State™</h2>' +
        '<p class="xar-section-desc">Define the future your organization is committed to creating. Describe the desired future state and the key characteristics that will define success.</p>' +
        field('future_state_narrative', 'Future State Narrative', 'Describe the future state your organization is working to create.', 'Enter future state narrative...', 2000, 6) +
        field('future_characteristics', 'Future Organizational Characteristics', 'What will be true about your organization when you achieve your future state?', 'Enter future organizational characteristics...', 1000, 4) +
        field('desired_culture', 'Desired Organizational Culture', 'What culture will support and sustain your future state?', 'Enter desired organizational culture...', 1000, 4) +
        field('desired_customer_experience', 'Desired Customer Experience', 'How will customers experience the difference your organization creates?', 'Enter desired customer experience...', 1000, 4) +
        field('desired_employee_experience', 'Desired Employee Experience', 'How will employees experience working in the organization?', 'Enter desired employee experience...', 1000, 4) +
        field('desired_leadership_environment', 'Desired Leadership Environment', 'What will leadership look like and how will leaders operate?', 'Enter desired leadership environment...', 1000, 4);
}
JS;
}
