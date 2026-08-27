<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\Announcement;
use App\Models\Student;
use App\Models\StudentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentAuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        request()->session()->put('portal_parent', 'student');

        if (Auth::guard('moonshine')->check()) {
            $hasStudentAccess = StudentAccess::query()
                ->where('user_id', Auth::guard('moonshine')->id())
                ->activeForPortal()
                ->exists();

            if ($hasStudentAccess && (bool) (Auth::guard('moonshine')->user()?->must_change_password ?? false)) {
                return redirect()
                    ->route('student.password.form')
                    ->with('warning', 'Please change your password before continuing.');
            }

            if ($hasStudentAccess) {
                return redirect()->route('student.dashboard');
            }
        }

        $announcements = Announcement::query()
            ->whereIn('target_audience', ['students', 'both'])
            ->where(function ($query): void {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('portals.student.login', compact('announcements'));
    }

    public function login(Request $request): RedirectResponse
    {
        $request->session()->put('portal_parent', 'student');

        if (Auth::guard('moonshine')->check()) {
            $hasStudentAccess = StudentAccess::query()
                ->where('user_id', Auth::guard('moonshine')->id())
                ->activeForPortal()
                ->exists();

            if ($hasStudentAccess) {
                return redirect()->route('student.dashboard');
            }

            Auth::guard('moonshine')->logout();
        }

        $credentials = $request->validate([
            'lrn' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::query()
            ->active()
            ->where('lrn', $credentials['lrn'])
            ->with('user')
            ->first();

        if (! $student || ! $student->user || ! Hash::check($credentials['password'], $student->user->password)) {
            return back()->withErrors([
                'lrn' => 'Invalid student credentials.',
            ])->onlyInput('lrn');
        }

        Auth::guard('moonshine')->loginUsingId($student->user->id, $request->boolean('remember'));
        $request->session()->regenerate();

        $hasStudentAccess = StudentAccess::query()
            ->where('user_id', Auth::guard('moonshine')->id())
            ->activeForPortal()
            ->exists();

        if (! $hasStudentAccess) {
            Auth::guard('moonshine')->logout();

            return back()->withErrors([
                'lrn' => 'This account has no active student access.',
            ])->onlyInput('lrn');
        }

        if ((bool) ($student->user->must_change_password ?? false)) {
            return redirect()
                ->route('student.password.form')
                ->with('warning', 'Please change your password before continuing.');
        }

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('moonshine')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }

    public function showChangePasswordForm(): View
    {
        return view('portals.student.change-password');
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
            ->route('student.dashboard')
            ->with('status', 'Password updated successfully.');
    }
}

// TODO: allow teachers and students to change their password in settings page
// TODO: add a "remember me" checkbox to the login form and implement it in the login method
// TODO: add API to accept attendance records from sms_pwa.
/*
  // Convert local scans to format expected by PHP
  const payload = scansToSync.map(scan => ({
    student_id: scan.student_id,
    currentdate: scan.currentdate, // example: 2026-05-14
    time: scan.time                // example: 08:30:00
  }));
*/
// TODO: add logo to student and teacher portals
// TODO: show announcements in student and teacher login/dashboards
// TODO: add column to announcements for "target audience" (students, teachers, or both) and filter announcements accordingly in the portals
// TODO: add a "last login" column to the users table and update it on login, then show it in the student and teacher dashboards
