<?php
/**
 * Admin menu: LLM Prompts — versioned system prompts per feature.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'xfusion_llm_prompts_register_admin_menu');
add_action('admin_init', 'xfusion_llm_prompts_handle_actions');

function xfusion_llm_prompts_register_admin_menu(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    add_menu_page(
        __('LLM Prompts', 'xfusion'),
        __('LLM Prompts', 'xfusion'),
        'manage_options',
        'xfusion-llm-prompts',
        'xfusion_llm_prompts_render_overview_page',
        'dashicons-editor-code',
        81
    );

    foreach (xfusion_llm_prompt_slug_definitions() as $slug => $def) {
        add_submenu_page(
            'xfusion-llm-prompts',
            (string) $def['title'],
            (string) $def['menu_title'],
            'manage_options',
            'xfusion-llm-prompt-' . $slug,
            static function () use ($slug): void {
                xfusion_llm_prompts_render_slug_page($slug);
            }
        );
    }
}

function xfusion_llm_prompts_admin_url(string $slug = ''): string
{
    if ($slug === '') {
        return admin_url('admin.php?page=xfusion-llm-prompts');
    }

    return admin_url('admin.php?page=xfusion-llm-prompt-' . rawurlencode($slug));
}

function xfusion_llm_prompts_handle_actions(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['xfusion_llm_prompt_set_active'], $_POST['xfusion_llm_prompt_slug'], $_POST['xfusion_llm_prompt_active_choice'])
        && check_admin_referer('xfusion_llm_prompt_manage')) {
        $slug = sanitize_key((string) wp_unslash($_POST['xfusion_llm_prompt_slug']));
        $choice = sanitize_text_field(wp_unslash((string) $_POST['xfusion_llm_prompt_active_choice']));
        if (xfusion_llm_prompt_is_valid_slug($slug)) {
            xfusion_llm_prompt_set_active($slug, $choice);
        }
        wp_safe_redirect(add_query_arg(['updated' => '1'], xfusion_llm_prompts_admin_url($slug)));
        exit;
    }

    if (isset($_POST['xfusion_llm_prompt_save_version'], $_POST['xfusion_llm_prompt_slug'])
        && check_admin_referer('xfusion_llm_prompt_manage')) {
        $slug = sanitize_key((string) wp_unslash($_POST['xfusion_llm_prompt_slug']));
        if (! xfusion_llm_prompt_is_valid_slug($slug)) {
            return;
        }

        $label = sanitize_text_field(wp_unslash((string) ($_POST['xfusion_llm_prompt_label'] ?? '')));
        $content = wp_unslash((string) ($_POST['xfusion_llm_prompt_content'] ?? ''));
        $content = is_string($content) ? trim($content) : '';
        $makeActive = ! empty($_POST['xfusion_llm_prompt_make_active']);

        if ($content !== '') {
            xfusion_llm_prompt_save_version($slug, $label, $content, $makeActive);
        }

        wp_safe_redirect(add_query_arg(['saved' => '1'], xfusion_llm_prompts_admin_url($slug)));
        exit;
    }
}

function xfusion_llm_prompts_render_notices(): void
{
    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Active prompt version updated.', 'xfusion') . '</p></div>';
    }
    if (isset($_GET['saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('New prompt version saved.', 'xfusion') . '</p></div>';
    }
}

function xfusion_llm_prompts_render_overview_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    xfusion_llm_prompt_maybe_migrate_legacy();

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('LLM Prompts', 'xfusion'); ?></h1>
        <p class="description"><?php esc_html_e('Manage versioned system prompts sent to Xfusion-llm. Each submenu is one prompt slot — save versions, pick active, full text is forwarded on generation.', 'xfusion'); ?></p>
        <?php xfusion_llm_prompts_render_notices(); ?>
        <table class="widefat striped" style="max-width:960px;margin-top:16px;">
            <thead>
            <tr>
                <th><?php esc_html_e('Prompt', 'xfusion'); ?></th>
                <th><?php esc_html_e('Active version', 'xfusion'); ?></th>
                <th><?php esc_html_e('Versions', 'xfusion'); ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (xfusion_llm_prompt_slug_definitions() as $slug => $def) :
                $active = xfusion_llm_prompt_get_active($slug);
                $count = count(xfusion_llm_prompt_versions_for_slug($slug));
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html((string) $def['menu_title']); ?></strong><br/>
                        <code><?php echo esc_html($slug); ?></code>
                    </td>
                    <td>
                        <?php if ($active !== null) : ?>
                            <?php echo esc_html($active['label']); ?><br/>
                            <span class="description"><code><?php echo esc_html($active['id']); ?></code></span>
                        <?php else : ?>
                            <span class="description"><?php esc_html_e('Not configured', 'xfusion'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) $count; ?></td>
                    <td><a class="button button-secondary" href="<?php echo esc_url(xfusion_llm_prompts_admin_url($slug)); ?>"><?php esc_html_e('Manage', 'xfusion'); ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description" style="margin-top:16px;">
            <?php esc_html_e('Connection settings (API URL, model, default status) remain under Settings → XFusion LLM.', 'xfusion'); ?>
            <a href="<?php echo esc_url(admin_url('options-general.php?page=xfusion-llm-settings')); ?>"><?php esc_html_e('Open XFusion LLM settings', 'xfusion'); ?></a>
        </p>
    </div>
    <?php
}

function xfusion_llm_prompts_render_slug_page(string $slug): void
{
    if (! current_user_can('manage_options') || ! xfusion_llm_prompt_is_valid_slug($slug)) {
        return;
    }

    $def = xfusion_llm_prompt_slug_definitions()[$slug];
    $versions = xfusion_llm_prompt_versions_for_slug($slug);
    $active = xfusion_llm_prompt_get_active($slug);
    $registry = xfusion_llm_prompt_registry();
    $activeId = trim((string) ($registry[$slug]['active_id'] ?? ''));

    ?>
    <div class="wrap">
        <h1><?php echo esc_html((string) $def['title']); ?></h1>
        <p>
            <a href="<?php echo esc_url(xfusion_llm_prompts_admin_url()); ?>">&larr; <?php esc_html_e('All LLM Prompts', 'xfusion'); ?></a>
        </p>
        <p class="description"><?php echo esc_html((string) $def['description']); ?></p>
        <?php xfusion_llm_prompts_render_notices(); ?>

        <?php if ($active !== null) : ?>
            <div style="margin:16px 0;padding:12px 16px;background:#f0f6fc;border:1px solid #c3c4c7;border-radius:6px;max-width:960px;">
                <strong><?php esc_html_e('Active version', 'xfusion'); ?>:</strong>
                <?php echo esc_html($active['label']); ?>
                <code style="margin-left:6px;"><?php echo esc_html($active['id']); ?></code>
                <?php if ($active['created_at'] !== '') : ?>
                    <span class="description"> — <?php echo esc_html($active['created_at']); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($versions !== []) : ?>
            <h2><?php esc_html_e('Versions', 'xfusion'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('xfusion_llm_prompt_manage'); ?>
                <input type="hidden" name="xfusion_llm_prompt_slug" value="<?php echo esc_attr($slug); ?>"/>
                <table class="widefat striped" style="max-width:960px;">
                    <thead>
                    <tr>
                        <th style="width:48px;"><?php esc_html_e('Active', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Label', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Version ID', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Created', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Preview', 'xfusion'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($versions as $version) : ?>
                        <tr>
                            <td><input type="radio" name="xfusion_llm_prompt_active_choice" value="<?php echo esc_attr($version['id']); ?>" <?php checked($activeId, $version['id']); ?>/></td>
                            <td><?php echo esc_html($version['label']); ?></td>
                            <td><code><?php echo esc_html($version['id']); ?></code></td>
                            <td><?php echo esc_html($version['created_at']); ?></td>
                            <td><span class="description"><?php echo esc_html(mb_substr($version['content'], 0, 80)); ?>…</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top:10px;">
                    <button type="submit" name="xfusion_llm_prompt_set_active" class="button"><?php esc_html_e('Set active version', 'xfusion'); ?></button>
                </p>
            </form>
        <?php endif; ?>

        <h2><?php esc_html_e('Save new version', 'xfusion'); ?></h2>
        <p class="description"><?php echo esc_html((string) $def['placeholder_hint']); ?></p>
        <form method="post" action="" style="max-width:960px;">
            <?php wp_nonce_field('xfusion_llm_prompt_manage'); ?>
            <input type="hidden" name="xfusion_llm_prompt_slug" value="<?php echo esc_attr($slug); ?>"/>
            <p>
                <label for="xfusion_llm_prompt_label"><strong><?php esc_html_e('Version label', 'xfusion'); ?></strong></label><br/>
                <input type="text" class="regular-text" id="xfusion_llm_prompt_label" name="xfusion_llm_prompt_label" placeholder="<?php esc_attr_e('e.g. July 2026 coaching rules', 'xfusion'); ?>"/>
            </p>
            <p>
                <label for="xfusion_llm_prompt_content"><strong><?php esc_html_e('Prompt content (full system prompt)', 'xfusion'); ?></strong></label><br/>
                <textarea id="xfusion_llm_prompt_content" name="xfusion_llm_prompt_content" rows="22" class="large-text code" placeholder="<?php esc_attr_e('Paste the complete system prompt…', 'xfusion'); ?>"><?php
                    echo esc_textarea($active['content'] ?? xfusion_llm_prompt_load_default_content($slug));
                ?></textarea>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="xfusion_llm_prompt_make_active" value="1" checked="checked"/>
                    <?php esc_html_e('Set as active after save', 'xfusion'); ?>
                </label>
            </p>
            <p>
                <button type="submit" name="xfusion_llm_prompt_save_version" class="button button-primary"><?php esc_html_e('Save prompt version', 'xfusion'); ?></button>
            </p>
        </form>
    </div>
    <?php
}
