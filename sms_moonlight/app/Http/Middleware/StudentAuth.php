<?php

namespace App\Http\Middleware;

use App\Models\StudentAccess;
use Closure;
use Illuminate\Support\Facades\Auth;

class StudentAuth
{
    public function handle($request, Closure $next)
    {
        if (! Auth::guard('moonshine')->check()) {
            $request->session()->put('portal_parent', 'student');

            return redirect()->route('student.login');
        }

        $hasStudentAccess = StudentAccess::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->activeForPortal()
            ->exists();

        if (! $hasStudentAccess) {
            Auth::guard('moonshine')->logout();

            $request->session()->put('portal_parent', 'student');

            return redirect()->route('student.login')->withErrors([
                'lrn' => 'This account has no active student access.',
            ]);
        }

        return $next($request);
    }
}
