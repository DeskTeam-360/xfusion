<?php
/**
 * One-time popup per user — editable title/content, dismiss stored in user_meta.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_ONCE_POPUP_OPTION = 'xfusion_once_popup_settings';
const XFUSION_ONCE_POPUP_NONCE = 'xfusion_once_popup_dismiss';
const XFUSION_ONCE_POPUP_USER_META = 'xfusion_once_popup_dismissed';

/**
 * @return array{
 *   enabled: bool,
 *   title: string,
 *   content: string,
 *   button_label: string,
 *   restrict_mode: string,
 *   path_rules: string[],
 *   page_ids: int[]
 * }
 */
function xfusion_once_popup_get_settings(): array
{
    $raw = get_option(XFUSION_ONCE_POPUP_OPTION, []);
    if (! is_array($raw)) {
        $raw = [];
    }

    $mode = (string) ($raw['restrict_mode'] ?? 'all');
    if (! in_array($mode, ['all', 'paths', 'page_ids'], true)) {
        $mode = 'all';
    }

    $pathRules = $raw['path_rules'] ?? [];
    if (! is_array($pathRules)) {
        $pathRules = xfusion_once_popup_parse_path_rules((string) $pathRules);
    }

    $pageIds = $raw['page_ids'] ?? [];
    if (! is_array($pageIds)) {
        $pageIds = xfusion_once_popup_parse_page_ids((string) $pageIds);
    } else {
        $pageIds = array_values(array_unique(array_filter(array_map('absint', $pageIds))));
    }

    return [
        'enabled' => ! empty($raw['enabled']),
        'title' => (string) ($raw['title'] ?? ''),
        'content' => (string) ($raw['content'] ?? ''),
        'button_label' => (string) ($raw['button_label'] ?? __('Close', 'xfusion')),
        'restrict_mode' => $mode,
        'path_rules' => $pathRules,
        'page_ids' => $pageIds,
    ];
}

/**
 * @return string[]
 */
function xfusion_once_popup_parse_path_rules(string $raw): array
{
    $paths = [];
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        if ($line[0] !== '/') {
            $line = '/' . $line;
        }
        $paths[] = $line;
    }

    return array_values(array_unique($paths));
}

/**
 * @return int[]
 */
function xfusion_once_popup_parse_page_ids(string $raw): array
{
    $parts = preg_split('/[\s,]+/', trim($raw)) ?: [];

    return array_values(array_unique(array_filter(array_map('absint', $parts))));
}

function xfusion_once_popup_normalize_request_path(): string
{
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
    $path = wp_parse_url($uri, PHP_URL_PATH);

    if (! is_string($path) || $path === '') {
        $path = '/';
    }

    $path = '/' . trim($path, '/');

    return $path === '/' ? '/' : $path . '/';
}

function xfusion_once_popup_path_matches_rule(string $requestPath, string $rule): bool
{
    $rule = trim($rule);
    if ($rule === '') {
        return false;
    }

    if ($rule[0] !== '/') {
        $rule = '/' . $rule;
    }

    $rulePath = '/' . trim($rule, '/');
    $rulePath = $rulePath === '/' ? '/' : $rulePath . '/';

    if (str_ends_with($rulePath, '*/')) {
        $prefix = rtrim($rulePath, '*');
        if ($prefix === '/') {
            return true;
        }

        return str_starts_with($requestPath, $prefix);
    }

    return strcasecmp($requestPath, $rulePath) === 0;
}

function xfusion_once_popup_matches_current_page(array $settings): bool
{
    $mode = (string) ($settings['restrict_mode'] ?? 'all');

    if ($mode === 'all') {
        return true;
    }

    if ($mode === 'paths') {
        $rules = $settings['path_rules'] ?? [];
        if ($rules === []) {
            return false;
        }

        $requestPath = xfusion_once_popup_normalize_request_path();
        foreach ($rules as $rule) {
            if (xfusion_once_popup_path_matches_rule($requestPath, (string) $rule)) {
                return true;
            }
        }

        return false;
    }

    if ($mode === 'page_ids') {
        $pageIds = $settings['page_ids'] ?? [];
        if ($pageIds === []) {
            return false;
        }

        if (is_page($pageIds)) {
            return true;
        }

        if (is_singular()) {
            $objectId = (int) get_queried_object_id();

            return $objectId > 0 && in_array($objectId, $pageIds, true);
        }

        return false;
    }

    return true;
}

function xfusion_once_popup_user_dismissed(int $userId): bool
{
    if ($userId < 1) {
        return true;
    }

    $dismissed = get_user_meta($userId, XFUSION_ONCE_POPUP_USER_META, true);

    return $dismissed !== '' && $dismissed !== false && $dismissed !== '0';
}

function xfusion_once_popup_record_dismiss(int $userId): bool
{
    if ($userId < 1) {
        return false;
    }

    if (xfusion_once_popup_user_dismissed($userId)) {
        return true;
    }

    $timestamp = gmdate('Y-m-d H:i:s');

    delete_user_meta($userId, XFUSION_ONCE_POPUP_USER_META);

    $metaId = add_user_meta($userId, XFUSION_ONCE_POPUP_USER_META, $timestamp, true);
    if (! $metaId) {
        update_user_meta($userId, XFUSION_ONCE_POPUP_USER_META, $timestamp);
    }

    wp_cache_delete($userId, 'user_meta');
    clean_user_cache($userId);

    return xfusion_once_popup_user_dismissed($userId);
}

function xfusion_once_popup_dismissed_user_count(): int
{
    global $wpdb;

    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value != '' AND meta_value != '0'",
            XFUSION_ONCE_POPUP_USER_META
        )
    );
}

/**
 * Users who confirmed / dismissed the AI Notify popup.
 *
 * @return array<int, array{user_id: int, dismissed_at: string, display_name: string, user_email: string, first_name: string}>
 */
function xfusion_once_popup_dismissed_users(int $limit = 500, int $offset = 0): array
{
    global $wpdb;

    $limit = max(1, min(500, $limit));
    $offset = max(0, $offset);

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, meta_value AS dismissed_at
            FROM {$wpdb->usermeta}
            WHERE meta_key = %s AND meta_value != '' AND meta_value != '0'
            ORDER BY meta_value DESC
            LIMIT %d OFFSET %d",
            XFUSION_ONCE_POPUP_USER_META,
            $limit,
            $offset
        )
    );

    if (! is_array($rows)) {
        return [];
    }

    $users = [];
    foreach ($rows as $row) {
        $userId = (int) ($row->user_id ?? 0);
        if ($userId < 1) {
            continue;
        }

        $wpUser = get_userdata($userId);
        $firstName = function_exists('xfusion_result_evaluation_user_first_name')
            ? xfusion_result_evaluation_user_first_name($userId)
            : '';

        $users[] = [
            'user_id' => $userId,
            'dismissed_at' => (string) ($row->dismissed_at ?? ''),
            'display_name' => $wpUser instanceof \WP_User ? (string) $wpUser->display_name : '',
            'user_email' => $wpUser instanceof \WP_User ? (string) $wpUser->user_email : '',
            'first_name' => $firstName,
        ];
    }

    return $users;
}

/**
 * Remove all stored confirmations so every user sees the popup again.
 *
 * @return int Number of user meta rows deleted
 */
function xfusion_once_popup_clear_all_dismissals(): int
{
    global $wpdb;

    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
            XFUSION_ONCE_POPUP_USER_META
        )
    );

    return $deleted === false ? 0 : (int) $deleted;
}

function xfusion_once_popup_should_display(): bool
{
    if (! is_user_logged_in()) {
        return false;
    }

    $settings = xfusion_once_popup_get_settings();
    if (! $settings['enabled']) {
        return false;
    }

    if (trim($settings['title']) === '' && trim(wp_strip_all_tags($settings['content'])) === '') {
        return false;
    }

    if (! xfusion_once_popup_matches_current_page($settings)) {
        return false;
    }

    return ! xfusion_once_popup_user_dismissed((int) get_current_user_id());
}

/** Shared DOM id for the Generate Insights AI Notify gate popup. */
const XFUSION_ONCE_POPUP_GATE_ID = 'xfusion-ai-notify-gate';

/**
 * Gate before Generate Insights — site-wide, ignores page/path restrictions.
 */
function xfusion_once_popup_should_show_gate(): bool
{
    if (! is_user_logged_in()) {
        return false;
    }

    $settings = xfusion_once_popup_get_settings();
    if (! $settings['enabled']) {
        return false;
    }

    if (trim($settings['title']) === '' && trim(wp_strip_all_tags($settings['content'])) === '') {
        return false;
    }

    return ! xfusion_once_popup_user_dismissed((int) get_current_user_id());
}

function xfusion_once_popup_flag_gate_needed(): void
{
    $GLOBALS['xfusion_once_popup_gate_needed'] = true;
}

function xfusion_once_popup_print_inline_styles_once(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
    echo '<style id="xfusion-once-popup-styles">' . xfusion_once_popup_css() . '</style>';
}

/**
 * Hidden AI Notify dialog markup (opened on Generate Insights click).
 */
function xfusion_once_popup_gate_markup(): string
{
    return xfusion_once_popup_notice_markup(XFUSION_ONCE_POPUP_GATE_ID, true);
}

/**
 * @param string $elementId DOM id for this popup instance.
 */
function xfusion_once_popup_notice_markup(string $elementId, bool $withCancel = true): string
{
    $settings = xfusion_once_popup_get_settings();
    $title = trim($settings['title']);
    $content = trim($settings['content']);
    $buttonLabel = trim($settings['button_label']) !== ''
        ? $settings['button_label']
        : __('Close', 'xfusion');

    $aria = $title !== ''
        ? 'aria-labelledby="' . esc_attr($elementId) . '-title"'
        : 'aria-label="' . esc_attr__('Notice', 'xfusion') . '"';

    ob_start();
    ?>
    <div id="<?php echo esc_attr($elementId); ?>" class="xfusion-once-popup xfusion-once-popup--gate" hidden role="dialog" aria-modal="true" <?php echo $aria; ?>>
        <div class="xfusion-once-popup__dialog">
            <?php if ($title !== '') : ?>
                <h2 id="<?php echo esc_attr($elementId); ?>-title" class="xfusion-once-popup__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($content !== '') : ?>
                <div class="xfusion-once-popup__content"><?php echo wp_kses_post(wpautop($content)); ?></div>
            <?php endif; ?>
            <div class="xfusion-once-popup__actions">
                <?php if ($withCancel) : ?>
                    <button type="button" class="xfusion-once-popup__btn xfusion-once-popup__btn--secondary" data-notice-dismiss><?php esc_html_e('Cancel', 'xfusion'); ?></button>
                <?php endif; ?>
                <button type="button" class="xfusion-once-popup__btn xfusion-once-popup__btn--confirm"><?php echo esc_html($buttonLabel); ?></button>
            </div>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * @return array{show: bool, element_id: string, markup: string}
 */
function xfusion_once_popup_gate_for_shortcode(string $instanceId): array
{
    unset($instanceId);

    $show = xfusion_once_popup_should_show_gate();
    if ($show) {
        xfusion_once_popup_flag_gate_needed();
    }

    return [
        'show' => $show,
        'element_id' => XFUSION_ONCE_POPUP_GATE_ID,
        'markup' => '',
    ];
}

add_action('admin_menu', 'xfusion_once_popup_register_admin_menu');

function xfusion_once_popup_register_admin_menu(): void
{
    add_options_page(
        __('XFusion Once Popup', 'xfusion'),
        __('XFusion Once Popup', 'xfusion'),
        'manage_options',
        'xfusion-once-popup',
        'xfusion_once_popup_admin_page'
    );
}

function xfusion_once_popup_admin_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $settings = xfusion_once_popup_get_settings();
    $dismissedCount = xfusion_once_popup_dismissed_user_count();

    if (
        isset($_POST['xfusion_once_popup_clear_dismissals'])
        && check_admin_referer('xfusion_once_popup_clear_dismissals')
    ) {
        $cleared = xfusion_once_popup_clear_all_dismissals();
        $dismissedCount = 0;

        echo '<div class="notice notice-success is-dismissible"><p>';
        echo esc_html(sprintf(
            /* translators: %d: number of cleared confirmation records */
            _n(
                'Cleared %d user confirmation. All logged-in users will see the AI Notify popup again.',
                'Cleared %d user confirmations. All logged-in users will see the AI Notify popup again.',
                $cleared,
                'xfusion'
            ),
            $cleared
        ));
        echo '</p></div>';
    }

    if (isset($_POST['xfusion_once_popup_save']) && check_admin_referer('xfusion_once_popup_settings')) {
        $restrictMode = isset($_POST['xfusion_once_popup_restrict_mode'])
            ? sanitize_key(wp_unslash((string) $_POST['xfusion_once_popup_restrict_mode']))
            : 'all';
        if (! in_array($restrictMode, ['all', 'paths', 'page_ids'], true)) {
            $restrictMode = 'all';
        }

        $pathRulesRaw = isset($_POST['xfusion_once_popup_path_rules'])
            ? (string) wp_unslash($_POST['xfusion_once_popup_path_rules'])
            : '';
        $pageIdsRaw = isset($_POST['xfusion_once_popup_page_ids'])
            ? (string) wp_unslash($_POST['xfusion_once_popup_page_ids'])
            : '';

        $settings = [
            'enabled' => ! empty($_POST['xfusion_once_popup_enabled']),
            'title' => isset($_POST['xfusion_once_popup_title'])
                ? sanitize_text_field(wp_unslash((string) $_POST['xfusion_once_popup_title']))
                : '',
            'content' => isset($_POST['xfusion_once_popup_content'])
                ? wp_kses_post(wp_unslash((string) $_POST['xfusion_once_popup_content']))
                : '',
            'button_label' => isset($_POST['xfusion_once_popup_button_label'])
                ? sanitize_text_field(wp_unslash((string) $_POST['xfusion_once_popup_button_label']))
                : __('Close', 'xfusion'),
            'restrict_mode' => $restrictMode,
            'path_rules' => xfusion_once_popup_parse_path_rules($pathRulesRaw),
            'page_ids' => xfusion_once_popup_parse_page_ids($pageIdsRaw),
        ];

        if ($settings['button_label'] === '') {
            $settings['button_label'] = __('Close', 'xfusion');
        }

        update_option(XFUSION_ONCE_POPUP_OPTION, $settings, false);

        echo '<div class="notice notice-success is-dismissible"><p>';
        esc_html_e('Popup settings saved.', 'xfusion');
        echo '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('XFusion Once Popup', 'xfusion'); ?></h1>
        <p class="description">
            <?php esc_html_e('Shows a popup once per logged-in user. After they click the button, they will not see it again.', 'xfusion'); ?>
        </p>
        <p>
            <?php
            echo esc_html(sprintf(
                /* translators: %d: number of users who dismissed the popup */
                __('Users who dismissed: %d', 'xfusion'),
                $dismissedCount
            ));
            ?>
        </p>

        <form method="post" action="">
            <?php wp_nonce_field('xfusion_once_popup_settings'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enabled', 'xfusion'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="xfusion_once_popup_enabled" value="1" <?php checked($settings['enabled']); ?> />
                            <?php esc_html_e('Show popup on the front end', 'xfusion'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xfusion_once_popup_title"><?php esc_html_e('Title', 'xfusion'); ?></label></th>
                    <td>
                        <input type="text" class="large-text" id="xfusion_once_popup_title" name="xfusion_once_popup_title"
                               value="<?php echo esc_attr($settings['title']); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xfusion_once_popup_content"><?php esc_html_e('Content', 'xfusion'); ?></label></th>
                    <td>
                        <textarea class="large-text" rows="8" id="xfusion_once_popup_content" name="xfusion_once_popup_content"><?php
                        echo esc_textarea($settings['content']);
                        ?></textarea>
                        <p class="description"><?php esc_html_e('Basic HTML is allowed (links, bold, lists, etc.).', 'xfusion'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xfusion_once_popup_button_label"><?php esc_html_e('Confirmation button text', 'xfusion'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="xfusion_once_popup_button_label" name="xfusion_once_popup_button_label"
                               value="<?php echo esc_attr($settings['button_label']); ?>"
                               placeholder="<?php echo esc_attr__('Got it', 'xfusion'); ?>" />
                        <p class="description"><?php esc_html_e('Text shown on the button that closes the popup and marks it as seen (once per user).', 'xfusion'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Show on pages', 'xfusion'); ?></th>
                    <td>
                        <fieldset>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" name="xfusion_once_popup_restrict_mode" value="all" <?php checked($settings['restrict_mode'], 'all'); ?> />
                                <?php esc_html_e('All pages (any front-end URL)', 'xfusion'); ?>
                            </label>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" name="xfusion_once_popup_restrict_mode" value="paths" <?php checked($settings['restrict_mode'], 'paths'); ?> />
                                <?php esc_html_e('Only matching URL paths', 'xfusion'); ?>
                            </label>
                            <label style="display:block;">
                                <input type="radio" name="xfusion_once_popup_restrict_mode" value="page_ids" <?php checked($settings['restrict_mode'], 'page_ids'); ?> />
                                <?php esc_html_e('Only specific page/post IDs', 'xfusion'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xfusion_once_popup_path_rules"><?php esc_html_e('URL paths', 'xfusion'); ?></label></th>
                    <td>
                        <textarea class="large-text code" rows="6" id="xfusion_once_popup_path_rules" name="xfusion_once_popup_path_rules"><?php
                        echo esc_textarea(implode("\n", $settings['path_rules']));
                        ?></textarea>
                        <p class="description">
                            <?php esc_html_e('One path per line. Example: /lms-home-screen/ or /topics/dependability/. Use /topics/* to match all URLs under /topics/.', 'xfusion'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xfusion_once_popup_page_ids"><?php esc_html_e('Page / post IDs', 'xfusion'); ?></label></th>
                    <td>
                        <input type="text" class="large-text" id="xfusion_once_popup_page_ids" name="xfusion_once_popup_page_ids"
                               value="<?php echo esc_attr(implode(', ', $settings['page_ids'])); ?>" placeholder="123, 456" />
                        <p class="description">
                            <?php esc_html_e('Comma-separated WordPress IDs (pages, posts, LearnDash lessons, etc.). Edit a page in admin and check the ID in the browser URL (?post=123).', 'xfusion'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save settings', 'xfusion'), 'primary', 'xfusion_once_popup_save'); ?>
        </form>

        <hr style="margin:28px 0;" />

        <h2><?php esc_html_e('Reset confirmations', 'xfusion'); ?></h2>
        <p class="description">
            <?php esc_html_e('After you update the popup title or content, clear all confirmations so every user must read and confirm again.', 'xfusion'); ?>
        </p>
        <p>
            <?php
            echo esc_html(sprintf(
                /* translators: %d: users who already confirmed */
                __('Currently confirmed: %d user(s).', 'xfusion'),
                $dismissedCount
            ));
            ?>
        </p>

        <form method="post" action="" onsubmit="return confirm(<?php echo wp_json_encode(
            __('Clear all AI Notify confirmations? Every user will see the popup again on their next visit.', 'xfusion')
        ); ?>);">
            <?php wp_nonce_field('xfusion_once_popup_clear_dismissals'); ?>
            <?php
            submit_button(
                __('Clear all confirmations', 'xfusion'),
                'delete',
                'xfusion_once_popup_clear_dismissals',
                false,
                $dismissedCount < 1 ? ['disabled' => 'disabled'] : []
            );
            ?>
        </form>
    </div>
    <?php
}

function xfusion_once_popup_register_styles(): void
{
    if (wp_style_is('xfusion-once-popup', 'registered')) {
        return;
    }

    wp_register_style('xfusion-once-popup', false, [], '1.3');
    wp_add_inline_style('xfusion-once-popup', xfusion_once_popup_css());
}

function xfusion_once_popup_enqueue_styles(): void
{
    if (is_admin()) {
        return;
    }

    xfusion_once_popup_register_styles();
    wp_enqueue_style('xfusion-once-popup');
}

function xfusion_once_popup_css(): string
{
    return <<<'CSS'
.xfusion-once-popup{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.55);}
.xfusion-once-popup[hidden],.xfusion-once-popup.is-closed{display:none!important;visibility:hidden!important;}
.xfusion-once-popup__dialog{box-sizing:border-box;width:100%;max-width:520px;max-height:90vh;overflow:auto;padding:24px 24px 20px;border-radius:12px;background:#fff;box-shadow:0 20px 50px rgba(0,0,0,.25);}
.xfusion-once-popup__title{margin:0 0 12px;font-size:1.35rem;font-weight:700;line-height:1.3;color:#1d2327;}
.xfusion-once-popup__content{margin:0 0 20px;font-size:15px;line-height:1.55;color:#374151;}
.xfusion-once-popup__content p{margin:0 0 0.75em;}
.xfusion-once-popup__content p:last-child{margin-bottom:0;}
.xfusion-once-popup__actions{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:0;}
.xfusion-once-popup__btn{display:inline-block;padding:10px 24px;border:0;border-radius:6px;background:#2271b1;color:#fff;font-size:14px;font-weight:600;cursor:pointer;}
.xfusion-once-popup__btn:hover{background:#135e96;}
.xfusion-once-popup__btn--secondary{background:#fff;border:1px solid #d1d5db;color:#374151;}
.xfusion-once-popup__btn--secondary:hover{background:#f6f7f7;}
html.xfusion-once-popup-open{overflow:hidden;}
CSS;
}

add_action('wp_footer', 'xfusion_once_popup_footer_gate', 20);

function xfusion_once_popup_footer_gate(): void
{
    if (is_admin() || ! xfusion_once_popup_should_show_gate()) {
        return;
    }

    xfusion_once_popup_enqueue_styles();
    xfusion_once_popup_print_inline_styles_once();
    echo xfusion_once_popup_gate_markup();
}

add_action('wp_ajax_xfusion_once_popup_dismiss', 'xfusion_once_popup_ajax_dismiss');

function xfusion_once_popup_ajax_dismiss(): void
{
    check_ajax_referer(XFUSION_ONCE_POPUP_NONCE, 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => __('You must be logged in.', 'xfusion')], 401);
    }

    $userId = (int) get_current_user_id();

    if (! xfusion_once_popup_record_dismiss($userId)) {
        wp_send_json_error(['message' => __('Could not save dismissal.', 'xfusion')], 500);
    }

    wp_send_json_success(['dismissed' => true]);
}

/**
 * Block evaluation AJAX until the user has confirmed the AI Notify popup.
 */
function xfusion_once_popup_require_confirmed_or_error(): void
{
    if (! xfusion_once_popup_should_show_gate()) {
        return;
    }

    wp_send_json_error([
        'message' => __('Please read and confirm the AI notification before generating insights.', 'xfusion'),
        'require_popup' => true,
        'ai_notify_required' => true,
    ], 403);
}

/**
 * Wrap shortcode JS so Generate Insights waits for popup confirmation when required.
 */
function xfusion_once_popup_gate_script(array $gate, string $coreJs): string
{
    if (empty($gate['show'])) {
        return '(function () { ' . $coreJs . ' })();';
    }

    $popupId = wp_json_encode(
        ! empty($gate['element_id']) ? (string) $gate['element_id'] : XFUSION_ONCE_POPUP_GATE_ID
    );
    $ajaxUrl = wp_json_encode(admin_url('admin-ajax.php'));
    $dismissNonce = wp_json_encode(wp_create_nonce(XFUSION_ONCE_POPUP_NONCE));

    return <<<JS
(function () {
    var popupId = {$popupId};
    var ajaxUrl = {$ajaxUrl};
    var dismissNonce = {$dismissNonce};
    var pendingRunFn = null;

    function getPopup() {
        return document.getElementById(popupId);
    }

    function closePopup() {
        var popup = getPopup();
        if (!popup) {
            return;
        }
        popup.setAttribute('hidden', 'hidden');
        popup.style.display = '';
        document.documentElement.classList.remove('xfusion-once-popup-open');
    }

    function openPopup() {
        var popup = getPopup();
        if (!popup) {
            return false;
        }
        if (popup.parentNode && popup.parentNode !== document.body) {
            document.body.appendChild(popup);
        }
        popup.removeAttribute('hidden');
        popup.style.display = 'flex';
        document.documentElement.classList.add('xfusion-once-popup-open');
        return true;
    }

    function saveDismissal() {
        var fd = new FormData();
        fd.append('action', 'xfusion_once_popup_dismiss');
        fd.append('nonce', dismissNonce || '');
        return fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    var j = null;
                    try {
                        j = JSON.parse(text);
                    } catch (err) {
                        j = null;
                    }
                    return { ok: r.ok, j: j };
                });
            });
    }

    function bindPopupControls() {
        var popup = getPopup();
        if (!popup || popup.getAttribute('data-gate-bound') === '1') {
            return;
        }
        popup.setAttribute('data-gate-bound', '1');
        popup.querySelectorAll('[data-notice-dismiss]').forEach(function (el) {
            el.addEventListener('click', function () {
                pendingRunFn = null;
                closePopup();
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && popup && !popup.hasAttribute('hidden')) {
                pendingRunFn = null;
                closePopup();
            }
        });
        var confirmBtn = popup.querySelector('.xfusion-once-popup__btn--confirm');
        if (!confirmBtn) {
            return;
        }
        confirmBtn.addEventListener('click', function (e) {
            if (e && e.preventDefault) {
                e.preventDefault();
            }
            var runFn = pendingRunFn;
            pendingRunFn = null;
            closePopup();
            saveDismissal().then(function (res) {
                if (res && res.j && res.j.success) {
                    if (typeof runFn === 'function') {
                        runFn(e);
                    }
                    return;
                }
                window.alert('Could not save confirmation. Please try again.');
                pendingRunFn = runFn;
                openPopup();
            }).catch(function () {
                window.alert('Could not save confirmation. Please try again.');
                pendingRunFn = runFn;
                openPopup();
            });
        });
    }

    function onGenerateClick(runFn) {
        return function (e) {
            if (e && e.preventDefault) {
                e.preventDefault();
            }
            bindPopupControls();
            pendingRunFn = runFn;
            if (!openPopup()) {
                pendingRunFn = null;
                window.alert('AI notification could not be loaded. Please refresh the page and try again.');
            }
        };
    }

    window.xfusionOpenAiNotifyGate = function (afterConfirmFn) {
        bindPopupControls();
        pendingRunFn = typeof afterConfirmFn === 'function' ? afterConfirmFn : null;
        return openPopup();
    };

    {$coreJs}
})();
JS;
}
