<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

/**
 * Run before switching the company-subdomain model on:
 *   php artisan companies:check-slugs
 *
 * Read-only. Lists every company whose custom URL cannot be used as a web
 * address (<url>.domain): empty, longer than 63 characters, not lowercase
 * letters/digits/inner dashes, reserved (www, admin, ...), or used twice.
 * Exit code 1 when something needs fixing (edit the company, or leave the URL
 * empty and run companies:backfill-slugs).
 */
class CheckCompanySlugs extends Command
{
    protected $signature = 'companies:check-slugs';

    protected $description = 'List companies whose custom URL cannot be used as a subdomain';

    public function handle(): int
    {
        $problems = 0;
        $seen = [];

        foreach (Company::query()->orderBy('id')->get(['id', 'title', 'slug']) as $company) {
            $slug = (string) $company->slug;
            $issues = [];

            if ($slug === '') {
                $issues[] = 'empty';
            } else {
                if (strlen($slug) > Company::SLUG_MAX_LENGTH) {
                    $issues[] = 'longer than '.Company::SLUG_MAX_LENGTH.' characters';
                }
                if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                    $issues[] = 'must be lowercase letters, digits and single dashes';
                }
                if (in_array(strtolower($slug), Company::RESERVED_SLUGS, true)) {
                    $issues[] = 'reserved name';
                }
                if (isset($seen[strtolower($slug)])) {
                    $issues[] = 'also used by company #'.$seen[strtolower($slug)];
                } else {
                    $seen[strtolower($slug)] = $company->id;
                }
            }

            if ($issues !== []) {
                $problems++;
                $this->error(sprintf('#%d %s  [%s]  %s', $company->id, $company->title, $slug, implode('; ', $issues)));
            }
        }

        if ($problems === 0) {
            $this->info('Every company has a custom URL that works as a subdomain.');

            return self::SUCCESS;
        }

        $this->error("{$problems} compan".($problems === 1 ? 'y needs' : 'ies need').' fixing before the subdomain model is switched on.');

        return self::FAILURE;
    }
}
