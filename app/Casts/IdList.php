<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A list of integer ids stored as a JSON array in a text column.
 *
 * Reading is deliberately forgiving, because these columns have been written
 * by several client versions: a proper array, a lone scalar (one owner saved
 * as 432 / "432"), a JSON string ("[432,437]"), a double-encoded JSON string,
 * or a comma-separated string all come back as a clean list<int>. Writing
 * always stores a normalized JSON array.
 */
class IdList implements CastsAttributes
{
    /**
     * @return list<int>
     */
    public static function parse(mixed $value): array
    {
        // Unwrap JSON text, including values that were JSON-encoded twice.
        for ($i = 0; $i < 3 && is_string($value); $i++) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [];
            }
            if ($trimmed[0] === '[' || $trimmed[0] === '"') {
                $decoded = json_decode($trimmed, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    break;
                }
                $value = $decoded;

                continue;
            }
            // "432,437" or a single "432"
            $value = preg_split('/\s*,\s*/', $trimmed) ?: [];
            break;
        }

        if (is_int($value) || is_float($value)) {
            $value = [$value];
        }
        if (! is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            $int = filter_var($item, FILTER_VALIDATE_INT);
            if ($int !== false && $int > 0) {
                $ids[$int] = $int;
            }
        }

        return array_values($ids);
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        return self::parse($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return json_encode(self::parse($value));
    }
}
