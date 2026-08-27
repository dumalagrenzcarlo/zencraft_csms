<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PaymentType extends Model
{
    protected $table = 'payment_types';

    protected $fillable = [
        'id',
        'name',
        'notes',
    ];

    protected $attributes = [
        'notes' => null,
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'notes' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function studentPaymentHistories(): HasMany
    {
        return $this->hasMany(StudentPaymentHistory::class, 'payment_type_id');
    }
}
