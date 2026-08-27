<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Laravel\MoonShineAuth;

final class PaymentAccess
{
    public const SESSION_USER_ID = 'payments.authorized_user_id';

    public const SESSION_PASSWORD_SIGNATURE = 'payments.password_signature';

    public const SESSION_ADMIN_USER_ID = 'payments.admin_user_id';

    public const SESSION_EXPIRES_AT = 'payments.expires_at';

    public static function authorizedUsername(): string
    {
        return trim((string) config('school_portal.payments.authorized_admin_username'));
    }

    public static function authorizedUser(): ?Model
    {
        $username = self::authorizedUsername();

        if ($username === '') {
            return null;
        }

        $model = MoonShineAuth::getModel();

        return $model::query()
            ->where('username', $username)
            ->where('moonshine_user_role_id', MoonshineUserRole::DEFAULT_ROLE_ID)
            ->first();
    }

    public static function isAuthorizedAdmin(mixed $user): bool
    {
        return is_object($user)
            && self::authorizedUsername() !== ''
            && hash_equals(self::authorizedUsername(), (string) $user->username);
    }

    public static function isSessionUnlocked(Request $request): bool
    {
        $authorizedUser = self::authorizedUser();
        $currentUser = $request->user('moonshine');

        if (! $authorizedUser || ! $currentUser) {
            return false;
        }

        return (int) $request->session()->get(self::SESSION_USER_ID) === (int) $authorizedUser->id
            && (int) $request->session()->get(self::SESSION_ADMIN_USER_ID) === (int) $currentUser->id
            && (int) $request->session()->get(self::SESSION_EXPIRES_AT) >= time()
            && hash_equals(
                self::passwordSignature($authorizedUser),
                (string) $request->session()->get(self::SESSION_PASSWORD_SIGNATURE)
            );
    }

    public static function unlock(Request $request, Model $authorizedUser): void
    {
        $currentUser = $request->user('moonshine');

        $request->session()->put([
            self::SESSION_USER_ID => $authorizedUser->id,
            self::SESSION_ADMIN_USER_ID => $currentUser?->id,
            self::SESSION_PASSWORD_SIGNATURE => self::passwordSignature($authorizedUser),
            self::SESSION_EXPIRES_AT => now()
                ->addMinutes((int) config('school_portal.payments.unlock_minutes', 15))
                ->timestamp,
        ]);
    }

    public static function canAccess(Request $request, mixed $user): bool
    {
        return self::isAuthorizedAdmin($user) || self::isSessionUnlocked($request);
    }

    public static function isPaymentRequest(Request $request): bool
    {
        $resourceUri = (string) $request->route('resourceUri', '');

        return in_array($resourceUri, [
            'student-payment-history-resource',
            'payment-type-resource',
        ], true)
            || $request->routeIs('admin.payments.export');
    }

    private static function passwordSignature(Model $authorizedUser): string
    {
        return hash('sha256', (string) $authorizedUser->password);
    }
}
