<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Compares the database with what the code expects (read-only).
 * Used by `php artisan fusion:check-schema` and GET /api/v1/schema-check.
 */
class SchemaChecker
{
    /**
     * @return array{
     *   ok: bool,
     *   tables: list<array{table: string, model: string, status: 'ok'|'missing_table'|'missing_columns', missing: list<string>, sql_hint: string}>,
     *   pending_migrations: list<string>
     * }
     */
    public function run(): array
    {
        $tables = [];
        foreach ($this->models() as $class) {
            /** @var Model $model */
            $model = new $class;
            $table = $model->getTable();
            $row = ['table' => $table, 'model' => $class, 'status' => 'ok', 'missing' => [], 'sql_hint' => ''];

            if (! Schema::hasTable($table)) {
                $row['status'] = 'missing_table';
            } else {
                $have = array_map('strtolower', Schema::getColumnListing($table));
                $row['missing'] = array_values(array_filter(
                    $model->getFillable(),
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

        $pending = $this->pendingMigrations();
        $ok = $pending === [] && collect($tables)->every(fn ($t) => $t['status'] === 'ok');

        return ['ok' => $ok, 'tables' => $tables, 'pending_migrations' => $pending];
    }

    /** @return list<class-string<Model>> */
    private function models(): array
    {
        $classes = [];
        foreach (File::allFiles(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            if (! class_exists($class)) {
                continue;
            }
            $ref = new \ReflectionClass($class);
            if ($ref->isAbstract() || ! $ref->isSubclassOf(Model::class)) {
                continue;
            }
            $classes[] = $class;
        }
        sort($classes);

        return $classes;
    }

    /** @return list<string> */
    private function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $repository = $migrator->getRepository();
        if (! $repository->repositoryExists()) {
            return ['(migrations table missing — run php artisan migrate)'];
        }

        $files = $migrator->getMigrationFiles($migrator->paths() + [database_path('migrations')]);

        return array_values(array_diff(array_keys($files), $repository->getRan()));
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
