<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantBackup extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id', 'status', 'disk', 'path', 'checksum', 'size_bytes',
        'table_count', 'row_count', 'started_at', 'completed_at', 'verified_at',
        'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'table_count' => 'integer',
            'row_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
