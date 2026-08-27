<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Support\PaymentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PaymentAuthorizationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user('moonshine');

        if (PaymentAccess::isAuthorizedAdmin($user) || PaymentAccess::isSessionUnlocked($request)) {
            return redirect()->to($this->intendedUrl($request));
        }

        return view('admin.payments.authorize', [
            'authorizedUsername' => PaymentAccess::authorizedUsername(),
        ]);
    }

    public function authorize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $rateLimitKey = 'payment-authorization:'.($request->user('moonshine')?->id ?? 'guest').'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()
                ->withErrors(['password' => 'Too many attempts. Please try again later.']);
        }

        $authorizedUser = PaymentAccess::authorizedUser();

        if (! $authorizedUser || ! Hash::check($data['password'], $authorizedUser->password)) {
            RateLimiter::hit($rateLimitKey, 60);

            return back()
                ->withErrors(['password' => 'The payment administrator password is incorrect.']);
        }

        RateLimiter::clear($rateLimitKey);
        $request->session()->regenerate();
        PaymentAccess::unlock($request, $authorizedUser);

        return redirect()->to($this->intendedUrl($request));
    }

    private function intendedUrl(Request $request): string
    {
        $default = route('moonshine.crud.index', ['resourceUri' => 'student-payment-history-resource']);
        $intended = (string) $request->session()->pull(
            'payments.intended_url',
            $default
        );

        $host = parse_url($intended, PHP_URL_HOST);
        $scheme = parse_url($intended, PHP_URL_SCHEME);

        if ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return $default;
        }

        return $host === null || strcasecmp((string) $host, $request->getHost()) === 0
            ? $intended
            : $default;
    }
}
