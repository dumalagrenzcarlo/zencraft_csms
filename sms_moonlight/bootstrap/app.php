<?php

use App\Http\Middleware\DetectPortal;
use App\Http\Middleware\EnforcePortalDomain;
use App\Http\Middleware\EnsureCentralDomain;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsurePlatformOwner;
use App\Http\Middleware\EnsurePlatformUser;
use App\Http\Middleware\EnsurePortalPasswordChanged;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\InitializeTenantFromSlugPath;
use App\Http\Middleware\MoonshineAuthenticate;
use App\Http\Middleware\StudentAuth;
use App\Http\Middleware\TeacherAuth;
use App\Http\Middleware\TenantRouteContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // This runs before Laravel matches a route, allowing /{school-slug}/...
        // to use the existing tenant route set on shared hosting.
        $middleware->prepend(InitializeTenantFromSlugPath::class);

        $middleware->redirectGuestsTo(function (Request $request): string {
            $host = $request->getHost();

            if ($request->is('platform', 'platform/*')) {
                return url('/platform/login');
            }

            if ($request->is('admin*') || $host === config('school_portal.domains.admin')) {
                return url('/admin');
            }

            if ($request->is('teacher*') || $host === config('school_portal.domains.teacher')) {
                return route('teacher.login');
            }

            if ($request->is('student*') || $host === config('school_portal.domains.student')) {
                return route('student.login');
            }

            return url('/');
        });

        $middleware->alias([
            'central.domain' => EnsureCentralDomain::class,
            'feature' => EnsureFeatureEnabled::class,
            'platform.user' => EnsurePlatformUser::class,
            'platform.owner' => EnsurePlatformOwner::class,
            'tenant.active' => EnsureTenantActive::class,
            'tenant.context' => TenantRouteContext::class,
            'portal.detect' => DetectPortal::class,
            'portal.domain' => EnforcePortalDomain::class,
            'moonshine.auth' => MoonshineAuthenticate::class,
            'student.auth' => StudentAuth::class,
            'teacher.auth' => TeacherAuth::class,
            'portal.password.changed' => EnsurePortalPasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if ($response->getStatusCode() !== 419 || $request->expectsJson()) {
                return $response;
            }

            $message = 'Your session expired. Please sign in again.';
            $host = $request->getHost();
            $portal = null;

            foreach (config('school_portal.domains', []) as $candidate => $domain) {
                if (($domain && strcasecmp($host, $domain) === 0) || str_starts_with($host, $candidate.'.')) {
                    $portal = $candidate;
                    break;
                }
            }

            if ($portal === null) {
                foreach (['teacher', 'student', 'admin'] as $candidate) {
                    if ($request->is($candidate, $candidate.'/*')) {
                        $portal = $candidate;
                        break;
                    }
                }
            }

            if ($portal === null && $request->hasSession()) {
                $portal = $request->session()->get('portal_parent');
            }

            if ($request->hasSession()) {
                Auth::guard('moonshine')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($portal === 'teacher') {
                return redirect()
                    ->route('teacher.login')
                    ->withErrors(['username' => $message]);
            }

            if ($portal === 'student') {
                return redirect()
                    ->route('student.login')
                    ->withErrors(['lrn' => $message]);
            }

            if ($portal === 'admin') {
                return redirect()
                    ->route('moonshine.login')
                    ->withErrors(['username' => $message]);
            }

            return redirect('/');
        });
    })->create();

