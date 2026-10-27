<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Run on the target server after deploying, before going live:
 *   php artisan fusion:check-schema
 *
 * Compares the database with what the code expects: every Eloquent model's
 * table must exist with all its $fillable columns, and no migration may be
 * pending. Read-only — it never changes the database. Exit code 1 when
 * anything is missing.
 */
class CheckSchema extends Command
{
    protected $signature = 'fusion:check-schema {--ok : Also list the tables that are complete}';

    protected $description = 'Check that every model table/column and migration is present in the database';

    public function handle(): int
    {
        $problems = 0;

        $this->line('<options=bold>Model tables and columns</>');
        foreach ($this->models() as $class) {
            /** @var Model $model */
            $model = new $class;
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                $problems++;
                $this->error("MISSING TABLE  {$table}   ({$class})".$this->sqlHint($table));

                continue;
            }

            $have = array_map('strtolower', Schema::getColumnListing($table));
            $missing = array_values(array_filter(
                $model->getFillable(),
                fn (string $c) => ! in_array(strtolower($c), $have, true)
            ));

            if ($missing) {
                $problems++;
                $this->error("MISSING COLUMNS {$table}: ".implode(', ', $missing)."   ({$class})".$this->sqlHint($table));
            } elseif ($this->option('ok')) {
                $this->info("ok             {$table}");
            }
        }

        $this->newLine();
        $this->line('<options=bold>Migrations</>');
        $pending = $this->pendingMigrations();
        if ($pending) {
            $problems += count($pending);
            foreach ($pending as $name) {
                $this->error("PENDING MIGRATION  {$name}");
            }
        } else {
            $this->info('No pending migrations.');
        }

        $this->newLine();
        if ($problems === 0) {
            $this->info('Schema is complete.');

            return self::SUCCESS;
        }

        $this->error("{$problems} problem(s) found. Run the listed .sql files / php artisan migrate, then re-run this check.");

        return self::FAILURE;
    }

    /** @return list<class-string<Model>> */
    private function models(): array
    {
        $classes = [];
        foreach (File::allFiles(app_path('Models')) as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = 'App\\Models\\'.$relative;
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
        $ran = $repository->getRan();

        return array_values(array_diff(array_keys($files), $ran));
    }

    /** Point at the hand-run SQL file that creates the table, if there is one. */
    private function sqlHint(string $table): string
    {
        $dir = database_path('sql');
        if (! is_dir($dir)) {
            return '';
        }
        foreach (File::files($dir) as $file) {
            if (stripos(File::get($file->getPathname()), $table) !== false) {
                return '  → database/sql/'.$file->getFilename();
            }
        }

        return '';
    }
}
