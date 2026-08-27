<?php

namespace App\Http\Middleware;

use App\Models\Adviser;
use Closure;
use Illuminate\Support\Facades\Auth;

class TeacherAuth
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('moonshine')->check()) {
            $request->session()->put('portal_parent', 'teacher');

            return redirect()->route('teacher.login');
        }

        $isTeacher = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->whereIn('staff_type', [Adviser::TYPE_TEACHER, Adviser::TYPE_INSTRUCTOR])
            ->exists();

        if (! $isTeacher) {
            Auth::guard('moonshine')->logout();

            $request->session()->put('portal_parent', 'teacher');

            return redirect()->route('teacher.login')->withErrors([
                'username' => 'This account is not assigned as a teacher.',
            ]);
        }

        return $next($request);
    }
}
