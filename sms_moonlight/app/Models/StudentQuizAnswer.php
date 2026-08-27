<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * Class StudentQuizAnswer
 *
 * @property int $id
 * @property int $quiz_group_days_id
 * @property int $quiz_id
 * @property int|null $answer_id
 * @property int $student_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StudentQuizAnswer extends Model
{
    public const CREATED_AT = 'record_created';

    public const UPDATED_AT = 'record_updated';

    protected $table = 'student_quiz_answers';

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
        'quiz_group_days_id',
        'quiz_id',
        'answer_id',
        'student_id',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'answer_id' => 'NULL',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'quiz_group_days_id' => 'integer',
            'quiz_id' => 'integer',
            'answer_id' => 'integer',
            'student_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(QuizAnswer::class, 'answer_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function quizGroupDay(): BelongsTo
    {
        return $this->belongsTo(QuizGroupDay::class, 'quiz_group_days_id');
    }

    protected static function booted(): void
    {
        static::saving(function (StudentQuizAnswer $answer): void {
            $questionIsAssigned = QuizQuizGroupDay::query()
                ->where('quiz_group_days_id', $answer->quiz_group_days_id)
                ->where('quiz_id', $answer->quiz_id)
                ->exists();
            $answerBelongsToQuestion = QuizQuizAnswer::query()
                ->where('quiz_id', $answer->quiz_id)
                ->where('answer_id', $answer->answer_id)
                ->exists();

            if (! $questionIsAssigned || ! $answerBelongsToQuestion) {
                throw ValidationException::withMessages([
                    'answers' => 'Every answer must belong to a question assigned to this quiz.',
                ]);
            }
        });
    }
}
