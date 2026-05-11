<?php

namespace App\Support;

use App\Models\User;

/**
 * Access based on WordPress/Corcel meta: user_role = "Company Admin" + company id meta.
 */
final class CompanyAdmin
{
    /** User meta keys (typically no wp_ prefix in Corcel meta table). */
    private const META_USER_ROLE = 'user_role';

    private const META_COMPANY = 'company';

    /**
     * Normalize meta string (scalar or first element of array).
     */
    private static function metaString(?object $metaRow): string
    {
        if ($metaRow === null) {
            return '';
        }
        $v = $metaRow->meta_value ?? ($metaRow['meta_value'] ?? '');
        if (is_array($v)) {
            return trim((string) ($v[0] ?? ''));
        }

        return trim((string) $v);
    }

    public static function portalUserRole(User $user): string
    {
        $row = $user->meta->where('meta_key', self::META_USER_ROLE)->first();

        return self::metaString($row);
    }

    /**
     * Apakah user diarahkan ke area /company/dashboard & /company/users.
     */
    public static function isCompanyAdminPortalUser(?User $user): bool
    {
        return $user !== null && self::portalUserRole($user) === 'Company Admin';
    }

    /** Company id from user meta (numeric string or int); null when empty */
    public static function portalCompanyMetaId(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }
        $row = $user->meta->where('meta_key', self::META_COMPANY)->first();
        $v = self::metaString($row);
        if ($v === '') {
            return null;
        }

        return ctype_digit($v) ? $v : $v;
    }
}
