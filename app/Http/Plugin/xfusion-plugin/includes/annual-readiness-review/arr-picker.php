<?php
/**
 * ARR picker gate — shown before the [fusion_arr_wizard] wizard opens.
 *
 * Rule: one ARR exists per (company, calendar year) — organization-wide,
 * unlike ARP/QBR's per-group scoping. Per the client's direction, the
 * "create new ARR" form still picks a company GROUP (same UX as ARP's
 * picker) — company_id is resolved from the selected group server-side.
 * Any user who leads at least one group in the company may edit/publish
 * that company's ARR; any member of any group in the company may view it.
 * All gating logic lives in Laravel (App\Http\Controllers\Api\ArrController)
 * — this file only renders what the API returns and proxies requests
 * through admin-ajax so the bearer token never reaches the browser.
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
function xfarr_picker_api_request(string $method, string $path, array $query = [], array $body = []): array
{
    $base = rtrim(XFUSION_LARAVEL_API_BASE, '/');
    $url  = $base . '/api/v1/arrs' . $path;
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

function xfarr_picker_send(array $result): void
{
    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json(array_merge(['success' => false, 'message' => $result['error'] ?? ($body['message'] ?? 'Request failed.')], $body), 200);
    }
    wp_send_json($result['body']);
}

function xfarr_picker_require_login(): void
{
    if (! is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => __('You must be logged in.', 'xfusion')], 401);
    }
    check_ajax_referer('xfarr_picker', 'nonce');
}

add_action('wp_ajax_xfarr_picker_list', function (): void {
    xfarr_picker_require_login();
    xfarr_picker_send(xfarr_picker_api_request('GET', '/list', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfarr_picker_leadable_groups', function (): void {
    xfarr_picker_require_login();
    xfarr_picker_send(xfarr_picker_api_request('GET', '/leadable-groups', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfarr_picker_create', function (): void {
    xfarr_picker_require_login();
    $groupId = (int) ($_POST['company_group_id'] ?? 0);
    $year    = (int) ($_POST['year'] ?? 0);
    xfarr_picker_send(xfarr_picker_api_request('POST', '/', [], [
        'user_id'          => get_current_user_id(),
        'company_group_id' => $groupId,
        'year'             => $year,
    ]));
});

/**
 * Renders the gate markup + JS. Call this inside the [fusion_arr_wizard]
 * shortcode, before the wizard's own HTML, when no arr_id is selected yet.
 */
function xfarr_render_picker_gate(): string
{
    $nonce   = wp_create_nonce('xfarr_picker');
    $ajaxUrl = esc_url(admin_url('admin-ajax.php'));
    $css     = xfarr_wizard_styles_css();

    ob_start();
    ?>
<div id="xfarr-wiz">

    <div class="xarr-header">
        <div class="xarr-header-inner">
            <div>
                <h1>ANNUAL READINESS REVIEW&trade; (ARR)</h1>
                <p>Select an organization to begin</p>
            </div>
        </div>
    </div>

    <div style="padding:1.5rem 1.75rem">
        <div id="xfarr-picker" data-ajax-url="<?php echo $ajaxUrl; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
            <div class="xarr-card" id="xfarr-picker-body">
                <p class="xarr-muted">Loading your organizations…</p>
            </div>
        </div>
    </div>
</div>

<style><?php echo $css; ?>
#xfarr-picker h2{margin:0 0 .3rem;font-size:22px;color:var(--navy)}
#xfarr-picker .xfarr-new-form{margin-top:1.25rem;border-top:1px solid var(--border);padding-top:1rem}
#xfarr-picker .xfarr-new-form select,
#xfarr-picker .xfarr-new-form input{margin:.2rem .5rem .5rem 0;width:auto;display:inline-block}
</style>

<script>
(function () {
    var root = document.getElementById('xfarr-picker');
    if (!root) return;
    var ajaxUrl = root.dataset.ajaxUrl, nonce = root.dataset.nonce;
    var body = document.getElementById('xfarr-picker-body');

    function call(action, data) {
        var params = new URLSearchParams(Object.assign({ action: action, nonce: nonce }, data || {}));
        return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: params }).then(function (r) { return r.json(); });
    }

    function escHtml(s) {
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openArr(id) {
        var url = new URL(window.location.href);
        url.searchParams.set('arr_id', id);
        window.location.href = url.toString();
    }

    function render(arrs, groups, canCreate) {
        var html = '<h2>Annual Readiness Review&trade; (ARR)</h2>' +
            '<p class="xarr-muted">' + (canCreate
                ? 'Select an existing ARR, or start a new one for an organization you lead.'
                : 'Select an ARR to view. Only leaders of your organization\'s group can edit or publish it.') + '</p>';

        if (arrs.length === 0) {
            html += '<p class="xarr-muted">No ARRs yet for your organization' + (canCreate ? ' — start one below.' : '.') + '</p>';
        } else {
            html += '<div class="xarr-table-scroll"><table class="xarr-table"><thead><tr><th>Organization</th><th>Year</th><th>Status</th><th>Executive Owner</th><th>Access</th><th></th></tr></thead><tbody>';
            arrs.forEach(function (a) {
                var badgeClass = a.status === 'published' ? 'xarr-badge green' : 'xarr-badge amber';
                var accessBadge = a.can_edit
                    ? '<span class="xarr-badge green">Editable</span>'
                    : '<span class="xarr-badge amber">View only</span>';
                html += '<tr><td>' + escHtml(a.company_name) + '</td><td>' + escHtml(a.year) + '</td>' +
                    '<td><span class="' + badgeClass + '">' + escHtml((a.status || '').replace(/_/g, ' ')) + '</span></td>' +
                    '<td>' + escHtml(a.executive_owner_name || '—') + '</td>' +
                    '<td>' + accessBadge + '</td>' +
                    '<td><a href="javascript:void(0)" class="xarr-link" data-open="' + a.id + '">Open &rarr;</a></td></tr>';
            });
            html += '</tbody></table></div>';
        }

        if (canCreate) {
            html += '<div class="xfarr-new-form">' +
                '<div style="font-weight:700;font-size:15px;color:var(--navy);margin-bottom:.5rem">Start a new ARR</div>' +
                '<select class="xarr-input" id="xfarr-new-group">' + groups.map(function (g) {
                    return '<option value="' + g.id + '">' + escHtml(g.name) + '</option>';
                }).join('') + '</select>' +
                '<input type="number" class="xarr-input" id="xfarr-new-year" value="' + new Date().getFullYear() + '" style="width:6rem"/>' +
                '<button type="button" class="xarr-btn xarr-btn-accent" id="xfarr-new-btn">+ Start ARR</button>' +
                '</div>';
        }

        body.innerHTML = html;

        body.querySelectorAll('[data-open]').forEach(function (a) {
            a.addEventListener('click', function () { openArr(a.dataset.open); });
        });

        var newBtn = document.getElementById('xfarr-new-btn');
        if (newBtn) {
            newBtn.addEventListener('click', function () {
                if (newBtn.dataset.busy === '1') return;
                newBtn.dataset.busy = '1';
                newBtn.disabled = true;
                newBtn.textContent = 'Starting…';
                call('xfarr_picker_create', {
                    company_group_id: document.getElementById('xfarr-new-group').value,
                    year: document.getElementById('xfarr-new-year').value,
                }).then(function (res) {
                    newBtn.disabled = false;
                    newBtn.dataset.busy = '';
                    newBtn.textContent = '+ Start ARR';
                    if (!res.success) { alert(res.message || 'Failed to start ARR.'); return; }
                    openArr(res.data.id);
                });
            });
        }
    }

    Promise.all([call('xfarr_picker_list'), call('xfarr_picker_leadable_groups')]).then(function (results) {
        var listRes = results[0], groupsRes = results[1];

        if (!listRes.success) {
            body.innerHTML = '<p class="xarr-muted">' + escHtml(listRes.message || 'Unable to load.') + '</p>';
            return;
        }
        if (listRes.has_access === false) {
            body.innerHTML = '<h2>Annual Readiness Review&trade; (ARR)</h2>' +
                '<p class="xarr-muted">You are not a member of any organization yet, so you do not have access to any Annual Readiness Review™. Contact your administrator if you believe this is a mistake.</p>';
            return;
        }

        var groups = (groupsRes.success ? groupsRes.data : []) || [];
        render(listRes.data || [], groups, !!listRes.can_create);
    });
})();
</script>
    <?php

    return (string) ob_get_clean();
}
