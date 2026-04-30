<?php
/**
 * XFusion — Company API shortcodes (WordPress)
 *
 * Copy this file to your WordPress site, e.g.:
 *   wp-content/plugins/xfusion-company-shortcode/xfusion-company-shortcode.php
 * Then activate the plugin, or require it from your theme's functions.php.
 *
 * Laravel endpoints (same app as admin, typically https://admin.*.xperiencefusion.com):
 *   GET /api/v1/companies          — paginated list (?per_page=50)
 *   GET /api/v1/companies/{id}     — one company
 *
 * If Laravel .env has FUSION_API_TOKEN set, send header:
 *   Authorization: Bearer <same token>
 *
 * Usage:
 *   [xfusion_company id="12"]
 *   [xfusion_companies per_page="20"]
 *   [xfusion_participation company_id="12"]
 *
 * Participation charts: course group dropdown + Chart.js (overall participation,
 * by work type, activity counts). Data is loaded via WordPress admin-ajax so the
 * API bearer token is not exposed in the browser.
 */

if (! defined('ABSPATH')) {
    exit;
}

/** Base URL of the Laravel application (must reach /api/v1/...). */
if (! defined('XFUSION_LARAVEL_API_BASE')) {
    define('XFUSION_LARAVEL_API_BASE', 'https://admin.sandbox.xperiencefusion.com');
}

/** Leave empty if FUSION_API_TOKEN is unset on Laravel; otherwise paste the token. */
if (! defined('XFUSION_API_BEARER_TOKEN')) {
    define('XFUSION_API_BEARER_TOKEN', '');
}

function xfusion_company_api_request(string $path, array $query = []): array
{
    $base = rtrim(XFUSION_LARAVEL_API_BASE, '/');
    $url = $base . '/api/v1' . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $args = [
        'timeout' => 20,
        'sslverify' => true,
    ];

    if (XFUSION_API_BEARER_TOKEN !== '') {
        $args['headers'] = [
            'Authorization' => 'Bearer ' . XFUSION_API_BEARER_TOKEN,
            'Accept' => 'application/json',
        ];
    } else {
        $args['headers'] = ['Accept' => 'application/json'];
    }

    $res = wp_remote_get($url, $args);

    if (is_wp_error($res)) {
        return ['ok' => false, 'error' => $res->get_error_message(), 'body' => null];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $body = json_decode(wp_remote_retrieve_body($res), true);

    if ($code < 200 || $code >= 300) {
        $msg = isset($body['message']) ? (string) $body['message'] : 'HTTP ' . $code;

        return ['ok' => false, 'error' => $msg, 'body' => $body];
    }

    return ['ok' => true, 'error' => null, 'body' => $body];
}

function xfusion_company_render_card(array $row): string
{
    $title = isset($row['title']) ? esc_html((string) $row['title']) : '';
    $url = isset($row['company_url']) ? esc_url((string) $row['company_url']) : '';
    $logo = isset($row['logo_url']) ? esc_url((string) $row['logo_url']) : '';
    $qr = isset($row['qrcode_url']) ? esc_url((string) $row['qrcode_url']) : '';
    $count = isset($row['employees_count']) ? (int) $row['employees_count'] : 0;
    $leader = '';
    if (! empty($row['leader']['display_name'])) {
        $leader = esc_html((string) $row['leader']['display_name']);
    } elseif (! empty($row['leader']['nicename'])) {
        $leader = esc_html((string) $row['leader']['nicename']);
    }

    ob_start(); ?>
<div class="xfusion-company-card" style="max-width:480px;padding:16px;border:1px solid #ddd;border-radius:8px;">
    <?php if ($logo !== '') : ?>
        <p style="margin:0 0 12px;"><img src="<?php echo $logo; ?>" alt="" style="max-height:80px;width:auto;" loading="lazy" /></p>
    <?php endif; ?>
    <h3 style="margin:0 0 8px;font-size:1.25rem;"><?php echo $title; ?></h3>
    <?php if ($url !== '') : ?>
        <p style="margin:0 0 8px;"><a href="<?php echo $url; ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Company website', 'xfusion-company'); ?></a></p>
    <?php endif; ?>
    <p style="margin:0 0 8px;color:#555;"><?php echo esc_html(sprintf(/* translators: %d employee count */ _n('%d employee', '%d employees', $count, 'xfusion-company'), $count)); ?></p>
    <?php if ($leader !== '') : ?>
        <p style="margin:0 0 8px;color:#555;"><?php echo esc_html__('Leader:', 'xfusion-company'); ?> <?php echo $leader; ?></p>
    <?php endif; ?>
    <?php if ($qr !== '') : ?>
        <p style="margin:0;"><img src="<?php echo $qr; ?>" alt="" style="max-width:120px;height:auto;" loading="lazy" /></p>
    <?php endif; ?>
</div>
    <?php
    return (string) ob_get_clean();
}

add_shortcode('xfusion_company', function ($atts) {
    $atts = shortcode_atts([
        'id' => 0,
    ], $atts, 'xfusion_company');

    $id = (int) $atts['id'];
    if ($id < 1) {
        return '<p class="xfusion-company-error">' . esc_html__('Company id missing or invalid.', 'xfusion-company') . '</p>';
    }

    $cache_key = 'xfusion_company_v1_' . $id;
    $cached = get_transient($cache_key);
    if ($cached !== false && is_string($cached)) {
        return $cached;
    }

    $res = xfusion_company_api_request('/companies/' . $id);
    if (! $res['ok']) {
        return '<p class="xfusion-company-error">' . esc_html($res['error']) . '</p>';
    }

    $data = $res['body']['data'] ?? null;
    if (! is_array($data)) {
        return '<p class="xfusion-company-error">' . esc_html__('Invalid API response.', 'xfusion-company') . '</p>';
    }

    $html = xfusion_company_render_card($data);
    set_transient($cache_key, $html, 5 * MINUTE_IN_SECONDS);

    return $html;
});

add_shortcode('xfusion_companies', function ($atts) {
    $atts = shortcode_atts([
        'per_page' => 50,
    ], $atts, 'xfusion_companies');

    $per = max(1, min((int) $atts['per_page'], 100));
    $cache_key = 'xfusion_companies_v1_' . $per;
    $cached = get_transient($cache_key);
    if ($cached !== false && is_string($cached)) {
        return $cached;
    }

    $res = xfusion_company_api_request('/companies', ['per_page' => $per]);
    if (! $res['ok']) {
        return '<p class="xfusion-company-error">' . esc_html($res['error']) . '</p>';
    }

    $rows = $res['body']['data'] ?? null;
    if (! is_array($rows)) {
        return '<p class="xfusion-company-error">' . esc_html__('Invalid API response.', 'xfusion-company') . '</p>';
    }

    $out = '<div class="xfusion-company-list" style="display:flex;flex-direction:column;gap:16px;">';
    foreach ($rows as $row) {
        if (is_array($row)) {
            $out .= xfusion_company_render_card($row);
        }
    }
    $out .= '</div>';

    set_transient($cache_key, $out, 5 * MINUTE_IN_SECONDS);

    return $out;
});

/** --- Participation charts (course group + Chart.js) --- */

add_action('wp_ajax_xfusion_participation_charts', 'xfusion_participation_charts_ajax');
add_action('wp_ajax_nopriv_xfusion_participation_charts', 'xfusion_participation_charts_ajax');

function xfusion_participation_charts_ajax(): void
{
    check_ajax_referer('xfusion_participation', 'nonce');

    $company_id = isset($_POST['company_id']) ? (int) $_POST['company_id'] : 0;
    $course_group_id = isset($_POST['course_group_id']) ? (int) $_POST['course_group_id'] : 0;

    if ($company_id < 1 || $course_group_id < 1) {
        wp_send_json([
            'success' => false,
            'message' => __('Invalid company or course group.', 'xfusion-company'),
        ], 400);
    }

    $res = xfusion_company_api_request('/companies/' . $company_id . '/participation-charts', [
        'course_group_id' => $course_group_id,
    ]);

    if (! $res['ok']) {
        $body = is_array($res['body'] ?? null) ? $res['body'] : [];
        $msg = isset($body['message']) ? (string) $body['message'] : (string) $res['error'];
        wp_send_json(array_merge(['success' => false, 'message' => $msg], $body), 200);
    }

    $body = $res['body'];
    if (! is_array($body)) {
        wp_send_json(['success' => false, 'message' => __('Invalid API response.', 'xfusion-company')], 200);
    }

    wp_send_json($body);
}

add_shortcode('xfusion_participation', function ($atts) {
    $atts = shortcode_atts([
        'company_id' => 0,
    ], $atts, 'xfusion_participation');

    $company_id = (int) $atts['company_id'];
    if ($company_id < 1) {
        return '<p class="xfusion-participation-error">' . esc_html__('company_id is required.', 'xfusion-company') . '</p>';
    }

    $cg_res = xfusion_company_api_request('/course-groups');
    $groups = [];
    if ($cg_res['ok'] && is_array($cg_res['body']['data'] ?? null)) {
        $groups = $cg_res['body']['data'];
    }
    $cg_load_error = ! $cg_res['ok'];

    $uid = 'xf-part-' . wp_unique_id();
    $nonce = wp_create_nonce('xfusion_participation');
    $ajax = admin_url('admin-ajax.php');
    $select_id = $uid . '-cg';

    ob_start(); ?>
<div class="xfusion-participation" id="<?php echo esc_attr($uid); ?>"
     data-ajax-url="<?php echo esc_url($ajax); ?>"
     data-nonce="<?php echo esc_attr($nonce); ?>"
     data-company-id="<?php echo esc_attr((string) $company_id); ?>"
     style="max-width:920px;margin:1rem 0;padding:16px;border:1px solid #ddd;border-radius:8px;">
    <p style="margin:0 0 8px;font-weight:600;"><?php esc_html_e('Course group', 'xfusion-company'); ?></p>
    <?php if ($cg_load_error) : ?>
        <p class="xfusion-participation-error" style="margin:0 0 8px;color:#b91c1c;font-size:14px;"><?php echo esc_html($cg_res['error'] ?? __('Could not load course groups.', 'xfusion-company')); ?></p>
    <?php endif; ?>
    <label for="<?php echo esc_attr($select_id); ?>" class="screen-reader-text"><?php esc_html_e('Select course group', 'xfusion-company'); ?></label>
    <select id="<?php echo esc_attr($select_id); ?>" class="xfusion-participation-cg" style="max-width:100%;padding:8px;margin-bottom:12px;">
        <option value=""><?php esc_html_e('— Select —', 'xfusion-company'); ?></option>
        <?php
        foreach ($groups as $g) {
            if (! is_array($g) || empty($g['id'])) {
                continue;
            }
            $gid = (int) $g['id'];
            $t = isset($g['title']) ? (string) $g['title'] : '';
            $st = isset($g['sub_title']) ? (string) $g['sub_title'] : '';
            $label = $t;
            if ($st !== '') {
                $label .= ' — ' . $st;
            }
            printf(
                '<option value="%d">%s</option>',
                $gid,
                esc_html($label),
            );
        }
        ?>
    </select>
    <p class="xfusion-participation-status" style="margin:0 0 8px;color:#555;font-size:14px;" hidden></p>
    <div class="xfusion-participation-charts" style="display:none;">
        <p style="margin:16px 0 8px;font-weight:600;"><?php esc_html_e('Participation', 'xfusion-company'); ?></p>
        <div style="max-width:360px;margin-bottom:24px;">
            <canvas class="xf-chart-overall" role="img" aria-label="<?php echo esc_attr__('Participating vs not participating', 'xfusion-company'); ?>"></canvas>
        </div>
        <p style="margin:16px 0 8px;font-weight:600;"><?php esc_html_e('By work type', 'xfusion-company'); ?></p>
        <div style="margin-bottom:24px;">
            <canvas class="xf-chart-worktype" role="img" aria-label="<?php echo esc_attr__('Participation by work type', 'xfusion-company'); ?>"></canvas>
        </div>
        <p style="margin:16px 0 8px;font-weight:600;"><?php esc_html_e('Activity participation', 'xfusion-company'); ?></p>
        <div style="overflow-x:auto;">
            <canvas class="xf-chart-bar" role="img" aria-label="<?php echo esc_attr__('Participation per activity', 'xfusion-company'); ?>" style="min-height:280px;"></canvas>
        </div>
        <p class="xfusion-participation-meta" style="margin:12px 0 0;font-size:13px;color:#666;"></p>
    </div>
</div>
<script>
(function () {
    var rootId = <?php echo wp_json_encode($uid); ?>;
    var root = document.getElementById(rootId);
    if (!root) return;

    var sel = root.querySelector('.xfusion-participation-cg');
    var chartsWrap = root.querySelector('.xfusion-participation-charts');
    var statusEl = root.querySelector('.xfusion-participation-status');
    var metaEl = root.querySelector('.xfusion-participation-meta');
    var cOverall = root.querySelector('.xf-chart-overall');
    var cWork = root.querySelector('.xf-chart-worktype');
    var cBar = root.querySelector('.xf-chart-bar');

    var ajaxUrl = root.getAttribute('data-ajax-url');
    var nonce = root.getAttribute('data-nonce');
    var companyId = root.getAttribute('data-company-id');

    var instOverall = null;
    var instWork = null;
    var instBar = null;

    function destroyCharts() {
        [instOverall, instWork, instBar].forEach(function (c) {
            if (c) { try { c.destroy(); } catch (e) {} }
        });
        instOverall = instWork = instBar = null;
    }

    function setStatus(msg, isError) {
        if (!statusEl) return;
        if (!msg) { statusEl.hidden = true; statusEl.textContent = ''; return; }
        statusEl.hidden = false;
        statusEl.textContent = msg;
        statusEl.style.color = isError ? '#b91c1c' : '#555';
    }

    function ensureChartJs(cb) {
        if (typeof Chart !== 'undefined') { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        s.onload = cb;
        s.onerror = function () { setStatus('Chart.js failed to load.', true); };
        document.head.appendChild(s);
    }

    function renderCharts(payload) {
        destroyCharts();
        if (!chartsWrap || typeof Chart === 'undefined') return;

        var pie = payload.pie || {};
        var p = Number(pie.participating || 0);
        var np = Number(pie.non_participating || 0);
        instOverall = new Chart(cOverall, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php echo wp_json_encode(__('Participating', 'xfusion-company')); ?>,
                    <?php echo wp_json_encode(__('Not participating', 'xfusion-company')); ?>
                ],
                datasets: [{ data: [p, np], backgroundColor: ['#22c55e', '#e5e7eb'], borderWidth: 1 }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });

        var wt = Array.isArray(payload.pie_by_work_type) ? payload.pie_by_work_type : [];
        var wtLabels = wt.map(function (r) { return String(r.label || ''); });
        var wtP = wt.map(function (r) { return Number(r.participating || 0); });
        var wtNp = wt.map(function (r) { return Number(r.non_participating || 0); });
        instWork = new Chart(cWork, {
            type: 'bar',
            data: {
                labels: wtLabels,
                datasets: [
                    {
                        label: <?php echo wp_json_encode(__('Participating', 'xfusion-company')); ?>,
                        data: wtP,
                        backgroundColor: '#22c55e'
                    },
                    {
                        label: <?php echo wp_json_encode(__('Not participating', 'xfusion-company')); ?>,
                        data: wtNp,
                        backgroundColor: '#d1d5db'
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: { x: { stacked: true }, y: { stacked: true } },
                plugins: { legend: { position: 'bottom' } }
            }
        });

        var bars = Array.isArray(payload.bar) ? payload.bar : [];
        var barLabels = bars.map(function (r) { return String(r.axis_label || r.label || ''); });
        var barCounts = bars.map(function (r) { return Number(r.count || 0); });
        var barColors = bars.map(function (r, i) { return r.color || '#93c5fd'; });
        instBar = new Chart(cBar, {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{ label: <?php echo wp_json_encode(__('Responses', 'xfusion-company')); ?>, data: barCounts, backgroundColor: barColors, borderWidth: 0 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        chartsWrap.style.display = 'block';
        var meta = payload.meta || {};
        if (metaEl) {
            var uc = meta.user_count != null ? meta.user_count : '—';
            var ac = meta.activities_count != null ? meta.activities_count : '—';
            metaEl.textContent = <?php
                echo wp_json_encode(
                    /* translators: 1: employees count, 2: activities count */
                    sprintf(
                        __('Employees in scope: %1$s · Activities (radio fields): %2$s', 'xfusion-company'),
                        '__UC__',
                        '__AC__',
                    ),
                );
            ?>.replace('__UC__', String(uc)).replace('__AC__', String(ac));
        }
    }

    sel.addEventListener('change', function () {
        var gid = sel.value;
        setStatus('', false);
        destroyCharts();
        if (chartsWrap) chartsWrap.style.display = 'none';
        if (metaEl) metaEl.textContent = '';
        if (!gid) return;

        setStatus(<?php echo wp_json_encode(__('Loading charts…', 'xfusion-company')); ?>, false);

        var body = new URLSearchParams();
        body.set('action', 'xfusion_participation_charts');
        body.set('nonce', nonce);
        body.set('company_id', companyId);
        body.set('course_group_id', gid);

        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.success === false) {
                    var m = (data && data.message) ? data.message : <?php echo wp_json_encode(__('Could not load charts.', 'xfusion-company')); ?>;
                    setStatus(m, true);
                    return;
                }
                setStatus('', false);
                ensureChartJs(function () { renderCharts(data); });
            })
            .catch(function () {
                setStatus(<?php echo wp_json_encode(__('Network error.', 'xfusion-company')); ?>, true);
            });
    });
})();
</script>
    <?php
    return (string) ob_get_clean();
});
