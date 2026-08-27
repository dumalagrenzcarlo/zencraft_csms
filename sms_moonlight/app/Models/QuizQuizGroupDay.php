<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class QuizQuizGroupDay
 *
 * @property int $id
 * @property int $quiz_id
 * @property int $quiz_group_days_id
 * @property int|null $record_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuizQuizGroupDay extends Model
{
    protected $table = 'quiz_quiz_group_days';

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
        'quiz_group_days_id',
        'record_order',
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
            'quiz_id' => 'integer',
            'quiz_group_days_id' => 'integer',
            'record_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function quizGroupDay(): BelongsTo
    {
        return $this->belongsTo(QuizGroupDay::class, 'quiz_group_days_id');
    }
}
