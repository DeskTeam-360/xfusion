<?php
/**
 * Step 7 — Publish ARP™: versioning bridge (archive / publish / version history).
 *
 * Reuses xfarp_picker_api_request() from arp-picker.php.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_xfarp_publish_versions', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('GET', "/{$arpId}/versions"));
});

add_action('wp_ajax_xfarp_publish_version_detail', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_GET['arp_id']) ? absint($_GET['arp_id']) : 0;
    $versionId = isset($_GET['version_id']) ? absint($_GET['version_id']) : 0;
    if ($arpId < 1 || $versionId < 1) {
        wp_send_json_error(['message' => 'arp_id and version_id are required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('GET', "/{$arpId}/versions/{$versionId}", [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarp_publish_archive', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/archive-version", [], [
        'user_id' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfarp_publish_now', function (): void {
    check_ajax_referer('xfarp_wizard_save_draft', 'nonce');
    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $arpId = isset($_POST['arp_id']) ? absint($_POST['arp_id']) : 0;
    if ($arpId < 1) {
        wp_send_json_error(['message' => 'arp_id is required.'], 422);
    }

    xfarp_picker_send(xfarp_picker_api_request('POST', "/{$arpId}/publish", [], [
        'user_id' => get_current_user_id(),
    ]));
});

/**
 * JS: real archive/publish calls + version history fetch, replacing the
 * Step 7 UI-shell alerts with actual Laravel-backed actions.
 */
function xfarp_wizard_publish_service_js(): string
{
    return <<<'JS'
window.xarLoadArpVersions = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.resolve([]);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarp_publish_versions');
    params.set('nonce', window.XFARP_WIZARD.nonce);
    params.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success && Array.isArray(json.data)) ? json.data : []; })
        .catch(function () { return []; });
};

window.xarLoadArpVersionDetail = function (versionId) {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId || !versionId) {
        return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'xfarp_publish_version_detail');
    params.set('nonce', window.XFARP_WIZARD.nonce);
    params.set('arp_id', String(window.XFARP_WIZARD.arpId));
    params.set('version_id', String(versionId));

    return fetch(window.XFARP_WIZARD.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) { return (json && json.success) ? json.data : null; })
        .catch(function () { return null; });
};

window.xarArchiveArpVersion = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_publish_archive');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

window.xarPublishArpNow = function () {
    if (!window.XFARP_WIZARD || !window.XFARP_WIZARD.arpId) {
        return Promise.reject(new Error('No ARP selected.'));
    }
    var payload = new URLSearchParams();
    payload.set('action', 'xfarp_publish_now');
    payload.set('nonce', window.XFARP_WIZARD.nonce);
    payload.set('arp_id', String(window.XFARP_WIZARD.arpId));

    return fetch(window.XFARP_WIZARD.ajaxUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
    }).then(function (res) { return res.json(); });
};

/* -------------------------------------------------------------------
 * Version History sidebar card (between "About This Step" and
 * "Progress") — lists every archived/published snapshot for this ARP
 * and opens a read-only modal with the full snapshot content.
 * ------------------------------------------------------------------- */
function xarVersionHistoryEsc(s) {
    return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function xarVersionHistoryFormatDate(iso) {
    if (!iso) return '—';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return String(iso);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) +
        ' ' + d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

function xarVersionHistoryEnsureModal() {
    var overlay = document.getElementById('xar-version-modal-overlay');
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.id = 'xar-version-modal-overlay';
    overlay.className = 'xar-modal-overlay xar-hidden';
    overlay.innerHTML =
        '<div class="xar-modal">' +
        '<div class="xar-modal-head">' +
        '<h3 id="xar-version-modal-title">Version</h3>' +
        '<button type="button" class="xar-modal-close" id="xar-version-modal-close" aria-label="Close">&times;</button>' +
        '</div>' +
        '<div class="xar-modal-body" id="xar-version-modal-body"></div>' +
        '</div>';
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            xarVersionHistoryCloseModal();
        }
    });
    document.getElementById('xar-version-modal-close').addEventListener('click', xarVersionHistoryCloseModal);

    return overlay;
}

function xarVersionHistoryCloseModal() {
    var overlay = document.getElementById('xar-version-modal-overlay');
    if (overlay) overlay.classList.add('xar-hidden');
}

/* Same field labels/order the real Step 1/2/5 forms use, so a version
   being viewed looks like the actual form instead of a generic key/value
   dump. Falls back to a Title-Cased version of the key for anything not
   listed here (forward-compatible with new fields). */
var XAR_VERSION_FIELD_LABELS = {
    mission: 'Mission',
    vision: 'Vision',
    core_values: 'Core Values (Optional)',
    organizational_description: 'Organizational Description',
    business_environment: 'Business Environment',
    executive_narrative: 'Executive Narrative',
    future_state_narrative: 'Future State Narrative',
    future_characteristics: 'Future Organizational Characteristics',
    desired_culture: 'Desired Organizational Culture',
    desired_customer_experience: 'Desired Customer Experience',
    desired_employee_experience: 'Desired Employee Experience',
    desired_leadership_environment: 'Desired Leadership Environment',
    assumptions: 'Key Organizational Assumptions',
    risks: 'Potential Risks',
    opportunities: 'Potential Opportunities',
    learning_objectives: 'Learning Objectives',
    leadership_questions: 'Questions Leadership Intends to Answer',
};

function xarVersionHistoryFieldLabel(key) {
    return XAR_VERSION_FIELD_LABELS[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
}

/* Read-only rendering of the same .xar-field layout Steps 1/2/5 use for
   their live textareas — a bordered box showing the saved text instead
   of an editable control. */
function xarVersionHistoryRenderTextRows(rows, fieldOrder) {
    if (!rows) return '';
    var keys = fieldOrder && fieldOrder.length ? fieldOrder : Object.keys(rows);
    var html = '';
    keys.forEach(function (key) {
        var value = rows[key];
        if (value === null || value === undefined || value === '') return;
        html += '<div class="xar-field">' +
            '<div class="xar-field-head"><h3 class="xar-field-label">' + xarVersionHistoryEsc(xarVersionHistoryFieldLabel(key)) + '</h3></div>' +
            '<div class="xar-version-readonly-value">' + xarVersionHistoryEsc(String(value)).replace(/\n/g, '<br>') + '</div>' +
            '</div>';
    });
    return html || '<p class="xar-muted">No content recorded.</p>';
}

/* Read-only rendering of the same .xar-prio-card layout Steps 3/4 use
   for readiness/strategic priority cards. */
function xarVersionHistoryRenderPriorityCards(items, config) {
    if (!items || !items.length) {
        return '<p class="xar-muted">None recorded.</p>';
    }
    return '<div class="xar-prio-list">' + items.map(function (item, i) {
        var fieldsHtml = config.fields.map(function (f) {
            var value = item[f.key];
            if (value === null || value === undefined || value === '') return '';
            return '<div><span class="xar-version-subfield-label">' + xarVersionHistoryEsc(f.label) + '</span>' +
                '<div class="xar-version-readonly-value xar-version-readonly-value-sm">' + xarVersionHistoryEsc(String(value)).replace(/\n/g, '<br>') + '</div></div>';
        }).join('');
        return '<div class="xar-prio-card">' +
            '<div class="xar-prio-rail"><div class="xar-prio-num">' + (i + 1) + '</div></div>' +
            '<div class="xar-prio-body">' +
            '<h3 class="xar-field-label" style="margin-bottom:.65rem">' + xarVersionHistoryEsc(item[config.titleKey] || 'Untitled') + '</h3>' +
            '<div class="xar-version-subfield-grid">' + fieldsHtml + '</div>' +
            '</div></div>';
    }).join('') + '</div>';
}

function xarVersionHistoryRenderSnapshot(snapshot) {
    if (!snapshot) {
        return '<p class="xar-muted">No snapshot content stored for this version.</p>';
    }

    var html = '';

    html += '<div class="xar-version-section"><h4>Organizational Foundation™</h4>' +
        xarVersionHistoryRenderTextRows(snapshot.foundation, ['mission', 'vision', 'core_values', 'organizational_description', 'business_environment', 'executive_narrative']) + '</div>';

    html += '<div class="xar-version-section"><h4>Future State™</h4>' +
        xarVersionHistoryRenderTextRows(snapshot.future_state, ['future_state_narrative', 'future_characteristics', 'desired_culture', 'desired_customer_experience', 'desired_employee_experience', 'desired_leadership_environment']) + '</div>';

    html += '<div class="xar-version-section"><h4>Organizational Readiness™ (' +
        ((snapshot.readiness_priorities || []).length) + ')</h4>' +
        xarVersionHistoryRenderPriorityCards(snapshot.readiness_priorities, {
            titleKey: 'name',
            fields: [
                { key: 'cor_capability', label: 'COR Capability™' },
                { key: 'primary_driver', label: 'Primary Behavioral Driver™' },
                { key: 'priority_level', label: 'Priority Level' },
                { key: 'description', label: 'Description' },
                { key: 'business_rationale', label: 'Business Rationale' },
                { key: 'expected_impact', label: 'Expected Impact' },
            ],
        }) + '</div>';

    html += '<div class="xar-version-section"><h4>Strategic Priorities™ (' +
        ((snapshot.strategic_priorities || []).length) + ')</h4>' +
        xarVersionHistoryRenderPriorityCards(snapshot.strategic_priorities, {
            titleKey: 'title',
            fields: [
                { key: 'description', label: 'Description' },
                { key: 'target_date', label: 'Target Date' },
                { key: 'success_measures', label: 'Success Measures' },
                { key: 'status', label: 'Status' },
            ],
        }) + '</div>';

    html += '<div class="xar-version-section"><h4>Organizational Learning™</h4>' +
        xarVersionHistoryRenderTextRows(snapshot.learning, ['assumptions', 'risks', 'opportunities', 'learning_objectives', 'leadership_questions']) + '</div>';

    if (snapshot.learnings && snapshot.learnings.length) {
        html += '<div class="xar-version-section"><h4>Learnings (' + snapshot.learnings.length + ')</h4>' +
            xarVersionHistoryRenderPriorityCards(snapshot.learnings, {
                titleKey: 'title',
                fields: [{ key: 'description', label: 'Description' }],
            }) + '</div>';
    }

    return html;
}

window.xarShowVersionModal = function (detail) {
    var overlay = xarVersionHistoryEnsureModal();
    var title = document.getElementById('xar-version-modal-title');
    var body = document.getElementById('xar-version-modal-body');

    if (!detail) {
        title.textContent = 'Version';
        body.innerHTML = '<p class="xar-muted">Unable to load this version.</p>';
        overlay.classList.remove('xar-hidden');
        return;
    }

    title.textContent = 'Version ' + detail.version + (detail.status === 'published' ? ' — Published' : ' — Archived');
    var meta = '<p class="xar-muted" style="margin-top:-.25rem">' +
        (detail.published_at ? 'Published ' + xarVersionHistoryFormatDate(detail.published_at) : 'Saved ' + xarVersionHistoryFormatDate(detail.created_at)) +
        '</p>';
    var fallbackNotice = detail._isLiveFallback
        ? '<div class="xar-banner warn" style="margin-bottom:1rem"><span class="xar-banner-icon" aria-hidden="true">&#9888;</span>' +
          '<span>The stored snapshot for this version couldn\'t be loaded, so this shows the <b>current in-progress data</b> instead — it may not exactly match what was saved at publish time.</span></div>'
        : '';
    body.innerHTML = meta + fallbackNotice + xarVersionHistoryRenderSnapshot(detail.snapshot);
    overlay.classList.remove('xar-hidden');
};

/**
 * Fallback for when the real snapshot endpoint can't be reached (e.g. the
 * Laravel API hasn't been deployed with it yet): approximates the LATEST
 * version's content from whatever step data is already loaded in this
 * wizard session. Only valid for the latest version — older archived
 * versions can't be reconstructed from current live data.
 */
function xarBuildLatestVersionDetailFromCurrentData(versionRow) {
    return {
        version: versionRow.version,
        status: versionRow.status,
        published_at: versionRow.published_at,
        created_at: versionRow.created_at,
        _isLiveFallback: true,
        snapshot: {
            foundation: window.xarFoundationCache || null,
            future_state: window.xarFutureStateCache || null,
            readiness_priorities: window.xarReadinessCache || [],
            strategic_priorities: window.xarStrategicCache || [],
            learning: window.xarLearningCache || null,
            learnings: [],
        },
    };
}

window.xarInitVersionHistoryCard = function () {
    var list = document.getElementById('xar-version-history-list');
    var emptyEl = document.getElementById('xar-version-history-empty');
    if (!list || !emptyEl || typeof window.xarLoadArpVersions !== 'function') {
        return;
    }

    emptyEl.textContent = 'Loading…';
    list.innerHTML = '';

    window.xarLoadArpVersions().then(function (versions) {
        if (!versions || !versions.length) {
            emptyEl.textContent = 'No versions yet. Archive or publish to create one.';
            return;
        }

        emptyEl.style.display = 'none';
        var versionsById = {};
        list.innerHTML = '<ul class="xar-version-history-list">' + versions.map(function (v, i) {
            versionsById[v.id] = v;
            var badgeClass = v.status === 'published' ? 'green' : 'amber';
            var dateLabel = v.published_at ? xarVersionHistoryFormatDate(v.published_at) : xarVersionHistoryFormatDate(v.created_at);
            return '<li>' +
                '<div class="xar-version-history-row">' +
                '<span class="xar-badge ' + badgeClass + '" style="font-size:12px">v' + xarVersionHistoryEsc(v.version) + '</span>' +
                '<span class="xar-muted" style="font-size:12px">' + xarVersionHistoryEsc(dateLabel) + '</span>' +
                '<button type="button" class="xar-link xar-version-view-btn" data-version-id="' + v.id + '" data-is-latest="' + (i === 0 ? '1' : '0') + '" style="font-size:12px;font-weight:600;color:#5f9a3f;background:none;border:none;cursor:pointer;padding:0;text-decoration:underline">View</button>' +
                '</div></li>';
        }).join('') + '</ul>';

        list.querySelectorAll('.xar-version-view-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var versionId = parseInt(btn.getAttribute('data-version-id'), 10);
                var isLatest = btn.getAttribute('data-is-latest') === '1';
                btn.textContent = 'Loading…';
                window.xarLoadArpVersionDetail(versionId).then(function (detail) {
                    btn.textContent = 'View';
                    if (!detail && isLatest) {
                        detail = xarBuildLatestVersionDetailFromCurrentData(versionsById[versionId]);
                    }
                    window.xarShowVersionModal(detail);
                });
            });
        });
    }).catch(function () {
        emptyEl.textContent = 'Unable to load version history.';
    });
};
JS;
}
