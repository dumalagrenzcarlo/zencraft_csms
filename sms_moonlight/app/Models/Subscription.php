<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Subscription extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'billable_users', 'starts_at',
        'trial_ends_at', 'renews_at', 'ends_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'renews_at' => 'datetime',
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
}
