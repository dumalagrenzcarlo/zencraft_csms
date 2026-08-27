<?php

namespace App\Http\Middleware;

use App\Support\PaymentAccess;
use Closure;
use Illuminate\Http\Request;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Symfony\Component\HttpFoundation\Response;

class MoonshineAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = MoonShineAuth::getGuard();
        $user = $guard->user();

        if (! $user) {
            return redirect()->guest(route('moonshine.login'));
        }

        if ((int) $user->moonshine_user_role_id !== MoonshineUserRole::DEFAULT_ROLE_ID) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('moonshine.login')
                ->withErrors(['username' => __('moonshine::auth.failed')]);
        }

        if ($user && PaymentAccess::isPaymentRequest($request) && ! PaymentAccess::canAccess($request, $user)) {
            $intendedUrl = $request->isMethod('GET')
                ? $request->fullUrl()
                : route('moonshine.crud.index', ['resourceUri' => 'student-payment-history-resource']);

            $request->session()->put('payments.intended_url', $intendedUrl);

            return redirect()->route('admin.payments.authorization');
        }

        return $next($request);
    }
}
