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
    public function saved(Company $company): void
    {
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

        $old = $slugChanged ? (string) ($company->getPrevious()['slug'] ?? '') : null;

        SyncCompanySubdomain::dispatchAfterResponse($slug, $old);
    }

    public function deleted(Company $company): void
    {
        if (! CpanelSubdomainService::enabled() || ! config('cpanel.delete_on_remove') || (string) $company->slug === '') {
            return;
        }

        SyncCompanySubdomain::dispatchAfterResponse((string) $company->slug, null, true);
    }
}
