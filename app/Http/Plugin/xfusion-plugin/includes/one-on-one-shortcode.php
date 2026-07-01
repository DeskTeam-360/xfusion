<?php
/**
 * XFusion — 1-on-1 Alignment Capture™ shortcode (WordPress)
 *
 * Usage: [fusion_one_on_one]
 *
 * Bridges to Laravel /api/v1/one-on-one/* via WP admin-ajax so the
 * FUSION_API_TOKEN bearer token never reaches the browser.
 *
 * Privacy: preparation content for the other party is never returned by
 * Laravel until reveal() is called. This file only renders what the API
 * gives — visibility rules are enforced server-side.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('XFUSION_LARAVEL_API_BASE')) {
    define('XFUSION_LARAVEL_API_BASE', 'https://admin.sandbox.xperiencefusion.com');
}

if (! defined('XFUSION_API_BEARER_TOKEN')) {
    define('XFUSION_API_BEARER_TOKEN', '');
}

/**
 * @return array{ok: bool, code: int, body: mixed, error: ?string}
 */
function xfusion_oo_api_request(string $method, string $path, array $query = [], array $body = []): array
{
    $base = rtrim(XFUSION_LARAVEL_API_BASE, '/');
    $url  = $base . '/api/v1/one-on-one' . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $headers = ['Accept' => 'application/json'];
    if (XFUSION_API_BEARER_TOKEN !== '') {
        $headers['Authorization'] = 'Bearer ' . XFUSION_API_BEARER_TOKEN;
    }

    $args = [
        'method'    => $method,
        'timeout'   => 20,
        'sslverify' => true,
        'headers'   => $headers,
    ];

    if ($method !== 'GET') {
        $headers['Content-Type'] = 'application/json';
        $args['headers']         = $headers;
        $args['body']            = wp_json_encode($body);
    }

    $res = wp_remote_request($url, $args);

    if (is_wp_error($res)) {
        return ['ok' => false, 'code' => 0, 'body' => null, 'error' => $res->get_error_message()];
    }

    $code    = (int) wp_remote_retrieve_response_code($res);
    $decoded = json_decode(wp_remote_retrieve_body($res), true);

    return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'body' => $decoded, 'error' => null];
}

function xfusion_oo_send(array $result): void
{
    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json(array_merge(['success' => false, 'message' => $result['error'] ?? ($body['message'] ?? 'Request failed.')], $body), 200);
    }
    wp_send_json($result['body']);
}

function xfusion_oo_require_login(): void
{
    if (! is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => __('You must be logged in.', 'xfusion')], 401);
    }
    check_ajax_referer('xfusion_one_on_one', 'nonce');
}

// ---------------------------------------------------------------------------
// AJAX handlers
// ---------------------------------------------------------------------------

add_action('wp_ajax_xfusion_oo_pairs', function (): void {
    xfusion_oo_require_login();
    xfusion_oo_send(xfusion_oo_api_request('GET', '/pairs', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfusion_oo_conversations', function (): void {
    xfusion_oo_require_login();
    $pairId = (int) ($_POST['pair_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/{$pairId}/conversations"));
});

add_action('wp_ajax_xfusion_oo_schedule', function (): void {
    xfusion_oo_require_login();
    $pairId      = (int) ($_POST['pair_id'] ?? 0);
    $scheduledAt = sanitize_text_field($_POST['scheduled_at'] ?? '');
    $meetingLink = esc_url_raw(wp_unslash($_POST['meeting_link'] ?? ''));
    $body = ['scheduled_at' => $scheduledAt];
    if ($meetingLink !== '') {
        $body['meeting_link'] = $meetingLink;
    }
    xfusion_oo_send(xfusion_oo_api_request('POST', "/{$pairId}/conversations", [], $body));
});

add_action('wp_ajax_xfusion_oo_my_preparation', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/my-preparation", ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfusion_oo_save_preparation', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $role           = sanitize_text_field($_POST['author_role'] ?? '');
    $content        = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/preparation", [], [
        'author_role'    => $role,
        'author_user_id' => get_current_user_id(),
        'content'        => $content,
    ]));
});

add_action('wp_ajax_xfusion_oo_preparation_status', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/preparation-status"));
});

add_action('wp_ajax_xfusion_oo_reveal', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/reveal"));
});

add_action('wp_ajax_xfusion_oo_brief', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/brief"));
});

add_action('wp_ajax_xfusion_oo_get_notes', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/notes"));
});

add_action('wp_ajax_xfusion_oo_save_note', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/notes", [], [
        'section'    => sanitize_text_field($_POST['section'] ?? 'general'),
        'note'       => sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')),
        'created_by' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfusion_oo_get_commitments', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('GET', "/conversations/{$conversationId}/commitments"));
});

add_action('wp_ajax_xfusion_oo_save_commitment', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/commitments", [], [
        'title'       => sanitize_text_field($_POST['title'] ?? ''),
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'owner_role'  => sanitize_text_field($_POST['owner_role'] ?? 'shared'),
        'due_date'    => sanitize_text_field($_POST['due_date'] ?? '') ?: null,
    ]));
});

add_action('wp_ajax_xfusion_oo_complete', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/complete"));
});

// ---------------------------------------------------------------------------
// Shortcode
// ---------------------------------------------------------------------------

function xfusion_one_on_one_shortcode(): string
{
    if (! is_user_logged_in()) {
        return '<p>' . esc_html__('Please log in to view your 1-on-1 conversations.', 'xfusion') . '</p>';
    }

    $nonce   = wp_create_nonce('xfusion_one_on_one');
    $ajaxUrl = esc_url(admin_url('admin-ajax.php'));

    ob_start();
    ?>
<div id="xfusion-oo-app"
     data-ajax-url="<?php echo $ajaxUrl; ?>"
     data-nonce="<?php echo esc_attr($nonce); ?>"
     data-user-id="<?php echo (int) get_current_user_id(); ?>">
    <div id="xfoo-left">
        <div id="xfoo-pairs-panel"><p class="xfoo-muted">Loading…</p></div>
    </div>
    <div id="xfoo-right">
        <div id="xfoo-conv-panel"></div>
        <div id="xfoo-workspace" style="display:none;"></div>
    </div>
</div>

<style>
#xfusion-oo-app{max-width:780px;margin:0 auto;font-family:inherit;color:#111}
#xfoo-left,#xfoo-right{min-width:0}
@media (min-width:1440px){
    #xfusion-oo-app{max-width:1200px;display:grid;grid-template-columns:320px 1fr;gap:1.25rem;align-items:start}
    #xfoo-left{position:sticky;top:1rem}
}
.xfoo-card{border:1px solid #e5e7eb;border-radius:.5rem;padding:1.2rem;margin-bottom:1rem;background:#fff}
.xfoo-card h3{margin:0 0 .75rem;font-size:1rem}
.xfoo-card h4{margin:.9rem 0 .4rem;font-size:.875rem;font-weight:600}
.xfoo-btn{cursor:pointer;border:1px solid #2563eb;background:#2563eb;color:#fff;border-radius:.375rem;padding:.35rem .85rem;font-size:.82rem;line-height:1.4}
.xfoo-btn.secondary{background:#fff;color:#2563eb}
.xfoo-btn.danger{border-color:#dc2626;background:#dc2626;color:#fff}
.xfoo-btn.success{border-color:#16a34a;background:#16a34a;color:#fff}
.xfoo-btn:disabled{opacity:.5;cursor:default}
textarea.xfoo-input,input.xfoo-input,select.xfoo-input{width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.35rem .55rem;margin:.15rem 0 .5rem;font-size:.85rem;box-sizing:border-box}
.xfoo-badge{display:inline-block;padding:.1rem .5rem;border-radius:999px;font-size:.7rem;background:#f3f4f6;color:#374151}
.xfoo-badge.green{background:#dcfce7;color:#166534}
.xfoo-badge.amber{background:#fef9c3;color:#854d0e}
.xfoo-badge.blue{background:#dbeafe;color:#1e40af}
.xfoo-muted{color:#6b7280;font-size:.82rem}
.xfoo-row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-bottom:.4rem}
.xfoo-note-item{background:#f9fafb;border:1px solid #e5e7eb;border-radius:.375rem;padding:.5rem .75rem;margin-bottom:.35rem;font-size:.85rem}
.xfoo-commitment-item{display:flex;gap:.5rem;align-items:flex-start;background:#f9fafb;border:1px solid #e5e7eb;border-radius:.375rem;padding:.5rem .75rem;margin-bottom:.35rem;font-size:.85rem}
.xfoo-commitment-item .title{flex:1;font-weight:500}
.xfoo-prep-box{background:#fffbeb;border:1px solid #fde68a;border-radius:.375rem;padding:.75rem 1rem;margin-bottom:.5rem;font-size:.85rem}
.xfoo-prep-box.other{background:#eff6ff;border-color:#bfdbfe}
table.xfoo-table{width:100%;border-collapse:collapse;font-size:.83rem}
table.xfoo-table th{text-align:left;padding:.35rem .5rem;background:#f9fafb;font-weight:600;border-bottom:1px solid #e5e7eb}
table.xfoo-table td{padding:.35rem .5rem;border-bottom:1px solid #f3f4f6;vertical-align:middle}
.xfoo-section-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin:.75rem 0 .25rem}
</style>

<script>
(function () {
    var ROOT     = document.getElementById('xfusion-oo-app');
    if (!ROOT) return;
    var AJAX_URL = ROOT.dataset.ajaxUrl;
    var NONCE    = ROOT.dataset.nonce;
    var USER_ID  = parseInt(ROOT.dataset.userId, 10);
    var pairsEl  = ROOT.querySelector('#xfoo-pairs-panel');
    var convEl   = ROOT.querySelector('#xfoo-conv-panel');
    var wsEl     = ROOT.querySelector('#xfoo-workspace');

    // -----------------------------------------------------------------------
    // HTTP helper
    // -----------------------------------------------------------------------
    function call(action, data) {
        var body = new URLSearchParams(Object.assign({ action: action, nonce: NONCE }, data || {}));
        return fetch(AJAX_URL, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); });
    }

    // -----------------------------------------------------------------------
    // Button loading state helpers — double-click safe
    // -----------------------------------------------------------------------
    function btnLoading(btn, label) {
        if (btn.dataset.busy === '1') return false;   // already in-flight → reject
        btn.dataset.busy = '1';
        btn.disabled = true;
        btn.dataset.originalText = btn.textContent;
        btn.textContent = label || 'Saving…';
        return true;
    }

    function btnDone(btn, label, durationMs) {
        btn.textContent = label || 'Saved!';
        setTimeout(function () {
            btn.disabled = false;
            btn.dataset.busy = '';
            btn.textContent = btn.dataset.originalText || btn.textContent;
        }, durationMs || 1200);
    }

    function btnError(btn) {
        btn.disabled = false;
        btn.dataset.busy = '';
        btn.textContent = btn.dataset.originalText || 'Error';
    }

    // Guard wrapper: call async fn only if button is not already busy.
    function withBtn(btn, loadingText, fn) {
        if (!btnLoading(btn, loadingText)) return;
        var p;
        try { p = fn(); } catch (e) { btnError(btn); return; }
        if (p && typeof p.then === 'function') {
            p.then(function () { /* caller handles btnDone */ })
             .catch(function () { btnError(btn); });
        }
    }

    // -----------------------------------------------------------------------
    // Pairs list
    // -----------------------------------------------------------------------
    function loadPairs() {
        pairsEl.innerHTML = '<p class="xfoo-muted">Loading…</p>';
        call('xfusion_oo_pairs').then(function (res) {
            if (!res.success) {
                pairsEl.innerHTML = '<p class="xfoo-muted">' + (res.message || 'Unable to load pairs.') + '</p>';
                return;
            }
            var pairs = res.data || [];
            if (pairs.length === 0) {
                pairsEl.innerHTML = '<p class="xfoo-muted">No 1-on-1 pairs found.</p>';
                return;
            }
            // Index pairs by id so openConversation can look up names
            var pairsMap = {};
            pairs.forEach(function (p) { pairsMap[p.id] = p; });

            var html = '<div class="xfoo-card"><label style="font-weight:600;font-size:.85rem">Select pairing</label>' +
                '<select class="xfoo-input" id="xfoo-pair-select" style="margin-bottom:0">';
            pairs.forEach(function (p) {
                var other = p.role === 'leader' ? p.employee : p.leader;
                html += '<option value="' + p.id + '" data-role="' + p.role + '">' +
                    (other ? other.name : '—') + ' (' + p.role + ')</option>';
            });
            html += '</select></div>';
            pairsEl.innerHTML = html;
            var sel = document.getElementById('xfoo-pair-select');
            sel.addEventListener('change', function () {
                wsEl.style.display = 'none';
                wsEl.innerHTML = '';
                loadConversations(pairsMap);
            });
            loadConversations(pairsMap);
        });
    }

    // -----------------------------------------------------------------------
    // Conversations list
    // -----------------------------------------------------------------------
    function loadConversations(pairsMap) {
        var sel    = document.getElementById('xfoo-pair-select');
        var pairId = sel.value;
        var role   = sel.selectedOptions[0].dataset.role;
        var pair   = pairsMap[pairId];
        var listEl = convEl;
        listEl.innerHTML = '<div class="xfoo-card"><p class="xfoo-muted">Loading conversations…</p></div>';

        call('xfusion_oo_conversations', { pair_id: pairId }).then(function (res) {
            if (!res.success) { listEl.innerHTML = '<div class="xfoo-card"><p class="xfoo-muted">' + (res.message || 'Error') + '</p></div>'; return; }
            var rows = res.data || [];
            var html = '<div class="xfoo-card"><h3 style="margin-top:0">Conversations with ' +
                escHtml((role === 'leader' ? (pair.employee ? pair.employee.name : '—') : (pair.leader ? pair.leader.name : '—'))) +
                '</h3>' +
                '<table class="xfoo-table" style="margin-top:.5rem"><thead><tr>' +
                '<th>Scheduled</th><th>Link</th><th>Status</th><th></th></tr></thead><tbody>';
            rows.forEach(function (c) {
                var badge = c.status === 'completed' ? 'green' : (c.status === 'in_progress' ? 'blue' : 'amber');
                var linkCell = c.meeting_link
                    ? '<a href="' + escHtml(c.meeting_link) + '" target="_blank" rel="noopener" style="font-size:.8rem">Join</a>'
                    : '<span class="xfoo-muted">—</span>';
                html += '<tr><td>' + (c.scheduled_at || '—') + '</td>' +
                    '<td>' + linkCell + '</td>' +
                    '<td><span class="xfoo-badge ' + badge + '">' + c.status + '</span></td>' +
                    '<td><button class="xfoo-btn secondary" data-open="' + c.id + '" data-role="' + role + '" data-link="' + escHtml(c.meeting_link || '') + '">Open</button></td></tr>';
            });
            html += '</tbody></table>';

            // Schedule form — leader only
            if (role === 'leader') {
                html += '<div style="margin-top:.75rem;border-top:1px solid #f3f4f6;padding-top:.75rem">' +
                    '<div class="xfoo-section-label" style="margin-top:0">Schedule new conversation</div>' +
                    '<div class="xfoo-row">' +
                    '<input type="datetime-local" class="xfoo-input" id="xfoo-new-date" style="width:auto;flex:1" placeholder="Date & time"/>' +
                    '</div>' +
                    '<input type="url" class="xfoo-input" id="xfoo-new-link" placeholder="Meeting link (Zoom, Meet, Teams…) — optional"/>' +
                    '<button class="xfoo-btn" id="xfoo-schedule-btn">Schedule</button>' +
                    '</div>';
            }

            html += '</div>';
            listEl.innerHTML = html;

            listEl.querySelectorAll('[data-open]').forEach(function (btn) {
                btn.addEventListener('click', function () { openConversation(btn.dataset.open, btn.dataset.role, pair, btn.dataset.link || ''); });
            });
            if (role === 'leader') {
                document.getElementById('xfoo-schedule-btn').addEventListener('click', function () {
                    var dt   = document.getElementById('xfoo-new-date').value;
                    var link = document.getElementById('xfoo-new-link').value.trim();
                    if (!dt) return;
                    var btn = this;
                    withBtn(btn, 'Scheduling…', function () {
                        return call('xfusion_oo_schedule', { pair_id: pairId, scheduled_at: dt, meeting_link: link }).then(function () {
                            document.getElementById('xfoo-new-date').value = '';
                            document.getElementById('xfoo-new-link').value = '';
                            btnDone(btn, 'Scheduled!');
                            loadConversations(pairsMap);
                        });
                    });
                });
            }
        });
    }

    // -----------------------------------------------------------------------
    // Open one conversation — load all data on entry
    // -----------------------------------------------------------------------
    function openConversation(conversationId, role, pair, meetingLink) {
        // Resolve display names from the pair object
        var myName    = role === 'leader' ? (pair.leader   ? pair.leader.name   : 'Leader')   : (pair.employee ? pair.employee.name : 'Employee');
        var otherName = role === 'leader' ? (pair.employee ? pair.employee.name : 'Employee') : (pair.leader   ? pair.leader.name   : 'Leader');
        var otherRole = role === 'leader' ? 'employee' : 'leader';

        // Map role → name for notes/commitments display
        function nameFor(r) {
            if (r === 'leader')   return pair.leader   ? pair.leader.name   : 'Leader';
            if (r === 'employee') return pair.employee ? pair.employee.name : 'Employee';
            return 'Shared';
        }

        wsEl.style.display = 'block';
        wsEl.innerHTML =
            '<div class="xfoo-card" id="xfoo-ws-inner">' +
            '<div class="xfoo-row" style="justify-content:space-between">' +
            '<h3 style="margin:0">Conversation #' + conversationId + ' <span class="xfoo-badge blue">' + role + ' — ' + escHtml(myName) + '</span></h3>' +
            (role === 'leader'
                ? '<button class="xfoo-btn secondary" id="xfoo-export-btn">Export conversation</button>'
                : '') +
            '</div>' +

            // Meeting link (visible to both parties)
            (meetingLink
                ? '<p style="margin:.4rem 0 .6rem"><a href="' + escHtml(meetingLink) + '" target="_blank" rel="noopener" class="xfoo-btn secondary" style="display:inline-flex;align-items:center;gap:.3rem">&#128249; Join meeting</a></p>'
                : '') +

            // Prep status bar
            '<p id="xfoo-prep-status" class="xfoo-muted" style="margin:.5rem 0 .75rem"></p>' +

            // Own preparation
            '<div class="xfoo-section-label">Your preparation</div>' +
            '<div id="xfoo-my-prep-display"></div>' +
            '<textarea class="xfoo-input" id="xfoo-prep-text" rows="3" placeholder="Private — not visible to the other party until the meeting starts"></textarea>' +
            '<div class="xfoo-row">' +
            '<button class="xfoo-btn" id="xfoo-save-prep">Save preparation</button>' +
            '<button class="xfoo-btn secondary" id="xfoo-reveal-btn">Start meeting &amp; reveal preparations</button>' +
            '</div>' +

            // Other party's preparation (shown after reveal)
            '<div id="xfoo-other-prep"></div>' +

            // AI brief (shown after reveal)
            '<div id="xfoo-brief"></div>' +

            // Notes
            '<div class="xfoo-section-label">Notes</div>' +
            '<div id="xfoo-notes-list"></div>' +
            '<div class="xfoo-row">' +
            '<textarea class="xfoo-input" id="xfoo-note-text" rows="2" placeholder="Add a note" style="flex:1;margin:0"></textarea>' +
            '</div>' +
            '<div class="xfoo-row" style="margin-top:.3rem">' +
            '<select class="xfoo-input" id="xfoo-note-section" style="width:auto">' +
            '<option value="general">General</option><option value="wins">Wins</option>' +
            '<option value="blockers">Blockers</option><option value="alignment">Alignment</option><option value="growth">Growth</option>' +
            '</select>' +
            '<button class="xfoo-btn secondary" id="xfoo-add-note">Add note</button>' +
            '</div>' +

            // Commitments
            '<div class="xfoo-section-label">Commitments</div>' +
            '<div id="xfoo-commitments-list"></div>' +
            '<input class="xfoo-input" id="xfoo-commit-title" placeholder="Commitment title"/>' +
            '<div class="xfoo-row">' +
            '<select class="xfoo-input" id="xfoo-commit-owner" style="width:auto">' +
            '<option value="shared">Shared</option><option value="employee">Employee</option><option value="leader">Leader</option>' +
            '</select>' +
            '<input type="date" class="xfoo-input" id="xfoo-commit-due" style="width:auto"/>' +
            '<button class="xfoo-btn secondary" id="xfoo-add-commit">Add commitment</button>' +
            '</div>' +

            // Complete
            '<div style="margin-top:1rem;border-top:1px solid #e5e7eb;padding-top:1rem">' +
            '<button class="xfoo-btn success" id="xfoo-complete-btn">Complete meeting &amp; generate AI synthesis</button>' +
            '</div>' +
            '<div id="xfoo-synthesis"></div>' +
            '</div>';

        // --- load own preparation ---
        function loadMyPrep() {
            call('xfusion_oo_my_preparation', { conversation_id: conversationId }).then(function (res) {
                var el = document.getElementById('xfoo-my-prep-display');
                if (res.success && res.data) {
                    el.innerHTML = '<div class="xfoo-prep-box">' + escHtml(res.data.content) + '</div>';
                    document.getElementById('xfoo-prep-text').value = res.data.content;
                } else {
                    el.innerHTML = '';
                }
            });
        }

        // --- load notes ---
        function loadNotes() {
            call('xfusion_oo_get_notes', { conversation_id: conversationId }).then(function (res) {
                var el = document.getElementById('xfoo-notes-list');
                if (!res.success || !(res.data || []).length) { el.innerHTML = '<p class="xfoo-muted">No notes yet.</p>'; return; }
                // Map created_by user id → name
                var leaderUid   = pair.leader   ? pair.leader.id   : null;
                var employeeUid = pair.employee ? pair.employee.id : null;
                el.innerHTML = res.data.map(function (n) {
                    var authorName = n.created_by == leaderUid
                        ? nameFor('leader')
                        : (n.created_by == employeeUid ? nameFor('employee') : 'User #' + n.created_by);
                    return '<div class="xfoo-note-item">' +
                        '<span class="xfoo-badge">' + escHtml(n.section) + '</span> ' +
                        '<span class="xfoo-muted" style="margin-right:.4rem">' + escHtml(authorName) + ':</span>' +
                        escHtml(n.note) + '</div>';
                }).join('');
            });
        }

        // --- load commitments ---
        function loadCommitments() {
            call('xfusion_oo_get_commitments', { conversation_id: conversationId }).then(function (res) {
                var el = document.getElementById('xfoo-commitments-list');
                if (!res.success || !(res.data || []).length) { el.innerHTML = '<p class="xfoo-muted">No commitments yet.</p>'; return; }
                el.innerHTML = res.data.map(function (c) {
                    var badge      = c.status === 'done' ? 'green' : (c.status === 'in_progress' ? 'blue' : '');
                    var ownerLabel = c.owner_role === 'shared'
                        ? 'Shared'
                        : nameFor(c.owner_role) + ' (' + c.owner_role + ')';
                    return '<div class="xfoo-commitment-item">' +
                        '<span class="title">' + escHtml(c.title) + '</span>' +
                        '<span class="xfoo-badge">' + escHtml(ownerLabel) + '</span>' +
                        '<span class="xfoo-badge ' + badge + '">' + escHtml(c.status) + '</span>' +
                        (c.due_date ? '<span class="xfoo-muted">Due: ' + escHtml(c.due_date) + '</span>' : '') +
                        '</div>';
                }).join('');
            });
        }

        // --- check prep status + reveal state ---
        function checkPrepStatus() {
            call('xfusion_oo_preparation_status', { conversation_id: conversationId }).then(function (res) {
                if (!res.success) return;
                var d = res.data;
                var statusEl = document.getElementById('xfoo-prep-status');
                if (statusEl) {
                    var leaderName   = pair.leader   ? pair.leader.name   : 'Leader';
                    var employeeName = pair.employee ? pair.employee.name : 'Employee';
                    statusEl.textContent =
                        employeeName + ': ' + (d.employee_submitted ? 'submitted' : 'pending') +
                        ' · ' + leaderName + ': ' + (d.leader_submitted ? 'submitted' : 'pending') +
                        (d.revealed ? ' · Revealed' : '');
                }
                // If already revealed, load the other party's prep immediately
                if (d.revealed) {
                    loadOtherPrep();
                    loadBrief();
                    var revealBtn = document.getElementById('xfoo-reveal-btn');
                    if (revealBtn) revealBtn.style.display = 'none';
                }
            });
        }

        // --- load other party's preparation (only after reveal) ---
        function loadOtherPrep() {
            call('xfusion_oo_reveal', { conversation_id: conversationId }).then(function (res) {
                if (!res.success) return;
                var el = document.getElementById('xfoo-other-prep');
                var html = '<div class="xfoo-section-label">Both preparations</div>';
                (res.data || []).forEach(function (p) {
                    var label = nameFor(p.author_role) + ' (' + p.author_role + ')';
                    html += '<div class="xfoo-prep-box' + (p.author_role !== role ? ' other' : '') + '">' +
                        '<strong>' + escHtml(label) + '</strong><br>' + escHtml(p.content) + '</div>';
                });
                el.innerHTML = html;
            });
        }

        // --- load AI brief ---
        function loadBrief() {
            call('xfusion_oo_brief', { conversation_id: conversationId }).then(function (res) {
                var el = document.getElementById('xfoo-brief');
                if (!res.success) { el.innerHTML = '<p class="xfoo-muted">AI Meeting Brief not yet available.</p>'; return; }
                el.innerHTML = '<div class="xfoo-section-label">AI Meeting Brief</div>' +
                    '<div class="xfoo-prep-box" style="white-space:pre-wrap">' + escHtml(JSON.stringify(res.data, null, 2)) + '</div>';
            });
        }

        // --- event listeners ---
        document.getElementById('xfoo-save-prep').addEventListener('click', function () {
            var content = document.getElementById('xfoo-prep-text').value.trim();
            if (!content) return;
            var btn = this;
            withBtn(btn, 'Saving…', function () {
                return call('xfusion_oo_save_preparation', {
                    conversation_id: conversationId, author_role: role, content: content,
                }).then(function () {
                    btnDone(btn, 'Saved!');
                    loadMyPrep();
                    checkPrepStatus();
                });
            });
        });

        document.getElementById('xfoo-reveal-btn').addEventListener('click', function () {
            if (!confirm('Start the meeting? Both preparations will become visible to each other.')) return;
            var btn = this;
            if (btn.dataset.busy === '1') return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Starting meeting…';
            loadOtherPrep();
            loadBrief();
            setTimeout(function () { btn.style.display = 'none'; }, 800);
        });

        document.getElementById('xfoo-add-note').addEventListener('click', function () {
            var t = document.getElementById('xfoo-note-text');
            if (!t.value.trim()) return;
            var btn = this;
            var section = document.getElementById('xfoo-note-section').value;
            withBtn(btn, 'Saving…', function () {
                return call('xfusion_oo_save_note', { conversation_id: conversationId, section: section, note: t.value }).then(function () {
                    t.value = '';
                    btnDone(btn, 'Added!');
                    loadNotes();
                });
            });
        });

        document.getElementById('xfoo-add-commit').addEventListener('click', function () {
            var t = document.getElementById('xfoo-commit-title');
            if (!t.value.trim()) return;
            var btn = this;
            withBtn(btn, 'Saving…', function () {
                return call('xfusion_oo_save_commitment', {
                    conversation_id: conversationId,
                    title: t.value,
                    owner_role: document.getElementById('xfoo-commit-owner').value,
                    due_date: document.getElementById('xfoo-commit-due').value || '',
                }).then(function () {
                    t.value = '';
                    btnDone(btn, 'Added!');
                    loadCommitments();
                });
            });
        });

        document.getElementById('xfoo-complete-btn').addEventListener('click', function () {
            if (!confirm('Mark this meeting as completed and generate AI synthesis?')) return;
            var btn = this;
            withBtn(btn, 'Completing meeting…', function () {
                return call('xfusion_oo_complete', { conversation_id: conversationId }).then(function (res) {
                    btnDone(btn, 'Completed!', 3000);
                    var el = document.getElementById('xfoo-synthesis');
                    if (!res.success) { el.innerHTML = '<p class="xfoo-muted">Completed. AI synthesis unavailable — Python service not configured yet.</p>'; return; }
                    el.innerHTML = res.data.synthesis_available
                        ? '<div class="xfoo-section-label">AI Meeting Synthesis</div>' +
                          '<div class="xfoo-prep-box" style="white-space:pre-wrap">' + escHtml(JSON.stringify(res.data.synthesis, null, 2)) + '</div>'
                        : '<p class="xfoo-muted">Completed. AI synthesis unavailable — Python service not configured yet.</p>';
                });
            });
        });

        // Export — leader only
        if (role === 'leader') {
            document.getElementById('xfoo-export-btn').addEventListener('click', function () {
                var btn = this;
                withBtn(btn, 'Preparing export…', function () {
                    return exportConversation(conversationId).then(function () {
                        btnDone(btn, 'Exported!');
                    });
                });
            });
        }

        // --- initial load ---
        loadMyPrep();
        loadNotes();
        loadCommitments();
        checkPrepStatus();
        wsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // -----------------------------------------------------------------------
    // Export — build a printable HTML page and open in new tab
    // -----------------------------------------------------------------------
    function exportConversation(conversationId) {
        return Promise.all([
            call('xfusion_oo_preparation_status', { conversation_id: conversationId }),
            call('xfusion_oo_get_notes',          { conversation_id: conversationId }),
            call('xfusion_oo_get_commitments',    { conversation_id: conversationId }),
        ]).then(function (results) {
            var prepStatus   = results[0].data  || {};
            var notes        = (results[1].data  || []);
            var commitments  = (results[2].data  || []);

            var html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                '<title>1-on-1 Conversation #' + conversationId + '</title>' +
                '<style>body{font-family:sans-serif;max-width:680px;margin:2rem auto;color:#111}' +
                'h1{font-size:1.2rem}h2{font-size:1rem;margin-top:1.5rem;border-bottom:1px solid #e5e7eb;padding-bottom:.25rem}' +
                '.item{background:#f9fafb;border:1px solid #e5e7eb;border-radius:.375rem;padding:.5rem .75rem;margin-bottom:.4rem;font-size:.9rem}' +
                '.badge{display:inline-block;padding:.1rem .4rem;border-radius:999px;font-size:.7rem;background:#f3f4f6}' +
                '@media print{body{margin:0}}</style></head><body>' +
                '<h1>1-on-1 Conversation #' + conversationId + '</h1>' +
                '<p style="color:#6b7280;font-size:.85rem">Exported ' + new Date().toLocaleString() + '</p>' +

                '<h2>Preparation status</h2>' +
                '<p>' + (pair.employee ? pair.employee.name : 'Employee') + ': <strong>' + (prepStatus.employee_submitted ? 'Submitted' : 'Not submitted') + '</strong> &nbsp;' +
                (pair.leader ? pair.leader.name : 'Leader') + ': <strong>' + (prepStatus.leader_submitted ? 'Submitted' : 'Not submitted') + '</strong> &nbsp;' +
                'Revealed: <strong>' + (prepStatus.revealed ? 'Yes' : 'No') + '</strong></p>' +

                '<h2>Notes (' + notes.length + ')</h2>';

            var leaderUid   = pair.leader   ? pair.leader.id   : null;
            var employeeUid = pair.employee ? pair.employee.id : null;
            if (notes.length === 0) {
                html += '<p style="color:#6b7280">No notes recorded.</p>';
            } else {
                notes.forEach(function (n) {
                    var authorName = n.created_by == leaderUid
                        ? (pair.leader ? pair.leader.name : 'Leader')
                        : (n.created_by == employeeUid ? (pair.employee ? pair.employee.name : 'Employee') : 'User #' + n.created_by);
                    html += '<div class="item"><span class="badge">' + escHtml(n.section) + '</span> <em>' + escHtml(authorName) + ':</em> ' + escHtml(n.note) + '</div>';
                });
            }

            html += '<h2>Commitments (' + commitments.length + ')</h2>';
            if (commitments.length === 0) {
                html += '<p style="color:#6b7280">No commitments recorded.</p>';
            } else {
                commitments.forEach(function (c) {
                    var ownerLabel = c.owner_role === 'shared' ? 'Shared'
                        : (c.owner_role === 'leader' ? (pair.leader ? pair.leader.name : 'Leader') : (pair.employee ? pair.employee.name : 'Employee')) + ' (' + c.owner_role + ')';
                    html += '<div class="item"><strong>' + escHtml(c.title) + '</strong>' +
                        ' <span class="badge">' + escHtml(ownerLabel) + '</span>' +
                        ' <span class="badge">' + escHtml(c.status) + '</span>' +
                        (c.due_date ? ' <span style="color:#6b7280;font-size:.8rem">Due: ' + escHtml(c.due_date) + '</span>' : '') +
                        (c.description ? '<br><span style="color:#374151;font-size:.85rem">' + escHtml(c.description) + '</span>' : '') +
                        '</div>';
                });
            }

            html += '</body></html>';

            var win = window.open('', '_blank');
            win.document.write(html);
            win.document.close();
            win.focus();
        });
    }

    // -----------------------------------------------------------------------
    // Utility
    // -----------------------------------------------------------------------
    function escHtml(str) {
        if (str == null) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    loadPairs();
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_one_on_one', 'xfusion_one_on_one_shortcode');
