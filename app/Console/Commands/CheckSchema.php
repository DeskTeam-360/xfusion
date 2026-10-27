<?php

namespace App\Console\Commands;

use App\Services\SchemaChecker;
use Illuminate\Console\Command;

/**
 * Run on the target server after deploying, before going live:
 *   php artisan fusion:check-schema
 *
 * Read-only. Exit code 1 when any table, column or migration is missing.
 */
class CheckSchema extends Command
{
    protected $signature = 'fusion:check-schema {--ok : Also list the tables that are complete}';

    protected $description = 'Check that every model table/column and migration is present in the database';

    public function handle(SchemaChecker $checker): int
    {
        $result = $checker->run();
        $problems = 0;

        foreach ($result['tables'] as $t) {
            $hint = $t['sql_hint'] !== '' ? '  → '.$t['sql_hint'] : '';
            if ($t['status'] === 'missing_table') {
                $problems++;
                $this->error("MISSING TABLE   {$t['table']}{$hint}");
            } elseif ($t['status'] === 'missing_columns') {
                $problems++;
                $this->error("MISSING COLUMNS {$t['table']}: ".implode(', ', $t['missing']).$hint);
            } elseif ($this->option('ok')) {
                $this->info("ok              {$t['table']}");
            }
        }

        foreach ($result['pending_migrations'] as $name) {
            $problems++;
            $this->error("PENDING MIGRATION {$name}");
        }

        if ($problems === 0) {
            $this->info('Schema is complete.');

            return self::SUCCESS;
        }

        $this->error("{$problems} problem(s) found. Run the listed .sql files / php artisan migrate, then re-run this check.");

        return self::FAILURE;
    }
}
