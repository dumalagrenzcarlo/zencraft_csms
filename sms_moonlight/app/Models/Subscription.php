<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Subscription extends Model
{
    use CentralConnection;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_TRIAL,
        self::STATUS_ACTIVE,
        self::STATUS_PAST_DUE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'billable_users', 'starts_at',
        'trial_ends_at', 'renews_at', 'grace_ends_at', 'cancel_at', 'ends_at',
        'provider', 'provider_subscription_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'cancel_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function permitsAccess(): bool
    {
        return match ($this->status) {
            self::STATUS_TRIAL => $this->trial_ends_at === null || $this->trial_ends_at->isFuture(),
            self::STATUS_ACTIVE => $this->ends_at === null || $this->ends_at->isFuture(),
            self::STATUS_PAST_DUE => $this->grace_ends_at?->isFuture() === true,
            default => false,
        };
    }

    protected static function booted(): void
    {
        static::saving(function (Subscription $subscription): void {
            if (! in_array($subscription->status, self::STATUSES, true)) {
                throw ValidationException::withMessages(['status' => 'Select a valid subscription status.']);
            }

            if ((int) $subscription->billable_users < 0) {
                throw ValidationException::withMessages(['billable_users' => 'Billable users cannot be negative.']);
            }
        });
    }
}
