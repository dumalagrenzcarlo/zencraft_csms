<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFeatureEnabled
{
    private const FEATURES = [
        'college_module',
        'payments_module',
        'quiz_module',
        'staff_module',
        'teacher_staff_attendance',
    ];

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(
            in_array($feature, self::FEATURES, true)
            && filter_var(config("school_portal.features.{$feature}", false), FILTER_VALIDATE_BOOLEAN),
            404
        );

        return $next($request);
    }
}
