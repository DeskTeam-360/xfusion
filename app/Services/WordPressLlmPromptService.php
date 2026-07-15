<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Read versioned LLM prompts stored by the WordPress plugin (wp_options).
 */
class WordPressLlmPromptService
{
    public const REGISTRY_OPTION = 'xfusion_llm_prompt_registry';

    public const SLUG_OO_BRIEF = 'one_on_one_brief_system';

    public const SLUG_OO_SYNTHESIS = 'one_on_one_synthesis_system';

    public const SLUG_COR_COACH = 'cor_unified_coach';

    public const SLUG_COR_USER = 'cor_unified_user';

    /**
     * @return array{content: string, id: string, label: string}|null
     */
    public function getActivePrompt(string $slug): ?array
    {
        $registry = $this->getRegistry();
        if (! isset($registry[$slug]) || ! is_array($registry[$slug])) {
            return null;
        }

        $bucket = $registry[$slug];
        $activeId = trim((string) ($bucket['active_id'] ?? ''));
        $versions = is_array($bucket['versions'] ?? null) ? $bucket['versions'] : [];

        $chosen = null;
        foreach ($versions as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            $content = trim((string) ($row['content'] ?? ''));
            if ($id === '' || $content === '') {
                continue;
            }
            if ($activeId !== '' && $id === $activeId) {
                $chosen = $row;
                break;
            }
            if ($chosen === null) {
                $chosen = $row;
            }
        }

        if ($chosen === null) {
            return null;
        }

        return [
            'content' => trim((string) ($chosen['content'] ?? '')),
            'id' => trim((string) ($chosen['id'] ?? '')),
            'label' => trim((string) ($chosen['label'] ?? '')),
        ];
    }

    /**
     * @return array<string, array{active_id?: string, versions?: list<array<string, mixed>>}>
     */
    public function getRegistry(): array
    {
        $raw = DB::table('wp_options')
            ->where('option_name', self::REGISTRY_OPTION)
            ->value('option_value');

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $value = @unserialize($raw, ['allowed_classes' => false]);
        if (! is_array($value)) {
            return [];
        }

        return $value;
    }
}
