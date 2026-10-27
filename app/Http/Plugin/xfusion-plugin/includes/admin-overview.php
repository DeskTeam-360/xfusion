<?php
/**
 * Admin page: "FUSION Overview".
 *
 * One place where a WordPress administrator sees every company's ARP, ARR,
 * QBR, IRR (360) and 1-on-1 records and opens any of them in a new tab (read/
 * edit follows the normal module rules: IRR and 1-on-1 open read-only).
 * Reads the shared database directly; nothing is changed here. Each list is
 * paginated and pages load asynchronously (admin-ajax), without a reload.
 * The same screen is available on the front end with [fusion_admin_overview].
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_OVERVIEW_PER_PAGE = 10;

add_action('admin_menu', function (): void {
    add_menu_page('FUSION Overview', 'FUSION Overview', 'manage_options', 'xfusion-overview', function (): void {
        echo '<div class="wrap">' . xfusion_admin_overview_html() . '</div>';
    }, 'dashicons-networking', 58);
});

add_shortcode('fusion_admin_overview', function (): string {
    if (! is_user_logged_in() || ! current_user_can('manage_options')) {
        return '';
    }

    return xfusion_admin_overview_html();
});

/** Module => [title, page slug, query param, table (without prefix), SQL for the row label]. */
function xfusion_admin_overview_modules(): array
{
    return [
        'arp' => ['Annual Readiness Plan', 'annual-readiness-plan', 'arp_id', 'fusion_arps', "IFNULL(NULLIF(t.title,''), CONCAT('ARP ', t.year))"],
        'arr' => ['Annual Readiness Review', 'annual-readiness-review', 'arr_id', 'fusion_arrs', "CONCAT('ARR ', t.year)"],
        'qbr' => ['Quarterly Business Review', 'quarterly-business-review', 'qbr_id', 'fusion_qbrs', "CONCAT('Q', t.quarter, ' ', t.year)"],
        'irr' => ['Individual Readiness Review (IRR)', 'individual-readiness-review', 'irr_id', 'fusion_360_reviews', "CONCAT('IRR ', t.year, ' - ', IFNULL(u.display_name, CONCAT('user #', t.employee_user_id)))"],
    ];
}

function xfusion_admin_overview_url(string $slug, string $page, string $param = '', int $id = 0): string
{
    $path = $slug !== '' ? '/' . $slug . '/' . $page . '/' : '/' . $page . '/';
    $url = add_query_arg('xf_overview', 1, home_url($path));

    return $param !== '' ? add_query_arg($param, $id, $url) : $url;
}

function xfusion_admin_overview_badge($status): string
{
    $status = (string) $status;

    return '<span class="xf-ov-badge is-' . esc_attr(sanitize_html_class($status)) . '">' . esc_html(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

/** @return array{id: int, title: string, slug: string}|null */
function xfusion_admin_overview_company(int $companyId): ?array
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, title, slug FROM {$wpdb->prefix}companies WHERE id = %d", $companyId), ARRAY_A);

    return $row ? ['id' => (int) $row['id'], 'title' => (string) $row['title'], 'slug' => (string) $row['slug']] : null;
}

/**
 * One paginated list (a module, or '1on1' for meetings) of one company: a
 * heading, the table and its pager. Also the unit the AJAX endpoint returns.
 */
function xfusion_admin_overview_section_html(string $key, int $companyId, int $page): string
{
    global $wpdb;
    $p = $wpdb->prefix;
    $company = xfusion_admin_overview_company($companyId);
    if ($company === null) {
        return '<p>Company not found.</p>';
    }
    $slug = $company['slug'];
    $e = static fn ($v) => esc_html((string) $v);
    $per = XFUSION_OVERVIEW_PER_PAGE;
    $modules = xfusion_admin_overview_modules();

    if ($key === '1on1') {
        $title = '1-on-1 meetings';
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}fusion_one_on_one_conversations c JOIN {$p}fusion_one_on_ones o ON o.id = c.one_on_one_id WHERE o.company_id = %d",
            $companyId
        ));
    } elseif (isset($modules[$key])) {
        $title = $modules[$key][0];
        $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}{$modules[$key][3]} WHERE company_id = %d", $companyId));
    } else {
        return '';
    }

    $pages = max(1, (int) ceil($total / $per));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * $per;

    $html = '<div class="xf-ov-section" data-key="' . esc_attr($key) . '" data-company="' . $companyId . '">';
    $html .= '<h3>' . $e($title) . ' <span class="xf-ov-count">' . $total . '</span></h3>';

    if ($total === 0) {
        return $html . '<p class="xf-ov-muted">None yet.</p></div>';
    }

    if ($key === '1on1') {
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.status, c.scheduled_at, l.display_name AS leader, e.display_name AS employee
             FROM {$p}fusion_one_on_one_conversations c
             JOIN {$p}fusion_one_on_ones o ON o.id = c.one_on_one_id
             LEFT JOIN {$p}users l ON l.ID = o.leader_user_id
             LEFT JOIN {$p}users e ON e.ID = o.employee_user_id
             WHERE o.company_id = %d ORDER BY c.scheduled_at DESC, c.id DESC LIMIT %d OFFSET %d",
            $companyId, $per, $offset
        ), ARRAY_A);
        $html .= '<table><thead><tr><th>Leader / Employee</th><th>Scheduled</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . $e($r['leader']) . ' / ' . $e($r['employee']) . '</td><td>' . $e($r['scheduled_at']) . '</td><td>' . xfusion_admin_overview_badge($r['status'])
                . '</td><td><a class="xf-open" target="_blank" rel="noopener" href="'
                . esc_url(xfusion_admin_overview_url($slug, '1-on-1-alignment', 'conversation_id', (int) $r['id'])) . '">Open (read-only)</a></td></tr>';
        }
    } else {
        $m = $modules[$key];
        $join = $key === 'irr' ? "LEFT JOIN {$p}users u ON u.ID = t.employee_user_id" : '';
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT t.id, t.status, {$m[4]} AS label FROM {$p}{$m[3]} t {$join} WHERE t.company_id = %d ORDER BY t.year DESC, t.id DESC LIMIT %d OFFSET %d",
            $companyId, $per, $offset
        ), ARRAY_A);
        $html .= '<table><thead><tr><th>Record</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . $e($r['label']) . '</td><td>' . xfusion_admin_overview_badge($r['status']) . '</td><td><a class="xf-open" target="_blank" rel="noopener" href="'
                . esc_url(xfusion_admin_overview_url($slug, $m[1], $m[2], (int) $r['id'])) . '">Open</a></td></tr>';
        }
    }
    $html .= '</tbody></table>';

    if ($pages > 1) {
        $html .= '<div class="xf-ov-pager">'
            . '<button type="button" class="button" data-page="' . ($page - 1) . '"' . ($page <= 1 ? ' disabled' : '') . '>&laquo; Prev</button>'
            . '<span>Page ' . $page . ' of ' . $pages . '</span>'
            . '<button type="button" class="button" data-page="' . ($page + 1) . '"' . ($page >= $pages ? ' disabled' : '') . '>Next &raquo;</button>'
            . '</div>';
    }

    return $html . '</div>';
}

add_action('wp_ajax_xfusion_overview_section', function (): void {
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Not allowed.'], 403);
    }
    check_ajax_referer('xfusion_overview', 'nonce');

    $key = sanitize_key((string) ($_POST['key'] ?? ''));
    $html = xfusion_admin_overview_section_html($key, (int) ($_POST['company_id'] ?? 0), (int) ($_POST['page'] ?? 1));
    if ($html === '') {
        wp_send_json_error(['message' => 'Unknown list.'], 422);
    }

    wp_send_json_success(['html' => $html]);
});

function xfusion_admin_overview_html(): string
{
    global $wpdb;
    $p = $wpdb->prefix;

    $companies = (array) $wpdb->get_results("SELECT id, title, slug FROM {$p}companies ORDER BY title", ARRAY_A);
    $selected = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
    $e = static fn ($v) => esc_html((string) $v);

    ob_start();
    echo '<style>'
        . '.xf-ov{--navy:#1e2a52;--navy-dark:#141d3d;--green:#5f9a3f;--green-light:#7cb356;--ink:#1f2937;--muted:#6b7280;--border:#e5e7eb;--bg:#f7f8fa;'
        . 'box-sizing:border-box;width:100%;max-width:none;font-family:inherit;font-size:18px;color:var(--ink);background:var(--bg);border:1px solid var(--border);border-radius:.5rem;overflow:hidden}'
        . '.xf-ov-header{background:linear-gradient(120deg,var(--navy-dark) 0%,var(--navy) 55%,var(--green) 140%);padding:1.5rem 1.75rem;color:#fff}'
        . '.xf-ov-header h1{margin:0;font-size:40px;letter-spacing:.02em;font-weight:600;color:#fff}'
        . '.xf-ov-header p{margin:.25rem 0 0;font-size:18px;opacity:.85;color:#fff}'
        . '.xf-ov-body{padding:1.5rem 1.75rem}'
        . '.xf-ov select{width:100%;padding:.55rem .75rem;border:1px solid var(--border);border-radius:.375rem;background:#fff;font-size:16px;margin:0 0 1rem}'
        . '.xf-ov-company{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;margin:0 0 1rem}'
        . '.xf-ov-company h2{margin:0;font-size:32px;color:var(--navy)}'
        . '.xf-ov-links{display:flex;flex-wrap:wrap;gap:.6rem}'
        . '.xf-ov-section{background:#fff;border:1px solid var(--border);border-radius:.5rem;padding:1.1rem 1.25rem;margin-bottom:1rem}'
        . '.xf-ov-section h3{display:flex;align-items:center;gap:.6rem;margin:0 0 .6rem;font-size:20px;font-weight:500;text-transform:uppercase;letter-spacing:.04em;color:var(--navy)}'
        . '.xf-ov-count{display:inline-block;padding:.1rem .55rem;border-radius:999px;background:#eef0f6;color:var(--navy);font-size:14px;font-weight:700}'
        . '.xf-ov table{width:100%;border-collapse:collapse;font-size:.8rem;border:0}'
        . '.xf-ov th{text-align:left;padding:.65rem .75rem;color:var(--muted);font-weight:700;border:0;border-bottom:1px solid var(--border);font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;background:transparent}'
        . '.xf-ov td{padding:.85rem .75rem;border:0;border-bottom:1px solid var(--border);vertical-align:top;font-size:16px}'
        . '.xf-ov tbody tr:hover td{background:#fafbfd}'
        . '.xf-ov td:last-child,.xf-ov th:last-child{text-align:right;white-space:nowrap}'
        . '.xf-ov .xf-btn,.xf-ov a.button,.xf-ov button.button{display:inline-block;cursor:pointer;border-radius:.375rem;padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;border:1px solid var(--border);background:#fff;color:var(--ink)!important;text-decoration:none!important;white-space:nowrap;line-height:1.3;font-family:inherit}'
        . '.xf-ov a.button:hover,.xf-ov button.button:hover{background:#f3f4f6}'
        . '.xf-ov a.button.is-primary{background:var(--green);border-color:var(--green);color:#fff!important}'
        . '.xf-ov a.button.is-primary:hover{background:var(--green-light)}'
        . '.xf-ov button.button[disabled]{opacity:.45;cursor:default}'
        . '.xf-ov a.xf-open{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:16px;font-weight:600;line-height:1.25;border:1px solid #86efac;background:#dcfce7;color:#166534!important;text-decoration:none!important;white-space:nowrap}'
        . '.xf-ov a.xf-open:hover{background:#bbf7d0}'
        . '.xf-ov-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:16px;font-weight:600;background:#f3f4f6;color:#374151}'
        . '.xf-ov-badge.is-active,.xf-ov-badge.is-published,.xf-ov-badge.is-completed{background:#dcfce7;color:#166534}'
        . '.xf-ov-badge.is-draft,.xf-ov-badge.is-scheduled{background:#fef3c7;color:#92400e}'
        . '.xf-ov-badge.is-in_progress{background:#dbeafe;color:#1e40af}'
        . '.xf-ov-badge.is-cancelled,.xf-ov-badge.is-archived{background:#fee2e2;color:#991b1b}'
        . '.xf-ov-pager{display:flex;align-items:center;justify-content:flex-end;gap:.75rem;margin:.85rem 0 0;color:var(--muted);font-size:16px}'
        . '.xf-ov-section.is-loading,#xf-ov-view.is-loading{opacity:.5;pointer-events:none;transition:opacity .15s}'
        . '.xf-ov .xf-ov-muted{color:var(--muted);line-height:1.5}'
        . '</style><div class="xf-ov">';
    echo '<div class="xf-ov-header"><h1>FUSION OVERVIEW</h1><p>Every company in one place. IRR and 1-on-1 open read-only; raw 1-on-1 preparation stays private.</p></div>';
    echo '<div class="xf-ov-body">';

    echo '<select id="xf-ov-select"><option value="0">All companies (summary)</option>';
    foreach ($companies as $c) {
        echo '<option value="' . (int) $c['id'] . '"' . selected($selected, (int) $c['id'], false) . '>' . $e($c['title']) . '</option>';
    }
    echo '</select>';

    echo '<div id="xf-ov-view">' . xfusion_admin_overview_view_html($selected) . '</div>';

    $ajax = esc_js(admin_url('admin-ajax.php'));
    $nonce = esc_js(wp_create_nonce('xfusion_overview'));
    ?>
<script>
(function () {
    var view = document.getElementById('xf-ov-view');
    var select = document.getElementById('xf-ov-select');
    if (!view || !select) { return; }

    function post(data) {
        data.nonce = '<?php echo $nonce; ?>';
        return fetch('<?php echo $ajax; ?>', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data) })
            .then(function (r) { return r.json(); });
    }

    function loadCompany(id, push) {
        view.classList.add('is-loading');
        post({ action: 'xfusion_overview_view', company_id: id })
            .then(function (res) {
                view.classList.remove('is-loading');
                if (!res || !res.success) { return; }
                view.innerHTML = res.data.html;
                select.value = String(id);
                if (push) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('company_id', id);
                    window.history.pushState({ company: id }, '', url);
                }
            })
            .catch(function () { view.classList.remove('is-loading'); });
    }

    select.addEventListener('change', function () { loadCompany(select.value, true); });
    window.addEventListener('popstate', function () {
        var id = new URL(window.location.href).searchParams.get('company_id') || '0';
        loadCompany(id, false);
    });

    view.addEventListener('click', function (ev) {
        var open = ev.target.closest('[data-company-open]');
        if (open) { ev.preventDefault(); loadCompany(open.dataset.companyOpen, true); return; }

        var btn = ev.target.closest('.xf-ov-pager button[data-page]');
        if (!btn || btn.disabled) { return; }
        var section = btn.closest('.xf-ov-section');
        section.classList.add('is-loading');
        post({ action: 'xfusion_overview_section', key: section.dataset.key, company_id: section.dataset.company, page: btn.dataset.page })
            .then(function (res) {
                if (res && res.success) { section.outerHTML = res.data.html; } else { section.classList.remove('is-loading'); }
            })
            .catch(function () { section.classList.remove('is-loading'); });
    });
})();
</script>
    <?php

    return (string) ob_get_clean() . '</div></div>';
}

/** The body under the company picker: the all-company summary (0) or one company's lists. */
function xfusion_admin_overview_view_html(int $companyId): string
{
    global $wpdb;
    $p = $wpdb->prefix;
    $e = static fn ($v) => esc_html((string) $v);
    $modules = xfusion_admin_overview_modules();

    ob_start();

    // ---- Summary of all companies ----
    if ($companyId === 0) {
        $companies = (array) $wpdb->get_results("SELECT id, title FROM {$p}companies ORDER BY title", ARRAY_A);
        $counts = [];
        foreach ($modules as $key => $m) {
            foreach ((array) $wpdb->get_results("SELECT company_id, COUNT(*) AS n FROM {$p}{$m[3]} GROUP BY company_id", ARRAY_A) as $r) {
                $counts[(int) $r['company_id']][$key] = (int) $r['n'];
            }
        }
        foreach ((array) $wpdb->get_results("SELECT company_id, COUNT(*) AS n FROM {$p}fusion_one_on_ones GROUP BY company_id", ARRAY_A) as $r) {
            $counts[(int) $r['company_id']]['oo'] = (int) $r['n'];
        }

        echo '<table style="margin-top:1rem"><thead><tr><th>Company</th><th>ARP</th><th>ARR</th><th>QBR</th><th>IRR</th><th>1-on-1 pairs</th><th></th></tr></thead><tbody>';
        foreach ($companies as $c) {
            $n = $counts[(int) $c['id']] ?? [];
            echo '<tr><td><strong>' . $e($c['title']) . '</strong></td>'
                . '<td>' . (int) ($n['arp'] ?? 0) . '</td><td>' . (int) ($n['arr'] ?? 0) . '</td><td>' . (int) ($n['qbr'] ?? 0)
                . '</td><td>' . (int) ($n['irr'] ?? 0) . '</td><td>' . (int) ($n['oo'] ?? 0) . '</td>'
                . '<td><a class="button is-primary" data-company-open="' . (int) $c['id'] . '" href="' . esc_url(add_query_arg('company_id', (int) $c['id'])) . '">Open</a></td></tr>';
        }
        echo '</tbody></table>';

        return (string) ob_get_clean();
    }

    // ---- One company ----
    $company = xfusion_admin_overview_company($companyId);
    if ($company === null) {
        return '<p class="xf-ov-muted">Company not found.</p>';
    }

    echo '<div class="xf-ov-company" style="margin-top:1rem"><h2>' . $e($company['title']) . '</h2><div class="xf-ov-links">';
    foreach ($modules as $m) {
        echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url(xfusion_admin_overview_url($company['slug'], $m[1])) . '">' . $e($m[0]) . ' page</a>';
    }
    echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url(xfusion_admin_overview_url($company['slug'], '1-on-1-alignment')) . '">1-on-1 page</a></div></div>';

    echo '<div id="xf-ov-lists">';
    foreach (array_merge(array_keys($modules), ['1on1']) as $key) {
        echo xfusion_admin_overview_section_html($key, $companyId, 1);
    }
    echo '</div>';

    return (string) ob_get_clean();
}

add_action('wp_ajax_xfusion_overview_view', function (): void {
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Not allowed.'], 403);
    }
    check_ajax_referer('xfusion_overview', 'nonce');

    wp_send_json_success(['html' => xfusion_admin_overview_view_html((int) ($_POST['company_id'] ?? 0))]);
});
