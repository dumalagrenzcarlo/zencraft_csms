<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectPortal
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        foreach (config('school_portal.domains') as $portal => $domain) {
            if (($domain && strcasecmp($host, $domain) === 0) || str_starts_with($host, $portal . '.')) {
                session(['portal_parent' => $portal]);

                break;
            }
        }

        return $next($request);
    }
}
