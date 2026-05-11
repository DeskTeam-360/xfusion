<?php

namespace App\Http\Middleware;

use App\Support\CompanyAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCompanyAdmin
{
    /**
     * Hanya pengguna bertipe meta user_role “Company Admin” dengan meta company.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (
            !$user ||
            ! CompanyAdmin::isCompanyAdminPortalUser($user)
            || CompanyAdmin::portalCompanyMetaId($user) === null
        ) {
            abort(403);
        }

        return $next($request);
    }
}
