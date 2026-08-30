<?php

namespace App\MoonShine\AuthPipelines;

use App\Models\MoonshineUser;
use Closure;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Http\Requests\LoginFormRequest;
use MoonShine\Laravel\Models\MoonshineUserRole;

class RedirectIntendedAfterLogin
{
    public function handle(LoginFormRequest $request, Closure $next): mixed
    {
        $usernameField = moonshineConfig()->getUserField('username', 'email');
        $username = $request->string('username')->squish()->value();

        $admin = MoonshineUser::query()
            ->where($usernameField, $username)
            ->where('moonshine_user_role_id', MoonshineUserRole::DEFAULT_ROLE_ID)
            ->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'username' => __('moonshine::auth.failed'),
            ]);
        }

        if (tenant('signup_requires_email_verification')
            && hash_equals((string) tenant('signup_admin_email'), strtolower((string) $admin->email))) {
            throw ValidationException::withMessages([
                'username' => 'Verify your email address before signing in.',
            ]);
        }

        return $next($request);
    }
}
