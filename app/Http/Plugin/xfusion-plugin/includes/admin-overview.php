<?php
/**
 * Admin page: "FUSION Overview".
 *
 * One place where a WordPress administrator sees every company's ARP, ARR,
 * QBR, IRR (360) and 1-on-1 records and opens any of them (read/edit follows
 * the normal module rules: IRR and 1-on-1 open read-only). Reads the shared
 * database directly; nothing is changed here. The same screen is available on
 * the front end with [fusion_admin_overview] for administrators.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

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
    $url = home_url($path);

    return $param !== '' ? add_query_arg($param, $id, $url) : $url;
}

function xfusion_admin_overview_html(): string
{
    global $wpdb;
    $p = $wpdb->prefix;

    $companies = (array) $wpdb->get_results("SELECT id, title, slug FROM {$p}companies ORDER BY title", ARRAY_A);
    $selected = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
    $e = static fn ($v) => esc_html((string) $v);

    ob_start();
    echo '<style>'
        . '.xf-ov{box-sizing:border-box;position:relative;left:50%;width:100vw;max-width:100vw;margin-left:-50vw;padding:0 24px}'
        . '.wrap .xf-ov{position:static;width:auto;max-width:none;margin-left:0;padding:0}'
        . '.xf-ov table{width:100%;border-collapse:collapse}'
        . '.xf-ov th,.xf-ov td{text-align:left;padding:.6rem .75rem;border:1px solid #ddd}'
        . '.xf-ov select{width:100%;max-width:420px}'
        . '.xf-ov a.button{display:inline-block;padding:.35rem .8rem;border:1px solid #c9ccd3;border-radius:6px;background:#f6f7f9;text-decoration:none}'
        . '</style><div class="xf-ov">';
    echo '<h1>FUSION Overview</h1>';
    echo '<p>Every company in one place. IRR and 1-on-1 open read-only for administrators; the raw 1-on-1 preparation stays private.</p>';

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
            $table = $p . $m[3];
            foreach ((array) $wpdb->get_results("SELECT company_id, COUNT(*) AS n FROM {$table} GROUP BY company_id", ARRAY_A) as $r) {
                $counts[(int) $r['company_id']][$key] = (int) $r['n'];
            }
        }
        foreach ((array) $wpdb->get_results("SELECT company_id, COUNT(*) AS n FROM {$p}fusion_one_on_ones GROUP BY company_id", ARRAY_A) as $r) {
            $counts[(int) $r['company_id']]['oo'] = (int) $r['n'];
        }

        echo '<table class="widefat striped"><thead><tr><th>Company</th><th>ARP</th><th>ARR</th><th>QBR</th><th>IRR</th><th>1-on-1 pairs</th><th></th></tr></thead><tbody>';
        foreach ($companies as $c) {
            $n = $counts[(int) $c['id']] ?? [];
            echo '<tr><td><strong>' . $e($c['title']) . '</strong></td>'
                . '<td>' . (int) ($n['arp'] ?? 0) . '</td><td>' . (int) ($n['arr'] ?? 0) . '</td><td>' . (int) ($n['qbr'] ?? 0)
                . '</td><td>' . (int) ($n['irr'] ?? 0) . '</td><td>' . (int) ($n['oo'] ?? 0) . '</td>'
                . '<td><a class="button" href="' . esc_url(add_query_arg('company_id', (int) $c['id'])) . '">Open</a></td></tr>';
        }
        echo '</tbody></table>';

        return (string) ob_get_clean() . '</div>';
    }

    // ---- One company ----
    $company = null;
    foreach ($companies as $c) {
        if ((int) $c['id'] === $selected) {
            $company = $c;
        }
    }
    if ($company === null) {
        echo '<p>Company not found.</p>';

        return (string) ob_get_clean() . '</div>';
    }
    $slug = (string) $company['slug'];

    echo '<h2>' . $e($company['title']) . '</h2><p>';
    foreach ($modules as $m) {
        echo '<a class="button" style="margin-right:6px" href="' . esc_url(xfusion_admin_overview_url($slug, $m[1])) . '">' . $e($m[0]) . ' page</a>';
    }
    echo '<a class="button" href="' . esc_url(xfusion_admin_overview_url($slug, '1-on-1-alignment')) . '">1-on-1 page</a></p>';

    foreach ($modules as $key => $m) {
        $table = $p . $m[3];
        $join = $key === 'irr' ? "LEFT JOIN {$p}users u ON u.ID = t.employee_user_id" : '';
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT t.id, t.status, {$m[4]} AS label FROM {$table} t {$join} WHERE t.company_id = %d ORDER BY t.year DESC, t.id DESC LIMIT 200",
            $selected
        ), ARRAY_A);

        echo '<h3>' . $e($m[0]) . ' (' . count($rows) . ')</h3>';
        if (! $rows) {
            echo '<p><em>None.</em></p>';
            continue;
        }
        echo '<table class="widefat striped"><thead><tr><th>Record</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td>' . $e($r['label']) . '</td><td>' . $e($r['status']) . '</td><td><a class="button" href="'
                . esc_url(xfusion_admin_overview_url($slug, $m[1], $m[2], (int) $r['id'])) . '">Open</a></td></tr>';
        }
        echo '</tbody></table>';
    }

    // 1-on-1 meetings of this company (read-only for administrators).
    $meetings = (array) $wpdb->get_results($wpdb->prepare(
        "SELECT c.id, c.status, c.scheduled_at, l.display_name AS leader, e.display_name AS employee
         FROM {$p}fusion_one_on_one_conversations c
         JOIN {$p}fusion_one_on_ones o ON o.id = c.one_on_one_id
         LEFT JOIN {$p}users l ON l.ID = o.leader_user_id
         LEFT JOIN {$p}users e ON e.ID = o.employee_user_id
         WHERE o.company_id = %d ORDER BY c.scheduled_at DESC, c.id DESC LIMIT 200",
        $selected
    ), ARRAY_A);
    echo '<h3>1-on-1 meetings (' . count($meetings) . ')</h3>';
    if (! $meetings) {
        echo '<p><em>None.</em></p>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>Leader / Employee</th><th>Scheduled</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($meetings as $r) {
            echo '<tr><td>' . $e($r['leader']) . ' / ' . $e($r['employee']) . '</td><td>' . $e($r['scheduled_at']) . '</td><td>' . $e($r['status'])
                . '</td><td><a class="button" href="' . esc_url(xfusion_admin_overview_url($slug, '1-on-1-alignment', 'conversation_id', (int) $r['id'])) . '">Open (read-only)</a></td></tr>';
        }
        echo '</tbody></table>';
    }

    return (string) ob_get_clean() . '</div>';
}
