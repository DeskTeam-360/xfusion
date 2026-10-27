<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Compares the database with the hand-kept requirement list in
 * config/fusion_schema.php (Laravel + WordPress plugin), read-only.
 * Deliberately not derived from migrations or models: tables such as `users`
 * are unused (wp_users is the source of truth).
 * Used by `php artisan fusion:check-schema` and GET /api/v1/schema-check.
 */
class SchemaChecker
{
    /**
     * @return array{
     *   ok: bool,
     *   tables: list<array{table: string, model: string, status: 'ok'|'missing_table'|'missing_columns', missing: list<string>, sql_hint: string}>
     * }
     */
    public function run(): array
    {
        $tables = [];
        foreach ($this->declaredModels() as $decl) {
            $table = $decl['table'];
            $row = ['table' => $table, 'model' => $decl['model'], 'status' => 'ok', 'missing' => [], 'sql_hint' => ''];

            if (! Schema::hasTable($table)) {
                $row['status'] = 'missing_table';
            } else {
                $have = array_map('strtolower', Schema::getColumnListing($table));
                $row['missing'] = array_values(array_filter(
                    $decl['fillable'],
                    fn (string $c) => ! in_array(strtolower($c), $have, true)
                ));
                if ($row['missing']) {
                    $row['status'] = 'missing_columns';
                }
            }
            if ($row['status'] !== 'ok') {
                $row['sql_hint'] = $this->sqlHint($table);
            }
            $tables[] = $row;
        }

        $ok = collect($tables)->every(fn ($t) => $t['status'] === 'ok');

        return ['ok' => $ok, 'tables' => $tables];
    }

    /**
     * The hand-kept manifest (config/fusion_schema.php), Laravel and plugin
     * requirements merged per table.
     *
     * @return list<array{model: string, table: string, fillable: list<string>}>
     */
    private function declaredModels(): array
    {
        $merged = [];
        foreach (['laravel', 'wordpress'] as $source) {
            foreach ((array) config("fusion_schema.{$source}", []) as $table => $columns) {
                $merged[$table]['sources'][] = $source;
                $merged[$table]['columns'] = array_values(array_unique(array_merge($merged[$table]['columns'] ?? [], (array) $columns)));
            }
        }
        $optional = (array) config('fusion_schema.optional', []);

        $out = [];
        foreach ($merged as $table => $info) {
            if (in_array($table, $optional, true)) {
                continue;
            }
            $out[] = ['model' => implode(' + ', $info['sources']), 'table' => $table, 'fillable' => $info['columns']];
        }
        usort($out, fn ($x, $y) => strcmp($x['table'], $y['table']));

        return $out;
    }

    /** The hand-run SQL file that creates the table, if there is one. */
    private function sqlHint(string $table): string
    {
        $dir = database_path('sql');
        if (! is_dir($dir)) {
            return '';
        }
        foreach (File::files($dir) as $file) {
            if (stripos(File::get($file->getPathname()), $table) !== false) {
                return 'database/sql/'.$file->getFilename();
            }
        }

        return '';
    }
}
