<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CpanelSubdomainService;
use App\Services\DomainOverviewService;
use App\Support\WpAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Admin page: which company subdomains are in use, missing or unused, with the two
 * actions that follow (create a missing one, remove an unused one).
 * WordPress administrators only.
 */
class DomainController extends Controller
{
    public function index(DomainOverviewService $overview)
    {
        $this->authorizeAdmin();

        return view('admin.domains.index', [
            'overview' => $overview->build(),
            'automation' => CpanelSubdomainService::enabled(),
            'canRemove' => CpanelSubdomainService::enabled() && (bool) config('cpanel.delete_on_remove'),
        ]);
    }

    /** Create the subdomain of a company that has a custom URL (idempotent) and start AutoSSL. */
    public function create(string $slug, CpanelSubdomainService $cpanel): RedirectResponse
    {
        $this->authorizeAdmin();

        if (! CpanelSubdomainService::enabled() || ! Company::query()->where('slug', $slug)->exists()) {
            return $this->back('error', 'Nothing to create: cPanel is not connected or no company has that custom URL.');
        }

        $result = $cpanel->provision($slug);
        if (! $result['ok']) {
            return $this->back('error', 'Could not create '.CpanelSubdomainService::fqdn($slug).': '.$result['message']);
        }

        $cpanel->startAutoSsl();
        DomainOverviewService::forgetSslPending();

        return $this->back('success', CpanelSubdomainService::fqdn($slug).' is ready. The certificate follows after AutoSSL runs.');
    }

    /** Remove a subdomain no company owns. Only when removal is switched on (CPANEL_DELETE_ON_REMOVE). */
    public function remove(string $slug, CpanelSubdomainService $cpanel): RedirectResponse
    {
        $this->authorizeAdmin();

        if (! CpanelSubdomainService::enabled() || ! config('cpanel.delete_on_remove')) {
            return $this->back('error', 'Removing subdomains is switched off (CPANEL_DELETE_ON_REMOVE).');
        }
        if (Company::query()->where('slug', $slug)->exists()) {
            return $this->back('error', 'A company still owns that custom URL; change or clear it first.');
        }

        $result = $cpanel->remove($slug);
        DomainOverviewService::forgetSslPending();

        return $result['ok']
            ? $this->back('success', CpanelSubdomainService::fqdn($slug).' was removed.')
            : $this->back('error', 'Could not remove it: '.$result['message']);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(WpAdmin::isAdministrator((int) Auth::id()), 403);
    }

    private function back(string $icon, string $title): RedirectResponse
    {
        return redirect()->route('domains.index')->with('swal', ['icon' => $icon, 'title' => $title, 'timeout' => 6000]);
    }
}
