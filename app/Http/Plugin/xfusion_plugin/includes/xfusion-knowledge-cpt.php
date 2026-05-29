<?php
/**
 * Custom post type xfusion_knowledge + sync ke XFusion-llm (FastAPI).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_KNOWLEDGE_POST_TYPE = 'xfusion_knowledge';
const XFUSION_KNOWLEDGE_META_CATEGORY = '_xfusion_knowledge_category';
const XFUSION_KNOWLEDGE_META_SYNC_STATUS = '_xfusion_llm_sync_status';
const XFUSION_KNOWLEDGE_META_SYNCED_AT = '_xfusion_llm_synced_at';
const XFUSION_KNOWLEDGE_META_SYNC_ERROR = '_xfusion_llm_sync_error';
const XFUSION_KNOWLEDGE_META_CHUNKS = '_xfusion_llm_chunks_added';

add_action('init', 'xfusion_register_knowledge_post_type');

function xfusion_register_knowledge_post_type(): void
{
    register_post_type(XFUSION_KNOWLEDGE_POST_TYPE, [
        'labels' => [
            'name' => __('XFusion Knowledge', 'xfusion'),
            'singular_name' => __('Knowledge', 'xfusion'),
            'add_new_item' => __('Add Knowledge', 'xfusion'),
            'edit_item' => __('Edit Knowledge', 'xfusion'),
            'all_items' => __('Knowledge (LLM)', 'xfusion'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-book-alt',
        'menu_position' => 26,
        'supports' => ['title', 'editor', 'revisions'],
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
}

add_action('add_meta_boxes', 'xfusion_knowledge_add_meta_boxes');

function xfusion_knowledge_add_meta_boxes(): void
{
    add_meta_box(
        'xfusion_knowledge_llm',
        __('LLM / Vector sync', 'xfusion'),
        'xfusion_knowledge_render_meta_box',
        XFUSION_KNOWLEDGE_POST_TYPE,
        'side',
        'high'
    );
}

function xfusion_knowledge_render_meta_box(\WP_Post $post): void
{
    wp_nonce_field('xfusion_knowledge_save', 'xfusion_knowledge_nonce');

    $category = get_post_meta($post->ID, XFUSION_KNOWLEDGE_META_CATEGORY, true);
    $sync = get_post_meta($post->ID, XFUSION_KNOWLEDGE_META_SYNC_STATUS, true);
    $syncedAt = get_post_meta($post->ID, XFUSION_KNOWLEDGE_META_SYNCED_AT, true);
    $syncError = get_post_meta($post->ID, XFUSION_KNOWLEDGE_META_SYNC_ERROR, true);
    $chunks = get_post_meta($post->ID, XFUSION_KNOWLEDGE_META_CHUNKS, true);

    $categories = apply_filters('xfusion_knowledge_categories', [
        'Customer Service',
        'Standard Operating Procedure',
        'HR Policy',
        'Safety',
        'Sales',
        'Operations',
    ]);
    ?>
    <p>
        <label for="xfusion_knowledge_category"><strong><?php esc_html_e('Category', 'xfusion'); ?></strong></label>
        <input type="text" class="widefat" id="xfusion_knowledge_category" name="xfusion_knowledge_category"
               list="xfusion_knowledge_category_list" value="<?php echo esc_attr((string) $category); ?>"/>
        <datalist id="xfusion_knowledge_category_list">
            <?php foreach ($categories as $cat) : ?>
                <option value="<?php echo esc_attr($cat); ?>"></option>
            <?php endforeach; ?>
        </datalist>
    </p>
    <p class="description"><?php esc_html_e('Must match exam evaluation category names.', 'xfusion'); ?></p>
    <hr/>
    <p><strong><?php esc_html_e('Sync status', 'xfusion'); ?>:</strong> <?php echo esc_html($sync ?: 'pending'); ?></p>
    <?php if ($syncedAt) : ?>
        <p><strong><?php esc_html_e('Last synced', 'xfusion'); ?>:</strong> <?php echo esc_html($syncedAt); ?></p>
    <?php endif; ?>
    <?php if ($chunks !== '') : ?>
        <p><strong><?php esc_html_e('Chunks', 'xfusion'); ?>:</strong> <?php echo esc_html((string) $chunks); ?></p>
    <?php endif; ?>
    <?php if ($syncError) : ?>
        <p style="color:#b32d2e;"><strong><?php esc_html_e('Error', 'xfusion'); ?>:</strong> <?php echo esc_html((string) $syncError); ?></p>
    <?php endif; ?>
    <?php
}

add_action('save_post_' . XFUSION_KNOWLEDGE_POST_TYPE, 'xfusion_knowledge_save_post', 20, 3);

function xfusion_knowledge_save_post(int $post_id, \WP_Post $post, bool $update): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! isset($_POST['xfusion_knowledge_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['xfusion_knowledge_nonce'])), 'xfusion_knowledge_save')) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $category = isset($_POST['xfusion_knowledge_category'])
        ? sanitize_text_field(wp_unslash($_POST['xfusion_knowledge_category']))
        : '';

    update_post_meta($post_id, XFUSION_KNOWLEDGE_META_CATEGORY, $category);

    if ($post->post_status === 'auto-draft' || $post->post_status === 'trash') {
        return;
    }

    xfusion_knowledge_sync_to_llm($post_id, $category, (string) $post->post_content);
}

add_action('before_delete_post', 'xfusion_knowledge_before_delete_post');

function xfusion_knowledge_before_delete_post(int $post_id): void
{
    $post = get_post($post_id);
    if (! $post instanceof \WP_Post || $post->post_type !== XFUSION_KNOWLEDGE_POST_TYPE) {
        return;
    }

    xfusion_knowledge_delete_from_llm($post_id);
}

/**
 * @return array{ok: bool, message: string, chunks_added?: int}
 */
function xfusion_knowledge_sync_to_llm(int $post_id, string $category, string $content): array
{
    $api_url = rtrim((string) get_option('xfusion_llm_api_url', ''), '/');
    $api_key = (string) get_option('xfusion_llm_api_key', '');
    $sync_enabled = (bool) get_option('xfusion_llm_sync_enabled', true);

    if (! $sync_enabled || $api_url === '') {
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_STATUS, 'skipped');
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNCED_AT, gmdate('c'));
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_ERROR, 'Sync disabled or API URL empty');

        return ['ok' => true, 'message' => 'Skipped'];
    }

    $plain = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($content)) ?? '');

    if ($plain === '' || $category === '') {
        $reason = $plain === '' ? 'Empty content' : 'Category required';
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_STATUS, 'skipped');
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNCED_AT, gmdate('c'));
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_ERROR, $reason);

        return ['ok' => true, 'message' => $reason];
    }

    $response = wp_remote_post($api_url . '/api/v1/knowledge/upsert', [
        'timeout' => 60,
        'headers' => array_filter([
            'Content-Type' => 'application/json',
            'Authorization' => $api_key !== '' ? 'Bearer ' . $api_key : null,
        ]),
        'body' => wp_json_encode([
            'wordpress_post_id' => $post_id,
            'category' => $category,
            'content' => $plain,
        ]),
    ]);

    if (is_wp_error($response)) {
        $msg = $response->get_error_message();
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_STATUS, 'failed');
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNCED_AT, gmdate('c'));
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_ERROR, $msg);

        return ['ok' => false, 'message' => $msg];
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode((string) wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300) {
        $detail = is_array($body) ? wp_json_encode($body) : (string) wp_remote_retrieve_body($response);
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_STATUS, 'failed');
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNCED_AT, gmdate('c'));
        update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_ERROR, $detail);

        return ['ok' => false, 'message' => $detail];
    }

    $chunks = (int) ($body['chunks_added'] ?? 0);
    update_post_meta($post_id, XFUSION_KNOWLEDGE_META_CHUNKS, (string) $chunks);
    update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_STATUS, 'synced');
    update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNCED_AT, gmdate('c'));
    update_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_ERROR, '');

    return ['ok' => true, 'message' => 'Synced', 'chunks_added' => $chunks];
}

function xfusion_knowledge_delete_from_llm(int $post_id): void
{
    $api_url = rtrim((string) get_option('xfusion_llm_api_url', ''), '/');
    $api_key = (string) get_option('xfusion_llm_api_key', '');

    if ($api_url === '') {
        return;
    }

    wp_remote_request($api_url . '/api/v1/knowledge/delete/' . $post_id, [
        'method' => 'DELETE',
        'timeout' => 30,
        'headers' => array_filter([
            'Authorization' => $api_key !== '' ? 'Bearer ' . $api_key : null,
        ]),
    ]);
}

add_action('admin_init', 'xfusion_knowledge_register_settings');
add_action('admin_menu', 'xfusion_knowledge_settings_menu');

function xfusion_knowledge_register_settings(): void
{
    register_setting('xfusion_llm_settings', 'xfusion_llm_api_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => '',
    ]);
    register_setting('xfusion_llm_settings', 'xfusion_llm_api_key', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('xfusion_llm_settings', 'xfusion_llm_sync_enabled', [
        'type' => 'boolean',
        'default' => true,
    ]);
}

function xfusion_knowledge_settings_menu(): void
{
    add_options_page(
        __('XFusion LLM', 'xfusion'),
        __('XFusion LLM', 'xfusion'),
        'manage_options',
        'xfusion-llm-settings',
        'xfusion_knowledge_render_settings_page'
    );
}

function xfusion_knowledge_render_settings_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('XFusion LLM settings', 'xfusion'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('xfusion_llm_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="xfusion_llm_api_url"><?php esc_html_e('API URL', 'xfusion'); ?></label></th>
                    <td><input type="url" class="regular-text" id="xfusion_llm_api_url" name="xfusion_llm_api_url"
                               value="<?php echo esc_attr((string) get_option('xfusion_llm_api_url', '')); ?>"
                               placeholder="http://127.0.0.1:8000"/></td>
                </tr>
                <tr>
                    <th><label for="xfusion_llm_api_key"><?php esc_html_e('API Key (Bearer)', 'xfusion'); ?></label></th>
                    <td><input type="password" class="regular-text" id="xfusion_llm_api_key" name="xfusion_llm_api_key"
                               value="<?php echo esc_attr((string) get_option('xfusion_llm_api_key', '')); ?>"/></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Enable sync', 'xfusion'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="xfusion_llm_sync_enabled" value="1"
                                <?php checked((bool) get_option('xfusion_llm_sync_enabled', true)); ?>/>
                            <?php esc_html_e('Sync knowledge to vector store on save', 'xfusion'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_filter('manage_' . XFUSION_KNOWLEDGE_POST_TYPE . '_posts_columns', 'xfusion_knowledge_admin_columns');

function xfusion_knowledge_admin_columns(array $columns): array
{
    $columns['xfusion_category'] = __('Category', 'xfusion');
    $columns['xfusion_sync'] = __('LLM sync', 'xfusion');

    return $columns;
}

add_action('manage_' . XFUSION_KNOWLEDGE_POST_TYPE . '_posts_custom_column', 'xfusion_knowledge_admin_column_content', 10, 2);

function xfusion_knowledge_admin_column_content(string $column, int $post_id): void
{
    if ($column === 'xfusion_category') {
        echo esc_html((string) get_post_meta($post_id, XFUSION_KNOWLEDGE_META_CATEGORY, true));

        return;
    }

    if ($column === 'xfusion_sync') {
        echo esc_html((string) (get_post_meta($post_id, XFUSION_KNOWLEDGE_META_SYNC_STATUS, true) ?: 'pending'));
    }
}
