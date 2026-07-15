<?php
/**
 * Step 1 — Organizational Foundation™ (UI shell).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_foundation_js(): string
{
    return <<<'JS'
foundation: function () {
    function field(key, label, desc, placeholder, maxlen, rows) {
        rows = rows || 4;
        return '<div class="xar-field" data-field="' + key + '">' +
            '<div class="xar-field-head">' +
            '<h3 class="xar-field-label">' + label + '</h3>' +
            '<span class="xar-field-count">0 / ' + maxlen + '</span>' +
            '</div>' +
            '<p class="xar-field-desc">' + desc + '</p>' +
            '<textarea rows="' + rows + '" placeholder="' + placeholder + '" data-maxlen="' + maxlen + '"></textarea>' +
            '</div>';
    }
    return '<h2 class="xar-section-title">Step 1. Organizational Foundation™</h2>' +
        '<p class="xar-section-desc">Define the strategic context that anchors your Annual Readiness Plan.</p>' +
        field('mission', 'Mission', 'Why your organization exists. What unique value do you provide?', 'Enter mission statement...', 500, 3) +
        field('vision', 'Vision', 'What future do you intend to create?', 'Enter vision statement...', 500, 3) +
        field('core_values', 'Core Values (Optional)', 'The guiding principles that shape how you operate.', 'Enter core values...', 1000, 4) +
        field('organizational_description', 'Organizational Description', 'Describe your organization, what you do, who you serve, and what makes you unique.', 'Enter organizational description...', 1000, 4) +
        field('business_environment', 'Business Environment', 'Key external factors, industry conditions, and market dynamics impacting your organization.', 'Enter business environment...', 1000, 4) +
        field('executive_narrative', 'Executive Narrative', 'Leadership perspective on where the organization is today and where it is headed.', 'Enter executive narrative...', 2000, 6);
}
JS;
}
