<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_CANCELLED = 'cancelled';

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'provisioned_at' => 'datetime',
        'suspended_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id', 'name', 'slug', 'status', 'timezone', 'current_plan_id',
            'trial_ends_at', 'provisioned_at', 'suspended_at',
            'onboarding_status', 'onboarding_completed_at',
        ];
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function supportAccessGrants(): HasMany
    {
        return $this->hasMany(SupportAccessGrant::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(TenantBackup::class);
    }

    public function isAvailable(): bool
    {
        if (! in_array($this->status, [self::STATUS_TRIAL, self::STATUS_ACTIVE], true)) {
            return false;
        }

        if ($this->status === self::STATUS_TRIAL
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast()) {
            return false;
        }

        return $this->currentSubscription?->permitsAccess() ?? false;
    }
}
