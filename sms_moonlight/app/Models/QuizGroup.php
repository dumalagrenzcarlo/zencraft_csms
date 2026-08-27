<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class QuizGroup
 *
 * @property int $id
 * @property int $school_year_id
 * @property int $grade_id
 * @property string $week
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuizGroup extends Model
{
    protected $table = 'quiz_group';

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
        'school_year_id',
        'grade_id',
        'week',
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
            'school_year_id' => 'integer',
            'grade_id' => 'integer',
            'week' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function school_year(): BelongsTo
    {
        return $this->schoolYear();
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function quizGroupDays(): HasMany
    {
        return $this->hasMany(QuizGroupDay::class, 'quiz_group_id');
    }
}
