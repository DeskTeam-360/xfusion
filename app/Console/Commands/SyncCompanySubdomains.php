<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\CpanelSubdomainService;
use Illuminate\Console\Command;

/**
 * Create the cPanel subdomains of companies that already exist (first run after turning the
 * automation on), or prove the cPanel credentials work.
 *
 *   php artisan companies:sync-subdomains --check      # credentials only
 *   php artisan companies:sync-subdomains --dry-run    # what it would create
 *   php artisan companies:sync-subdomains              # create, then start AutoSSL once
 *   php artisan companies:sync-subdomains --only=acme  # one company by custom URL
 */
class SyncCompanySubdomains extends Command
{
    protected $signature = 'companies:sync-subdomains {--check : Only test the cPanel connection} {--dry-run : List what would be created} {--only= : One company custom URL}';

    protected $description = 'Create company subdomains in cPanel and start AutoSSL';

    public function handle(CpanelSubdomainService $cpanel): int
    {
        if (! CpanelSubdomainService::enabled()) {
            $this->error('Not enabled: set CPANEL_AUTOMATION_ENABLED=true, CPANEL_HOST, CPANEL_USER, CPANEL_API_TOKEN, CPANEL_DOCUMENT_ROOT and WORDPRESS_TENANT_BASE_DOMAIN (see config/cpanel.php), then php artisan config:clear.');

            return self::FAILURE;
        }

        if ($this->option('check')) {
            $result = $cpanel->check();
            $result['ok'] ? $this->info('cPanel: '.$result['message']) : $this->error('cPanel: '.$result['message']);

            return $result['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $companies = Company::query()
            ->whereNotNull('slug')->where('slug', '!=', '')
            ->when($this->option('only'), fn ($q, $only) => $q->where('slug', $only))
            ->orderBy('id')->get(['id', 'title', 'slug']);

        if ($companies->isEmpty()) {
            $this->warn('No companies with a custom URL.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($companies as $company) {
            $host = CpanelSubdomainService::fqdn($company->slug);

            if ($this->option('dry-run')) {
                $this->line("would create  {$host}  (#{$company->id} {$company->title})");

                continue;
            }

            $result = $cpanel->provision($company->slug);
            if ($result['ok']) {
                $this->info("ok      {$host}  ({$result['message']})");
            } else {
                $failed++;
                $this->error("FAILED  {$host}  {$result['message']}");
            }
        }

        if (! $this->option('dry-run') && $failed < $companies->count()) {
            $ssl = $cpanel->startAutoSsl();
            $ssl['ok'] ? $this->info('AutoSSL: '.$ssl['message']) : $this->warn('AutoSSL: '.$ssl['message'].' (the daily run will still pick the names up)');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
