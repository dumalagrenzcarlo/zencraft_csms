<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupportAccessGrant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class SupportAccess
{
    public function scopeVisible(Builder $query, User $user): Builder
    {
        if ($user->role === 'owner') {
            return $query;
        }

        return $query->whereHas('supportAccessGrants', fn (Builder $grant) => $grant
            ->active()
            ->where('support_user_id', $user->id));
    }

    public function allows(User $user, Tenant $tenant): bool
    {
        return $user->role === 'owner' || $tenant->supportAccessGrants()
            ->active()
            ->where('support_user_id', $user->id)
            ->exists();
    }

    public function grant(Tenant $tenant, User $supportUser, User $owner, string $reason): SupportAccessGrant
    {
        abort_unless($supportUser->active && $supportUser->role === 'support', 422, 'Select an active support user.');

        return SupportAccessGrant::query()->create([
            'tenant_id' => $tenant->id,
            'support_user_id' => $supportUser->id,
            'granted_by' => $owner->id,
            'reason' => $reason,
            'expires_at' => now()->addMinutes((int) config('saas.support_access_minutes', 60)),
        ]);
    }

    public function revoke(SupportAccessGrant $grant): void
    {
        $grant->forceFill(['revoked_at' => now()])->save();
    }
}
