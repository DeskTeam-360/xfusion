<?php
/**
 * Central LLM prompt registry — versioned prompts stored in wp_options.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_LLM_PROMPT_REGISTRY_OPTION = 'xfusion_llm_prompt_registry';

const XFUSION_LLM_PROMPT_SLUG_COR_COACH = 'cor_unified_coach';
const XFUSION_LLM_PROMPT_SLUG_COR_USER = 'cor_unified_user';
const XFUSION_LLM_PROMPT_SLUG_OO_BRIEF = 'one_on_one_brief_system';
const XFUSION_LLM_PROMPT_SLUG_OO_SYNTHESIS = 'one_on_one_synthesis_system';

/**
 * @return array<string, array{
 *   title: string,
 *   menu_title: string,
 *   description: string,
 *   placeholder_hint: string,
 *   default_files: list<string>
 * }>
 */
function xfusion_llm_prompt_slug_definitions(): array
{
    $pluginDir = defined('XFUSION_PLUGIN_DIR') ? XFUSION_PLUGIN_DIR : '';
    $repoRoot = $pluginDir !== '' ? dirname($pluginDir, 4) : '';

    return apply_filters('xfusion_llm_prompt_slug_definitions', [
        XFUSION_LLM_PROMPT_SLUG_COR_COACH => [
            'title' => __('COR Unified — Coach (system)', 'xfusion'),
            'menu_title' => __('COR Coach System', 'xfusion'),
            'description' => __('System coaching rules sent to POST /api/v1/evaluation/evaluate-unified as coach_prompt.', 'xfusion'),
            'placeholder_hint' => __('Full system prompt. No placeholders required — user data is in the user template.', 'xfusion'),
            'default_files' => array_values(array_filter([
                $repoRoot . '/AI-PROMPT.md',
                $pluginDir . 'prompts/cor_coach_system.md',
            ])),
        ],
        XFUSION_LLM_PROMPT_SLUG_COR_USER => [
            'title' => __('COR Unified — User template', 'xfusion'),
            'menu_title' => __('COR User Template', 'xfusion'),
            'description' => __('User instruction template for evaluate-unified. Placeholders: {cor_perf_context}, {caps}, {performance}, {category_hint}.', 'xfusion'),
            'placeholder_hint' => __('Use {cor_perf_context}, {caps}, {performance}, {category_hint}. Double braces for literal JSON: {{ and }}.', 'xfusion'),
            'default_files' => array_values(array_filter([
                $pluginDir . 'prompts/unified_user_prompt.md',
                $repoRoot . '/Xfusion-llm/prompts/unified_user_prompt.md',
            ])),
        ],
        XFUSION_LLM_PROMPT_SLUG_OO_BRIEF => [
            'title' => __('1-on-1 — Meeting Brief (system)', 'xfusion'),
            'menu_title' => __('1-on-1 Brief System', 'xfusion'),
            'description' => __('System prompt for POST /api/v1/one-on-one/meeting-brief (via Laravel).', 'xfusion'),
            'placeholder_hint' => __('Defines JSON output sections for the AI Meeting Brief.', 'xfusion'),
            'default_files' => array_values(array_filter([
                $pluginDir . 'prompts/one_on_one_brief_system.md',
                $repoRoot . '/Xfusion-llm/prompts/one_on_one_brief_system.md',
            ])),
        ],
        XFUSION_LLM_PROMPT_SLUG_OO_SYNTHESIS => [
            'title' => __('1-on-1 — Meeting Synthesis (system)', 'xfusion'),
            'menu_title' => __('1-on-1 Synthesis System', 'xfusion'),
            'description' => __('System prompt for POST /api/v1/one-on-one/meeting-synthesis (via Laravel).', 'xfusion'),
            'placeholder_hint' => __('Defines JSON output sections for the AI Meeting Synthesis.', 'xfusion'),
            'default_files' => array_values(array_filter([
                $pluginDir . 'prompts/one_on_one_synthesis_system.md',
                $repoRoot . '/Xfusion-llm/prompts/one_on_one_synthesis_system.md',
            ])),
        ],
    ]);
}

function xfusion_llm_prompt_is_valid_slug(string $slug): bool
{
    return isset(xfusion_llm_prompt_slug_definitions()[$slug]);
}

/**
 * @return array<string, array{active_id: string, versions: list<array{id: string, label: string, content: string, created_at: string}>}>
 */
function xfusion_llm_prompt_registry(): array
{
    $raw = get_option(XFUSION_LLM_PROMPT_REGISTRY_OPTION, []);
    if (! is_array($raw)) {
        return [];
    }

    return $raw;
}

function xfusion_llm_prompt_save_registry(array $registry): void
{
    update_option(XFUSION_LLM_PROMPT_REGISTRY_OPTION, $registry, false);
}

function xfusion_llm_prompt_load_default_content(string $slug): string
{
    $defs = xfusion_llm_prompt_slug_definitions();
    if (! isset($defs[$slug])) {
        return '';
    }

    foreach ($defs[$slug]['default_files'] as $path) {
        if (is_readable($path)) {
            $text = trim((string) file_get_contents($path));
            if ($text !== '') {
                return $text;
            }
        }
    }

    return '';
}

/**
 * @return list<array{id: string, label: string, content: string, created_at: string}>
 */
function xfusion_llm_prompt_versions_for_slug(string $slug): array
{
    if (! xfusion_llm_prompt_is_valid_slug($slug)) {
        return [];
    }

    xfusion_llm_prompt_maybe_migrate_legacy();
    xfusion_llm_prompt_maybe_seed_slug($slug);

    $bucket = xfusion_llm_prompt_registry()[$slug] ?? [];
    $versions = is_array($bucket['versions'] ?? null) ? $bucket['versions'] : [];
    $out = [];

    foreach ($versions as $row) {
        if (! is_array($row)) {
            continue;
        }
        $id = trim((string) ($row['id'] ?? ''));
        $content = trim((string) ($row['content'] ?? ''));
        if ($id === '' || $content === '') {
            continue;
        }
        $out[] = [
            'id' => $id,
            'label' => trim((string) ($row['label'] ?? $id)),
            'content' => $content,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    return $out;
}

/**
 * @return array{id: string, label: string, content: string, created_at: string}|null
 */
function xfusion_llm_prompt_get_active(string $slug): ?array
{
    if (! xfusion_llm_prompt_is_valid_slug($slug)) {
        return null;
    }

    $registry = xfusion_llm_prompt_registry();
    $bucket = $registry[$slug] ?? [];
    $activeId = trim((string) ($bucket['active_id'] ?? ''));

    foreach (xfusion_llm_prompt_versions_for_slug($slug) as $version) {
        if ($activeId !== '' && $version['id'] === $activeId) {
            return $version;
        }
    }

    $versions = xfusion_llm_prompt_versions_for_slug($slug);

    return $versions[0] ?? null;
}

function xfusion_llm_prompt_set_active(string $slug, string $versionId): bool
{
    if (! xfusion_llm_prompt_is_valid_slug($slug)) {
        return false;
    }

    foreach (xfusion_llm_prompt_versions_for_slug($slug) as $version) {
        if ($version['id'] === $versionId) {
            $registry = xfusion_llm_prompt_registry();
            if (! isset($registry[$slug]) || ! is_array($registry[$slug])) {
                $registry[$slug] = ['active_id' => '', 'versions' => []];
            }
            $registry[$slug]['active_id'] = $versionId;
            xfusion_llm_prompt_save_registry($registry);

            return true;
        }
    }

    return false;
}

function xfusion_llm_prompt_save_version(string $slug, string $label, string $content, bool $makeActive = true): ?string
{
    if (! xfusion_llm_prompt_is_valid_slug($slug) || trim($content) === '') {
        return null;
    }

    $id = 'pv_' . $slug . '_' . gmdate('Ymd_His');
    $registry = xfusion_llm_prompt_registry();
    if (! isset($registry[$slug]) || ! is_array($registry[$slug])) {
        $registry[$slug] = ['active_id' => '', 'versions' => []];
    }
    if (! is_array($registry[$slug]['versions'] ?? null)) {
        $registry[$slug]['versions'] = [];
    }

    $registry[$slug]['versions'][] = [
        'id' => $id,
        'label' => $label !== '' ? $label : sprintf(__('Version %s', 'xfusion'), gmdate('Y-m-d H:i')),
        'content' => trim($content),
        'created_at' => gmdate('c'),
    ];

    if ($makeActive) {
        $registry[$slug]['active_id'] = $id;
    }

    xfusion_llm_prompt_save_registry($registry);

    return $id;
}

function xfusion_llm_prompt_maybe_seed_slug(string $slug): void
{
    if (! xfusion_llm_prompt_is_valid_slug($slug)) {
        return;
    }

    $registry = xfusion_llm_prompt_registry();
    $bucket = $registry[$slug] ?? [];
    $existing = is_array($bucket['versions'] ?? null) ? $bucket['versions'] : [];
    if ($existing !== []) {
        return;
    }

    $default = xfusion_llm_prompt_load_default_content($slug);
    if ($default === '') {
        return;
    }

    xfusion_llm_prompt_save_version(
        $slug,
        __('Seed (default file)', 'xfusion'),
        $default,
        true
    );
}

function xfusion_llm_prompt_maybe_seed_cor_unified(): void
{
    xfusion_llm_prompt_maybe_migrate_legacy();
    xfusion_llm_prompt_maybe_seed_slug(XFUSION_LLM_PROMPT_SLUG_COR_COACH);
    xfusion_llm_prompt_maybe_seed_slug(XFUSION_LLM_PROMPT_SLUG_COR_USER);
}

function xfusion_llm_prompt_maybe_migrate_legacy(): void
{
    if (xfusion_llm_prompt_registry() !== []) {
        return;
    }

    if (! defined('XFUSION_LLM_PROMPT_VERSIONS_OPTION') || ! defined('XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION')) {
        return;
    }

    $legacy = get_option(XFUSION_LLM_PROMPT_VERSIONS_OPTION, []);
    if (! is_array($legacy) || $legacy === []) {
        return;
    }

    $registry = [];
    $legacyActive = (string) get_option(XFUSION_LLM_ACTIVE_PROMPT_ID_OPTION, '');

    foreach ($legacy as $row) {
        if (! is_array($row)) {
            continue;
        }
        $id = trim((string) ($row['id'] ?? ''));
        $coach = trim((string) ($row['content'] ?? ''));
        $user = trim((string) ($row['user_template'] ?? ''));
        if ($id === '') {
            continue;
        }

        if ($coach !== '') {
            if (! isset($registry[XFUSION_LLM_PROMPT_SLUG_COR_COACH])) {
                $registry[XFUSION_LLM_PROMPT_SLUG_COR_COACH] = ['active_id' => '', 'versions' => []];
            }
            $registry[XFUSION_LLM_PROMPT_SLUG_COR_COACH]['versions'][] = [
                'id' => $id . '_coach',
                'label' => trim((string) ($row['label'] ?? $id)),
                'content' => $coach,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
            if ($legacyActive !== '' && $id === $legacyActive) {
                $registry[XFUSION_LLM_PROMPT_SLUG_COR_COACH]['active_id'] = $id . '_coach';
            }
        }

        if ($user !== '') {
            if (! isset($registry[XFUSION_LLM_PROMPT_SLUG_COR_USER])) {
                $registry[XFUSION_LLM_PROMPT_SLUG_COR_USER] = ['active_id' => '', 'versions' => []];
            }
            $registry[XFUSION_LLM_PROMPT_SLUG_COR_USER]['versions'][] = [
                'id' => $id . '_user',
                'label' => trim((string) ($row['label'] ?? $id)),
                'content' => $user,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
            if ($legacyActive !== '' && $id === $legacyActive) {
                $registry[XFUSION_LLM_PROMPT_SLUG_COR_USER]['active_id'] = $id . '_user';
            }
        }
    }

    if ($registry !== []) {
        xfusion_llm_prompt_save_registry($registry);
    }
}

/**
 * Active COR unified prompt (coach + user) for evaluate-unified.
 *
 * @return array{id: string, label: string, content: string, user_template: string, created_at: string, coach_version_id: string, user_version_id: string}|null
 */
function xfusion_llm_get_active_cor_unified_prompt(): ?array
{
    $coach = xfusion_llm_prompt_get_active(XFUSION_LLM_PROMPT_SLUG_COR_COACH);
    $user = xfusion_llm_prompt_get_active(XFUSION_LLM_PROMPT_SLUG_COR_USER);

    if ($coach === null && $user === null) {
        return null;
    }

    $coachId = $coach['id'] ?? '';
    $userId = $user['id'] ?? '';

    return [
        'id' => $coachId !== '' ? $coachId : $userId,
        'label' => trim(($coach['label'] ?? '') . ' / ' . ($user['label'] ?? '')),
        'content' => $coach['content'] ?? '',
        'user_template' => $user['content'] ?? (function_exists('xfusion_llm_default_user_prompt_template') ? xfusion_llm_default_user_prompt_template() : ''),
        'created_at' => $coach['created_at'] ?? ($user['created_at'] ?? ''),
        'coach_version_id' => $coachId,
        'user_version_id' => $userId,
    ];
}

/**
 * Payload for Laravel / LLM — active system prompt for a slug.
 *
 * @return array{content: string, id: string, label: string}|null
 */
function xfusion_llm_prompt_active_payload(string $slug): ?array
{
    $active = xfusion_llm_prompt_get_active($slug);
    if ($active === null) {
        return null;
    }

    return [
        'content' => $active['content'],
        'id' => $active['id'],
        'label' => $active['label'],
    ];
}
