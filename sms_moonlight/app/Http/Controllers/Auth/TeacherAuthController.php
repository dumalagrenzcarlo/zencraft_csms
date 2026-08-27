<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\Adviser;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeacherAuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        request()->session()->put('portal_parent', 'teacher');

        if (Auth::guard('moonshine')->check()) {
            $isTeacher = Adviser::query()
                ->where('user_id', Auth::guard('moonshine')->id())
                ->whereIn('staff_type', [Adviser::TYPE_TEACHER, Adviser::TYPE_INSTRUCTOR])
                ->exists();

            if ($isTeacher && (bool) (Auth::guard('moonshine')->user()?->must_change_password ?? false)) {
                return redirect()
                    ->route('teacher.password.form')
                    ->with('warning', 'Please change your password before continuing.');
            }

            if ($isTeacher) {
                return redirect()->route('teacher.dashboard');
            }
        }

        $announcements = Announcement::query()
            ->forAudience('teachers')
            ->active()
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('portals.teacher.login', compact('announcements'));
    }

    public function login(Request $request): RedirectResponse
    {
        $request->session()->put('portal_parent', 'teacher');

        if (Auth::guard('moonshine')->check()) {
            $isTeacher = Adviser::query()
                ->where('user_id', Auth::guard('moonshine')->id())
                ->whereIn('staff_type', [Adviser::TYPE_TEACHER, Adviser::TYPE_INSTRUCTOR])
                ->exists();

            if ($isTeacher) {
                return redirect()->route('teacher.dashboard');
            }

            Auth::guard('moonshine')->logout();
        }

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $teacher = Adviser::query()
            ->with('user')
            ->whereIn('staff_type', [Adviser::TYPE_TEACHER, Adviser::TYPE_INSTRUCTOR])
            ->whereHas('user', fn ($query) => $query->where('username', $credentials['username']))
            ->first();

        if (! $teacher || ! $teacher->user || ! Hash::check($credentials['password'], $teacher->user->password)) {
            return back()->withErrors([
                'username' => 'Invalid teacher credentials.',
            ])->onlyInput('username');
        }

        Auth::guard('moonshine')->loginUsingId($teacher->user->id, $request->boolean('remember'));
        $request->session()->regenerate();

        $isTeacher = Adviser::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->whereIn('staff_type', [Adviser::TYPE_TEACHER, Adviser::TYPE_INSTRUCTOR])
            ->exists();

        if (! $isTeacher) {
            Auth::guard('moonshine')->logout();

            return back()->withErrors([
                'username' => 'This account is not assigned as a teacher.',
            ])->onlyInput('username');
        }

        if ((bool) ($teacher->user->must_change_password ?? false)) {
            return redirect()
                ->route('teacher.password.form')
                ->with('warning', 'Please change your password before continuing.');
        }

        return redirect()->route('teacher.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('moonshine')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login');
    }

    public function showChangePasswordForm(): View
    {
        return view('portals.teacher.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::guard('moonshine')->user();

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->must_change_password = false;
        $user->save();

        return redirect()
            ->route('teacher.dashboard')
            ->with('status', 'Password updated successfully.');
    }
}
