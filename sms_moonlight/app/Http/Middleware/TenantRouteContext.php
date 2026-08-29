<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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
            $slug = strtolower(trim((string) $request->query('_tenant_slug')));

            if ($slug !== '') {
                abort_unless(preg_match('/^[a-z0-9_-]+$/', $slug) === 1, 404);

                $request->query->remove('_tenant_slug');

                $tenant = Tenant::query()->where('slug', $slug)->first();
                abort_unless($tenant !== null, 404);

                tenancy()->initialize($tenant);

                // All route(), url(), form and MoonShine URLs generated during
                // this request must stay inside the tenant's slug path.
                URL::forceRootUrl($request->getSchemeAndHttpHost().'/'.$slug);

                try {
                    return $next($request);
                } finally {
                    URL::forceRootUrl(null);
                }
            }

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
