<?php
/**
 * Step 5 — Organizational Learning™ (assumptions, risks, opportunities, learning).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarp_wizard_step_learning_js(): string
{
    return <<<'JS'
learning: function () {
    var saved = window.xarLearningCache || {};
    function field(key, label, desc, placeholder) {
        var val = saved[key] || '';
        return '<div class="xar-field" data-field="' + key + '">' +
            '<div class="xar-field-head">' +
            '<h3 class="xar-field-label">' + label +
            ' <span class="xar-info-icon" title="More information" aria-hidden="true">i</span></h3>' +
            '<span class="xar-field-count">' + val.length + ' / 2000</span>' +
            '</div>' +
            '<p class="xar-field-desc">' + desc + '</p>' +
            '<textarea rows="5" placeholder="' + placeholder + '" data-maxlen="2000" data-key="' + key + '">' +
            escHtml(val) +
            '</textarea></div>';
    }
    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
    return '<h2 class="xar-section-title">Step 5. Organizational Learning™</h2>' +
        '<p class="xar-section-desc">Capture the strategic assumptions, risks, opportunities, and key learning objectives that will guide leadership throughout the year.</p>' +
        field('assumptions', 'Key Organizational Assumptions',
            'What assumptions are we making about the future that must be true for our plan to succeed?',
            'Enter key organizational assumptions...') +
        field('risks', 'Potential Risks',
            'What internal or external risks could impact our ability to achieve our strategic priorities?',
            'Enter potential risks...') +
        field('opportunities', 'Potential Opportunities',
            'What internal or external opportunities could accelerate our success?',
            'Enter potential opportunities...') +
        field('learning_objectives', 'Learning Objectives',
            'What must we learn or improve to strengthen our readiness and achieve our future state?',
            'Enter learning objectives...') +
        field('leadership_questions', 'Questions Leadership Intends to Answer',
            'What key questions will we seek to answer over the course of the year?',
            'Enter key questions...');
}
JS;
}

function xfarp_wizard_learning_init_js(): string
{
    return <<<'JS'
(function () {
    window.initLearningStep = function () {
        if (!window.xarLearningCache) {
            window.xarLearningCache = {};
        }
        var main = document.getElementById('xar-main');
        if (!main) {
            return;
        }
        main.querySelectorAll('.xar-field textarea[data-key]').forEach(function (t) {
            t.addEventListener('input', function () {
                window.xarLearningCache[t.getAttribute('data-key')] = t.value;
            });
        });
    };
})();
JS;
}
