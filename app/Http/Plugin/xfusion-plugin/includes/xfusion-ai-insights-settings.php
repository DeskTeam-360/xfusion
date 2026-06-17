<?php
/**
 * AI Insights settings — model, prompt versioning, default publish status.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_LLM_PROMPT_VERSIONS_OPTION = 'xfusion_llm_prompt_versions';
const XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION = 'xfusion_llm_active_prompt_id';
const XFUSION_LLM_INSIGHT_MODEL_OPTION = 'xfusion_llm_insight_model';
const XFUSION_LLM_INSIGHT_DEFAULT_STATUS_OPTION = 'xfusion_llm_insight_default_status';

const XFUSION_RESULT_EVAL_STATUS_DRAFT = 'draft';
const XFUSION_RESULT_EVAL_STATUS_PUBLISHED = 'published';
const XFUSION_RESULT_EVAL_STATUS_SANDBOX = 'sandbox';

/**
 * Full insight model catalog (pricing per 1M tokens — admin estimates only).
 *
 * @return array<string, array{family: string, label: string, input_usd: float, output_usd: float, description: string}>
 */
function xfusion_llm_insight_model_catalog(): array
{
    return apply_filters('xfusion_llm_insight_model_catalog', [
        'gpt-4.1' => [
            'family' => 'GPT-4',
            'label' => 'GPT-4.1',
            'input_usd' => 2.00,
            'output_usd' => 8.00,
            'description' => __('Best overall GPT-4 model for production apps', 'xfusion'),
        ],
        'gpt-4o' => [
            'family' => 'GPT-4',
            'label' => 'GPT-4o',
            'input_usd' => 2.50,
            'output_usd' => 10.00,
            'description' => __('Multimodal (image + text + audio) applications', 'xfusion'),
        ],
        'gpt-4.1-mini' => [
            'family' => 'GPT-4',
            'label' => 'GPT-4.1 Mini',
            'input_usd' => 0.40,
            'output_usd' => 1.60,
            'description' => __('High-volume content generation', 'xfusion'),
        ],
        'gpt-4o-mini' => [
            'family' => 'GPT-4',
            'label' => 'GPT-4o Mini',
            'input_usd' => 0.15,
            'output_usd' => 0.60,
            'description' => __('Cheapest practical chatbot model', 'xfusion'),
        ],
        'gpt-4.1-nano' => [
            'family' => 'GPT-4',
            'label' => 'GPT-4.1 Nano',
            'input_usd' => 0.10,
            'output_usd' => 0.40,
            'description' => __('Classification, extraction, routing', 'xfusion'),
        ],
        'gpt-5.5' => [
            'family' => 'GPT-5',
            'label' => 'GPT-5.5',
            'input_usd' => 5.00,
            'output_usd' => 30.00,
            'description' => __('Best quality available', 'xfusion'),
        ],
        'gpt-5.4' => [
            'family' => 'GPT-5',
            'label' => 'GPT-5.4',
            'input_usd' => 2.50,
            'output_usd' => 15.00,
            'description' => __('Best balance of quality and cost', 'xfusion'),
        ],
        'gpt-5' => [
            'family' => 'GPT-5',
            'label' => 'GPT-5',
            'input_usd' => 1.25,
            'output_usd' => 10.00,
            'description' => __('General-purpose production model', 'xfusion'),
        ],
        'gpt-5-mini' => [
            'family' => 'GPT-5',
            'label' => 'GPT-5 Mini',
            'input_usd' => 0.25,
            'output_usd' => 2.00,
            'description' => __('Automation, content, agents', 'xfusion'),
        ],
        'gpt-5-nano' => [
            'family' => 'GPT-5',
            'label' => 'GPT-5 Nano',
            'input_usd' => 0.05,
            'output_usd' => 0.40,
            'description' => __('Ultra-cheap classification and routing', 'xfusion'),
        ],
    ]);
}

/**
 * @return list<string>
 */
function xfusion_llm_allowed_insight_models(): array
{
    return array_keys(xfusion_llm_insight_model_catalog());
}

/**
 * @return array{input_usd: float, output_usd: float, label: string, description: string, family: string}|null
 */
function xfusion_llm_insight_model_meta(string $model): ?array
{
    $catalog = xfusion_llm_insight_model_catalog();
    if (! isset($catalog[$model])) {
        return null;
    }

    return array_merge(['input_usd' => 0.0, 'output_usd' => 0.0], $catalog[$model]);
}

/**
 * @return array{input_usd: float, output_usd: float}
 */
function xfusion_llm_model_token_pricing(string $model): array
{
    $meta = xfusion_llm_insight_model_meta($model);
    if ($meta === null) {
        return ['input_usd' => 0.15, 'output_usd' => 0.60];
    }

    return [
        'input_usd' => (float) $meta['input_usd'],
        'output_usd' => (float) $meta['output_usd'],
    ];
}

function xfusion_llm_estimate_cost(string $model, int $promptTokens, int $completionTokens): float
{
    $rates = xfusion_llm_model_token_pricing($model);

    return max(0.0, ($promptTokens / 1000000) * $rates['input_usd']
        + ($completionTokens / 1000000) * $rates['output_usd']);
}

function xfusion_llm_format_cost_usd(float $usd): string
{
    if ($usd <= 0) {
        return '~$0.00';
    }

    $decimals = $usd < 0.01 ? 4 : 2;

    return '~$' . number_format_i18n($usd, $decimals);
}

function xfusion_llm_insight_model(): string
{
    $model = (string) get_option(XFUSION_LLM_INSIGHT_MODEL_OPTION, 'gpt-4o-mini');
    $allowed = xfusion_llm_allowed_insight_models();

    return in_array($model, $allowed, true) ? $model : 'gpt-4o-mini';
}

function xfusion_llm_insight_default_status(): string
{
    $status = (string) get_option(XFUSION_LLM_INSIGHT_DEFAULT_STATUS_OPTION, XFUSION_RESULT_EVAL_STATUS_DRAFT);
    $allowed = [
        XFUSION_RESULT_EVAL_STATUS_DRAFT,
        XFUSION_RESULT_EVAL_STATUS_PUBLISHED,
        XFUSION_RESULT_EVAL_STATUS_SANDBOX,
    ];

    return in_array($status, $allowed, true) ? $status : XFUSION_RESULT_EVAL_STATUS_DRAFT;
}

/**
 * Whether Generate Insights cooldown applies (disabled in sandbox default mode).
 */
function xfusion_llm_insight_cooldown_enabled(): bool
{
    return xfusion_llm_insight_default_status() !== XFUSION_RESULT_EVAL_STATUS_SANDBOX;
}

/**
 * @return list<array{id: string, label: string, content: string, user_template: string, created_at: string}>
 */
function xfusion_llm_prompt_versions(): array
{
    $raw = get_option(XFUSION_LLM_PROMPT_VERSIONS_OPTION, []);
    if (! is_array($raw)) {
        return [];
    }

    $defaultUser = xfusion_llm_default_user_prompt_template();
    $out = [];
    foreach ($raw as $row) {
        if (! is_array($row)) {
            continue;
        }
        $id = trim((string) ($row['id'] ?? ''));
        $content = trim((string) ($row['content'] ?? ''));
        if ($id === '' || $content === '') {
            continue;
        }
        $userTemplate = trim((string) ($row['user_template'] ?? ''));
        $out[] = [
            'id' => $id,
            'label' => trim((string) ($row['label'] ?? $id)),
            'content' => $content,
            'user_template' => $userTemplate !== '' ? $userTemplate : $defaultUser,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    return $out;
}

function xfusion_llm_default_user_prompt_template(): string
{
    $paths = [];
    if (defined('XFUSION_PLUGIN_DIR')) {
        $paths[] = XFUSION_PLUGIN_DIR . 'prompts/unified_user_prompt.md';
        $paths[] = dirname(XFUSION_PLUGIN_DIR, 4) . '/Xfusion-llm/prompts/unified_user_prompt.md';
    }
    $paths[] = dirname(__DIR__, 2) . '/prompts/unified_user_prompt.md';

    foreach ($paths as $path) {
        if (is_readable($path)) {
            $text = trim((string) file_get_contents($path));
            if ($text !== '') {
                return $text;
            }
        }
    }

    return <<<'PROMPT'
You are generating unified COR™ insights. Scores are pre-calculated — do NOT recalculate or invent numeric scores.

COR Performance Knowledge Base (category: COR Performance — use ALL of this context when interpreting results):
{cor_perf_context}

COR Organization Capabilities (scores 0-5, pre-calculated):
{caps}

Performance by FUSION dimension (Primary = highest-weight questions, Secondary, Tertiary):
{performance}

Return ONLY raw JSON with keys cor_organization_capabilities, performance ({category_hint}), key_observation, recommended_focus_area.
PROMPT;
}

function xfusion_llm_maybe_seed_prompt_versions(): void
{
    if (xfusion_llm_prompt_versions() !== []) {
        return;
    }

    $defaultPath = defined('XFUSION_PLUGIN_DIR')
        ? dirname(XFUSION_PLUGIN_DIR, 4) . '/AI-PROMPT.md'
        : dirname(__DIR__, 5) . '/AI-PROMPT.md';
    $content = '';
    if (is_readable($defaultPath)) {
        $content = trim((string) file_get_contents($defaultPath));
    }
    if ($content === '') {
        $content = 'You are a Human Performance Coach operating within the FUSION framework.';
    }

    $id = 'pv_seed_' . gmdate('Ymd');
    $versions = [[
        'id' => $id,
        'label' => __('Seed (AI-PROMPT.md)', 'xfusion'),
        'content' => $content,
        'user_template' => xfusion_llm_default_user_prompt_template(),
        'created_at' => gmdate('c'),
    ]];

    update_option(XFUSION_LLM_PROMPT_VERSIONS_OPTION, $versions, false);
    update_option(XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION, $id, false);
}

/**
 * @return array{id: string, label: string, content: string, user_template: string, created_at: string}|null
 */
function xfusion_llm_get_prompt_version(string $id): ?array
{
    foreach (xfusion_llm_prompt_versions() as $version) {
        if ($version['id'] === $id) {
            return $version;
        }
    }

    return null;
}

/**
 * Active coaching prompt for unified insight generation.
 *
 * @return array{id: string, label: string, content: string, user_template: string, created_at: string}|null
 */
function xfusion_llm_get_active_prompt(): ?array
{
    xfusion_llm_maybe_seed_prompt_versions();

    $activeId = (string) get_option(XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION, '');
    if ($activeId !== '') {
        $found = xfusion_llm_get_prompt_version($activeId);
        if ($found !== null) {
            return $found;
        }
    }

    $versions = xfusion_llm_prompt_versions();

    return $versions[0] ?? null;
}

/**
 * Generation config sent to evaluate-unified.
 *
 * @return array<string, mixed>
 */
function xfusion_llm_insight_generation_config(): array
{
    $prompt = xfusion_llm_get_active_prompt();

    return [
        'model' => xfusion_llm_insight_model(),
        'coach_prompt' => $prompt['content'] ?? '',
        'user_prompt_template' => $prompt['user_template'] ?? xfusion_llm_default_user_prompt_template(),
        'prompt_version_id' => $prompt['id'] ?? '',
        'prompt_version_label' => $prompt['label'] ?? '',
    ];
}

add_action('admin_init', 'xfusion_llm_insights_register_settings');
add_action('admin_init', 'xfusion_llm_insights_handle_prompt_actions');

function xfusion_llm_insights_register_settings(): void
{
    register_setting('xfusion_llm_settings', XFUSION_LLM_INSIGHT_MODEL_OPTION, [
        'type' => 'string',
        'sanitize_callback' => static function (string $value): string {
            $value = sanitize_text_field($value);
            $allowed = function_exists('xfusion_llm_allowed_insight_models')
                ? xfusion_llm_allowed_insight_models()
                : ['gpt-4o-mini'];

            return in_array($value, $allowed, true) ? $value : 'gpt-4o-mini';
        },
        'default' => 'gpt-4o-mini',
    ]);

    register_setting('xfusion_llm_settings', XFUSION_LLM_INSIGHT_DEFAULT_STATUS_OPTION, [
        'type' => 'string',
        'sanitize_callback' => static function (string $value): string {
            $value = sanitize_key($value);
            $allowed = [
                XFUSION_RESULT_EVAL_STATUS_DRAFT,
                XFUSION_RESULT_EVAL_STATUS_PUBLISHED,
                XFUSION_RESULT_EVAL_STATUS_SANDBOX,
            ];

            return in_array($value, $allowed, true) ? $value : XFUSION_RESULT_EVAL_STATUS_DRAFT;
        },
        'default' => XFUSION_RESULT_EVAL_STATUS_DRAFT,
    ]);
}

function xfusion_llm_insights_handle_prompt_actions(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['xfusion_llm_set_active_prompt'], $_POST['xfusion_llm_active_prompt_choice'])
        && check_admin_referer('xfusion_llm_prompt_versions')) {
        $choice = sanitize_text_field(wp_unslash((string) $_POST['xfusion_llm_active_prompt_choice']));
        if (xfusion_llm_get_prompt_version($choice) !== null) {
            update_option(XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION, $choice, false);
        }
        wp_safe_redirect(add_query_arg(['page' => 'xfusion-llm-settings', 'prompt_updated' => '1'], admin_url('options-general.php')));
        exit;
    }

    if (isset($_POST['xfusion_llm_save_prompt_version']) && check_admin_referer('xfusion_llm_prompt_versions')) {
        $label = sanitize_text_field(wp_unslash((string) ($_POST['xfusion_llm_prompt_label'] ?? '')));
        $content = wp_unslash((string) ($_POST['xfusion_llm_prompt_content'] ?? ''));
        $content = is_string($content) ? trim($content) : '';
        $userTemplate = wp_unslash((string) ($_POST['xfusion_llm_user_prompt_template'] ?? ''));
        $userTemplate = is_string($userTemplate) ? trim($userTemplate) : '';
        $makeActive = ! empty($_POST['xfusion_llm_prompt_make_active']);

        if ($content !== '') {
            $id = 'pv_' . gmdate('Ymd_His');
            $versions = xfusion_llm_prompt_versions();
            $versions[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : sprintf(__('Version %s', 'xfusion'), gmdate('Y-m-d H:i')),
                'content' => $content,
                'user_template' => $userTemplate !== '' ? $userTemplate : xfusion_llm_default_user_prompt_template(),
                'created_at' => gmdate('c'),
            ];
            update_option(XFUSION_LLM_PROMPT_VERSIONS_OPTION, $versions, false);
            if ($makeActive) {
                update_option(XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION, $id, false);
            }
        }

        wp_safe_redirect(add_query_arg(['page' => 'xfusion-llm-settings', 'prompt_saved' => '1'], admin_url('options-general.php')));
        exit;
    }
}

function xfusion_llm_insights_render_option_fields(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $currentModel = xfusion_llm_insight_model();
    $currentStatus = xfusion_llm_insight_default_status();
    $catalog = xfusion_llm_insight_model_catalog();
    $catalogJson = wp_json_encode($catalog);
    ?>
    <hr/>
    <h2><?php esc_html_e('AI Insights', 'xfusion'); ?></h2>
    <p class="description"><?php esc_html_e('Configure the model and default visibility for Generate Insights results. Save with the button below.', 'xfusion'); ?></p>

    <table class="form-table">
        <tr>
            <th><label for="xfusion_llm_insight_model"><?php esc_html_e('Insight model', 'xfusion'); ?></label></th>
            <td>
                <select id="xfusion_llm_insight_model" name="<?php echo esc_attr(XFUSION_LLM_INSIGHT_MODEL_OPTION); ?>" class="regular-text">
                    <?php
                    $families = ['GPT-4' => [], 'GPT-5' => []];
                    foreach ($catalog as $slug => $meta) {
                        $family = (string) ($meta['family'] ?? 'GPT-4');
                        if (! isset($families[$family])) {
                            $families[$family] = [];
                        }
                        $families[$family][$slug] = $meta;
                    }
                    foreach ($families as $familyName => $items) :
                        if ($items === []) {
                            continue;
                        }
                        ?>
                        <optgroup label="<?php echo esc_attr($familyName); ?>">
                            <?php foreach ($items as $slug => $meta) : ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($currentModel, $slug); ?>>
                                    <?php
                                    echo esc_html(sprintf(
                                        '%s — $%s / $%s per 1M (in/out)',
                                        (string) ($meta['label'] ?? $slug),
                                        number_format((float) $meta['input_usd'], 2),
                                        number_format((float) $meta['output_usd'], 2)
                                    ));
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <div id="xfusion-llm-model-desc" class="description" style="margin-top:10px;max-width:720px;padding:10px 12px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;"></div>
                <?php if (is_string($catalogJson) && $catalogJson !== '') : ?>
                    <script>
                    (function () {
                        var catalog = <?php echo $catalogJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
                        var select = document.getElementById('xfusion_llm_insight_model');
                        var box = document.getElementById('xfusion-llm-model-desc');
                        if (!select || !box) return;
                        function render() {
                            var m = catalog[select.value];
                            if (!m) { box.textContent = ''; return; }
                            box.innerHTML = '<strong>' + (m.label || select.value) + '</strong><br/>'
                                + (m.description || '') + '<br/>'
                                + '<span style="color:#646970;">Input: $' + Number(m.input_usd).toFixed(2)
                                + ' / 1M · Output: $' + Number(m.output_usd).toFixed(2) + ' / 1M</span>';
                        }
                        select.addEventListener('change', render);
                        render();
                    })();
                    </script>
                <?php endif; ?>
                <table class="widefat striped" style="margin-top:14px;max-width:960px;">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Family', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Model', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Input / 1M', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Output / 1M', 'xfusion'); ?></th>
                        <th><?php esc_html_e('Best use case', 'xfusion'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($catalog as $slug => $meta) : ?>
                        <tr>
                            <td><?php echo esc_html((string) ($meta['family'] ?? '')); ?></td>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td>$<?php echo esc_html(number_format((float) $meta['input_usd'], 2)); ?></td>
                            <td>$<?php echo esc_html(number_format((float) $meta['output_usd'], 2)); ?></td>
                            <td><?php echo esc_html((string) ($meta['description'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <th><label for="xfusion_llm_insight_default_status"><?php esc_html_e('Default insight status', 'xfusion'); ?></label></th>
            <td>
                <select id="xfusion_llm_insight_default_status" name="<?php echo esc_attr(XFUSION_LLM_INSIGHT_DEFAULT_STATUS_OPTION); ?>">
                    <option value="<?php echo esc_attr(XFUSION_RESULT_EVAL_STATUS_DRAFT); ?>" <?php selected($currentStatus, XFUSION_RESULT_EVAL_STATUS_DRAFT); ?>>
                        <?php esc_html_e('Draft — hidden from dashboard; no cooldown', 'xfusion'); ?>
                    </option>
                    <option value="<?php echo esc_attr(XFUSION_RESULT_EVAL_STATUS_SANDBOX); ?>" <?php selected($currentStatus, XFUSION_RESULT_EVAL_STATUS_SANDBOX); ?>>
                        <?php esc_html_e('Sandbox — shown on dashboard; generate again anytime (no cooldown)', 'xfusion'); ?>
                    </option>
                    <option value="<?php echo esc_attr(XFUSION_RESULT_EVAL_STATUS_PUBLISHED); ?>" <?php selected($currentStatus, XFUSION_RESULT_EVAL_STATUS_PUBLISHED); ?>>
                        <?php esc_html_e('Published — shown on dashboard; cooldown applies', 'xfusion'); ?>
                    </option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

function xfusion_llm_insights_render_settings_sections(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    xfusion_llm_maybe_seed_prompt_versions();

    $versions = xfusion_llm_prompt_versions();
    $activeId = (string) get_option(XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION, '');
    $activePrompt = xfusion_llm_get_active_prompt();
    ?>
    <h3><?php esc_html_e('Active coaching prompt', 'xfusion'); ?></h3>
    <?php if ($activePrompt !== null) : ?>
        <p><strong><?php echo esc_html($activePrompt['label']); ?></strong>
            <code><?php echo esc_html($activePrompt['id']); ?></code>
            <?php if ($activePrompt['created_at'] !== '') : ?>
                — <?php echo esc_html($activePrompt['created_at']); ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if ($versions !== []) : ?>
        <form method="post" action="">
            <?php wp_nonce_field('xfusion_llm_prompt_versions'); ?>
            <table class="widefat striped" style="max-width:960px;">
                <thead>
                <tr>
                    <th><?php esc_html_e('Active', 'xfusion'); ?></th>
                    <th><?php esc_html_e('Label', 'xfusion'); ?></th>
                    <th><?php esc_html_e('Version ID', 'xfusion'); ?></th>
                    <th><?php esc_html_e('Created', 'xfusion'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($versions as $version) : ?>
                    <tr>
                        <td><input type="radio" name="xfusion_llm_active_prompt_choice" value="<?php echo esc_attr($version['id']); ?>" <?php checked($activeId, $version['id']); ?>/></td>
                        <td><?php echo esc_html($version['label']); ?></td>
                        <td><code><?php echo esc_html($version['id']); ?></code></td>
                        <td><?php echo esc_html($version['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="submit" name="xfusion_llm_set_active_prompt" class="button"><?php esc_html_e('Set active prompt', 'xfusion'); ?></button></p>
        </form>
    <?php endif; ?>

    <h3><?php esc_html_e('Save new prompt version', 'xfusion'); ?></h3>
    <p class="description"><?php esc_html_e('Each version includes a system prompt (coach rules) and a user instruction template. Use placeholders in the user template: {cor_perf_context}, {caps}, {performance}, {category_hint}. For literal JSON braces use double braces, e.g. {{ and }}.', 'xfusion'); ?></p>
    <form method="post" action="">
        <?php wp_nonce_field('xfusion_llm_prompt_versions'); ?>
        <p>
            <label for="xfusion_llm_prompt_label"><strong><?php esc_html_e('Version label', 'xfusion'); ?></strong></label><br/>
            <input type="text" class="regular-text" id="xfusion_llm_prompt_label" name="xfusion_llm_prompt_label" placeholder="<?php esc_attr_e('e.g. June 2026 coaching rules', 'xfusion'); ?>"/>
        </p>
        <p>
            <label for="xfusion_llm_prompt_content"><strong><?php esc_html_e('System prompt (coach rules)', 'xfusion'); ?></strong></label><br/>
            <textarea id="xfusion_llm_prompt_content" name="xfusion_llm_prompt_content" rows="12" class="large-text code" placeholder="<?php esc_attr_e('FUSION coaching system prompt…', 'xfusion'); ?>"><?php
                echo esc_textarea($activePrompt['content'] ?? '');
            ?></textarea>
        </p>
        <p>
            <label for="xfusion_llm_user_prompt_template"><strong><?php esc_html_e('User instruction template', 'xfusion'); ?></strong></label><br/>
            <textarea id="xfusion_llm_user_prompt_template" name="xfusion_llm_user_prompt_template" rows="18" class="large-text code" placeholder="<?php esc_attr_e('Unified insight user prompt with placeholders…', 'xfusion'); ?>"><?php
                echo esc_textarea($activePrompt['user_template'] ?? xfusion_llm_default_user_prompt_template());
            ?></textarea>
        </p>
        <p>
            <label>
                <input type="checkbox" name="xfusion_llm_prompt_make_active" value="1" checked="checked"/>
                <?php esc_html_e('Set as active prompt after save', 'xfusion'); ?>
            </label>
        </p>
        <p><button type="submit" name="xfusion_llm_save_prompt_version" class="button button-primary"><?php esc_html_e('Save prompt version', 'xfusion'); ?></button></p>
    </form>
    <?php
}

add_action('xfusion_llm_settings_in_form', 'xfusion_llm_insights_render_option_fields');
add_action('xfusion_llm_settings_after_connection', 'xfusion_llm_insights_render_settings_sections');
