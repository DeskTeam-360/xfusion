<?php
/**
 * ARP picker gate — shown before the [fusion_arp_wizard] wizard opens.
 *
 * Rule: one ARP exists per (company, calendar year); only users who lead at
 * least one company group (wp_company_group_details.status = 'leader') may
 * view or create an ARP. All gating logic lives in Laravel
 * (App\Http\Controllers\Api\ArpController) — this file only renders what the
 * API returns and proxies requests through admin-ajax so the bearer token
 * never reaches the browser.
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
function xfarp_picker_api_request(string $method, string $path, array $query = [], array $body = []): array
{
    $base = rtrim(XFUSION_LARAVEL_API_BASE, '/');
    $url  = $base . '/api/v1/arp' . $path;
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

function xfarp_picker_send(array $result): void
{
    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json(array_merge(['success' => false, 'message' => $result['error'] ?? ($body['message'] ?? 'Request failed.')], $body), 200);
    }
    wp_send_json($result['body']);
}

function xfarp_picker_require_login(): void
{
    if (! is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => __('You must be logged in.', 'xfusion')], 401);
    }
    check_ajax_referer('xfarp_picker', 'nonce');
}

add_action('wp_ajax_xfarp_picker_list', function (): void {
    xfarp_picker_require_login();
    xfarp_picker_send(xfarp_picker_api_request('GET', '/list', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfarp_picker_leadable_companies', function (): void {
    xfarp_picker_require_login();
    xfarp_picker_send(xfarp_picker_api_request('GET', '/leadable-companies', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfarp_picker_create', function (): void {
    xfarp_picker_require_login();
    $groupId = (int) ($_POST['company_group_id'] ?? 0);
    $year    = (int) ($_POST['year'] ?? 0);
    $title   = sanitize_text_field($_POST['title'] ?? '');
    xfarp_picker_send(xfarp_picker_api_request('POST', '/', [], [
        'user_id'          => get_current_user_id(),
        'company_group_id' => $groupId,
        'year'             => $year,
        'title'            => $title,
    ]));
});

/**
 * Renders the gate markup + JS. Call this inside the [fusion_arp_wizard]
 * shortcode, before the wizard's own HTML, when no arp_id is selected yet.
 */
function xfarp_render_picker_gate(): string
{
    $nonce   = wp_create_nonce('xfarp_picker');
    $ajaxUrl = esc_url(admin_url('admin-ajax.php'));

    ob_start();
    ?>
<div id="xfarp-picker" data-ajax-url="<?php echo $ajaxUrl; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="xar-card" id="xfarp-picker-body">
        <p class="xar-muted">Loading your organizations…</p>
    </div>
</div>

<style>
#xfarp-picker{max-width:720px;margin:0 auto}
#xfarp-picker .xar-card{border:1px solid #e5e7eb;border-radius:.5rem;padding:1.5rem;background:#fff}
#xfarp-picker h2{margin:0 0 .3rem;font-size:1.15rem;color:#1e2a52}
#xfarp-picker .xar-muted{color:#6b7280;font-size:.85rem;line-height:1.5}
#xfarp-picker table{width:100%;border-collapse:collapse;margin-top:1rem;font-size:.85rem}
#xfarp-picker th{text-align:left;padding:.5rem;color:#6b7280;font-size:.72rem;text-transform:uppercase;border-bottom:1px solid #e5e7eb}
#xfarp-picker td{padding:.6rem .5rem;border-bottom:1px solid #f3f4f6}
#xfarp-picker .xar-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;background:#fef3c7;color:#92400e}
#xfarp-picker .xar-badge.active{background:#dcfce7;color:#166534}
#xfarp-picker a.xar-open-link{color:#5f9a3f;font-weight:600;text-decoration:underline}
#xfarp-picker .xfarp-new-form{margin-top:1.25rem;border-top:1px solid #e5e7eb;padding-top:1rem}
#xfarp-picker select,#xfarp-picker input{border:1px solid #d1d5db;border-radius:.375rem;padding:.4rem .6rem;font-size:.85rem;margin:.2rem .4rem .2rem 0}
#xfarp-picker button{cursor:pointer;border:1px solid #5f9a3f;background:#5f9a3f;color:#fff;border-radius:.375rem;padding:.4rem 1rem;font-size:.85rem;font-weight:600}
#xfarp-picker button:disabled{opacity:.5;cursor:default}
</style>

<script>
(function () {
    var root = document.getElementById('xfarp-picker');
    if (!root) return;
    var ajaxUrl = root.dataset.ajaxUrl, nonce = root.dataset.nonce;
    var body = document.getElementById('xfarp-picker-body');

    function call(action, data) {
        var params = new URLSearchParams(Object.assign({ action: action, nonce: nonce }, data || {}));
        return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: params }).then(function (r) { return r.json(); });
    }

    function escHtml(s) {
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openArp(id) {
        var url = new URL(window.location.href);
        url.searchParams.set('arp_id', id);
        window.location.href = url.toString();
    }

    function render(arps, companies, canCreate) {
        var html = '<h2>Annual Readiness Plan™</h2>' +
            '<p class="xar-muted">' + (canCreate
                ? 'Select an existing ARP, or create a new one for an organization you lead.'
                : 'Select an ARP to view. Only leaders of your organization\'s group can edit or publish it.') + '</p>';

        if (arps.length === 0) {
            html += '<p class="xar-muted">No ARPs yet for your organization' + (canCreate ? ' — create one below.' : '.') + '</p>';
        } else {
            html += '<table><thead><tr><th>Organization</th><th>Year</th><th>Status</th><th>Access</th><th></th></tr></thead><tbody>';
            arps.forEach(function (a) {
                var badgeClass = a.status === 'active' ? 'xar-badge active' : 'xar-badge';
                var accessBadge = a.can_edit
                    ? '<span class="xar-badge active">Editable</span>'
                    : '<span class="xar-badge">View only</span>';
                html += '<tr><td>' + escHtml(a.company_name) + '</td><td>' + escHtml(a.year) + '</td>' +
                    '<td><span class="' + badgeClass + '">' + escHtml(a.status) + '</span></td>' +
                    '<td>' + accessBadge + '</td>' +
                    '<td><a href="javascript:void(0)" class="xar-open-link" data-open="' + a.id + '">Open</a></td></tr>';
            });
            html += '</tbody></table>';
        }

        if (canCreate) {
            html += '<div class="xfarp-new-form">' +
                '<div style="font-weight:700;font-size:.85rem;margin-bottom:.5rem">Create a new ARP</div>' +
                '<select id="xfarp-new-company">' + companies.map(function (c) {
                    return '<option value="' + c.id + '">' + escHtml(c.name) + '</option>';
                }).join('') + '</select>' +
                '<input type="number" id="xfarp-new-year" value="' + new Date().getFullYear() + '" style="width:6rem"/>' +
                '<button type="button" id="xfarp-new-btn">+ Create ARP</button>' +
                '</div>';
        }

        body.innerHTML = html;

        body.querySelectorAll('[data-open]').forEach(function (a) {
            a.addEventListener('click', function () { openArp(a.dataset.open); });
        });

        var newBtn = document.getElementById('xfarp-new-btn');
        if (newBtn) {
            newBtn.addEventListener('click', function () {
                if (newBtn.dataset.busy === '1') return;
                newBtn.dataset.busy = '1';
                newBtn.disabled = true;
                newBtn.textContent = 'Creating…';
                call('xfarp_picker_create', {
                    company_group_id: document.getElementById('xfarp-new-company').value,
                    year: document.getElementById('xfarp-new-year').value,
                }).then(function (res) {
                    newBtn.disabled = false;
                    newBtn.dataset.busy = '';
                    newBtn.textContent = '+ Create ARP';
                    if (!res.success) { alert(res.message || 'Failed to create ARP.'); return; }
                    openArp(res.data.id);
                });
            });
        }
    }

    Promise.all([call('xfarp_picker_list'), call('xfarp_picker_leadable_companies')]).then(function (results) {
        var listRes = results[0], companiesRes = results[1];

        if (!listRes.success) {
            body.innerHTML = '<p class="xar-muted">' + escHtml(listRes.message || 'Unable to load.') + '</p>';
            return;
        }
        if (listRes.has_access === false) {
            body.innerHTML = '<h2>Annual Readiness Plan™</h2>' +
                '<p class="xar-muted">You are not a member of any organization yet, so you do not have access to any Annual Readiness Plan™. Contact your administrator if you believe this is a mistake.</p>';
            return;
        }

        var companies = (companiesRes.success ? companiesRes.data : []) || [];
        render(listRes.data || [], companies, !!listRes.can_create);
    });
})();
</script>
    <?php

    return (string) ob_get_clean();
}
