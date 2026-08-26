<?php
/**
 * Step 4 — Executive Strategic Reflection™.
 *
 * Real: loads/saves via Laravel (wp_fusion_arr_executive_reflections, one
 * row per ARR). The 8 reflection prompts + Conversation Notes are saved by
 * the wizard's real Save Draft button (header/footer) via
 * window.xarrSaveReflectionStep, dispatched from arr-save-draft.php — same
 * pattern as IRR Step 4/5. Each field also autosaves individually on blur
 * so a field is never lost if the user navigates away without clicking
 * Save Draft. AI Insight & Guidance / Discussion Tips remain static
 * educational copy (not data-backed, same as the mockup).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfarr_wizard_step_reflection_js(): string
{
    return <<<'JS'
reflection: function () {
    var fields = [
        ['&#128218;', 'Organizational Learning', 'What were our most important organizational learnings this year?', 'organizational_learning'],
        ['&#128200;', 'Readiness Progression', 'How has our organizational readiness progressed over the past year?', 'readiness_progression'],
        ['&#127919;', 'Strategic Assumptions', 'What assumptions about our strategy or environment were validated or challenged?', 'strategic_assumptions'],
        ['&#9888;&#65039;', 'Organizational Barriers', 'What barriers continue to limit our performance and growth?', 'organizational_barriers'],
        ['&#11088;', 'Organizational Strengths', 'What are our greatest strengths that we should leverage more?', 'organizational_strengths'],
        ['&#128101;', 'Leadership Effectiveness', 'How effective was our leadership this year? What should we continue, stop, or start?', 'leadership_effectiveness'],
        ['&#128202;', 'Resource Allocation', 'Did we allocate our resources to the right priorities? What should change?', 'resource_allocation'],
        ['&#128640;', 'Future Opportunities', 'What opportunities should we pursue to accelerate our future state next year?', 'future_opportunities'],
    ];
    return '<h2 class="xarr-section-title">Step 4. Executive Strategic Reflection™</h2>' +
        '<p class="xarr-section-desc">This is your opportunity to reflect on the year\'s evidence, discuss key insights, and explore what they mean for our future. The AI has prepared talking points and insights to guide your conversation.</p>' +
        '<div class="xarr-banner">&#8505;&#65039; <span>This is the primary executive learning conversation. The AI informs. Leadership decides.</span></div>' +
        '<p class="xarr-muted" id="xarr-reflection-status" style="margin:-.4rem 0 .6rem"></p>' +
        fields.map(function (f) {
            return '<div class="xarr-reflect-field"><div class="xarr-reflect-icon">' + f[0] + '</div>' +
                '<div class="xarr-reflect-body"><label>' + f[1] + '</label>' +
                '<p class="xarr-muted">' + f[2] + '</p>' +
                '<textarea class="xarr-input" data-field="' + f[3] + '" rows="3" maxlength="4000"></textarea></div></div>';
        }).join('') +

        '<div class="xarr-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">' +
        '<div class="xarr-card" style="margin-bottom:0"><h4 style="margin-top:0">&#10024; AI Insight &amp; Guidance</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">Based on your annual evidence and AI assessment, here are key areas to explore in your reflection.</p>' +
        '<ul class="xarr-check-list">' +
        '<li>Readiness improved in Alignment and Communication.</li>' +
        '<li>Behavioral Drivers trend strongest in Get Real and Drive Growth.</li>' +
        '<li>Leadership bench strength is developing, but consistency is needed.</li>' +
        '<li>Resource constraints remain a moderate risk to execution.</li>' +
        '<li>Opportunities exist to strengthen cross-functional collaboration and innovation.</li>' +
        '</ul></div>' +
        '<div class="xarr-card" style="margin-bottom:0"><h4 style="margin-top:0">&#128172; Discussion Tips</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">To maximize the value of your reflection conversation:</p>' +
        '<ul class="xarr-check-list">' +
        '<li>Be open and candid.</li><li>Focus on patterns, not isolated events.</li>' +
        '<li>Challenge assumptions.</li><li>Prioritize what will have the greatest impact.</li>' +
        '<li>Capture decisions and next steps.</li>' +
        '</ul></div></div>' +

        '<div class="xarr-card"><h4 style="margin-top:0">&#128221; Conversation Notes</h4>' +
        '<p class="xarr-muted" style="margin-top:-.2rem">Capture key insights, decisions, and reflections from your discussion.</p>' +
        '<textarea class="xarr-input" id="xarr-reflection-notes" data-field="conversation_notes" rows="4" maxlength="4000" placeholder="Start typing your notes here..."></textarea>' +
        '<p class="xarr-muted" style="font-size:12px;margin-top:.3rem" id="xarr-notes-count">0 / 4000 characters</p>' +
        '</div>';
}
JS;
}

function xfarr_wizard_reflection_init_js(): string
{
    return <<<'JS'
(function () {
    function fieldEls() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-field]'));
    }

    function collectValues() {
        var values = {};
        fieldEls().forEach(function (el) { values[el.dataset.field] = el.value; });
        return values;
    }

    function applyValues(data) {
        fieldEls().forEach(function (el) {
            el.value = (data && data[el.dataset.field]) ? data[el.dataset.field] : '';
        });
        var notes = document.getElementById('xarr-reflection-notes');
        var count = document.getElementById('xarr-notes-count');
        if (notes && count) count.textContent = notes.value.length + ' / 4000 characters';
    }

    function wireAutosaveOnBlur() {
        fieldEls().forEach(function (el) {
            if (el.dataset.wired) return;
            el.dataset.wired = '1';
            el.addEventListener('blur', function () {
                if (typeof window.xfarrSaveReflection !== 'function') return;
                var field = el.dataset.field;
                var payload = {};
                payload[field] = el.value;
                window.xfarrSaveReflection(payload).then(function (res) {
                    if (typeof window.xarrSetAutosaveStatus === 'function') {
                        if (res && res.success) {
                            window.xarrSetAutosaveStatus('Draft autosaved', false);
                        } else {
                            window.xarrSetAutosaveStatus((res && res.message) ? res.message : 'Save failed.', true);
                        }
                    }
                });
            });
        });
        var notes = document.getElementById('xarr-reflection-notes');
        var count = document.getElementById('xarr-notes-count');
        if (notes && count && !notes.dataset.countWired) {
            notes.dataset.countWired = '1';
            notes.addEventListener('input', function () { count.textContent = notes.value.length + ' / 4000 characters'; });
        }
    }

    window.xarrSaveReflectionStep = function () {
        if (typeof window.xfarrSaveReflection !== 'function') {
            return Promise.resolve({ success: false, message: 'Reflection service unavailable.' });
        }
        return window.xfarrSaveReflection(collectValues());
    };

    window.initReflectionStep = function () {
        var statusEl = document.getElementById('xarr-reflection-status');
        if (window.XFARR_WIZARD && window.XFARR_WIZARD.canEdit === false) {
            fieldEls().forEach(function (el) { el.setAttribute('readonly', 'readonly'); });
        }

        wireAutosaveOnBlur();

        if (typeof window.xfarrLoadReflection !== 'function') {
            if (statusEl) statusEl.textContent = 'Reflection service unavailable.';
            return;
        }

        if (statusEl) statusEl.textContent = 'Loading reflection…';
        window.xfarrLoadReflection().then(function (data) {
            applyValues(data);
            if (statusEl) statusEl.textContent = data ? 'Loaded saved reflection.' : '';
        });
    };
})();
JS;
}
