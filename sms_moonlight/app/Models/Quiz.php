<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Quiz
 *
 * @property int $id
 * @property string $question
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Quiz extends Model
{
    protected $table = 'quizzes';

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
        'question',
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
            'question' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function quizQuizAnswers(): HasMany
    {
        return $this->hasMany(QuizQuizAnswer::class, 'quiz_id');
    }

    public function quizQuizGroupDays(): HasMany
    {
        return $this->hasMany(QuizQuizGroupDay::class, 'quiz_id');
    }

    public function studentQuizAnswers(): HasMany
    {
        return $this->hasMany(StudentQuizAnswer::class, 'quiz_id');
    }
}
