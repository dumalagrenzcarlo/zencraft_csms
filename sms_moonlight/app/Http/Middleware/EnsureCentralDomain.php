<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $domains = array_map('strtolower', config('tenancy.central_domains', []));

        abort_unless(in_array(strtolower($request->getHost()), $domains, true), 404);

        return $next($request);
    }
}
