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
        return $html . '<p><em>None.</em></p></div>';
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
                . '</td><td><a class="button" target="_blank" rel="noopener" href="'
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
            $html .= '<tr><td>' . $e($r['label']) . '</td><td>' . xfusion_admin_overview_badge($r['status']) . '</td><td><a class="button" target="_blank" rel="noopener" href="'
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
        . '.xf-ov{box-sizing:border-box;width:100%;max-width:none;--xf-brand:#36435f;--xf-line:#e3e6ec;--xf-soft:#f6f7fa}'
        . '.xf-ov h1{margin:0 0 .25rem}'
        . '.xf-ov .xf-ov-lead{margin:0 0 1.25rem;color:#667085}'
        . '.xf-ov select{width:100%;padding:.65rem .9rem;border:1px solid var(--xf-line);border-radius:10px;background:#fff}'
        . '.xf-ov-company{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin:1.25rem 0}'
        . '.xf-ov-company h2{margin:0}'
        . '.xf-ov-links{display:flex;flex-wrap:wrap;gap:8px}'
        . '.xf-ov-section{background:#fff;border:1px solid var(--xf-line);border-radius:14px;padding:1rem 1.25rem 1.1rem;margin:0 0 1.25rem;box-shadow:0 1px 3px rgba(16,24,40,.05)}'
        . '.xf-ov-section h3{display:flex;align-items:center;gap:10px;margin:0 0 .75rem}'
        . '.xf-ov-count{display:inline-block;min-width:1.7rem;padding:.1rem .55rem;border-radius:999px;background:var(--xf-soft);color:var(--xf-brand);font-size:.8em;text-align:center}'
        . '.xf-ov table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--xf-line);border-radius:10px;overflow:hidden}'
        . '.xf-ov th{background:var(--xf-soft);color:#667085;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}'
        . '.xf-ov th,.xf-ov td{text-align:left;padding:.7rem .9rem;border:0;border-bottom:1px solid var(--xf-line)}'
        . '.xf-ov tbody tr:last-child td{border-bottom:0}'
        . '.xf-ov tbody tr:hover td{background:#fafbfd}'
        . '.xf-ov td:last-child,.xf-ov th:last-child{text-align:right;white-space:nowrap}'
        . '.xf-ov a.button,.xf-ov button.button{display:inline-block;padding:.4rem .95rem;border:1px solid var(--xf-brand);border-radius:8px;background:#fff;color:var(--xf-brand)!important;font-weight:600;text-decoration:none!important;cursor:pointer;line-height:1.3}'
        . '.xf-ov a.button:hover,.xf-ov button.button:hover{background:var(--xf-brand);color:#fff!important}'
        . '.xf-ov a.button.is-primary{background:var(--xf-brand);color:#fff!important}'
        . '.xf-ov button.button[disabled]{opacity:.4;cursor:default;background:#fff;color:var(--xf-brand)!important}'
        . '.xf-ov-badge{display:inline-block;padding:.15rem .65rem;border-radius:999px;font-size:.8rem;font-weight:600;background:#eef0f4;color:#475467}'
        . '.xf-ov-badge.is-active,.xf-ov-badge.is-published,.xf-ov-badge.is-completed{background:#dcfae6;color:#067647}'
        . '.xf-ov-badge.is-draft,.xf-ov-badge.is-scheduled{background:#fef0c7;color:#b54708}'
        . '.xf-ov-badge.is-in_progress{background:#d1e9ff;color:#175cd3}'
        . '.xf-ov-badge.is-cancelled,.xf-ov-badge.is-archived{background:#fee4e2;color:#b42318}'
        . '.xf-ov-pager{display:flex;align-items:center;justify-content:flex-end;gap:12px;margin:.85rem 0 0;color:#667085}'
        . '.xf-ov-section.is-loading{opacity:.5;pointer-events:none;transition:opacity .15s}'
        . '</style><div class="xf-ov">';
    echo '<h1>FUSION Overview</h1>';
    echo '<p class="xf-ov-lead">Every company in one place. IRR and 1-on-1 open read-only for administrators; the raw 1-on-1 preparation stays private.</p>';

    echo '<form method="get" style="margin:12px 0">';
    if (isset($_GET['page'])) {
        echo '<input type="hidden" name="page" value="' . esc_attr((string) $_GET['page']) . '">';
    }
    echo '<select name="company_id" onchange="this.form.submit()"><option value="0">All companies (summary)</option>';
    foreach ($companies as $c) {
        echo '<option value="' . (int) $c['id'] . '"' . selected($selected, (int) $c['id'], false) . '>' . $e($c['title']) . '</option>';
    }
    echo '</select> <noscript><button>Show</button></noscript></form>';

    $modules = xfusion_admin_overview_modules();

    // ---- Summary of all companies ----
    if ($selected === 0) {
        $counts = [];
        foreach ($modules as $key => $m) {
            foreach ((array) $wpdb->get_results("SELECT company_id, COUNT(*) AS n FROM {$p}{$m[3]} GROUP BY company_id", ARRAY_A) as $r) {
                $counts[(int) $r['company_id']][$key] = (int) $r['n'];
            }
        }
        foreach ((array) $wpdb->get_results("SELECT company_id, COUNT(*) AS n FROM {$p}fusion_one_on_ones GROUP BY company_id", ARRAY_A) as $r) {
            $counts[(int) $r['company_id']]['oo'] = (int) $r['n'];
        }

        echo '<table><thead><tr><th>Company</th><th>ARP</th><th>ARR</th><th>QBR</th><th>IRR</th><th>1-on-1 pairs</th><th></th></tr></thead><tbody>';
        foreach ($companies as $c) {
            $n = $counts[(int) $c['id']] ?? [];
            echo '<tr><td><strong>' . $e($c['title']) . '</strong></td>'
                . '<td>' . (int) ($n['arp'] ?? 0) . '</td><td>' . (int) ($n['arr'] ?? 0) . '</td><td>' . (int) ($n['qbr'] ?? 0)
                . '</td><td>' . (int) ($n['irr'] ?? 0) . '</td><td>' . (int) ($n['oo'] ?? 0) . '</td>'
                . '<td><a class="button is-primary" href="' . esc_url(add_query_arg('company_id', (int) $c['id'])) . '">Open</a></td></tr>';
        }
        echo '</tbody></table>';

        return (string) ob_get_clean() . '</div>';
    }

    // ---- One company ----
    $company = xfusion_admin_overview_company($selected);
    if ($company === null) {
        echo '<p>Company not found.</p>';

        return (string) ob_get_clean() . '</div>';
    }

    echo '<div class="xf-ov-company"><h2>' . $e($company['title']) . '</h2><div class="xf-ov-links">';
    foreach ($modules as $m) {
        echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url(xfusion_admin_overview_url($company['slug'], $m[1])) . '">' . $e($m[0]) . ' page</a>';
    }
    echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url(xfusion_admin_overview_url($company['slug'], '1-on-1-alignment')) . '">1-on-1 page</a></div></div>';

    echo '<div id="xf-ov-lists">';
    foreach (array_merge(array_keys($modules), ['1on1']) as $key) {
        echo xfusion_admin_overview_section_html($key, $selected, 1);
    }
    echo '</div>';

    $ajax = esc_js(admin_url('admin-ajax.php'));
    $nonce = esc_js(wp_create_nonce('xfusion_overview'));
    ?>
<script>
(function () {
    var box = document.getElementById('xf-ov-lists');
    if (!box) { return; }
    box.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.xf-ov-pager button[data-page]');
        if (!btn || btn.disabled) { return; }
        var section = btn.closest('.xf-ov-section');
        var body = new URLSearchParams({
            action: 'xfusion_overview_section',
            nonce: '<?php echo $nonce; ?>',
            key: section.dataset.key,
            company_id: section.dataset.company,
            page: btn.dataset.page
        });
        section.classList.add('is-loading');
        fetch('<?php echo $ajax; ?>', { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success) { section.outerHTML = res.data.html; } else { section.classList.remove('is-loading'); }
            })
            .catch(function () { section.classList.remove('is-loading'); });
    });
})();
</script>
    <?php

    return (string) ob_get_clean() . '</div>';
}
