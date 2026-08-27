<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsurePortalPasswordChanged
{
    public function handle(Request $request, Closure $next, string $portal)
    {
        $user = Auth::guard('moonshine')->user();

        if (! $user || ! (bool) ($user->must_change_password ?? false)) {
            return $next($request);
        }

        $formRoute = $portal . '.password.form';
        $updateRoute = $portal . '.password.update';
        $logoutRoute = $portal . '.logout';

        if ($request->routeIs($formRoute, $updateRoute, $logoutRoute)) {
            return $next($request);
        }

        return redirect()
            ->route($formRoute)
            ->with('warning', 'Please change your password before continuing.');
    }
}
