<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Compares the database with the tables/columns the models declare (read-only).
 * Deliberately ignores migrations: tables such as `users` are unused (WordPress's
 * wp_users is the source of truth) and production tables come from database/sql.
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
     * Reads $table and $fillable straight from each model's source instead of
     * loading the class, so one broken model cannot take the whole check down.
     * Models without an explicit $table are skipped.
     *
     * @return list<array{model: string, table: string, fillable: list<string>}>
     */
    private function declaredModels(): array
    {
        $out = [];
        foreach (File::allFiles(app_path('Models')) as $file) {
            $src = File::get($file->getPathname());
            if (! preg_match('/protected\s+\$table\s*=\s*[\x27\x22]([^\x27\x22]+)[\x27\x22]/', $src, $t)) {
                continue;
            }
            $fillable = [];
            if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\]\s*;/s', $src, $f)) {
                preg_match_all('/[\x27\x22]([A-Za-z0-9_]+)[\x27\x22]/', $f[1], $names);
                $fillable = $names[1];
            }
            $out[] = ['model' => str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()), 'table' => $t[1], 'fillable' => $fillable];
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
