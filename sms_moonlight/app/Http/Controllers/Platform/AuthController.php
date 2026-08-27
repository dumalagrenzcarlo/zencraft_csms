<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\PlatformAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('platform.dashboard') : view('platform.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The supplied credentials are invalid.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        PlatformAuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'platform.login',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->intended(route('platform.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        PlatformAuditLog::create([
            'user_id' => $request->user()?->id,
            'event' => 'platform.logout',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
