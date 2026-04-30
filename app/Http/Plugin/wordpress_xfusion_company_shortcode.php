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
