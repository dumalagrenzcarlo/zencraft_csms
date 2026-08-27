<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class QuizQuizAnswer
 *
 * @property int $id
 * @property int|null $quiz_id
 * @property int|null $answer_id
 * @property int|null $is_correct_answer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuizQuizAnswer extends Model
{
    protected $table = 'quiz_quiz_answers';

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
        'quiz_id',
        'answer_id',
        'is_correct_answer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quiz_id' => 'NULL',
        'answer_id' => 'NULL',
        'is_correct_answer' => 'NULL',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'quiz_id' => 'integer',
            'answer_id' => 'integer',
            'is_correct_answer' => 'integer',
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
}
