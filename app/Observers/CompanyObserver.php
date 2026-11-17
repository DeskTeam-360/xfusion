<?php

namespace App\Observers;

use App\Jobs\SyncCompanySubdomain;
use App\Models\Company;
use App\Services\CpanelSubdomainService;

/**
 * The trigger for cPanel automation: a company saved with a new custom URL (slug) needs
 * its subdomain. Does nothing unless config/cpanel.php is switched on and complete.
 */
class CompanyObserver
{
    /**
     * Custom URL a company had before this save, by model instance. Eloquent forgets the old
     * value once the save finishes, so it is taken in updating() and read in saved().
     *
     * @var array<int, string>
     */
    private array $previousSlugs = [];

    public function updating(Company $company): void
    {
        $this->previousSlugs[spl_object_id($company)] = (string) $company->getOriginal('slug');
    }

    public function saved(Company $company): void
    {
        // Always forget the remembered value, whatever happens next.
        $key = spl_object_id($company);
        $previous = $this->previousSlugs[$key] ?? '';
        unset($this->previousSlugs[$key]);

        // This is a side effect of saving a company: it must never be able to break the save.
        try {
            if (! CpanelSubdomainService::enabled()) {
                return;
            }

            $slugChanged = $company->wasChanged('slug');
            if (! $company->wasRecentlyCreated && ! $slugChanged) {
                return;
            }

            $slug = (string) $company->slug;
            if ($slug === '') {
                return;
            }

            SyncCompanySubdomain::dispatchAfterResponse($slug, $slugChanged ? $previous : null);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function deleted(Company $company): void
    {
        try {
            if (! CpanelSubdomainService::enabled() || ! config('cpanel.delete_on_remove') || (string) $company->slug === '') {
                return;
            }

            SyncCompanySubdomain::dispatchAfterResponse((string) $company->slug, null, true);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
