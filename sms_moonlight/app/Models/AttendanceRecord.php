<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AttendanceRecord
 *
 * @property int $id
 * @property int $student_id
 * @property int|null $adviser_id
 * @property string $amlogin
 * @property string $amlogout
 * @property string $pmlogin
 * @property string $pmlogout
 * @property Carbon|null $currentdate
 * @property string|null $logged_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AttendanceRecord extends Model
{
    protected $table = 'attendance_record';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'student_id',
        'adviser_id',
        'amlogin',
        'amlogout',
        'pmlogin',
        'pmlogout',
        'currentdate',
        'logged_time',
        'source',
        'source_event_id',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'student_id' => 'integer',
            'adviser_id' => 'integer',
            'amlogin' => 'string',
            'amlogout' => 'string',
            'pmlogin' => 'string',
            'pmlogout' => 'string',
            'currentdate' => 'datetime',
            'logged_time' => 'datetime',
            'source' => 'string',
            'source_event_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class, 'adviser_id');
    }

    public function getAttendeeNameAttribute(): string
    {
        if ($this->student) {
            return trim($this->student->firstname.' '.$this->student->lastname);
        }

        return (string) ($this->adviser?->name ?? 'Unknown');
    }

    public function getAttendeeTypeAttribute(): string
    {
        if (! $this->adviser_id) {
            return 'Student';
        }

        return $this->adviser?->staff_type === Adviser::TYPE_STAFF
            ? 'Staff'
            : 'Teacher';
    }

    public function getTardinessStatusAttribute(): string
    {
        if (! $this->adviser_id || ! $this->adviser?->shift_start_time || ! $this->logged_time) {
            return '—';
        }

        return $this->logged_time->format('H:i:s') > $this->adviser->shift_start_time
            ? 'Late'
            : 'On time';
    }
}
