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

    return update_user_meta($userId, XFUSION_ONCE_POPUP_USER_META, gmdate('Y-m-d H:i:s')) !== false;
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

add_action('wp_enqueue_scripts', 'xfusion_once_popup_enqueue_assets');

function xfusion_once_popup_enqueue_assets(): void
{
    if (is_admin() || ! xfusion_once_popup_should_display()) {
        return;
    }

    $handle = 'xfusion-once-popup';

    wp_register_style($handle, false, [], '1.1');
    wp_enqueue_style($handle);
    wp_add_inline_style($handle, xfusion_once_popup_css());
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
.xfusion-once-popup__actions{display:flex;justify-content:center;margin:0;}
.xfusion-once-popup__btn{display:inline-block;padding:10px 24px;border:0;border-radius:6px;background:#2271b1;color:#fff;font-size:14px;font-weight:600;cursor:pointer;}
.xfusion-once-popup__btn:hover{background:#135e96;}
CSS;
}

add_action('wp_footer', 'xfusion_once_popup_render', 20);

function xfusion_once_popup_render(): void
{
    if (! xfusion_once_popup_should_display()) {
        return;
    }

    $settings = xfusion_once_popup_get_settings();
    $title = trim($settings['title']);
    $content = trim($settings['content']);
    $buttonLabel = trim($settings['button_label']) !== ''
        ? $settings['button_label']
        : __('Close', 'xfusion');

    ?>
    <div id="xfusion-once-popup" class="xfusion-once-popup" role="dialog" aria-modal="true"
         <?php echo $title !== '' ? 'aria-labelledby="xfusion-once-popup-title"' : 'aria-label="' . esc_attr__('Notice', 'xfusion') . '"'; ?>>
        <div class="xfusion-once-popup__dialog">
            <?php if ($title !== '') : ?>
                <h2 id="xfusion-once-popup-title" class="xfusion-once-popup__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($content !== '') : ?>
                <div class="xfusion-once-popup__content"><?php echo wp_kses_post(wpautop($content)); ?></div>
            <?php endif; ?>
            <div class="xfusion-once-popup__actions">
                <button type="button" class="xfusion-once-popup__btn"><?php echo esc_html($buttonLabel); ?></button>
            </div>
        </div>
    </div>
    <style id="xfusion-once-popup-body-lock">html.xfusion-once-popup-open{overflow:hidden;}</style>
    <script>
    (function () {
        var root = document.getElementById('xfusion-once-popup');
        if (!root) return;

        document.documentElement.classList.add('xfusion-once-popup-open');

        var btn = root.querySelector('.xfusion-once-popup__btn');
        if (!btn) return;

        var config = <?php echo wp_json_encode([
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(XFUSION_ONCE_POPUP_NONCE),
        ]); ?>;

        function closePopup() {
            root.classList.add('is-closed');
            root.setAttribute('hidden', 'hidden');
            root.style.display = 'none';
            document.documentElement.classList.remove('xfusion-once-popup-open');
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closePopup();

            var fd = new FormData();
            fd.append('action', 'xfusion_once_popup_dismiss');
            fd.append('nonce', config.nonce || '');

            fetch(config.ajaxUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
            }).catch(function () {});
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !root.classList.contains('is-closed')) {
                btn.click();
            }
        });
    })();
    </script>
    <?php
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
