<?php
/**
 * QBR picker gate — shown before the [fusion_qbr_wizard] wizard opens.
 *
 * Rule: one QBR exists per (company GROUP, quarter, calendar year); only
 * users who lead the group (wp_company_group_details.status = 'leader') may
 * edit/publish, any member may view. All gating logic lives in Laravel
 * (App\Http\Controllers\Api\QbrController) — this file only renders what the
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
function xfqbr_picker_api_request(string $method, string $path, array $query = [], array $body = []): array
{
    $base = rtrim(XFUSION_LARAVEL_API_BASE, '/');
    $url  = $base . '/api/v1/qbrs' . $path;
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

function xfqbr_picker_send(array $result): void
{
    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json(array_merge(['success' => false, 'message' => $result['error'] ?? ($body['message'] ?? 'Request failed.')], $body), 200);
    }
    wp_send_json($result['body']);
}

function xfqbr_picker_require_login(): void
{
    if (! is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => __('You must be logged in.', 'xfusion')], 401);
    }
    check_ajax_referer('xfqbr_picker', 'nonce');
}

add_action('wp_ajax_xfqbr_picker_list', function (): void {
    xfqbr_picker_require_login();
    xfqbr_picker_send(xfqbr_picker_api_request('GET', '/list', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfqbr_picker_leadable_companies', function (): void {
    xfqbr_picker_require_login();
    xfqbr_picker_send(xfqbr_picker_api_request('GET', '/leadable-companies', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfqbr_picker_create', function (): void {
    xfqbr_picker_require_login();
    $groupId = (int) ($_POST['company_group_id'] ?? 0);
    $quarter = (int) ($_POST['quarter'] ?? 0);
    $year    = (int) ($_POST['year'] ?? 0);
    xfqbr_picker_send(xfqbr_picker_api_request('POST', '/', [], [
        'user_id'          => get_current_user_id(),
        'company_group_id' => $groupId,
        'quarter'          => $quarter,
        'year'             => $year,
    ]));
});

/**
 * Renders the gate markup + JS. Call this inside the [fusion_qbr_wizard]
 * shortcode, before the wizard's own HTML, when no qbr_id is selected yet.
 */
function xfqbr_render_picker_gate(): string
{
    $nonce   = wp_create_nonce('xfqbr_picker');
    $ajaxUrl = esc_url(admin_url('admin-ajax.php'));
    $css     = xfqbr_wizard_styles_css();

    ob_start();
    ?>
<div id="xfqbr-wiz">

    <div class="xqbr-header">
        <div class="xqbr-header-inner">
            <div>
                <h1>QUARTERLY BUSINESS REVIEW&trade; (QBR)</h1>
                <p>Select a company group to begin</p>
            </div>
        </div>
    </div>

    <div style="padding:1.5rem 1.75rem">
        <div id="xfqbr-picker" data-ajax-url="<?php echo $ajaxUrl; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
            <div class="xqbr-card" id="xfqbr-picker-body">
                <p class="xqbr-muted">Loading your organizations…</p>
            </div>
        </div>
    </div>
</div>

<style><?php echo $css; ?>
#xfqbr-picker h2{margin:0 0 .3rem;font-size:22px;color:var(--navy)}
#xfqbr-picker .xfqbr-new-form{margin-top:1.25rem;border-top:1px solid var(--border);padding-top:1rem}
#xfqbr-picker .xfqbr-new-form select,
#xfqbr-picker .xfqbr-new-form input{margin:.2rem .5rem .5rem 0;width:auto;display:inline-block}
</style>

<script>
(function () {
    var root = document.getElementById('xfqbr-picker');
    if (!root) return;
    var ajaxUrl = root.dataset.ajaxUrl, nonce = root.dataset.nonce;
    var body = document.getElementById('xfqbr-picker-body');

    function call(action, data) {
        var params = new URLSearchParams(Object.assign({ action: action, nonce: nonce }, data || {}));
        return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: params }).then(function (r) { return r.json(); });
    }

    function escHtml(s) {
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function quarterLabel(q) {
        var months = { 1: 'Jan – Mar', 2: 'Apr – Jun', 3: 'Jul – Sep', 4: 'Oct – Dec' };
        return 'Q' + q + ' (' + (months[q] || '') + ')';
    }

    function openQbr(id) {
        var url = new URL(window.location.href);
        url.searchParams.set('qbr_id', id);
        window.location.href = url.toString();
    }

    function currentQuarter() {
        return Math.floor(new Date().getMonth() / 3) + 1;
    }

    function render(qbrs, companies, canCreate) {
        var html = '<h2>Quarterly Business Review™</h2>' +
            '<p class="xqbr-muted">' + (canCreate
                ? 'Select an existing QBR, or create a new one for a group you lead.'
                : 'Select a QBR to view. Only leaders of your group can edit or publish it.') + '</p>';

        if (qbrs.length === 0) {
            html += '<p class="xqbr-muted">No QBRs yet for your organization' + (canCreate ? ' — create one below.' : '.') + '</p>';
        } else {
            html += '<div class="xqbr-table-scroll"><table class="xqbr-table"><thead><tr><th>Organization</th><th>Quarter</th><th>Status</th><th>Access</th><th></th></tr></thead><tbody>';
            qbrs.forEach(function (q) {
                var badgeClass = (q.status === 'closed' || q.status === 'held') ? 'xqbr-badge green' : 'xqbr-badge amber';
                var accessBadge = q.can_edit
                    ? '<span class="xqbr-badge green">Editable</span>'
                    : '<span class="xqbr-badge amber">View only</span>';
                html += '<tr><td>' + escHtml(q.company_name) + '</td><td>' + quarterLabel(q.quarter) + ' ' + escHtml(q.year) + '</td>' +
                    '<td><span class="' + badgeClass + '">' + escHtml(q.status) + '</span></td>' +
                    '<td>' + accessBadge + '</td>' +
                    '<td><a href="javascript:void(0)" class="xqbr-link" data-open="' + q.id + '">Open &rarr;</a></td></tr>';
            });
            html += '</tbody></table></div>';
        }

        if (canCreate) {
            var qOpts = [1, 2, 3, 4].map(function (q) {
                return '<option value="' + q + '"' + (q === currentQuarter() ? ' selected' : '') + '>' + quarterLabel(q) + '</option>';
            }).join('');
            html += '<div class="xfqbr-new-form">' +
                '<div style="font-weight:700;font-size:15px;color:var(--navy);margin-bottom:.5rem">Create a new QBR</div>' +
                '<select class="xqbr-input" id="xfqbr-new-company">' + companies.map(function (c) {
                    return '<option value="' + c.id + '">' + escHtml(c.name) + '</option>';
                }).join('') + '</select>' +
                '<select class="xqbr-input" id="xfqbr-new-quarter">' + qOpts + '</select>' +
                '<input type="number" class="xqbr-input" id="xfqbr-new-year" value="' + new Date().getFullYear() + '" style="width:6rem"/>' +
                '<button type="button" class="xqbr-btn xqbr-btn-accent" id="xfqbr-new-btn">+ Create QBR</button>' +
                '</div>';
        }

        body.innerHTML = html;

        body.querySelectorAll('[data-open]').forEach(function (a) {
            a.addEventListener('click', function () { openQbr(a.dataset.open); });
        });

        var newBtn = document.getElementById('xfqbr-new-btn');
        if (newBtn) {
            newBtn.addEventListener('click', function () {
                if (newBtn.dataset.busy === '1') return;
                newBtn.dataset.busy = '1';
                newBtn.disabled = true;
                newBtn.textContent = 'Creating…';
                call('xfqbr_picker_create', {
                    company_group_id: document.getElementById('xfqbr-new-company').value,
                    quarter: document.getElementById('xfqbr-new-quarter').value,
                    year: document.getElementById('xfqbr-new-year').value,
                }).then(function (res) {
                    newBtn.disabled = false;
                    newBtn.dataset.busy = '';
                    newBtn.textContent = '+ Create QBR';
                    if (!res.success) { alert(res.message || 'Failed to create QBR.'); return; }
                    openQbr(res.data.id);
                });
            });
        }
    }

    Promise.all([call('xfqbr_picker_list'), call('xfqbr_picker_leadable_companies')]).then(function (results) {
        var listRes = results[0], companiesRes = results[1];

        if (!listRes.success) {
            body.innerHTML = '<p class="xqbr-muted">' + escHtml(listRes.message || 'Unable to load.') + '</p>';
            return;
        }
        if (listRes.has_access === false) {
            body.innerHTML = '<h2>Quarterly Business Review™</h2>' +
                '<p class="xqbr-muted">You are not a member of any organization yet, so you do not have access to any Quarterly Business Review™. Contact your administrator if you believe this is a mistake.</p>';
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
