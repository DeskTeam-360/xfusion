<?php

namespace App\Support;

use App\Models\User;

/**
 * Decode Gravity / app user_access payloads (CSV or JSON array) for UI.
 */
final class UserAccessCoder
{
    private function __construct() {}

    public static function normalizeSlug(string $item): string
    {
        return strtolower(trim($item));
    }

    /**
     * @return list<string>
     */
    public static function slugsFromStored(mixed $stored): array
    {
        if ($stored === null) {
            return [];
        }
        if (is_array($stored)) {
            $stored = json_encode($stored);
        }

        $s = trim((string) $stored);
        if ($s === '') {
            return [];
        }

        $decoded = json_decode($s, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $slugs = [];
            foreach ($decoded as $x) {
                if (is_string($x) || is_numeric($x)) {
                    $slug = self::normalizeSlug((string) $x);
                    if ($slug !== '') {
                        $slugs[$slug] = true;
                    }
                }
            }

            return array_keys($slugs);
        }

        $slugs = [];
        foreach (explode(',', $s) as $chunk) {
            $slug = self::normalizeSlug($chunk);
            if ($slug !== '') {
                $slugs[$slug] = true;
            }
        }

        return array_keys($slugs);
    }

    public static function slugsFromUser(User $user): array
    {
        $metaRow = $user->meta->where('meta_key', 'user_access')->first();
        $raw = is_object($metaRow) ? ($metaRow->meta_value ?? '') : ($metaRow['meta_value'] ?? '');

        return self::slugsFromStored($raw);
    }

    /** HTML badge list; safe quoted via e(). */
    public static function badgesHtmlFromUser(User $user, int $maxVisible = 8): string
    {
        $slugs = self::slugsFromUser($user);
        if ($slugs === []) {
            return '<span class="text-xs text-muted">—</span>';
        }

        $visible = array_slice($slugs, 0, $maxVisible);
        $more = max(0, count($slugs) - $maxVisible);
        $html = '<div class="flex flex-wrap gap-1">';
        foreach ($visible as $slug) {
            $html .= '<span class="inline-block whitespace-nowrap rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[11px] text-dark dark:border-gray-600 dark:bg-darkgray dark:text-light">' . e($slug) . '</span>';
        }
        if ($more > 0) {
            $html .= '<span class="text-xs text-muted" title="' . e(implode(', ', array_slice($slugs, $maxVisible))) . '">+' . $more . '</span>';
        }
        $html .= '</div>';

        return $html;
    }
}
