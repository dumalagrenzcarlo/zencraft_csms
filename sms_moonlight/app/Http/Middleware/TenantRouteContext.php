<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

class TenantRouteContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $isCentralDomain = in_array(
            strtolower($request->getHost()),
            array_map('strtolower', config('tenancy.central_domains', [])),
            true
        );

        if ($isCentralDomain) {
            // The tenant route set also defines "/". Because tenant routes are
            // registered after central routes, explicitly send the central
            // domain root to the platform portal instead of returning 404.
            if ($request->path() === '/') {
                return redirect('/platform');
            }

            // Legacy SMS Moonlight tests run against a disposable central schema.
            // Production central domains must never expose other tenant routes.
            abort_unless(app()->environment('testing'), 404);

            return $next($request);
        }

        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}
