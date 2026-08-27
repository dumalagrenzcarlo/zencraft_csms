<?php

namespace App\MoonShine\AuthPipelines;

use Closure;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Http\Requests\LoginFormRequest;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;

class RedirectIntendedAfterLogin
{
    public function handle(LoginFormRequest $request, Closure $next): mixed
    {
        $usernameField = moonshineConfig()->getUserField('username', 'email');
        $username = $request->string('username')->squish()->value();

        $isAdmin = MoonshineUser::query()
            ->where($usernameField, $username)
            ->where('moonshine_user_role_id', MoonshineUserRole::DEFAULT_ROLE_ID)
            ->exists();

        if (! $isAdmin) {
            throw ValidationException::withMessages([
                'username' => __('moonshine::auth.failed'),
            ]);
        }

        return $next($request);
    }
}
