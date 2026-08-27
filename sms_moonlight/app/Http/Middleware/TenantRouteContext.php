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
            // Legacy SMS Moonlight tests run against a disposable central schema.
            // Production central domains must never expose tenant routes.
            abort_unless(app()->environment('testing'), 404);

            return $next($request);
        }

        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}
