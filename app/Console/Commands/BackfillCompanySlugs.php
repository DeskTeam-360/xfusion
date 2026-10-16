<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class BackfillCompanySlugs extends Command
{
    protected $signature = 'companies:backfill-slugs';

    protected $description = 'Fill an empty slug on every company from its name (existing slugs are left alone)';

    public function handle(): int
    {
        $count = 0;
        Company::query()->whereNull('slug')->orWhere('slug', '')->get()->each(function (Company $company) use (&$count) {
            $company->slug = Company::uniqueSlug((string) $company->title, $company->id);
            $company->save();
            $count++;
            $this->line("#{$company->id} {$company->title} → {$company->slug}");
        });

        $this->info("Done: {$count} compan".($count === 1 ? 'y' : 'ies').' updated.');

        return self::SUCCESS;
    }
}
