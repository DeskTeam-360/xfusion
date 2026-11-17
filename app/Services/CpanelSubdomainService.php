<?php

namespace App\Services;

use App\Models\Company;
use App\Support\WordpressPublicUrl;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Creates/removes company subdomains in cPanel and starts AutoSSL.
 * Configuration: config/cpanel.php. Every method returns ['ok' => bool, 'message' => string]
 * and never throws, so a cPanel problem cannot break saving a company.
 */
class CpanelSubdomainService
{
    /** Automation is on and every setting it needs is present. */
    public static function enabled(): bool
    {
        return (bool) config('cpanel.enabled')
            && filled(config('cpanel.host'))
            && filled(config('cpanel.user'))
            && filled(config('cpanel.token'))
            && filled(config('cpanel.document_root'))
            && WordpressPublicUrl::tenantBase() !== null;
    }

    /**
     * Names of the settings that stop the automation from being on (values are never shown).
     *
     * @return list<string>
     */
    public static function missingSettings(): array
    {
        $missing = [];
        if (! config('cpanel.enabled')) {
            $missing[] = 'CPANEL_AUTOMATION_ENABLED=true';
        }
        foreach (['host' => 'CPANEL_HOST', 'user' => 'CPANEL_USER', 'token' => 'CPANEL_API_TOKEN', 'document_root' => 'CPANEL_DOCUMENT_ROOT'] as $key => $env) {
            if (! filled(config('cpanel.'.$key))) {
                $missing[] = $env;
            }
        }
        if (WordpressPublicUrl::tenantBase() === null) {
            $missing[] = 'WORDPRESS_TENANT_BASE_DOMAIN';
        }

        return $missing;
    }

    /**
     * The "domain" value cPanel wants for a company: the label plus whatever sits between the
     * label and the root domain. Base sandbox.xperiencefusion.com, root xperiencefusion.com,
     * slug "acme" => "acme.sandbox" (cPanel appends the root domain). Null when the slug
     * cannot be a subdomain or the base does not end with the root domain.
     */
    public static function subdomainParam(string $slug, string $tenantBase, string $rootDomain): ?string
    {
        $slug = strtolower($slug);
        $tenantBase = strtolower(trim($tenantBase, '.'));
        $rootDomain = strtolower(trim($rootDomain, '.'));

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug) || in_array($slug, Company::RESERVED_SLUGS, true)) {
            return null;
        }

        if ($tenantBase === $rootDomain) {
            return $slug;
        }

        $suffix = '.'.$rootDomain;
        if (! str_ends_with($tenantBase, $suffix)) {
            return null;
        }

        return $slug.'.'.substr($tenantBase, 0, -strlen($suffix));
    }

    /** Full host name of a company's subdomain, e.g. acme.sandbox.xperiencefusion.com. */
    public static function fqdn(string $slug): string
    {
        return strtolower($slug).'.'.WordpressPublicUrl::tenantBase();
    }

    /** Add the subdomain (already existing counts as success). */
    public function provision(string $slug): array
    {
        $param = self::subdomainParam($slug, (string) WordpressPublicUrl::tenantBase(), (string) config('cpanel.root_domain'));
        if ($param === null) {
            return $this->fail("'{$slug}' cannot be used as a subdomain of ".WordpressPublicUrl::tenantBase().'.');
        }

        $result = $this->api2('SubDomain', 'addsubdomain', [
            'domain' => $param,
            'rootdomain' => (string) config('cpanel.root_domain'),
            'dir' => (string) config('cpanel.document_root'),
            'disallowdot' => 0, // the label may sit under another label (acme.sandbox)
        ]);

        if (! $result['ok'] && preg_match('/already (exists|in use)|is already/i', $result['message'])) {
            return ['ok' => true, 'message' => 'already exists'];
        }

        $this->log($result['ok'] ? 'info' : 'warning', 'cPanel add subdomain', self::fqdn($slug), $result['message']);

        return $result;
    }

    /** Remove the subdomain. Only used when config('cpanel.delete_on_remove') is on. */
    public function remove(string $slug): array
    {
        $result = $this->api2('SubDomain', 'delsubdomain', ['domain' => self::fqdn($slug)]);
        $this->log($result['ok'] ? 'info' : 'warning', 'cPanel remove subdomain', self::fqdn($slug), $result['message']);

        return $result;
    }

    /** Ask cPanel to issue certificates for names that do not have one yet. */
    public function startAutoSsl(): array
    {
        $mode = (string) config('cpanel.autossl', 'uapi');

        if ($mode === 'none') {
            return ['ok' => true, 'message' => 'AutoSSL left to the daily run'];
        }

        try {
            if ($mode === 'whm') {
                if (! filled(config('cpanel.whm_host')) || ! filled(config('cpanel.whm_token'))) {
                    return $this->fail('WHM mode needs WHM_HOST and WHM_API_TOKEN.');
                }
                $response = $this->http('whm '.config('cpanel.whm_user').':'.config('cpanel.whm_token'))
                    ->get('https://'.config('cpanel.whm_host').':'.config('cpanel.whm_port').'/json-api/start_autossl_check_for_one_user', [
                        'api.version' => 1,
                        'username' => config('cpanel.user'),
                    ]);
                $ok = $response->successful() && (int) data_get($response->json(), 'metadata.result', 0) === 1;
                $message = (string) data_get($response->json(), 'metadata.reason', 'HTTP '.$response->status());
            } else {
                $response = $this->http()->get($this->base().'/execute/SSL/start_autossl_check');
                $ok = $response->successful() && (int) data_get($response->json(), 'status', 0) === 1;
                $message = (string) (collect((array) data_get($response->json(), 'errors', []))->first() ?: 'HTTP '.$response->status());
            }
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        $this->log($ok ? 'info' : 'warning', 'cPanel AutoSSL check ('.$mode.')', '-', $ok ? 'started' : $message);

        return ['ok' => $ok, 'message' => $ok ? 'started' : $message];
    }

    /** Harmless call to prove the host, user and token work. */
    public function check(): array
    {
        try {
            $response = $this->http()->get($this->base().'/execute/Variables/get_user_information');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        $ok = $response->successful() && (int) data_get($response->json(), 'status', 0) === 1;

        return [
            'ok' => $ok,
            'message' => $ok
                ? 'connected as '.data_get($response->json(), 'data.user', config('cpanel.user'))
                : (string) (collect((array) data_get($response->json(), 'errors', []))->first() ?: 'HTTP '.$response->status()),
        ];
    }

    /**
     * Every subdomain that exists in the cPanel account.
     *
     * @return array{ok: bool, message: string, hosts: array<string, string>} host => document root
     */
    public function listSubdomains(): array
    {
        try {
            $response = $this->http()->get($this->base().'/json-api/cpanel', [
                'cpanel_jsonapi_user' => config('cpanel.user'),
                'cpanel_jsonapi_apiversion' => 2,
                'cpanel_jsonapi_module' => 'SubDomain',
                'cpanel_jsonapi_func' => 'listsubdomains',
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage()) + ['hosts' => []];
        }

        $json = $response->json();
        $error = data_get($json, 'cpanelresult.error');
        if (! $response->successful() || filled($error)) {
            return ['ok' => false, 'message' => (string) ($error ?: 'HTTP '.$response->status()), 'hosts' => []];
        }

        $hosts = [];
        foreach ((array) data_get($json, 'cpanelresult.data', []) as $row) {
            $host = strtolower((string) data_get($row, 'domain', ''));
            if ($host === '' && filled(data_get($row, 'subdomain')) && filled(data_get($row, 'rootdomain'))) {
                $host = strtolower(data_get($row, 'subdomain').'.'.data_get($row, 'rootdomain'));
            }
            if ($host !== '') {
                $hosts[$host] = (string) (data_get($row, 'basedir') ?: data_get($row, 'dir', ''));
            }
        }

        return ['ok' => true, 'message' => 'ok', 'hosts' => $hosts];
    }

    /**
     * Certificates installed on the account, by host. Best effort: the shape of this answer
     * differs between cPanel versions, so anything unexpected simply yields no entry.
     *
     * @return array<string, array{expires: ?int, self_signed: bool}>
     */
    public function sslHosts(): array
    {
        try {
            $response = $this->http()->get($this->base().'/execute/SSL/installed_hosts');
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful() || (int) data_get($response->json(), 'status', 0) !== 1) {
            return [];
        }

        $map = [];
        foreach ((array) data_get($response->json(), 'data', []) as $item) {
            $expires = data_get($item, 'certificate.not_after');
            $entry = [
                'expires' => is_numeric($expires) ? (int) $expires : null,
                'self_signed' => (bool) data_get($item, 'certificate.is_self_signed', false),
            ];
            $names = (array) data_get($item, 'domains', []);
            if ($names === [] && filled(data_get($item, 'servername'))) {
                $names = [data_get($item, 'servername')];
            }
            foreach ($names as $name) {
                $map[strtolower((string) $name)] = $entry;
            }
        }

        return $map;
    }

    // ---- internals ----

    private function base(): string
    {
        return 'https://'.config('cpanel.host').':'.config('cpanel.port');
    }

    private function http(?string $authorization = null): PendingRequest
    {
        return Http::withHeaders(['Authorization' => $authorization ?? 'cpanel '.config('cpanel.user').':'.config('cpanel.token')])
            ->timeout((int) config('cpanel.timeout', 25))
            ->withOptions(['verify' => (bool) config('cpanel.verify_ssl', true)]);
    }

    /** cPanel API 2 (still supported; it is the long-standing way to manage subdomains). */
    private function api2(string $module, string $function, array $params): array
    {
        try {
            $response = $this->http()->get($this->base().'/json-api/cpanel', array_merge([
                'cpanel_jsonapi_user' => config('cpanel.user'),
                'cpanel_jsonapi_apiversion' => 2,
                'cpanel_jsonapi_module' => $module,
                'cpanel_jsonapi_func' => $function,
            ], $params));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        $json = $response->json();
        $row = data_get($json, 'cpanelresult.data.0', []);
        $ok = $response->successful() && (int) data_get($row, 'result', 0) === 1;
        $message = (string) (data_get($row, 'reason') ?: data_get($json, 'cpanelresult.error') ?: 'HTTP '.$response->status());

        return ['ok' => $ok, 'message' => $ok ? 'ok' : $message];
    }

    private function fail(string $message): array
    {
        Log::warning('cPanel automation failed: '.$message);

        return ['ok' => false, 'message' => $message];
    }

    private function log(string $level, string $what, string $host, string $message): void
    {
        Log::{$level}("{$what}: {$host} - {$message}");
    }
}
