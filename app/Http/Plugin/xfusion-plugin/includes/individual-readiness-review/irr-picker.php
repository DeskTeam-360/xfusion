<?php
/**
 * IRR picker gate — shown before the [fusion_irr_wizard] wizard opens.
 *
 * Leaders pick a company group → team member → review year to start (or resume)
 * an Individual Readiness Review™. Existing reviews are listed for open/view.
 * All gating logic lives in Laravel (IrrController) — this file proxies via
 * admin-ajax so the bearer token never reaches the browser.
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
function xfirr_picker_api_request(string $method, string $path, array $query = [], array $body = []): array
{
    $base = rtrim(XFUSION_LARAVEL_API_BASE, '/');
    $url  = $base . '/api/v1/irrs' . $path;
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

function xfirr_picker_send(array $result): void
{
    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json(array_merge(['success' => false, 'message' => $result['error'] ?? ($body['message'] ?? 'Request failed.')], $body), 200);
    }
    wp_send_json($result['body']);
}

function xfirr_picker_require_login(): void
{
    if (! is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => __('You must be logged in.', 'xfusion')], 401);
    }
    check_ajax_referer('xfirr_picker', 'nonce');
}

add_action('wp_ajax_xfirr_picker_dashboard', function (): void {
    xfirr_picker_require_login();
    xfirr_picker_send(xfirr_picker_api_request('GET', '/picker-dashboard', ['user_id' => get_current_user_id()]));
});

add_action('wp_ajax_xfirr_picker_create', function (): void {
    xfirr_picker_require_login();
    $groupId = (int) ($_POST['company_group_id'] ?? 0);
    $employeeId = (int) ($_POST['employee_user_id'] ?? 0);
    $year = (int) ($_POST['year'] ?? 0);
    xfirr_picker_send(xfirr_picker_api_request('POST', '/', [], [
        'user_id' => get_current_user_id(),
        'company_group_id' => $groupId,
        'employee_user_id' => $employeeId,
        'year' => $year,
    ]));
});

/**
 * Renders the gate markup + JS. Call when no irr_id is selected yet.
 */
function xfirr_render_picker_gate(): string
{
    $nonce   = wp_create_nonce('xfirr_picker');
    $ajaxUrl = esc_url(admin_url('admin-ajax.php'));

    ob_start();
    ?>
<div id="xfirr-picker" data-ajax-url="<?php echo $ajaxUrl; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="xirr-card" id="xfirr-picker-body">
        <p class="xirr-muted">Loading your groups and reviews…</p>
    </div>
</div>

<style>
#xfirr-picker{max-width:960px;margin:0 auto}
#xfirr-picker .xirr-card{border:1px solid #e5e7eb;border-radius:.5rem;padding:1.5rem;background:#fff}
#xfirr-picker h2{margin:0 0 .3rem;font-size:1.15rem;color:#1e2a52}
#xfirr-picker h3{margin:0 0 .35rem;font-size:.95rem;color:#1e2a52}
#xfirr-picker .xirr-muted{color:#6b7280;font-size:.85rem;line-height:1.5}
#xfirr-picker .xfirr-gate-columns{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1rem}
@media(max-width:720px){#xfirr-picker .xfirr-gate-columns{grid-template-columns:1fr}}
#xfirr-picker .xfirr-gate-col{border:1px solid #f3f4f6;border-radius:.375rem;padding:1rem;background:#fafafa}
#xfirr-picker table{width:100%;border-collapse:collapse;margin-top:.75rem;font-size:.85rem}
#xfirr-picker th{text-align:left;padding:.5rem;color:#6b7280;font-size:.72rem;text-transform:uppercase;border-bottom:1px solid #e5e7eb}
#xfirr-picker td{padding:.6rem .5rem;border-bottom:1px solid #f3f4f6;vertical-align:middle}
#xfirr-picker .xirr-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;background:#fef3c7;color:#92400e}
#xfirr-picker .xirr-badge.active{background:#dcfce7;color:#166534}
#xfirr-picker .xirr-badge.gray{background:#f3f4f6;color:#4b5563}
#xfirr-picker a.xirr-open-link{color:#5f9a3f;font-weight:600;text-decoration:underline}
#xfirr-picker label{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:.25rem}
#xfirr-picker select,#xfirr-picker input{border:1px solid #d1d5db;border-radius:.375rem;padding:.45rem .6rem;font-size:.85rem;width:100%;max-width:100%;box-sizing:border-box;margin:0 0 .65rem}
#xfirr-picker button{cursor:pointer;border:1px solid #5f9a3f;background:#5f9a3f;color:#fff;border-radius:.375rem;padding:.45rem 1rem;font-size:.85rem;font-weight:600;width:100%;margin-top:.25rem}
#xfirr-picker button:disabled{opacity:.5;cursor:default}
#xfirr-picker .xfirr-field-gap{margin-top:.5rem}
</style>

<script>
(function () {
    var root = document.getElementById('xfirr-picker');
    if (!root) return;
    var ajaxUrl = root.dataset.ajaxUrl, nonce = root.dataset.nonce;
    var body = document.getElementById('xfirr-picker-body');

    var pickerState = { groups: [], selectedGroup: null };

    function call(action, data) {
        var params = new URLSearchParams(Object.assign({ action: action, nonce: nonce }, data || {}));
        return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: params }).then(function (r) { return r.json(); });
    }

    function escHtml(s) {
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openIrr(id) {
        var url = new URL(window.location.href);
        url.searchParams.set('irr_id', id);
        window.location.href = url.toString();
    }

    function groupLabel(g) {
        var title = g.title || 'Group';
        return g.company ? title + ' — ' + g.company : title;
    }

    function statusLabel(status) {
        var key = String(status || 'draft').toLowerCase();
        var labels = {
            draft: 'Draft',
            in_progress: 'In Progress',
            ready_to_publish: 'Ready to Publish',
            published: 'Published',
            archived: 'Archived',
        };
        return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function (ch) { return ch.toUpperCase(); });
    }

    function statusBadgeClass(status) {
        var key = String(status || 'draft').toLowerCase();
        if (key === 'published') return 'xirr-badge active';
        if (key === 'archived') return 'xirr-badge gray';
        return 'xirr-badge';
    }

    function renderReviews(reviews) {
        if (!reviews.length) {
            return '<p class="xirr-muted">No reviews yet' + (pickerState.groups.length ? ' — start one on the left.' : '.') + '</p>';
        }
        var html = '<table><thead><tr><th>Employee</th><th>Year</th><th>Group</th><th>Status</th><th></th></tr></thead><tbody>';
        reviews.forEach(function (r) {
            var access = r.can_edit
                ? '<span class="xirr-badge active">Editable</span>'
                : (r.is_self ? '<span class="xirr-badge">Your review</span>' : '<span class="xirr-badge gray">View only</span>');
            html += '<tr>' +
                '<td>' + escHtml(r.employee_name) + '</td>' +
                '<td>' + escHtml(r.year) + '</td>' +
                '<td>' + escHtml(r.group_name) + '</td>' +
                '<td><span class="' + statusBadgeClass(r.status) + '">' + escHtml(statusLabel(r.status)) + '</span><br>' + access + '</td>' +
                '<td><a href="javascript:void(0)" class="xirr-open-link" data-open="' + r.id + '">Open</a></td>' +
                '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }

    function renderMemberSelect(group) {
        var wrap = document.getElementById('xfirr-gate-member-wrap');
        if (!wrap) return;
        var members = (group && group.members) ? group.members : [];
        if (!members.length) {
            wrap.innerHTML = '<p class="xirr-muted">No team members in this group yet.</p>';
            return;
        }
        var html = '<label for="xfirr-gate-member-select">Team member</label>' +
            '<select id="xfirr-gate-member-select"><option value="">— Select team member —</option>';
        members.forEach(function (m) {
            html += '<option value="' + m.employee.id + '">' + escHtml(m.employee.name) + '</option>';
        });
        html += '</select>';
        wrap.innerHTML = html;
    }

    function renderNewReviewForm(groups) {
        if (!groups.length) {
            return '<p class="xirr-muted">Only leaders can start a new review. You can open existing reviews on the right if any are assigned to you.</p>';
        }

        var html = '<p class="xirr-muted">Select a company group and team member, then choose the review year.</p>' +
            '<label for="xfirr-gate-group-select">Company group</label>' +
            '<select id="xfirr-gate-group-select"><option value="">— Select company group —</option>';
        groups.forEach(function (g) {
            html += '<option value="' + g.id + '">' + escHtml(groupLabel(g)) + '</option>';
        });
        html += '</select>' +
            '<div id="xfirr-gate-member-wrap" class="xfirr-field-gap"></div>' +
            '<label for="xfirr-new-year">Review year</label>' +
            '<input type="number" id="xfirr-new-year" value="' + new Date().getFullYear() + '" min="2000" max="2100"/>' +
            '<button type="button" id="xfirr-new-btn" disabled>+ Start Review</button>';

        return html;
    }

    function wireNewReviewForm(groups) {
        var groupSel = document.getElementById('xfirr-gate-group-select');
        var startBtn = document.getElementById('xfirr-new-btn');
        if (!groupSel || !startBtn) return;

        function updateStartEnabled() {
            var memberSel = document.getElementById('xfirr-gate-member-select');
            var yearEl = document.getElementById('xfirr-new-year');
            var ok = pickerState.selectedGroup && memberSel && memberSel.value && yearEl && yearEl.value;
            startBtn.disabled = !ok;
        }

        groupSel.addEventListener('change', function () {
            var id = parseInt(groupSel.value, 10);
            if (!id) {
                pickerState.selectedGroup = null;
                renderMemberSelect(null);
                updateStartEnabled();
                return;
            }
            var group = groups.find(function (g) { return g.id === id; });
            pickerState.selectedGroup = group || null;
            renderMemberSelect(group || null);
            var memberSel = document.getElementById('xfirr-gate-member-select');
            if (memberSel) {
                memberSel.addEventListener('change', updateStartEnabled);
            }
            updateStartEnabled();
        });

        if (groups.length === 1) {
            groupSel.value = String(groups[0].id);
            groupSel.dispatchEvent(new Event('change'));
        }

        startBtn.addEventListener('click', function () {
            if (startBtn.dataset.busy === '1' || startBtn.disabled) return;
            var memberSel = document.getElementById('xfirr-gate-member-select');
            var yearEl = document.getElementById('xfirr-new-year');
            if (!pickerState.selectedGroup || !memberSel || !memberSel.value || !yearEl) return;

            startBtn.dataset.busy = '1';
            startBtn.disabled = true;
            startBtn.textContent = 'Starting…';

            call('xfirr_picker_create', {
                company_group_id: pickerState.selectedGroup.id,
                employee_user_id: memberSel.value,
                year: yearEl.value,
            }).then(function (res) {
                startBtn.disabled = false;
                startBtn.dataset.busy = '';
                startBtn.textContent = '+ Start Review';
                if (!res.success) {
                    alert(res.message || 'Failed to start review.');
                    updateStartEnabled();
                    return;
                }
                openIrr(res.data.id);
            }).catch(function () {
                startBtn.disabled = false;
                startBtn.dataset.busy = '';
                startBtn.textContent = '+ Start Review';
                alert('Failed to start review.');
                updateStartEnabled();
            });
        });

        var yearEl = document.getElementById('xfirr-new-year');
        if (yearEl) {
            yearEl.addEventListener('input', updateStartEnabled);
        }
    }

    function render(dashboard, canCreate) {
        var groups = (dashboard.groups || []).filter(function (g) {
            return g.role === 'leader' && (g.members || []).length > 0;
        });
        pickerState.groups = groups;
        var reviews = dashboard.reviews || [];

        var html = '<h2>Individual Readiness Review&trade;</h2>' +
            '<p class="xirr-muted">' + (canCreate
                ? 'Start a new annual review for a team member, or open an existing review below.'
                : 'Open an existing review below. Only leaders of your group can start or edit reviews.') + '</p>' +
            '<div class="xfirr-gate-columns">' +
            '<div class="xfirr-gate-col">' +
            '<h3>Start a new review</h3>' +
            '<div id="xfirr-gate-new">' + renderNewReviewForm(groups) + '</div>' +
            '</div>' +
            '<div class="xfirr-gate-col">' +
            '<h3>Your reviews</h3>' +
            '<div id="xfirr-gate-reviews">' + renderReviews(reviews) + '</div>' +
            '</div>' +
            '</div>';

        body.innerHTML = html;

        body.querySelectorAll('[data-open]').forEach(function (a) {
            a.addEventListener('click', function () { openIrr(a.dataset.open); });
        });

        wireNewReviewForm(groups);
    }

    call('xfirr_picker_dashboard').then(function (res) {
        if (!res.success) {
            body.innerHTML = '<p class="xirr-muted">' + escHtml(res.message || 'Unable to load.') + '</p>';
            return;
        }
        if (res.has_access === false) {
            body.innerHTML = '<h2>Individual Readiness Review&trade;</h2>' +
                '<p class="xirr-muted">You are not a member of any company group yet, so you do not have access to Individual Readiness Reviews. Contact your administrator if you believe this is a mistake.</p>';
            return;
        }
        render(res.data || {}, !!res.can_create);
    }).catch(function () {
        body.innerHTML = '<p class="xirr-muted">Unable to load reviews.</p>';
    });
})();
</script>
    <?php

    return (string) ob_get_clean();
}
