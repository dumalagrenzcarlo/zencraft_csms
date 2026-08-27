<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->active || ! in_array($request->user()->role, ['owner', 'support'], true)) {
            abort(403);
        }

        return $next($request);
    }
}
