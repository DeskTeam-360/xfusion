<?php

namespace App\Support;

use App\Models\WpUserMeta;

/**
 * Whether a wp_users.ID holds the WordPress "administrator" role. Used to let
 * administrators work across every company in ARP/ARR/QBR, and view IRR and
 * 1-on-1 read-only (never create or edit them: they are tied to a specific
 * person-to-person pairing, and raw preparation stays private).
 */
class WpAdmin
{
    /** @var array<int, bool> */
    private static array $cache = [];

    public static function isAdministrator(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }

        return self::$cache[$userId] ??= self::lookup($userId);
    }

    private static function lookup(int $userId): bool
    {
        $raw = WpUserMeta::query()
            ->where('user_id', $userId)
            ->where('meta_key', config('app.wp_prefix', 'wp_').'capabilities')
            ->value('meta_value');

        if (! is_string($raw) || $raw === '') {
            return false;
        }

        $caps = @unserialize($raw, ['allowed_classes' => false]);

        return is_array($caps) && ! empty($caps['administrator']);
    }
}
