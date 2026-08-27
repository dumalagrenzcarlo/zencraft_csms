<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * Class StudentPaymentHistory
 *
 * @property int $id
 * @property int $student_id
 * @property int|null $payment_type_id
 * @property Carbon|null $payment_date
 * @property string $amount
 * @property string|null $reference
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StudentPaymentHistory extends Model
{
    protected $table = 'student_payment_histories';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'student_id',
        'payment_type_id',
        'payment_date',
        'amount',
        'reference',
        'notes',
    ];

    protected $attributes = [
        'reference' => null,
        'notes' => null,
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'student_id' => 'integer',
            'payment_type_id' => 'integer',
            'payment_date' => 'datetime',
            'amount' => 'decimal:2',
            'reference' => 'string',
            'notes' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }

    protected static function booted(): void
    {
        static::saving(function (StudentPaymentHistory $payment): void {
            if (! Student::query()->whereKey($payment->student_id)->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Select a valid student.',
                ]);
            }

            if ($payment->payment_type_id !== null
                && ! PaymentType::query()->whereKey($payment->payment_type_id)->exists()) {
                throw ValidationException::withMessages([
                    'payment_type_id' => 'Select a valid payment type.',
                ]);
            }

            if (! is_numeric($payment->amount) || (float) $payment->amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount must be greater than zero.',
                ]);
            }

            if (! $payment->payment_date || $payment->payment_date->isAfter(now()->addDay())) {
                throw ValidationException::withMessages([
                    'payment_date' => 'Enter a valid payment date that is not in the future.',
                ]);
            }
        });
    }
}
