<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Grade
 *
 * @property int $id
 * @property string $grade
 * @property string $status
 * @property int $term_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Grade extends Model
{
    protected $table = 'grade';

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
        'grade',
        'status',
        'term_count',
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
            'grade' => 'string',
            'status' => 'string',
            'term_count' => 'integer',
            'theme_colors' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassesModel::class, 'grade_id');
    }

    public function studentClasses(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'grade_id');
    }

    public function classStudentGrades(): HasMany
    {
        return $this->hasMany(ClassStudentGrade::class, 'grade_id');
    }

    public function quizGroups(): HasMany
    {
        return $this->hasMany(QuizGroup::class, 'grade_id');
    }

    public function termCount(): int
    {
        return max(1, min(4, (int) ($this->term_count ?: 4)));
    }

    /**
     * @return list<string>
     */
    public function termKeys(): array
    {
        return array_slice(['q1', 'q2', 'q3', 'q4'], 0, $this->termCount());
    }
}
