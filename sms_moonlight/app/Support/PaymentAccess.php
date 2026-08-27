<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\MoonShineAuth;

final class PaymentAccess
{
    public const SESSION_USER_ID = 'payments.authorized_user_id';

    public const SESSION_PASSWORD_SIGNATURE = 'payments.password_signature';

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

        if (! $authorizedUser) {
            return false;
        }

        return (int) $request->session()->get(self::SESSION_USER_ID) === (int) $authorizedUser->id
            && hash_equals(
                self::passwordSignature($authorizedUser),
                (string) $request->session()->get(self::SESSION_PASSWORD_SIGNATURE)
            );
    }

    public static function unlock(Request $request, Model $authorizedUser): void
    {
        $request->session()->put([
            self::SESSION_USER_ID => $authorizedUser->id,
            self::SESSION_PASSWORD_SIGNATURE => self::passwordSignature($authorizedUser),
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
