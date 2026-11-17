<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Support\WordpressPublicUrl;

/**
 * Which company subdomains are in use and which are not: the companies' custom URLs
 * against the subdomains that really exist in cPanel.
 *
 *   in_use  — company has a custom URL and its subdomain exists in cPanel
 *   missing — company has a custom URL but no subdomain exists yet (can be created)
 *   unused  — a subdomain exists under the base domain but no company owns that name
 *   system  — a subdomain that is not a company one (a reserved name such as admin, or one that
 *             points somewhere other than the WordPress folder); shown, never counted as unused
 *   unknown — cPanel is not connected, so existence cannot be told
 */
class DomainOverviewService
{
    public function __construct(private readonly CpanelSubdomainService $cpanel) {}

    /**
     * @return array{
     *   base: ?string,
     *   connected: bool,
     *   error: ?string,
     *   rows: list<array<string, mixed>>,
     *   counts: array{in_use: int, missing: int, unused: int, unknown: int}
     * }
     */
    public function build(): array
    {
        $base = WordpressPublicUrl::tenantBase();
        $result = ['base' => $base, 'connected' => false, 'error' => null, 'rows' => [], 'counts' => ['in_use' => 0, 'missing' => 0, 'unused' => 0, 'unknown' => 0, 'system' => 0]];

        if ($base === null) {
            $result['error'] = 'The subdomain model is off: set WORDPRESS_TENANT_BASE_DOMAIN in .env.';

            return $result;
        }

        $companies = Company::query()
            ->whereNotNull('slug')->where('slug', '!=', '')
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        // Counted with the employee model's own query: Company and CompanyEmployee sit on different
        // connections (different table prefixes), which withCount() cannot join.
        $employees = CompanyEmployee::query()
            ->selectRaw('company_id, COUNT(*) AS total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $hosts = [];
        $ssl = [];
        if (CpanelSubdomainService::enabled()) {
            $list = $this->cpanel->listSubdomains();
            if ($list['ok']) {
                $result['connected'] = true;
                $hosts = $list['hosts'];
                $ssl = $this->cpanel->sslHosts();
            } else {
                $result['error'] = 'cPanel: '.$list['message'];
            }
        }

        $owned = [];
        foreach ($companies as $company) {
            $fqdn = CpanelSubdomainService::fqdn($company->slug);
            $owned[$fqdn] = true;

            $status = ! $result['connected'] ? 'unknown' : (isset($hosts[$fqdn]) ? 'in_use' : 'missing');
            $result['rows'][] = $this->row($status, $fqdn, $company, (int) ($employees[$company->id] ?? 0), $hosts[$fqdn] ?? null, $ssl[$fqdn] ?? null);
            $result['counts'][$status]++;
        }

        if ($result['connected']) {
            $suffix = '.'.$base;
            foreach ($hosts as $fqdn => $dir) {
                // Only names shaped like a company subdomain (one label under the base domain).
                if (isset($owned[$fqdn]) || ! str_ends_with($fqdn, $suffix) || str_contains(substr($fqdn, 0, -strlen($suffix)), '.')) {
                    continue;
                }
                $label = substr($fqdn, 0, -strlen($suffix));
                $status = (in_array($label, Company::RESERVED_SLUGS, true) || ! $this->pointsToWordpress($dir)) ? 'system' : 'unused';
                $result['rows'][] = $this->row($status, $fqdn, null, null, $dir, $ssl[$fqdn] ?? null);
                $result['counts'][$status]++;
            }
        }

        return $result;
    }

    /** Does this document root belong to the WordPress site every company subdomain shares? */
    private function pointsToWordpress(?string $dir): bool
    {
        $wanted = trim((string) config('cpanel.document_root'), '/');
        $dir = trim((string) $dir, '/');

        return $wanted !== '' && ($dir === $wanted || str_ends_with($dir, '/'.$wanted));
    }

    /** @param array{expires: ?int, self_signed: bool}|null $ssl */
    private function row(string $status, string $fqdn, ?Company $company, ?int $employees, ?string $dir, ?array $ssl): array
    {
        $sslState = null;
        $expires = null;
        if ($ssl !== null) {
            $expires = $ssl['expires'] ? date('Y-m-d', $ssl['expires']) : null;
            $sslState = $ssl['self_signed'] ? 'self-signed'
                : (($ssl['expires'] && $ssl['expires'] < time()) ? 'expired'
                    : (($ssl['expires'] && $ssl['expires'] < time() + 14 * 86400) ? 'expiring' : 'valid'));
        } elseif (in_array($status, ['in_use', 'unused'], true)) {
            $sslState = 'none';
        }

        return [
            'status' => $status,
            'fqdn' => $fqdn,
            'slug' => $company?->slug ?? substr($fqdn, 0, (int) strpos($fqdn, '.')),
            'url' => WordpressPublicUrl::tenantUrl($company?->slug ?? substr($fqdn, 0, (int) strpos($fqdn, '.'))),
            'company_id' => $company?->id,
            'company' => $company?->title,
            'employees' => $employees,
            'dir' => $dir,
            'ssl' => $sslState,
            'ssl_expires' => $expires,
        ];
    }
}
