<?php

namespace App\Http\Middleware;

use App\Support\WpAdmin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * For maintenance URLs that change data for many people (refresh every user's progress, export
 * stored passwords to Keap). They used to answer anyone on the internet. Now: a signed-in
 * WordPress administrator, or a caller that sends the shared CRON_TOKEN (header X-Cron-Token or
 * ?token=) for scheduled jobs. With CRON_TOKEN unset, only administrators get in.
 */
final class EnsureAdminOrCronToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && WpAdmin::isAdministrator((int) Auth::id())) {
            return $next($request);
        }

        $expected = (string) config('fusion_api.cron_token', '');
        $given = (string) ($request->header('X-Cron-Token') ?: $request->query('token', ''));
        if ($expected !== '' && $given !== '' && hash_equals($expected, $given)) {
            return $next($request);
        }

        abort(403);
    }
}
