<?php
/**
 * XFusion — 1-on-1 Alignment Capture™ shortcode (WordPress)
 *
 * Usage: [fusion_one_on_one]
 *
 * Bridges to Laravel /api/v1/one-on-one/* via WP admin-ajax (same pattern as
 * wordpress_xfusion_company_shortcode.php) so the FUSION_API_TOKEN bearer
 * token never reaches the browser.
 *
 * Privacy: preparation text for the *other* party is never returned by the
 * Laravel API until the conversation is revealed — this file only renders
 * what the API gives it, it does not add its own visibility rules.
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
    $url = $base . '/api/v1/one-on-one' . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $headers = ['Accept' => 'application/json'];
    if (XFUSION_API_BEARER_TOKEN !== '') {
        $headers['Authorization'] = 'Bearer ' . XFUSION_API_BEARER_TOKEN;
    }

    $args = [
        'method' => $method,
        'timeout' => 20,
        'sslverify' => true,
        'headers' => $headers,
    ];

    if ($method !== 'GET') {
        $headers['Content-Type'] = 'application/json';
        $args['headers'] = $headers;
        $args['body'] = wp_json_encode($body);
    }

    $res = wp_remote_request($url, $args);

    if (is_wp_error($res)) {
        return ['ok' => false, 'code' => 0, 'body' => null, 'error' => $res->get_error_message()];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
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
    $pairId = (int) ($_POST['pair_id'] ?? 0);
    $scheduledAt = sanitize_text_field($_POST['scheduled_at'] ?? '');
    xfusion_oo_send(xfusion_oo_api_request('POST', "/{$pairId}/conversations", [], ['scheduled_at' => $scheduledAt]));
});

add_action('wp_ajax_xfusion_oo_save_preparation', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $role = sanitize_text_field($_POST['author_role'] ?? '');
    $content = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/preparation", [], [
        'author_role' => $role,
        'author_user_id' => get_current_user_id(),
        'content' => $content,
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

add_action('wp_ajax_xfusion_oo_save_note', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/notes", [], [
        'section' => sanitize_text_field($_POST['section'] ?? 'general'),
        'note' => sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')),
        'created_by' => get_current_user_id(),
    ]));
});

add_action('wp_ajax_xfusion_oo_save_commitment', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/commitments", [], [
        'title' => sanitize_text_field($_POST['title'] ?? ''),
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'owner_role' => sanitize_text_field($_POST['owner_role'] ?? 'shared'),
        'due_date' => sanitize_text_field($_POST['due_date'] ?? '') ?: null,
    ]));
});

add_action('wp_ajax_xfusion_oo_complete', function (): void {
    xfusion_oo_require_login();
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    xfusion_oo_send(xfusion_oo_api_request('POST', "/conversations/{$conversationId}/complete"));
});

function xfusion_one_on_one_shortcode(): string
{
    if (! is_user_logged_in()) {
        return '<p>' . esc_html__('Please log in to view your 1-on-1 conversations.', 'xfusion') . '</p>';
    }

    $nonce = wp_create_nonce('xfusion_one_on_one');
    $ajaxUrl = esc_url(admin_url('admin-ajax.php'));

    ob_start();
    ?>
<div id="xfusion-oo-app" class="xfusion-oo" data-ajax-url="<?php echo $ajaxUrl; ?>" data-nonce="<?php echo esc_attr($nonce); ?>" data-user-id="<?php echo (int) get_current_user_id(); ?>">
    <div class="xfusion-oo__pairs"><p>Loading…</p></div>
    <div class="xfusion-oo__workspace" style="display:none;"></div>
</div>
<style>
.xfusion-oo{max-width:760px;margin:0 auto;font-family:inherit}
.xfusion-oo .card{border:1px solid #e5e7eb;border-radius:.5rem;padding:1rem;margin-bottom:1rem}
.xfusion-oo button{cursor:pointer;border:1px solid #2563eb;background:#2563eb;color:#fff;border-radius:.375rem;padding:.4rem .9rem;font-size:.85rem}
.xfusion-oo button.secondary{background:#fff;color:#2563eb}
.xfusion-oo textarea,.xfusion-oo input,.xfusion-oo select{width:100%;border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;margin:.25rem 0 .6rem;font-size:.85rem}
.xfusion-oo .badge{display:inline-block;padding:.1rem .5rem;border-radius:999px;font-size:.7rem;background:#f3f4f6}
.xfusion-oo .muted{color:#6b7280;font-size:.8rem}
</style>
<script>
(function(){
    var root = document.getElementById('xfusion-oo-app');
    if (!root) return;
    var ajaxUrl = root.dataset.ajaxUrl, nonce = root.dataset.nonce, userId = parseInt(root.dataset.userId, 10);
    var pairsEl = root.querySelector('.xfusion-oo__pairs');
    var wsEl = root.querySelector('.xfusion-oo__workspace');

    function call(action, data) {
        var body = new URLSearchParams(Object.assign({ action: action, nonce: nonce }, data || {}));
        return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); });
    }

    function loadPairs() {
        call('xfusion_oo_pairs').then(function (res) {
            if (!res.success) { pairsEl.innerHTML = '<p class="muted">' + (res.message || 'Unable to load.') + '</p>'; return; }
            var pairs = res.data || [];
            if (pairs.length === 0) { pairsEl.innerHTML = '<p class="muted">No 1-on-1 pairs yet.</p>'; return; }
            var html = '<div class="card"><label>Select pairing</label><select id="xfoo-pair">';
            pairs.forEach(function (p) {
                var other = p.role === 'leader' ? p.employee : p.leader;
                html += '<option value="' + p.id + '" data-role="' + p.role + '">' + (other ? other.name : '—') + ' (' + p.role + ')</option>';
            });
            html += '</select><div id="xfoo-conversations"></div></div>';
            pairsEl.innerHTML = html;
            document.getElementById('xfoo-pair').addEventListener('change', loadConversations);
            loadConversations();
        });
    }

    function loadConversations() {
        var sel = document.getElementById('xfoo-pair');
        var pairId = sel.value, role = sel.selectedOptions[0].dataset.role;
        call('xfusion_oo_conversations', { pair_id: pairId }).then(function (res) {
            var el = document.getElementById('xfoo-conversations');
            if (!res.success) { el.innerHTML = '<p class="muted">' + (res.message||'Error') + '</p>'; return; }
            var rows = res.data || [];
            var html = '<table style="width:100%;font-size:.85rem;margin-top:.5rem"><tbody>';
            rows.forEach(function (c) {
                html += '<tr><td>' + (c.scheduled_at||'—') + '</td><td><span class="badge">' + c.status + '</span></td>' +
                    '<td><button type="button" data-open="' + c.id + '" data-role="' + role + '" class="secondary">Open</button></td></tr>';
            });
            html += '</tbody></table>' +
                '<div style="margin-top:.5rem"><input type="datetime-local" id="xfoo-new-date"/><button type="button" id="xfoo-schedule">Schedule new</button></div>';
            el.innerHTML = html;

            el.querySelectorAll('[data-open]').forEach(function (btn) {
                btn.addEventListener('click', function () { openConversation(btn.dataset.open, btn.dataset.role); });
            });
            document.getElementById('xfoo-schedule').addEventListener('click', function () {
                var dt = document.getElementById('xfoo-new-date').value;
                if (!dt) return;
                call('xfusion_oo_schedule', { pair_id: pairId, scheduled_at: dt }).then(loadConversations);
            });
        });
    }

    function openConversation(conversationId, role) {
        wsEl.style.display = 'block';
        wsEl.innerHTML = '<div class="card"><h3>Conversation #' + conversationId + ' — your role: ' + role + '</h3>' +
            '<div id="xfoo-prep-status" class="muted">Checking preparation…</div>' +
            '<label>Your private preparation</label>' +
            '<textarea id="xfoo-prep-text" rows="4" placeholder="Not visible to the other party until the meeting starts"></textarea>' +
            '<button type="button" id="xfoo-save-prep">Save preparation</button> ' +
            '<button type="button" id="xfoo-reveal" class="secondary">Start meeting (reveal)</button>' +
            '<div id="xfoo-revealed"></div>' +
            '<div id="xfoo-brief"></div>' +
            '<div id="xfoo-notes"><h4>Notes</h4><textarea id="xfoo-note-text" rows="2" placeholder="Add a note"></textarea><button type="button" id="xfoo-add-note" class="secondary">Add note</button></div>' +
            '<div id="xfoo-commitments"><h4>Commitments</h4>' +
                '<input id="xfoo-commit-title" placeholder="Commitment title"/>' +
                '<button type="button" id="xfoo-add-commit" class="secondary">Add commitment</button></div>' +
            '<button type="button" id="xfoo-complete">Complete meeting (run AI synthesis)</button>' +
            '<div id="xfoo-synthesis"></div>' +
            '</div>';

        document.getElementById('xfoo-save-prep').addEventListener('click', function () {
            call('xfusion_oo_save_preparation', {
                conversation_id: conversationId, author_role: role,
                content: document.getElementById('xfoo-prep-text').value,
            }).then(checkPrepStatus);
        });

        document.getElementById('xfoo-reveal').addEventListener('click', function () {
            call('xfusion_oo_reveal', { conversation_id: conversationId }).then(function (res) {
                if (!res.success) return;
                var html = '<h4>Revealed preparation</h4>';
                (res.data || []).forEach(function (p) { html += '<p><b>' + p.author_role + ':</b> ' + p.content + '</p>'; });
                document.getElementById('xfoo-revealed').innerHTML = html;
                loadBrief();
            });
        });

        document.getElementById('xfoo-add-note').addEventListener('click', function () {
            var t = document.getElementById('xfoo-note-text');
            if (!t.value.trim()) return;
            call('xfusion_oo_save_note', { conversation_id: conversationId, section: 'general', note: t.value }).then(function () { t.value = ''; });
        });

        document.getElementById('xfoo-add-commit').addEventListener('click', function () {
            var t = document.getElementById('xfoo-commit-title');
            if (!t.value.trim()) return;
            call('xfusion_oo_save_commitment', { conversation_id: conversationId, title: t.value, owner_role: 'shared' }).then(function () { t.value = ''; });
        });

        document.getElementById('xfoo-complete').addEventListener('click', function () {
            call('xfusion_oo_complete', { conversation_id: conversationId }).then(function (res) {
                var el = document.getElementById('xfoo-synthesis');
                if (!res.success) { el.innerHTML = '<p class="muted">Completed; synthesis unavailable.</p>'; return; }
                el.innerHTML = res.data.synthesis_available
                    ? '<h4>AI Meeting Synthesis</h4><pre style="white-space:pre-wrap">' + JSON.stringify(res.data.synthesis, null, 2) + '</pre>'
                    : '<p class="muted">Completed; AI synthesis unavailable.</p>';
            });
        });

        function checkPrepStatus() {
            call('xfusion_oo_preparation_status', { conversation_id: conversationId }).then(function (res) {
                if (!res.success) return;
                var d = res.data;
                document.getElementById('xfoo-prep-status').innerText =
                    'Employee: ' + (d.employee_submitted ? 'submitted' : 'pending') +
                    ' · Leader: ' + (d.leader_submitted ? 'submitted' : 'pending') +
                    (d.revealed ? ' · Revealed' : '');
            });
        }

        function loadBrief() {
            call('xfusion_oo_brief', { conversation_id: conversationId }).then(function (res) {
                if (!res.success) { document.getElementById('xfoo-brief').innerHTML = '<p class="muted">AI brief unavailable.</p>'; return; }
                document.getElementById('xfoo-brief').innerHTML = '<h4>AI Meeting Brief</h4><pre style="white-space:pre-wrap">' + JSON.stringify(res.data, null, 2) + '</pre>';
            });
        }

        checkPrepStatus();
    }

    loadPairs();
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_one_on_one', 'xfusion_one_on_one_shortcode');
