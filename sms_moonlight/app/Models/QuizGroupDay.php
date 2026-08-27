<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * Class QuizGroupDay
 *
 * @property int $id
 * @property string $title
 * @property int $quiz_group_id
 * @property string $day
 * @property int $quiz_duration_seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuizGroupDay extends Model
{
    public const CREATED_AT = 'record_created';

    public const UPDATED_AT = 'record_updated';

    protected $table = 'quiz_group_days';

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
        'title',
        'quiz_group_id',
        'day',
        'quiz_duration_seconds',
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
            'title' => 'string',
            'quiz_group_id' => 'integer',
            'day' => 'string',
            'quiz_duration_seconds' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function quizGroup(): BelongsTo
    {
        return $this->belongsTo(QuizGroup::class, 'quiz_group_id');
    }

    public function quiz_group(): BelongsTo
    {
        return $this->quizGroup();
    }

    public function quiz_quiz_group_days(): HasMany
    {
        return $this->hasMany(QuizQuizGroupDay::class, 'quiz_group_days_id')
            ->orderBy('record_order')
            ->orderBy('id');
    }

    public function studentQuizAnswers(): HasMany
    {
        return $this->hasMany(StudentQuizAnswer::class, 'quiz_group_days_id');
    }

    protected static function booted(): void
    {
        static::saving(function (QuizGroupDay $day): void {
            if (! in_array($day->day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], true)) {
                throw ValidationException::withMessages(['day' => 'Select a valid school day.']);
            }

            if ((int) $day->quiz_duration_seconds < 30 || (int) $day->quiz_duration_seconds > 14400) {
                throw ValidationException::withMessages([
                    'quiz_duration_seconds' => 'Quiz duration must be between 30 seconds and four hours.',
                ]);
            }
        });
    }
}
