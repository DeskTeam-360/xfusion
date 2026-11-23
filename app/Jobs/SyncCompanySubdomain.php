<?php

namespace App\Jobs;

use App\Services\CpanelSubdomainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Create a company's subdomain in cPanel (and start AutoSSL), and optionally remove the
 * old one after a rename. Dispatch with dispatchAfterResponse(): it runs once the HTTP
 * response has been sent, so saving a company never waits on cPanel and no queue worker
 * is required.
 */
class SyncCompanySubdomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public string $slug,
        public ?string $oldSlug = null,
        public bool $removeOnly = false,
    ) {}

    public function handle(CpanelSubdomainService $cpanel): void
    {
        if (! CpanelSubdomainService::enabled()) {
            return;
        }

        if ($this->removeOnly) {
            if (config('cpanel.delete_on_remove')) {
                $cpanel->remove($this->slug);
            }

            return;
        }

        if ($cpanel->provision($this->slug)['ok']) {
            $cpanel->startAutoSsl();
        }
        \App\Services\DomainOverviewService::forgetSslPending();

        if ($this->oldSlug !== null && $this->oldSlug !== '' && $this->oldSlug !== $this->slug && config('cpanel.delete_on_remove')) {
            $cpanel->remove($this->oldSlug);
        }
    }
}
