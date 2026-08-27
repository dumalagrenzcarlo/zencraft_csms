<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class QuizAnswer
 *
 * @property int $id
 * @property string $answer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuizAnswer extends Model
{
    protected $table = 'quiz_answers';

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
        'answer',
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
            'answer' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function quizQuizAnswers(): HasMany
    {
        return $this->hasMany(QuizQuizAnswer::class, 'answer_id');
    }

    public function studentQuizAnswers(): HasMany
    {
        return $this->hasMany(StudentQuizAnswer::class, 'answer_id');
    }
}
