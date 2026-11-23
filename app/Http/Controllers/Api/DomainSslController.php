<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CpanelSubdomainService;
use App\Services\DomainOverviewService;

/**
 * Lets the WordPress admin show the same "these domains need a certificate" reminder as the
 * Laravel admin. WordPress cannot ask cPanel itself (the token lives only here).
 */
class DomainSslController extends Controller
{
    public function __invoke(DomainOverviewService $overview)
    {
        // Domain names are internal detail: only answer when an API token protects the route.
        if (blank(config('fusion_api.token'))) {
            return response()->json(['message' => 'Set FUSION_API_TOKEN to use this endpoint.'], 403);
        }

        if (! CpanelSubdomainService::enabled()) {
            return response()->json(['connected' => false, 'domains' => []]);
        }

        $pending = $overview->pendingSsl();

        return response()->json($pending + [
            'cpanel_url' => 'https://'.config('cpanel.host').':'.config('cpanel.port').'/',
        ]);
    }
}
