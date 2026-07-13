<?php
/**
 * Load Draft — fetch saved GF values for preparation + conversation notes.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfoo_wizard_load_draft', 'xfoo_wizard_ajax_load_draft');

function xfoo_wizard_ajax_load_draft(): void
{
    check_ajax_referer('xfoo_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $conversationId = isset($_GET['conversation_id']) ? absint($_GET['conversation_id']) : 0;
    if ($conversationId < 1) {
        wp_send_json_error(['message' => 'conversation_id is required.'], 422);
    }

    if (isset($_GET['user_role'])) {
        $_REQUEST['user_role'] = sanitize_key(wp_unslash($_GET['user_role']));
    }

    $data = xfoo_wizard_load_draft_data($conversationId);

    wp_send_json_success([
        'conversation_id' => $conversationId,
        'employee' => $data['employee'],
        'leader' => $data['leader'],
        'conversation' => $data['conversation'],
    ]);
}

/**
 * JS: fetch draft from server, cache, and apply to step UI.
 */
function xfoo_wizard_load_draft_js(): string
{
    return <<<'JS'
window.xfwDraftCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };

var xfwResetDraftCache = function () {
    window.xfwDraftCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };
};

var applyPreparationDraft = function (data) {
    if (!data) {
        return;
    }
    ['employee', 'leader'].forEach(function (role) {
        var values = data[role] || {};
        if (!Object.keys(values).length) {
            return;
        }
        var col = root.querySelector('.xfw-prep-col.' + role);
        if (!col) {
            return;
        }
        col.querySelectorAll('[data-field][data-type="scale"]').forEach(function (el) {
            var val = values[el.dataset.field];
            if (!val) {
                return;
            }
            el.querySelectorAll('.xfw-scale-btn').forEach(function (b) {
                b.classList.remove('selected', 'employee', 'leader');
                if (String(b.dataset.value) === String(val)) {
                    b.classList.add('selected');
                }
            });
        });
        col.querySelectorAll('[data-field][data-type="textarea"]').forEach(function (el) {
            var val = values[el.dataset.field];
            if (val === undefined) {
                return;
            }
            var ta = el.querySelector('textarea');
            if (!ta) {
                return;
            }
            ta.value = val;
            var counter = el.querySelector('.count');
            if (counter) {
                var max = parseInt(ta.dataset.maxlen, 10) || 0;
                counter.textContent = val.length + ' / ' + max;
            }
        });
    });
};

var applyConversationDraft = function (data) {
    if (!data) {
        return;
    }
    var values = data.conversation || data;
    root.querySelectorAll('[data-field][data-type="textarea"]').forEach(function (el) {
        if (el.closest('.xfw-prep-col')) {
            return;
        }
        var val = values[el.dataset.field];
        if (!val) {
            return;
        }
        var ta = el.querySelector('textarea');
        if (!ta) {
            return;
        }
        ta.value = val;
        var panel = el.closest('.xfw-guide-notes-panel');
        if (panel) {
            panel.classList.remove('xfw-hidden');
            var item = panel.closest('.xfw-guide-item');
            if (item) {
                var toggle = item.querySelector('.xfw-guide-notes-toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                    var chevron = toggle.querySelector('.xfw-chevron');
                    if (chevron) {
                        chevron.innerHTML = '&#9652;';
                    }
                }
            }
            var counter = panel.querySelector('.count');
            if (counter) {
                var max = parseInt(ta.dataset.maxlen, 10) || 0;
                counter.textContent = val.length + ' / ' + max;
            }
        }
    });
};

var loadWizardDraft = function (force) {
    if (!window.XFW_WIZARD) {
        return Promise.resolve(null);
    }
    var cid = xfwGetActiveConversationId();
    if (cid > 0) {
        window.XFW_WIZARD.conversationId = cid;
        if (root) {
            root.dataset.conversationId = String(cid);
        }
    }
    if (!cid) {
        return Promise.resolve(null);
    }
    if (!force && window.xfwDraftCache.loaded && window.xfwDraftCache.conversationId === cid) {
        return Promise.resolve(window.xfwDraftCache.data);
    }
    if (window.xfwDraftCache.loading && window.xfwDraftCache.conversationId === cid && window.xfwDraftCache._promise) {
        return window.xfwDraftCache._promise;
    }

    window.xfwDraftCache.loading = true;
    window.xfwDraftCache.conversationId = cid;

    var url = window.XFW_WIZARD.ajaxUrl + '?action=xfoo_wizard_load_draft&nonce=' +
        encodeURIComponent(window.XFW_WIZARD.nonce) + '&conversation_id=' + cid;
    if (window.XFW_WIZARD.userRole) {
        url += '&user_role=' + encodeURIComponent(window.XFW_WIZARD.userRole);
    }

    window.xfwDraftCache._promise = fetch(url, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !json.data) {
                window.xfwDraftCache.data = { employee: {}, leader: {}, conversation: {} };
            } else {
                window.xfwDraftCache.data = json.data;
            }
            window.xfwDraftCache.loaded = true;
            return window.xfwDraftCache.data;
        })
        .catch(function () {
            window.xfwDraftCache.data = { employee: {}, leader: {}, conversation: {} };
            window.xfwDraftCache.loaded = true;
            return window.xfwDraftCache.data;
        })
        .finally(function () {
            window.xfwDraftCache.loading = false;
        });

    return window.xfwDraftCache._promise;
};

var applyDraftForCurrentStep = function () {
    if (!window.xfwDraftCache || !window.xfwDraftCache.data || typeof STEPS === 'undefined') {
        return;
    }
    var key = STEPS[current] ? STEPS[current].key : '';
    if (key === 'preparation') {
        applyPreparationDraft(window.xfwDraftCache.data);
    }
    if (key === 'conversation') {
        applyConversationDraft(window.xfwDraftCache.data);
    }
};

window.xfwOnDraftLoaded = function () {
    applyDraftForCurrentStep();
};

if (root && typeof xfwInitMeetingGate === 'function' && !root.dataset.meetingGateInit) {
    root.dataset.meetingGateInit = '1';
    xfwInitMeetingGate();
}
JS;
}
