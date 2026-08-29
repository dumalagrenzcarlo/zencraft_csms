<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenantFromSlugPath
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isCentralDomain($request)) {
            return $next($request);
        }

        $segments = array_values(array_filter(explode('/', trim($request->path(), '/')), 'strlen'));
        $slug = strtolower($segments[0] ?? '');

        if ($slug === ''
            || preg_match('/^[a-z0-9_-]+$/', $slug) !== 1
            || in_array($slug, config('saas.reserved_tenant_slugs', []), true)) {
            return $next($request);
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if ($tenant === null) {
            return $next($request);
        }

        array_shift($segments);
        $tenantPath = '/'.implode('/', $segments);
        $tenantPath = $tenantPath === '/' ? '/' : rtrim($tenantPath, '/');

        $this->replaceRequestPath($request, $tenantPath);
        tenancy()->initialize($tenant);

        $origin = $request->getSchemeAndHttpHost();
        URL::forceRootUrl($origin.'/'.$slug);
        URL::useAssetOrigin($origin);

        try {
            return $next($request);
        } finally {
            URL::forceRootUrl(null);
            URL::useAssetOrigin(null);
        }
    }

    private function isCentralDomain(Request $request): bool
    {
        return in_array(
            strtolower($request->getHost()),
            array_map('strtolower', config('tenancy.central_domains', [])),
            true
        );
    }

    private function replaceRequestPath(Request $request, string $path): void
    {
        $server = $request->server->all();
        $queryString = http_build_query($request->query->all());

        $server['PATH_INFO'] = $path;
        $server['REQUEST_URI'] = $path.($queryString === '' ? '' : '?'.$queryString);

        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $server,
            $request->getContent()
        );
    }
}

