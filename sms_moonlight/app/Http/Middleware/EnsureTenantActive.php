<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant === null && app()->environment('testing')) {
            return $next($request);
        }

        abort_unless($tenant?->isAvailable(), 423, 'This school workspace is not active.');

        return $next($request);
    }
}
