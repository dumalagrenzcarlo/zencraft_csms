<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePortalDomain
{
    /**
     * Prevent a specialized portal hostname from serving another portal's URLs.
     *
     * Root tenant domains continue to support the path-prefixed routes as a
     * local-development and custom-domain fallback.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hostPortal = $this->portalFromHost($request->getHost());
        $pathPortal = $this->portalFromPath($request->path());

        if ($hostPortal !== null && $pathPortal !== null && $hostPortal !== $pathPortal) {
            abort(404);
        }

        return $next($request);
    }

    private function portalFromHost(string $host): ?string
    {
        $firstLabel = strtolower(explode('.', $host)[0] ?? '');

        return in_array($firstLabel, ['admin', 'teacher', 'student'], true)
            ? $firstLabel
            : null;
    }

    private function portalFromPath(string $path): ?string
    {
        $firstSegment = strtolower(explode('/', trim($path, '/'))[0] ?? '');

        return in_array($firstSegment, ['admin', 'teacher', 'student'], true)
            ? $firstSegment
            : null;
    }
}
