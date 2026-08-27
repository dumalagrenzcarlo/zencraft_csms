<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id', 'name', 'slug', 'status', 'timezone', 'current_plan_id',
            'trial_ends_at', 'provisioned_at', 'suspended_at',
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

    public function isAvailable(): bool
    {
        return in_array($this->status, [self::STATUS_TRIAL, self::STATUS_ACTIVE], true);
    }
}
